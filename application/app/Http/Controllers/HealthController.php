<?php

namespace App\Http\Controllers;

class HealthController
{
    public function __invoke(): array
    {
        return ["success" => true];
    }

    public function health1()
    {
        return ["success" => true, ["data" => "Health 1"]];
    }
}
