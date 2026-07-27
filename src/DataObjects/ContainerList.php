<?php

declare(strict_types=1);

namespace Sejongtf\LaravelUnipass\DataObjects;

use Sejongtf\LaravelUnipass\Support\Xml;

/**
 * 컨테이너내역(API020) 응답(cntrQryBrkdQryRtnVo) 파싱 결과.
 */
final class ContainerList
{
    /**
     * @param  array<int, ContainerItem>  $containers
     */
    public function __construct(
        public readonly array $containers,
        public readonly int $count,
        public readonly ?string $notice,
        public readonly array $raw = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        $containers = array_map(
            ContainerItem::fromArray(...),
            Xml::listOf($data, 'cntrQryBrkdQryVo'),
        );

        return new self(
            $containers,
            (int) (Xml::scalar($data, 'tCnt') ?? 0),
            Xml::scalar($data, 'ntceInfo'),
            $data,
        );
    }

    public function isEmpty(): bool
    {
        return $this->containers === [];
    }
}
