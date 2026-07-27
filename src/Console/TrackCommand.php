<?php

declare(strict_types=1);

namespace Sejongtf\LaravelUnipass\Console;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Sejongtf\LaravelUnipass\Exceptions\UnipassException;
use Sejongtf\LaravelUnipass\Unipass;

/**
 * UNI-PASS Open API 점검용 CLI.
 *
 *   php artisan unipass:track cargo     <화물관리번호>
 *   php artisan unipass:track cargo     <HBL> --by=hbl --year=2026
 *   php artisan unipass:track export    <B/L> [--by=declaration]
 *   php artisan unipass:track arrival   <선박호출부호> [--by=submission]
 *   php artisan unipass:track container <화물관리번호>
 */
class TrackCommand extends Command
{
    protected $signature = 'unipass:track
        {service : cargo|export|arrival|container}
        {value : 조회 값 (화물관리번호 / B/L / 수출신고번호 / 선박호출부호 등)}
        {--by= : 조회 방식 (cargo: cargo|hbl|mbl, export: bl|declaration, arrival: call-sign|submission)}
        {--year= : 입항년도 (cargo --by=hbl|mbl 일 때 필요)}';

    protected $description = 'UNI-PASS Open API 조회 (점검용)';

    public function handle(Unipass $unipass): int
    {
        $service = (string) $this->argument('service');
        $value = (string) $this->argument('value');

        try {
            return match ($service) {
                'cargo' => $this->cargo($unipass, $value),
                'export' => $this->export($unipass, $value),
                'arrival' => $this->arrival($unipass, $value),
                'container' => $this->container($unipass, $value),
                default => $this->bail("알 수 없는 서비스 [{$service}]. cargo|export|arrival|container 중 하나를 쓰세요."),
            };
        } catch (UnipassException $e) {
            $this->error('[UNI-PASS 오류] '.$e->getMessage());

            return self::FAILURE;
        } catch (InvalidArgumentException $e) {
            return $this->bail($e->getMessage());
        }
    }

    private function cargo(Unipass $unipass, string $value): int
    {
        $by = $this->option('by') ?: 'cargo';
        $result = match ($by) {
            'cargo' => $unipass->cargoClearance()->byCargoNo($value),
            'hbl' => $unipass->cargoClearance()->byHouseBl($value, $this->requireYear()),
            'mbl' => $unipass->cargoClearance()->byMasterBl($value, $this->requireYear()),
            default => throw new InvalidArgumentException("cargo 의 --by 는 cargo|hbl|mbl 만 가능합니다 (입력: {$by})."),
        };

        $this->info('■ 화물통관진행정보 (API001)');

        if ($result->isEmpty()) {
            return $this->empty();
        }

        if ($result->isList()) {
            $this->warn("다건 목록 ({$result->count}건) — 각 화물관리번호로 재조회하세요.");
            $this->table(['화물관리번호', 'HBL', 'MBL', '입항일자'], array_map(static fn ($s) => [
                $s->cargoManagementNo ?? '-', $s->houseBlNo ?? '-', $s->masterBlNo ?? '-', $s->entryDate ?? '-',
            ], $result->summaries));

            return self::SUCCESS;
        }

        $s = $result->summary();
        $this->table(['항목', '값'], [
            ['화물관리번호', $s->cargoManagementNo ?? '-'],
            ['통관진행상태', $s->customsProgressStatus ?? '-'],
            ['진행상태', $s->progressStatus ?? '-'],
            ['선박명', $s->vesselName ?? '-'],
            ['선사/항공사', $s->carrierName ?? '-'],
            ['항차', $s->voyage ?? '-'],
            ['입항일자', $s->entryDate ?? '-'],
            ['양륙항', $s->dischargePortName ?? '-'],
            ['컨테이너번호', $s->containerNo ?? '-'],
        ]);

        if ($result->events !== []) {
            $this->line('진행 이력:');
            $this->table(['처리구분', '처리일시', '장치장', '내용'], array_map(static fn ($e) => [
                $e->processType ?? '-', $e->processedAt ?? '-', $e->shedName ?? '-', $e->releaseContent ?? '-',
            ], $result->events));
        }

        return self::SUCCESS;
    }

