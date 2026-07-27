<?php

declare(strict_types=1);

namespace Sejongtf\LaravelUnipass\DataObjects;

use Sejongtf\LaravelUnipass\Support\Xml;

/**
 * 수출이행 내역(API002) 한 건.
 *
 * 조회키에 따라 응답 VO 가 다르지만(수출신고번호 → QryRsltVo/DtlQryRsltVo,
 * B/L → BlNoQryRsltVo) 필드가 대부분 겹치므로 하나의 DTO 로 흡수한다.
 */
final class ExportFulfillmentItem
{
    public function __construct(
        public readonly ?string $exportDeclarationNo, // expDclrNo 수출신고번호
        public readonly ?string $blNo,                // blNo B/L 번호
        public readonly ?string $exporterName,        // exppnConm 수출자상호
        public readonly ?string $manufacturerName,    // mnurConm 제조자상호
        public readonly ?string $shipmentCompleted,   // shpmCmplYn / shpmcmplYn 선적완료여부 (Y/N)
        public readonly ?string $acceptDate,          // acptDt 수리일자 (YYYYMMDD)
        public readonly ?string $acceptTime,          // acptDttm 수리일시 (HHMMSS)
        public readonly ?string $departureDate,       // tkofDt 출항일자 (YYYYMMDD)
        public readonly ?string $loadingDeadline,     // loadDtyTmlm 적재의무기한 (YYYYMMDD)
        public readonly ?string $vesselFlight,        // sanm 선박/편명
        public readonly ?string $shipmentPlace,       // shpmAirptPortNm 선적지
        public readonly ?string $mrn,                 // mrn 적하목록관리번호
        public readonly ?string $shipmentWeight,      // shpmWght 선적중량
        public readonly ?string $customsWeight,       // csclWght 통관중량
        public readonly ?string $loadingInspectionTarget, // ldpInscTrgtYn 적재지검사대상여부
        public readonly array $raw = [],
    ) {
    }

    public static function fromArray(array $row): self
    {
        return new self(
            exportDeclarationNo: Xml::scalar($row, 'expDclrNo'),
            blNo: Xml::scalar($row, 'blNo'),
            exporterName: Xml::scalar($row, 'exppnConm'),
            manufacturerName: Xml::scalar($row, 'mnurConm'),
            // 가이드상 헤더는 shpmCmplYn, 상세는 shpmcmplYn 으로 대소문자가 갈린다.
            shipmentCompleted: Xml::scalar($row, 'shpmCmplYn') ?? Xml::scalar($row, 'shpmcmplYn'),
            acceptDate: Xml::scalar($row, 'acptDt'),
            acceptTime: Xml::scalar($row, 'acptDttm'),
            departureDate: Xml::scalar($row, 'tkofDt'),
            loadingDeadline: Xml::scalar($row, 'loadDtyTmlm'),
            vesselFlight: Xml::scalar($row, 'sanm'),
            shipmentPlace: Xml::scalar($row, 'shpmAirptPortNm'),
            mrn: Xml::scalar($row, 'mrn'),
            shipmentWeight: Xml::scalar($row, 'shpmWght'),
            customsWeight: Xml::scalar($row, 'csclWght'),
            loadingInspectionTarget: Xml::scalar($row, 'ldpInscTrgtYn'),
            raw: $row,
        );
    }

    /**
     * 선적완료 여부.
     */
    public function isCompleted(): bool
    {
        return $this->shipmentCompleted === 'Y';
    }
}
