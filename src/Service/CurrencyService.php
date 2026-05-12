<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CurrencyService
{
    private $httpClient;
    private $cache;
    private const BASE_URL = 'https://api.exchangerate-api.com/v4/latest/';

    public function __construct(HttpClientInterface $httpClient, CacheInterface $cache)
    {
        $this->httpClient = $httpClient;
        $this->cache = $cache;
    }

    /**
     * Get all available exchange rates for a base currency
     */
    public function getLatestRates(string $base = 'USD'): array
    {
        return $this->cache->get('latest_rates_' . $base, function (ItemInterface $item) use ($base) {
            $item->expiresAfter(3600); // Cache for 1 hour

            try {
                $response = $this->httpClient->request('GET', self::BASE_URL . $base, [
                    'timeout' => 10,
                    'verify_peer' => false, // Bypass SSL issues on some environments
                    'verify_host' => false,
                ]);

                if ($response->getStatusCode() !== 200) {
                    return ['error' => 'Currency API returned status ' . $response->getStatusCode()];
                }

                return $response->toArray();
            } catch (\Exception $e) {
                return ['error' => 'Connection failed: ' . $e->getMessage()];
            }
        });
    }

    /**
     * Convert an amount between currencies
     */
    public function convert(float $amount, string $from, string $to): array
    {
        if ($from === $to) {
            return [
                'amount' => $amount,
                'from' => $from,
                'to' => $to,
                'converted' => $amount,
                'rate' => 1.0
            ];
        }

        $rates = $this->getLatestRates($from);

        if (isset($rates['error'])) {
            return ['error' => $rates['error']];
        }

        if (!isset($rates['rates'][$to])) {
            return ['error' => "Currency '$to' not found in rates for '$from'"];
        }

        $rate = $rates['rates'][$to];
        $converted = $amount * $rate;

        return [
            'amount' => $amount,
            'from' => $from,
            'to' => $to,
            'converted' => round($converted, 4),
            'rate' => $rate,
            'date' => $rates['date'] ?? date('Y-m-d')
        ];
    }
}
