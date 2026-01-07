<?php

namespace Expose\Server\StatisticsCollector;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class ApiStatisticsCollector extends DatabaseStatisticsCollector
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

    public function save()
    {
        $sharedSites = 0;
        collect($this->sharedSites)->map(function ($numSites) use (&$sharedSites) {
            $sharedSites += $numSites;
        });

        $sharedPorts = 0;
        collect($this->sharedPorts)->map(function ($numPorts) use (&$sharedPorts) {
            $sharedPorts += $numPorts;
        });

        $this->client->post('/save', [
            'timestamp' => today()->toDateString(),
            'shared_sites' => $sharedSites,
            'shared_ports' => $sharedPorts,
            'unique_shared_sites' => count($this->sharedSites),
            'unique_shared_ports' => count($this->sharedPorts),
            'incoming_requests' => $this->requests,
        ]);

        $this->flush();
    }
}
