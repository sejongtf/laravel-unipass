<?php

declare(strict_types=1);

namespace Sejongtf\LaravelUnipass\Tests\DataObjects;

use PHPUnit\Framework\TestCase;
use Sejongtf\LaravelUnipass\DataObjects\CargoClearanceProgress;
use Sejongtf\LaravelUnipass\Http\Client;

final class CargoClearanceProgressTest extends TestCase
{
    public function test_single_result_with_events(): void
    {
        // 실제 응답과 동일하게 XML 디코드를 거쳐 검증한다
        $progress = CargoClearanceProgress::fromArray(Client::decode(<<<'XML'
            <cargCsclPrgsInfoQryRtnVo>
                <tCnt>1</tCnt>
                <ntceInfo/>
                <cargCsclPrgsInfoQryVo>
                    <cargMtNo>00ANLU083N59007001</cargMtNo>
                    <csclPrgsStts>통관완료</csclPrgsStts>
                    <mblNo>MBL123</mblNo>
                    <hblNo>HBL456</hblNo>
                    <prnm>AUTO PARTS</prnm>
                    <etprDt>20260115</etprDt>
                    <cntrNo>TCNU1234567</cntrNo>
                    <cntrGcnt>2</cntrGcnt>
                    <pckGcnt>100</pckGcnt>
                    <vydf/>
                </cargCsclPrgsInfoQryVo>
                <cargCsclPrgsInfoDtlQryVo>
                    <cargTrcnRelaBsopTpcd>수입신고수리</cargTrcnRelaBsopTpcd>
                    <prcsDttm>20260116143000</prcsDttm>
                    <shedNm>부산신항 CFS</shedNm>
                </cargCsclPrgsInfoDtlQryVo>
                <cargCsclPrgsInfoDtlQryVo>
                    <cargTrcnRelaBsopTpcd>반출신고</cargTrcnRelaBsopTpcd>
                    <prcsDttm>20260117090000</prcsDttm>
                    <shedNm/>
                </cargCsclPrgsInfoDtlQryVo>
            </cargCsclPrgsInfoQryRtnVo>
            XML));

        $this->assertFalse($progress->isEmpty());
        $this->assertFalse($progress->isList());
        $this->assertSame(1, $progress->count);
        $this->assertSame('통관완료', $progress->status());

        $summary = $progress->summary();
        $this->assertSame('00ANLU083N59007001', $summary->cargoManagementNo);
        $this->assertSame('MBL123', $summary->masterBlNo);
        $this->assertSame('HBL456', $summary->houseBlNo);
        $this->assertSame('TCNU1234567', $summary->containerNo);
        $this->assertSame(2, $summary->containerCount);
        $this->assertSame(100, $summary->packageCount);
        $this->assertNull($summary->voyage); // 빈 엘리먼트 → null
        $this->assertSame('2026-01-15', $summary->entryDateAt()?->toDateString());
        $this->assertSame('AUTO PARTS', $summary->field('prnm'));

        $this->assertCount(2, $progress->events);
        $this->assertSame('수입신고수리', $progress->latestEvent()->processType);
        $this->assertSame('2026-01-16 14:30:00', $progress->latestEvent()->processedAtCarbon()?->toDateTimeString());
        $this->assertNull($progress->events[1]->shedName);
    }

    public function test_list_result_from_bl_query(): void
    {
        // HBL/MBL 다건 조회: [N00] 안내 + 요약 N건, 이력 없음
        $progress = CargoClearanceProgress::fromArray(Client::decode(<<<'XML'
            <cargCsclPrgsInfoQryRtnVo>
                <tCnt>2</tCnt>
                <ntceInfo>[N00] 조회 결과가 여러 건입니다.</ntceInfo>
                <cargCsclPrgsInfoQryVo>
                    <cargMtNo>CARGO-1</cargMtNo>
                </cargCsclPrgsInfoQryVo>
                <cargCsclPrgsInfoQryVo>
                    <cargMtNo>CARGO-2</cargMtNo>
                </cargCsclPrgsInfoQryVo>
            </cargCsclPrgsInfoQryRtnVo>
            XML));

        $this->assertTrue($progress->isList());
        $this->assertFalse($progress->isEmpty());
        $this->assertSame(2, $progress->count);
        $this->assertCount(2, $progress->summaries);
        $this->assertSame([], $progress->events);
        $this->assertSame('CARGO-1', $progress->summary()->cargoManagementNo);
        $this->assertSame('CARGO-2', $progress->summaries[1]->cargoManagementNo);
    }

    public function test_empty_result(): void
    {
        $progress = CargoClearanceProgress::fromArray(Client::decode(
            '<cargCsclPrgsInfoQryRtnVo><tCnt>0</tCnt><ntceInfo/></cargCsclPrgsInfoQryRtnVo>',
        ));

        $this->assertTrue($progress->isEmpty());
        $this->assertFalse($progress->isList());
        $this->assertNull($progress->summary());
        $this->assertNull($progress->status());
        $this->assertNull($progress->latestEvent());
    }

    public function test_invalid_dates_return_null_carbon(): void
    {
        $progress = CargoClearanceProgress::fromArray([
            'cargCsclPrgsInfoQryVo' => ['etprDt' => '2026'],
            'cargCsclPrgsInfoDtlQryVo' => ['prcsDttm' => 'invalid', 'rlbrDttm' => []],
        ]);

        $this->assertNull($progress->summary()->entryDateAt());
        $this->assertNull($progress->latestEvent()->processedAtCarbon());
        $this->assertNull($progress->latestEvent()->releaseAtCarbon());
    }
}
