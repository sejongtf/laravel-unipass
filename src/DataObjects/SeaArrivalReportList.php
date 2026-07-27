<?php

declare(strict_types=1);

namespace Sejongtf\LaravelUnipass\DataObjects;

use Sejongtf\LaravelUnipass\Support\Xml;

/**
 * 입항보고내역(해상)(API021) 응답(etprRprtQryBrkdQryRtnVo) 파싱 결과.
 */
final class SeaArrivalReportList
{
    /**
     * @param  array<int, SeaArrivalReportItem>  $reports
     */
    public function __construct(
        public readonly array $reports,
        public readonly int $count,
        public readonly ?string $notice,
        public readonly array $raw = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        $reports = array_map(
            SeaArrivalReportItem::fromArray(...),
            Xml::listOf($data, 'etprRprtQryBrkdQryVo'),
        );

        return new self(
            $reports,
            (int) (Xml::scalar($data, 'tCnt') ?? 0),
            Xml::scalar($data, 'ntceInfo'),
            $data,
        );
    }

    public function isEmpty(): bool
    {
        return $this->reports === [];
    }

    public function first(): ?SeaArrivalReportItem
    {
        return $this->reports[0] ?? null;
    }
}
