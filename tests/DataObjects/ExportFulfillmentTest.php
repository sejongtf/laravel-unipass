<?php

declare(strict_types=1);

namespace Sejongtf\LaravelUnipass\Tests\DataObjects;

use PHPUnit\Framework\TestCase;
use Sejongtf\LaravelUnipass\DataObjects\ExportFulfillment;
use Sejongtf\LaravelUnipass\Http\Client;

final class ExportFulfillmentTest extends TestCase
{
    public function test_declaration_no_result_uses_header_summary_and_detail_shipments(): void
    {
        $result = ExportFulfillment::fromArray(Client::decode(<<<'XML'
            <expDclrNoPrExpFfmnBrkdQryRtnVo>
                <tCnt>1</tCnt>
                <ntceInfo/>
                <expDclrNoPrExpFfmnBrkdQryRsltVo>
                    <expDclrNo>15210100057248</expDclrNo>
                    <exppnConm>세종상사</exppnConm>
                    <shpmCmplYn>Y</shpmCmplYn>
                    <acptDt>20260110</acptDt>
                </expDclrNoPrExpFfmnBrkdQryRsltVo>
                <expDclrNoPrExpFfmnBrkdDtlQryRsltVo>
                    <blNo>SJBL0389056</blNo>
                    <shpmcmplYn>Y</shpmcmplYn>
                    <tkofDt>20260112</tkofDt>
                </expDclrNoPrExpFfmnBrkdDtlQryRsltVo>
            </expDclrNoPrExpFfmnBrkdQryRtnVo>
            XML));

        $this->assertFalse($result->isEmpty());
        $this->assertTrue($result->isCompleted());
        $this->assertSame('15210100057248', $result->summary->exportDeclarationNo);
        $this->assertSame('세종상사', $result->summary->exporterName);

        $this->assertCount(1, $result->shipments);
        $this->assertSame('SJBL0389056', $result->shipments[0]->blNo);
        $this->assertSame('20260112', $result->shipments[0]->departureDate);
        // 상세 VO 는 shpmcmplYn(소문자) 필드를 쓴다
        $this->assertTrue($result->shipments[0]->isCompleted());
    }

    public function test_bl_result_uses_first_row_as_summary(): void
    {
        $result = ExportFulfillment::fromArray(Client::decode(<<<'XML'
            <expDclrNoPrExpFfmnBrkdQryRtnVo>
                <tCnt>2</tCnt>
                <ntceInfo/>
                <expDclrNoPrExpFfmnBrkdBlNoQryRsltVo>
                    <expDclrNo>DECL-1</expDclrNo>
                    <blNo>BL-1</blNo>
                    <shpmCmplYn>N</shpmCmplYn>
                </expDclrNoPrExpFfmnBrkdBlNoQryRsltVo>
                <expDclrNoPrExpFfmnBrkdBlNoQryRsltVo>
                    <expDclrNo>DECL-2</expDclrNo>
                    <blNo>BL-2</blNo>
                    <shpmCmplYn>Y</shpmCmplYn>
                </expDclrNoPrExpFfmnBrkdBlNoQryRsltVo>
            </expDclrNoPrExpFfmnBrkdQryRtnVo>
            XML));

        $this->assertSame(2, $result->count);
        $this->assertSame('DECL-1', $result->summary->exportDeclarationNo);
        $this->assertCount(2, $result->shipments);
        $this->assertFalse($result->isCompleted()); // 요약(첫 건) 기준
        $this->assertTrue($result->shipments[1]->isCompleted());
    }

    public function test_empty_result(): void
    {
        $result = ExportFulfillment::fromArray(Client::decode(
            '<expDclrNoPrExpFfmnBrkdQryRtnVo><tCnt>0</tCnt><ntceInfo/></expDclrNoPrExpFfmnBrkdQryRtnVo>',
        ));

        $this->assertTrue($result->isEmpty());
        $this->assertNull($result->summary);
        $this->assertFalse($result->isCompleted());
    }
}
