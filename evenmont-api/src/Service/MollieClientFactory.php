<?php
namespace App\Service;

use Mollie\Api\MollieApiClient;

class MollieClientFactory
{
    public function __construct(private string $apiKey) {}

    public static function fromEnv(): self
    {
        $key = $_ENV['MOLLIE_API_KEY'] ?? '';
        return new self($key);
    }

    public function client(): MollieApiClient
    {
        $m = new MollieApiClient();
        $m->setApiKey($this->apiKey);
        return $m;
    }
}
