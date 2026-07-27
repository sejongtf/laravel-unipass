<?php

declare(strict_types=1);

namespace Sejongtf\LaravelUnipass\DataObjects;

use Sejongtf\LaravelUnipass\Support\Xml;

/**
 * 컨테이너내역(API020) 한 건(cntrQryBrkdQryVo).
 */
final class ContainerItem
{
    /**
     * @param  array<int, string>  $sealNumbers  컨테이너 봉인번호(cntrSelgNo1~3) 중 값이 있는 것
     */
    public function __construct(
        public readonly ?string $number,        // cntrNo 컨테이너번호
        public readonly ?string $sizeTypeCode,  // cntrStszCd 컨테이너규격코드 (예: 45GP)
        public readonly array $sealNumbers,
        public readonly array $raw = [],
    ) {
    }

    public static function fromArray(array $row): self
    {
        $seals = array_values(array_filter([
            Xml::scalar($row, 'cntrSelgNo1'),
            Xml::scalar($row, 'cntrSelgNo2'),
            Xml::scalar($row, 'cntrSelgNo3'),
        ], static fn ($value) => $value !== null));

        return new self(
            number: Xml::scalar($row, 'cntrNo'),
            sizeTypeCode: Xml::scalar($row, 'cntrStszCd'),
            sealNumbers: $seals,
            raw: $row,
        );
    }
}
