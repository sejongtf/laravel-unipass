<?php

declare(strict_types=1);

namespace Sejongtf\LaravelUnipass\Tests\DataObjects;

use PHPUnit\Framework\TestCase;
use Sejongtf\LaravelUnipass\DataObjects\SeaArrivalReportList;
use Sejongtf\LaravelUnipass\Http\Client;

final class SeaArrivalReportListTest extends TestCase
{
    public function test_single_report_is_normalized_to_list(): void
    {
        $list = SeaArrivalReportList::fromArray(Client::decode(<<<'XML'
            <etprRprtQryBrkdQryRtnVo>
                <tCnt>1</tCnt>
                <ntceInfo/>
                <etprRprtQryBrkdQryVo>
                    <cstmSgn>020</cstmSgn>
                    <mrn>16SRDJA0023</mrn>
                    <shipFlgtNm>HANJIN BUSAN</shipFlgtNm>
                    <etprDttm>20260120063000</etprDttm>
                    <alCrmbPecnt>21</alCrmbPecnt>
                    <alPsngPecnt/>
                </etprRprtQryBrkdQryVo>
            </etprRprtQryBrkdQryRtnVo>
            XML));

        $this->assertFalse($list->isEmpty());
        $this->assertSame(1, $list->count);
        $this->assertCount(1, $list->reports);

        $report = $list->first();
        $this->assertSame('HANJIN BUSAN', $report->vesselName);
        $this->assertSame('16SRDJA0023', $report->mrn);
        $this->assertSame(21, $report->crewCount);
        $this->assertNull($report->passengerCount); // 빈 엘리먼트 → null
        $this->assertSame('2026-01-20 06:30:00', $report->arrivalAtCarbon()?->toDateTimeString());
    }

    public function test_empty_result(): void
    {
        $list = SeaArrivalReportList::fromArray(Client::decode(
            '<etprRprtQryBrkdQryRtnVo><tCnt>0</tCnt><ntceInfo/></etprRprtQryBrkdQryRtnVo>',
        ));

        $this->assertTrue($list->isEmpty());
        $this->assertNull($list->first());
    }

    public function test_invalid_arrival_datetime_returns_null_carbon(): void
    {
        $list = SeaArrivalReportList::fromArray([
            'etprRprtQryBrkdQryVo' => ['etprDttm' => '2026'],
        ]);

        $this->assertNull($list->first()->arrivalAtCarbon());
    }
}
