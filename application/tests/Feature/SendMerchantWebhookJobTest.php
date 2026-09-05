<?php

namespace Tests\Feature;

use App\Contracts\SignatureContract;
use App\Jobs\SendMerchantWebhook;
use App\Models\Cashbox;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Traits\WithAuditLogs;


class SendMerchantWebhookJobTest extends TestCase
{
    use RefreshDatabase, WithAuditLogs;
    private User $user;
    private Cashbox $cashbox;
    private Payment $payment;

    const WEBHOOK_TEST_URL = "https://example.com/webhook";
    const TEST_SIGNATURE = "good-signature";

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->cashbox = Cashbox::factory()->state([
            'user_id' => $this->user->id,
            "webhook_url" => self::WEBHOOK_TEST_URL,
        ])->create();
        $this->payment = Payment::factory()->state([
            "cashbox_id" => $this->cashbox->id,
            "status" => Payment::STATUS_PAID
        ])->create();
        $this->mock(
            SignatureContract::class,
            function (MockInterface $mock) {
                $mock->shouldReceive("sign")->andReturn(self::TEST_SIGNATURE);
            }
        );
    }

    public function testSuccessWebhookCalling(): void
    {
        Http::fake();
        $service = app(PaymentProcessingService::class);
        $job = new SendMerchantWebhook($this->payment->id);
        $job->handle($service);
        Http::assertSent(function (Request $request) {
            return $request->url() === self::WEBHOOK_TEST_URL &&
                $request->method() === "POST" &&
                $request["order_id"] === $this->payment->order_id &&
                $request["amount"] === $this->payment->amount &&
                $request["status"] === $this->payment->status &&
                $request->hasHeader("X-Signature", self::TEST_SIGNATURE) === true;
        });
        $this->assertLog("webhook-called",
            null,
            null,
            $this->cashbox->id,
            parameters: [
                "payment_id" => $this->payment->id,
                "status" => $this->payment->status,
                "url" => $this->cashbox->webhook_url,
            ]
        );
    }

    public function testFailedWebhookCalling(): void
    {
        $this->freezeTime();
        Http::fake([
            '*' => Http::failedConnection(),
        ]);
        Queue::fake();
        $service = app(PaymentProcessingService::class);
        $job = new SendMerchantWebhook($this->payment->id, 3);
        $job->handle($service);
        Queue::assertPushed(SendMerchantWebhook::class, function (SendMerchantWebhook $job) {
            return $job->retry_number === 4
                && $job->payment_id === $this->payment->id
                && $job->delay->diff(now())->i === 4;
        });
        $this->assertLog("webhook-failed-with-retry",
            null,
            null,
            $this->cashbox->id,
            parameters: [
                "payment_id" => $this->payment->id,
                "status" => $this->payment->status,
                "url" => $this->cashbox->webhook_url,
            ]
        );
    }

    public function test5FailedWebhookCalling(): void
    {
        $this->freezeTime();
        Http::fake([
            '*' => Http::failedConnection(),
        ]);
        Queue::fake();
        $service = app(PaymentProcessingService::class);
        $job = new SendMerchantWebhook($this->payment->id, 5);
        $job->handle($service);
        Queue::assertNotPushed(SendMerchantWebhook::class);
        $this->assertLog("webhook-failed-5-times",
            null,
            null,
            $this->cashbox->id,
            parameters: [
                "payment_id" => $this->payment->id,
                "status" => $this->payment->status,
                "url" => $this->cashbox->webhook_url,
            ]
        );
    }

    public function testInvalidWebhookCalling(): void
    {
        Http::fake([
            '*' => Http::response(null, 404),
        ]);
        Queue::fake();
        $service = app(PaymentProcessingService::class);
        $job = new SendMerchantWebhook($this->payment->id, 5);
        $job->handle($service);
        Queue::assertNotPushed(SendMerchantWebhook::class);
        $this->assertLog("webhook-failed-with-40x-code",
            null,
            null,
            $this->cashbox->id,
            parameters: [
                "code" => 404,
                "payment_id" => $this->payment->id,
                "status" => $this->payment->status,
                "url" => $this->cashbox->webhook_url,
            ]
        );
    }
}
