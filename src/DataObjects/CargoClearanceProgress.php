<?php

declare(strict_types=1);

namespace Sejongtf\LaravelUnipass\DataObjects;

use Sejongtf\LaravelUnipass\Support\Xml;

/**
 * 화물통관진행정보(API001) 응답(cargCsclPrgsInfoQryRtnVo) 파싱 결과.
 *
 * - 단건 조회: summaries 1건 + events 0..n건
 * - 다건 조회(목록): summaries N건, events 없음. ntceInfo 가 '[N00]' 으로 시작하며,
 *   각 summary 의 화물관리번호로 재조회하면 상세를 얻을 수 있다.
 */
class CargoClearanceProgress
{
    /**
     * @param  array<int, CargoClearanceSummary>  $summaries
     * @param  array<int, CargoClearanceEvent>  $events
     */
    public function __construct(
        public readonly array $summaries,
        public readonly array $events,
        public readonly int $count,
        public readonly ?string $notice,
        public readonly bool $isList,
        public readonly array $raw = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        $summaries = array_map(
            CargoClearanceSummary::fromArray(...),
            Xml::listOf($data, 'cargCsclPrgsInfoQryVo'),
        );

        $events = array_map(
            CargoClearanceEvent::fromArray(...),
            Xml::listOf($data, 'cargCsclPrgsInfoDtlQryVo'),
        );

        $notice = Xml::scalar($data, 'ntceInfo');
        $count = (int) (Xml::scalar($data, 'tCnt') ?? 0);
        $isList = $notice !== null && str_starts_with($notice, '[N00]');

        return new self($summaries, $events, $count, $notice, $isList, $data);
    }

    /**
     * 조회 결과가 없는지 여부.
     */
    public function isEmpty(): bool
    {
        return $this->summaries === [];
    }

    /**
     * 다건(목록) 응답인지 여부. 목록인 경우 events 는 비어 있으며 각 summary 의
     * 화물관리번호로 재조회해야 상세를 얻을 수 있다.
     */
    public function isList(): bool
    {
        return $this->isList;
    }

    /**
     * 대표(첫) 헤더. 단건 조회 시 그 건, 목록일 때는 첫 건.
     */
    public function summary(): ?CargoClearanceSummary
    {
        return $this->summaries[0] ?? null;
    }

    /**
     * 통관진행상태(csclPrgsStts). 단건일 때 유효.
     */
    public function status(): ?string
    {
        return $this->summary()?->customsProgressStatus;
    }

    /**
     * 가장 최근 진행 이력. (응답은 통상 최신순으로 내려온다)
     */
    public function latestEvent(): ?CargoClearanceEvent
    {
        return $this->events[0] ?? null;
    }
}
