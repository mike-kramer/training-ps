<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CurrencyRateService
{
    const NEED_CURRENCIES = ["USD", "EUR", "RUB"];

    public function getRates(): array
    {
        return \Cache::rememberForever(
            "currency-rates",
            $this->loadRates(...)
        );
    }

    public function updateCache(): void
    {
        \Cache::put("currency-rates", $this->getRates());
    }

    private function loadRates(): array
    {
        $resp = Http::get(
            "https://cbu.uz/ru/arkhiv-kursov-valyut/json/"
        );
        $data = $resp->json();
        $result = [];
        foreach ($data as $rate) {
            if (!in_array($rate["Ccy"], self::NEED_CURRENCIES)) {
                continue;
            }
            $result[$rate['Ccy']] = $rate["Rate"];
        }
        return $result;
    }
}
