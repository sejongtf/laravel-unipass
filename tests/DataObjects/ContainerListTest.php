<?php

declare(strict_types=1);

namespace Sejongtf\LaravelUnipass\Tests\DataObjects;

use PHPUnit\Framework\TestCase;
use Sejongtf\LaravelUnipass\DataObjects\ContainerList;
use Sejongtf\LaravelUnipass\Http\Client;

final class ContainerListTest extends TestCase
{
    public function test_containers_with_seal_numbers(): void
    {
        $list = ContainerList::fromArray(Client::decode(<<<'XML'
            <cntrQryBrkdQryRtnVo>
                <tCnt>2</tCnt>
                <ntceInfo/>
                <cntrQryBrkdQryVo>
                    <cntrNo>TCNU1234567</cntrNo>
                    <cntrStszCd>45GP</cntrStszCd>
                    <cntrSelgNo1>SEAL-1</cntrSelgNo1>
                    <cntrSelgNo2>SEAL-2</cntrSelgNo2>
                    <cntrSelgNo3/>
                </cntrQryBrkdQryVo>
                <cntrQryBrkdQryVo>
                    <cntrNo>TCNU7654321</cntrNo>
                    <cntrStszCd>22GP</cntrStszCd>
                    <cntrSelgNo1/>
                    <cntrSelgNo2/>
                    <cntrSelgNo3/>
                </cntrQryBrkdQryVo>
            </cntrQryBrkdQryRtnVo>
            XML));

        $this->assertFalse($list->isEmpty());
        $this->assertSame(2, $list->count);
        $this->assertCount(2, $list->containers);

        $this->assertSame('TCNU1234567', $list->containers[0]->number);
        $this->assertSame('45GP', $list->containers[0]->sizeTypeCode);
        $this->assertSame(['SEAL-1', 'SEAL-2'], $list->containers[0]->sealNumbers);

        // 봉인번호가 모두 빈 엘리먼트면 빈 리스트
        $this->assertSame([], $list->containers[1]->sealNumbers);
    }

    public function test_single_container_is_normalized_to_list(): void
    {
        $list = ContainerList::fromArray(Client::decode(<<<'XML'
            <cntrQryBrkdQryRtnVo>
                <tCnt>1</tCnt>
                <cntrQryBrkdQryVo>
                    <cntrNo>TCNU1234567</cntrNo>
                </cntrQryBrkdQryVo>
            </cntrQryBrkdQryRtnVo>
            XML));

        $this->assertCount(1, $list->containers);
        $this->assertSame('TCNU1234567', $list->containers[0]->number);
    }

    public function test_empty_result(): void
    {
        $list = ContainerList::fromArray(Client::decode(
            '<cntrQryBrkdQryRtnVo><tCnt>0</tCnt><ntceInfo/></cntrQryBrkdQryRtnVo>',
        ));

        $this->assertTrue($list->isEmpty());
    }
}