    private function export(Unipass $unipass, string $value): int
    {
        $by = $this->option('by') ?: 'bl';
        $result = match ($by) {
            'bl' => $unipass->exportFulfillment()->byBl($value),
            'declaration' => $unipass->exportFulfillment()->byDeclarationNo($value),
            default => throw new InvalidArgumentException("export 의 --by 는 bl|declaration 만 가능합니다 (입력: {$by})."),
        };

        $this->info('■ 수출이행 내역 (API002)');

        if ($result->isEmpty()) {
            return $this->empty();
        }

        $s = $result->summary;
        $this->table(['항목', '값'], [
            ['수출신고번호', $s?->exportDeclarationNo ?? '-'],
            ['수출자', $s?->exporterName ?? '-'],
            ['제조자', $s?->manufacturerName ?? '-'],
            ['선적완료', $result->isCompleted() ? 'Y' : 'N'],
            ['수리일자', $s?->acceptDate ?? '-'],
            ['출항일자', $s?->departureDate ?? '-'],
            ['선적지', $s?->shipmentPlace ?? '-'],
        ]);

        if ($result->shipments !== []) {
            $this->line('선적 내역:');
            $this->table(['B/L', '수출신고번호', '출항일자', '선적중량', 'MRN'], array_map(static fn ($i) => [
                $i->blNo ?? '-', $i->exportDeclarationNo ?? '-', $i->departureDate ?? '-', $i->shipmentWeight ?? '-', $i->mrn ?? '-',
            ], $result->shipments));
        }

        return self::SUCCESS;
    }

    private function arrival(Unipass $unipass, string $value): int
    {
        $by = $this->option('by') ?: 'call-sign';
        $result = match ($by) {
            'call-sign' => $unipass->seaArrivalReport()->byCallSign($value),
            'submission' => $unipass->seaArrivalReport()->bySubmissionNo($value),
            default => throw new InvalidArgumentException("arrival 의 --by 는 call-sign|submission 만 가능합니다 (입력: {$by})."),
        };

        $this->info('■ 입항보고내역(해상) (API021)');

        if ($result->isEmpty()) {
            return $this->empty();
        }

        $this->table(['선박명', 'MRN', 'IMO', '입항일시', '계선장소', '세관', '승무원', '승객'], array_map(static fn ($r) => [
            $r->vesselName ?? '-', $r->mrn ?? '-', $r->imoNo ?? '-', $r->arrivalAt ?? '-',
            $r->berthName ?? '-', $r->customsCode ?? '-', $r->crewCount ?? '-', $r->passengerCount ?? '-',
        ], $result->reports));

        return self::SUCCESS;
    }

    private function container(Unipass $unipass, string $value): int
    {
        $result = $unipass->containers()->byCargoNo($value);

        $this->info('■ 컨테이너내역 (API020)');

        if ($result->isEmpty()) {
            return $this->empty();
        }

        $this->table(['컨테이너번호', '규격코드', '봉인번호'], array_map(static fn ($c) => [
            $c->number ?? '-', $c->sizeTypeCode ?? '-', implode(', ', $c->sealNumbers) ?: '-',
        ], $result->containers));

        return self::SUCCESS;
    }

    private function requireYear(): int
    {
        $year = $this->option('year');

        if (! $year) {
            throw new InvalidArgumentException('--by=hbl|mbl 조회에는 --year=YYYY (입항년도)가 필요합니다.');
        }

        return (int) $year;
    }

    private function empty(): int
    {
        $this->warn('조회 결과가 없습니다.');

        return self::SUCCESS;
    }

    private function bail(string $message): int
    {
        $this->error($message);

        return self::INVALID;
    }
}
