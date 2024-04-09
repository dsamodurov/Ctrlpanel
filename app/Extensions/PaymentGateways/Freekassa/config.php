<?php namespace App\Extensions\PaymentGateways\Freekassa;

function getConfig(): array
{
    return [
        "name" => "Freekassa",
        "description" => "Платежный шлюз Freekassa",
        "RoutesIgnoreCsrf" => [],
        "enabled" => true,//(config('SETTINGS::PAYMENTS:PAYPAL:SECRET') && config('SETTINGS::PAYMENTS:PAYPAL:CLIENT_ID')) || (config('SETTINGS::PAYMENTS:PAYPAL:SANDBOX_SECRET') && config('SETTINGS::PAYMENTS:PAYPAL:SANDBOX_CLIENT_ID') && env("APP_ENV") === "local"),
    ];
}
