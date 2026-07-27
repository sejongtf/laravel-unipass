<?php

declare(strict_types=1);

namespace Sejongtf\LaravelUnipass\DataObjects;

use Sejongtf\LaravelUnipass\Support\Xml;

/**
 * 수출이행 내역(API002) 응답(expDclrNoPrExpFfmnBrkdQryRtnVo) 파싱 결과.
 *
 * - 수출신고번호로 조회: 요약(QryRsltVo) 1건 + 분할선적 상세(DtlQryRsltVo) 0..n건
 * - B/L 번호로 조회: 이행 내역(BlNoQryRsltVo) 1..n건 — summary 는 그 첫 건
 */
final class ExportFulfillment
{
    /**
     * @param  array<int, ExportFulfillmentItem>  $shipments
     */
    public function __construct(
        public readonly ?ExportFulfillmentItem $summary,
        public readonly array $shipments,
        public readonly int $count,
        public readonly ?string $notice,
        public readonly array $raw = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        $header = array_map(
            ExportFulfillmentItem::fromArray(...),
            Xml::listOf($data, 'expDclrNoPrExpFfmnBrkdQryRsltVo'),
        );
        $details = array_map(
            ExportFulfillmentItem::fromArray(...),
            Xml::listOf($data, 'expDclrNoPrExpFfmnBrkdDtlQryRsltVo'),
        );
        $byBl = array_map(
            ExportFulfillmentItem::fromArray(...),
            Xml::listOf($data, 'expDclrNoPrExpFfmnBrkdBlNoQryRsltVo'),
        );

        // 수출신고번호 조회: header[0] 이 요약, details 가 분할선적.
        // B/L 조회: byBl 목록만 존재(별도 요약 없음) → 첫 건을 요약으로.
        if ($header !== []) {
            $summary = $header[0];
            $shipments = $details;
        } else {
            $summary = $byBl[0] ?? null;
            $shipments = $byBl;
        }

        return new self(
            $summary,
            $shipments,
            (int) (Xml::scalar($data, 'tCnt') ?? 0),
            Xml::scalar($data, 'ntceInfo'),
            $data,
        );
    }

    public function isEmpty(): bool
    {
        return $this->summary === null && $this->shipments === [];
    }

    /**
     * 선적이 완료되었는지(요약 기준).
     */
    public function isCompleted(): bool
    {
        return $this->summary?->isCompleted() ?? false;
    }
}
