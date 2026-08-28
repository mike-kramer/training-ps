<?php

namespace Tests\Unit;

use App\Services\SignatureService;
use PHPUnit\Framework\TestCase;

class SignatureServiceTest extends TestCase
{
    public function test_example(): void
    {
        $data = [
            "b" => "c",
            "a" => "d",
            "e" => "f"
        ];

        $key = "constant-key";
        $controlStrToSign = "a|d|b|c|e|f";
        $controlSignature = hash_hmac("sha256", $controlStrToSign, $key);

        $signatureService = new SignatureService();
        $signature = $signatureService->sign($data, $key);

        $this->assertTrue(hash_equals($controlSignature, $signature));
    }

    public function testCheckSignature()
    {
        $data = [
            "b" => "c",
            "a" => "d",
            "e" => "f"
        ];

        $key = "constant-key";
        $controlStrToSign = "a|d|b|c|e|f";
        $controlSignature = hash_hmac("sha256", $controlStrToSign, $key);

        $signatureService = new SignatureService();
        $res = $signatureService->checkSignature($data, $key, $controlSignature);
        $this->assertTrue($res);
    }
}
