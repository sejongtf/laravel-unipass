<?php

declare(strict_types=1);

namespace Sejongtf\LaravelUnipass;

use Illuminate\Support\Arr;
use Sejongtf\LaravelUnipass\Contracts\Client;
use Sejongtf\LaravelUnipass\Resources\CargoClearance;
use Sejongtf\LaravelUnipass\Resources\Container;
use Sejongtf\LaravelUnipass\Resources\ExportFulfillment;
use Sejongtf\LaravelUnipass\Resources\SeaArrivalReport;

/**
 * UNI-PASS 공개 API 진입점. config('unipass.services') 레지스트리에서 서비스를
 * resolve 해 엔드포인트별 리소스를 노출한다.
 *
 * 사용 예: Unipass::cargoClearance()->byCargoNo('00ANLU083N59007001')
 */
final class Unipass
{
    /**
     * @param  array<string, mixed>  $config  config('unipass')
     */
    public function __construct(
        private Client $client,
        private array $config = [],
    ) {
    }

    /**
     * 화물통관진행정보 (API001, 수입).
     */
    public function cargoClearance(): CargoClearance
    {
        return new CargoClearance($this->client, $this->service('cargo_clearance'));
    }

    /**
     * 수출신고번호별 수출이행 내역 (API002, 수출).
     */
    public function exportFulfillment(): ExportFulfillment
    {
        return new ExportFulfillment($this->client, $this->service('export_fulfillment'));
    }

    /**
     * 입항보고내역(해상) (API021).
     */
    public function seaArrivalReport(): SeaArrivalReport
    {
        return new SeaArrivalReport($this->client, $this->service('sea_arrival_report'));
    }

    /**
     * 컨테이너내역 (API020).
     */
    public function containers(): Container
    {
        return new Container($this->client, $this->service('container'));
    }

    /**
     * 전송 계층 클라이언트(미구현 API 를 임시로 직접 호출할 때 사용).
     */
    public function client(): Client
    {
        return $this->client;
    }

    /**
     * 레지스트리에서 서비스 설정을 가져온다(서비스명 포함).
     *
     * @return array<string, mixed>
     */
    private function service(string $name): array
    {
        $service = (array) Arr::get($this->config, "services.{$name}", []);
        $service['name'] = $name;

        return $service;
    }
}
