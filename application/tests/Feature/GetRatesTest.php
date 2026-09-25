<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GetRatesTest extends TestCase
{
    private array $testRates;
    private array $exceptedResponse;

    public function setUp(): void
    {
        parent::setUp();
        $testJSON = <<<RATE_JSON
            [
          {
            "id": 68,
            "Code": "840",
            "Ccy": "USD",
            "CcyNm_RU": "Доллар США",
            "CcyNm_UZ": "AQSH dollari",
            "CcyNm_UZC": "АҚШ доллари",
            "CcyNm_EN": "US Dollar",
            "Nominal": "1",
            "Rate": "11830.87",
            "Diff": "16.82",
            "Date": "25.09.2026"
          },
          {
            "id": 20,
            "Code": "978",
            "Ccy": "EUR",
            "CcyNm_RU": "Евро",
            "CcyNm_UZ": "EVRO",
            "CcyNm_UZC": "EВРО",
            "CcyNm_EN": "Euro",
            "Nominal": "1",
            "Rate": "13450.52",
            "Diff": "-31.67",
            "Date": "25.09.2026"
          },
          {
            "id": 56,
            "Code": "643",
            "Ccy": "RUB",
            "CcyNm_RU": "Российский рубль",
            "CcyNm_UZ": "Rossiya rubli",
            "CcyNm_UZC": "Россия рубли",
            "CcyNm_EN": "Russian Ruble",
            "Nominal": "1",
            "Rate": "139.27",
            "Diff": "-0.66",
            "Date": "25.09.2026"
          }
          ]
        RATE_JSON;
        $this->testRates = json_decode($testJSON, true);
        foreach ($this->testRates as $rate) {
            $this->exceptedResponse[$rate['Ccy']] = $rate['Rate'];
        }
    }

    public function testGetRates(): void
    {
        Http::fake([
            'https://cbu.uz/ru/arkhiv-kursov-valyut/json/' => Http::response(
                $this->testRates,
                200,
                ['Content-Type' => 'application/json']
            ),
        ]);

        $response = $this->get('/api/currency-rates');

        $response->assertStatus(200);
        $response->assertJson([
            "success" => true,
            "data" => $this->exceptedResponse
        ]);
    }

    public function testRateCaching(): void
    {
        Http::fake();
        \Cache::set("currency-rates", $this->exceptedResponse);
        $response = $this->get('/api/currency-rates');

        $response->assertStatus(200);
        $response->assertJson([
            "success" => true,
            "data" => $this->exceptedResponse
        ]);
        Http::assertSentCount(0);
    }

    public function testCacheUpdate(): void
    {
        Http::fake([
            'https://cbu.uz/ru/arkhiv-kursov-valyut/json/' => Http::response(
                $this->testRates,
                200,
                ['Content-Type' => 'application/json']
            ),
        ]);
        $this->artisan("update:cache");
        $this->assertEquals($this->exceptedResponse, \Cache::get("currency-rates"));
    }

}
