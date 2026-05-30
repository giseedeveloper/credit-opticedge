<?php

namespace App\Contracts\Integrations;

interface SmsGateway
{
    public function driverName(): string;

    /**
     * @param  array<string, mixed>  $meta
     */
    public function send(string $phone, string $message, array $meta = []): bool;
}
