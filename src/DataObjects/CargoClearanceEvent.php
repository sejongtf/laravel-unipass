<?php

declare(strict_types=1);

namespace Sejongtf\LaravelUnipass\DataObjects;

use Illuminate\Support\Carbon;
use Sejongtf\LaravelUnipass\Support\Xml;

/**
 * 화물통관진행 이력 한 건(cargCsclPrgsInfoDtlQryVo). 단건 조회 시 0..n 건 제공.
 */
class CargoClearanceEvent
{
    public function __construct(
        public readonly ?string $processType,      // cargTrcnRelaBsopTpcd 처리구분 (예: 반출신고)
        public readonly ?string $processedAt,      // prcsDttm 처리일시 (YYYYMMDDHHMMSS)
        public readonly ?string $releaseDateTime,  // rlbrDttm 반출입일시 (YYYYMMDDHHMMSS)
        public readonly ?string $releaseContent,   // rlbrCn 반출입내용
        public readonly ?string $declarationNo,    // dclrNo 신고번호
        public readonly ?string $releaseBasisNo,   // rlbrBssNo 반출입근거번호
        public readonly ?string $shedCode,         // shedSgn 장치장부호
        public readonly ?string $shedName,         // shedNm 장치장명
        public readonly ?int $packageCount,        // pckGcnt 포장개수
        public readonly ?string $packageUnit,      // pckUt 포장단위
        public readonly ?string $weight,           // wght 중량
        public readonly ?string $weightUnit,       // wghtUt 중량단위
        public readonly ?string $guidance,         // bfhnGdncCn 사전안내내용
        public readonly array $raw = [],
    ) {
    }

    public static function fromArray(array $row): self
    {
        $package = Xml::scalar($row, 'pckGcnt');

        return new self(
            processType: Xml::scalar($row, 'cargTrcnRelaBsopTpcd'),
            processedAt: Xml::scalar($row, 'prcsDttm'),
            releaseDateTime: Xml::scalar($row, 'rlbrDttm'),
            releaseContent: Xml::scalar($row, 'rlbrCn'),
            declarationNo: Xml::scalar($row, 'dclrNo'),
            releaseBasisNo: Xml::scalar($row, 'rlbrBssNo'),
            shedCode: Xml::scalar($row, 'shedSgn'),
            shedName: Xml::scalar($row, 'shedNm'),
            packageCount: $package === null ? null : (int) $package,
            packageUnit: Xml::scalar($row, 'pckUt'),
            weight: Xml::scalar($row, 'wght'),
            weightUnit: Xml::scalar($row, 'wghtUt'),
            guidance: Xml::scalar($row, 'bfhnGdncCn'),
            raw: $row,
        );
    }

    /**
     * 처리일시(prcsDttm, YYYYMMDDHHMMSS)를 Carbon 으로 변환.
     */
    public function processedAtCarbon(): ?Carbon
    {
        return $this->toCarbon($this->processedAt);
    }

    /**
     * 반출입일시(rlbrDttm, YYYYMMDDHHMMSS)를 Carbon 으로 변환.
     */
    public function releaseAtCarbon(): ?Carbon
    {
        return $this->toCarbon($this->releaseDateTime);
    }

    private function toCarbon(?string $value): ?Carbon
    {
        if ($value === null || strlen($value) !== 14) {
            return null;
        }

        return Carbon::createFromFormat('YmdHis', $value);
    }
}
