<?php

namespace Expose\Server\StatisticsRepository;

use Expose\Server\Contracts\StatisticsRepository;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

class ApiStatisticsRepository implements StatisticsRepository
{
    protected PendingRequest $client;

    public function __construct() {
        $baseUrl = config('expose-server.statistics.api.url');
        $apiKey = config('expose-server.statistics.api.api_key');

        if ($baseUrl === null || $apiKey === null) {
            throw new \RuntimeException('Missing configuration for Api Statistics');
        }

        $this->client = Http::withHeaders([
            'X-Api-Key' => $apiKey,
        ])
            ->baseUrl($baseUrl)
            ->acceptJson();
    }

    public function getStatistics($from, $until): PromiseInterface
    {
        return new Promise(function (callable $resolve, callable $reject) use ($from, $until) {
            try {
                $response = $this->client
                    ->get("/get?from={$from}&until={$until}")
                    ->throw();

                $resolve($response->json());
            } catch (\Exception $e) {
                $reject($e);
            }
        });
    }
}
