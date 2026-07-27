<?php

declare(strict_types=1);

namespace Sejongtf\LaravelUnipass\Resources;

use Sejongtf\LaravelUnipass\DataObjects\ExportFulfillment as ExportFulfillmentResult;

/**
 * 수출신고번호별 수출이행 내역(API002). 수출 화물의 이행/잔량 조회.
 */
final class ExportFulfillment extends Resource
{
    /**
     * 수출신고번호(expDclrNo)로 조회. 요약 + 분할선적 상세를 반환한다.
     */
    public function byDeclarationNo(string $exportDeclarationNo): ExportFulfillmentResult
    {
        return ExportFulfillmentResult::fromArray($this->call(['expDclrNo' => $exportDeclarationNo]));
    }

    /**
     * B/L 번호(blNo)로 조회. 이행 내역 목록을 반환한다.
     */
    public function byBl(string $blNo): ExportFulfillmentResult
    {
        return ExportFulfillmentResult::fromArray($this->call(['blNo' => $blNo]));
    }
}
