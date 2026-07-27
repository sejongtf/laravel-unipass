<?php

declare(strict_types=1);

namespace Sejongtf\LaravelUnipass\DataObjects;

use Illuminate\Support\Carbon;
use Sejongtf\LaravelUnipass\Support\Xml;

/**
 * 입항보고내역(해상)(API021) 한 건(etprRprtQryBrkdQryVo).
 */
final class SeaArrivalReportItem
{
    public function __construct(
        public readonly ?string $customsCode,     // cstmSgn 세관부호
        public readonly ?string $mrn,             // mrn 적하목록관리번호
        public readonly ?string $vesselName,      // shipFlgtNm 선박항공편명
        public readonly ?string $imoNo,           // shipCallImoNo 선박호출 IMO 번호
        public readonly ?string $countryCode,     // shipAirCntyCd 선박항공기국가코드
        public readonly ?string $arrivalAt,       // etprDttm 입항일시 (YYYYMMDDHHMMSS)
        public readonly ?string $berthName,       // shipLamrPlcNm 선박계선장소명
        public readonly ?string $berthCode,       // shipLamrPlcCd 선박계선장소코드
        public readonly ?int $crewCount,          // alCrmbPecnt 전체승무원인원수
        public readonly ?int $passengerCount,     // alPsngPecnt 전체승객인원수
        public readonly ?string $clearedAt,       // acptDttm 수리일시 (YYYYMMDDHHMMSS)
        public readonly array $raw = [],
    ) {
    }

    public static function fromArray(array $row): self
    {
        $crew = Xml::scalar($row, 'alCrmbPecnt');
        $passenger = Xml::scalar($row, 'alPsngPecnt');

        return new self(
            customsCode: Xml::scalar($row, 'cstmSgn'),
            mrn: Xml::scalar($row, 'mrn'),
            vesselName: Xml::scalar($row, 'shipFlgtNm'),
            imoNo: Xml::scalar($row, 'shipCallImoNo'),
            countryCode: Xml::scalar($row, 'shipAirCntyCd'),
            arrivalAt: Xml::scalar($row, 'etprDttm'),
            berthName: Xml::scalar($row, 'shipLamrPlcNm'),
            berthCode: Xml::scalar($row, 'shipLamrPlcCd'),
            crewCount: $crew === null ? null : (int) $crew,
            passengerCount: $passenger === null ? null : (int) $passenger,
            clearedAt: Xml::scalar($row, 'acptDttm'),
            raw: $row,
        );
    }

    /**
     * 입항일시(etprDttm, YYYYMMDDHHMMSS)를 Carbon 으로 변환.
     */
    public function arrivalAtCarbon(): ?Carbon
    {
        return $this->arrivalAt !== null && strlen($this->arrivalAt) === 14
            ? Carbon::createFromFormat('YmdHis', $this->arrivalAt)
            : null;
    }
}
