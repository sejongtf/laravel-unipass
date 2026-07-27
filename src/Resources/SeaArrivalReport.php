<?php

declare(strict_types=1);

namespace Sejongtf\LaravelUnipass\Resources;

use Sejongtf\LaravelUnipass\DataObjects\SeaArrivalReportList;

/**
 * 입항보고내역(해상)(API021). 입출항제출번호 또는 선박호출부호로 해상 입항정보 조회.
 */
final class SeaArrivalReport extends Resource
{
    /**
     * 입출항제출번호(ioprSbmtNo)로 조회.
     */
    public function bySubmissionNo(string $submissionNo): SeaArrivalReportList
    {
        return SeaArrivalReportList::fromArray($this->call(['ioprSbmtNo' => $submissionNo]));
    }

    /**
     * 선박호출부호(shipCallSgn)로 조회.
     */
    public function byCallSign(string $callSign): SeaArrivalReportList
    {
        return SeaArrivalReportList::fromArray($this->call(['shipCallSgn' => $callSign]));
    }
}
