<?php

declare(strict_types=1);

namespace Sejongtf\LaravelUnipass\Tests;

use PHPUnit\Framework\TestCase;
use Sejongtf\LaravelUnipass\Exceptions\UnipassException;
use Sejongtf\LaravelUnipass\Unipass;

final class UnipassTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function config(): array
    {
        return [
            'services' => [
                'cargo_clearance' => ['key' => 'key-001', 'path' => 'path/001', 'cache_ttl' => 0],
                'export_fulfillment' => ['key' => 'key-002', 'path' => 'path/002', 'cache_ttl' => 0],
                'sea_arrival_report' => ['key' => 'key-021', 'path' => 'path/021', 'cache_ttl' => 0],
                'container' => ['key' => 'key-020', 'path' => 'path/020', 'cache_ttl' => 0],
            ],
        ];
    }

    public function test_resources_resolve_their_service_from_registry(): void
    {
        $client = new FakeClient;
        $unipass = new Unipass($client, $this->config());

        $unipass->cargoClearance()->byCargoNo('A');
        $this->assertSame('path/001', $client->lastCall()['path']);
        $this->assertSame('key-001', $client->lastCall()['query']['crkyCn']);

        $unipass->exportFulfillment()->byBl('B');
        $this->assertSame('path/002', $client->lastCall()['path']);
        $this->assertSame('key-002', $client->lastCall()['query']['crkyCn']);

        $unipass->seaArrivalReport()->byCallSign('C');
        $this->assertSame('path/021', $client->lastCall()['path']);
        $this->assertSame('key-021', $client->lastCall()['query']['crkyCn']);

        $unipass->containers()->byCargoNo('D');
        $this->assertSame('path/020', $client->lastCall()['path']);
        $this->assertSame('key-020', $client->lastCall()['query']['crkyCn']);
    }

    public function test_client_exposes_transport_layer(): void
    {
        $client = new FakeClient;
        $unipass = new Unipass($client, $this->config());

        $this->assertSame($client, $unipass->client());
    }

    public function test_unregistered_service_throws_on_call(): void
    {
        $unipass = new Unipass(new FakeClient, ['services' => []]);

        $this->expectException(UnipassException::class);
        $this->expectExceptionMessage('unipass.services.cargo_clearance.key');

        $unipass->cargoClearance()->byCargoNo('A');
    }
}
