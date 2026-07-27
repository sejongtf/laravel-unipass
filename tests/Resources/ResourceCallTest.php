<?php

declare(strict_types=1);

namespace Sejongtf\LaravelUnipass\Tests\Resources;

use PHPUnit\Framework\TestCase;
use Sejongtf\LaravelUnipass\Exceptions\UnipassException;
use Sejongtf\LaravelUnipass\Resources\CargoClearance;
use Sejongtf\LaravelUnipass\Resources\Container;
use Sejongtf\LaravelUnipass\Resources\ExportFulfillment;
use Sejongtf\LaravelUnipass\Resources\SeaArrivalReport;
use Sejongtf\LaravelUnipass\Tests\FakeClient;

final class ResourceCallTest extends TestCase
{
    private FakeClient $client;

    protected function setUp(): void
    {
        $this->client = new FakeClient;
    }

    /**
     * @return array<string, mixed>
     */
    private function service(string $name, string $path): array
    {
        return ['name' => $name, 'key' => 'test-key', 'path' => $path, 'cache_ttl' => 0];
    }

    public function test_cargo_clearance_query_params(): void
    {
        $resource = new CargoClearance($this->client, $this->service('cargo_clearance', 'cargCsclPrgsInfoQry/retrieveCargCsclPrgsInfo'));

        $resource->byCargoNo('00ANLU083N59007001');
        $this->assertSame(
            ['crkyCn' => 'test-key', 'cargMtNo' => '00ANLU083N59007001'],
            $this->client->lastCall()['query'],
        );
        $this->assertSame('cargCsclPrgsInfoQry/retrieveCargCsclPrgsInfo', $this->client->lastCall()['path']);
        $this->assertSame(0, $this->client->lastCall()['cacheTtl']);

        $resource->byHouseBl('605118340404', 2026);
        $this->assertSame(
            ['crkyCn' => 'test-key', 'hblNo' => '605118340404', 'blYy' => '2026'],
            $this->client->lastCall()['query'],
        );

        $resource->byMasterBl('MBL123', '2026');
        $this->assertSame(
            ['crkyCn' => 'test-key', 'mblNo' => 'MBL123', 'blYy' => '2026'],
            $this->client->lastCall()['query'],
        );
    }

    public function test_cargo_clearance_query_drops_null_and_empty_params(): void
    {
        $resource = new CargoClearance($this->client, $this->service('cargo_clearance', 'path'));

        $resource->query(['cargMtNo' => 'A', 'hblNo' => null, 'blYy' => '']);

        $this->assertSame(
            ['crkyCn' => 'test-key', 'cargMtNo' => 'A'],
            $this->client->lastCall()['query'],
        );
    }

    public function test_export_fulfillment_query_params(): void
    {
        $resource = new ExportFulfillment($this->client, $this->service('export_fulfillment', 'expDclrNoPrExpFfmnBrkdQry/retrieveExpDclrNoPrExpFfmnBrkd'));

        $resource->byDeclarationNo('15210100057248');
        $this->assertSame(
            ['crkyCn' => 'test-key', 'expDclrNo' => '15210100057248'],
            $this->client->lastCall()['query'],
        );

        $resource->byBl('SJBL0389056');
        $this->assertSame(
            ['crkyCn' => 'test-key', 'blNo' => 'SJBL0389056'],
            $this->client->lastCall()['query'],
        );
    }

    public function test_sea_arrival_report_query_params(): void
    {
        $resource = new SeaArrivalReport($this->client, $this->service('sea_arrival_report', 'etprRprtQryBrkdQry/retrieveetprRprtQryBrkd'));

        $resource->bySubmissionNo('16SRDJA0023');
        $this->assertSame(
            ['crkyCn' => 'test-key', 'ioprSbmtNo' => '16SRDJA0023'],
            $this->client->lastCall()['query'],
        );

        $resource->byCallSign('3FBO6');
        $this->assertSame(
            ['crkyCn' => 'test-key', 'shipCallSgn' => '3FBO6'],
            $this->client->lastCall()['query'],
        );
    }

    public function test_container_query_params(): void
    {
        $resource = new Container($this->client, $this->service('container', 'cntrQryBrkdQry/retrieveCntrQryBrkd'));

        $resource->byCargoNo('00ANLU083N59007001');
        $this->assertSame(
            ['crkyCn' => 'test-key', 'cargMtNo' => '00ANLU083N59007001'],
            $this->client->lastCall()['query'],
        );
    }

    public function test_cache_ttl_is_forwarded(): void
    {
        $resource = new Container($this->client, [
            'name' => 'container', 'key' => 'test-key', 'path' => 'path', 'cache_ttl' => 3600,
        ]);

        $resource->byCargoNo('A');

        $this->assertSame(3600, $this->client->lastCall()['cacheTtl']);
    }

    public function test_missing_key_throws_with_service_name(): void
    {
        $resource = new CargoClearance($this->client, ['name' => 'cargo_clearance', 'key' => '', 'path' => 'path']);

        $this->expectException(UnipassException::class);
        $this->expectExceptionMessage('unipass.services.cargo_clearance.key');

        $resource->byCargoNo('A');
    }
}
