<?php

declare(strict_types=1);

namespace Sejongtf\LaravelUnipass\DataObjects;

use Illuminate\Support\Carbon;
use Sejongtf\LaravelUnipass\Support\Xml;

/**
 * 화물통관진행정보 헤더(cargCsclPrgsInfoQryVo).
 *
 * 단건 조회 시 1건, 다건 조회(목록) 시 N건 제공된다. 다건 목록에서는 일부
 * 필드(화물관리번호/MBL/HBL/입항일자/양륙항/선사항공사)만 채워질 수 있다.
 */
class CargoClearanceSummary
{
    public function __construct(
        public readonly ?string $cargoManagementNo,   // cargMtNo 화물관리번호
        public readonly ?string $progressStatus,       // prgsStts 진행상태
        public readonly ?string $progressStatusCode,   // prgsStCd 진행상태코드
        public readonly ?string $customsProgressStatus, // csclPrgsStts 통관진행상태
        public readonly ?string $masterBlNo,           // mblNo
        public readonly ?string $houseBlNo,            // hblNo
        public readonly ?string $productName,          // prnm 품명
        public readonly ?string $cargoType,            // cargTp 화물구분
        public readonly ?string $vesselName,           // shipNm 선박명
        public readonly ?string $voyage,               // vydf 항차
        public readonly ?string $carrierCode,          // shcoFlcoSgn 선사항공사부호
        public readonly ?string $carrierName,          // shcoFlco 선사항공사
        public readonly ?string $forwarderCode,        // frwrSgn 포워더부호
        public readonly ?string $forwarderName,        // frwrEntsConm 포워더명
        public readonly ?string $agency,               // agnc 대리점
        public readonly ?string $loadingPortCode,      // ldprCd 적재항코드
        public readonly ?string $loadingPortName,      // ldprNm 적재항명
        public readonly ?string $loadingCountryCode,   // lodCntyCd 적출국가코드
        public readonly ?string $dischargePortCode,    // dsprCd 양륙항코드
        public readonly ?string $dischargePortName,    // dsprNm 양륙항명
        public readonly ?string $entryCustoms,         // etprCstm 입항세관
        public readonly ?string $entryDate,            // etprDt 입항일자 (YYYYMMDD)
        public readonly ?string $blType,               // blPt B/L 유형
        public readonly ?string $blTypeName,           // blPtNm B/L 유형명
        public readonly ?string $shipNationality,      // shipNat 선박국적
        public readonly ?string $shipNationalityName,  // shipNatNm 선박국적명
        public readonly ?string $containerNo,          // cntrNo 컨테이너번호
        public readonly ?int $containerCount,          // cntrGcnt 컨테이너개수
        public readonly ?int $packageCount,            // pckGcnt 포장개수
        public readonly ?string $packageUnit,          // pckUt 포장단위
        public readonly ?string $weight,               // ttwg 총중량
        public readonly ?string $weightUnit,           // wghtUt 중량단위
        public readonly ?string $volume,               // msrm 용적
        public readonly ?string $processedAt,          // prcsDttm 처리일시 (YYYYMMDDHHMMSS)
        public readonly array $raw = [],
    ) {
    }

    public static function fromArray(array $row): self
    {
        $container = Xml::scalar($row, 'cntrGcnt');
        $package = Xml::scalar($row, 'pckGcnt');

        return new self(
            cargoManagementNo: Xml::scalar($row, 'cargMtNo'),
            progressStatus: Xml::scalar($row, 'prgsStts'),
            progressStatusCode: Xml::scalar($row, 'prgsStCd'),
            customsProgressStatus: Xml::scalar($row, 'csclPrgsStts'),
            masterBlNo: Xml::scalar($row, 'mblNo'),
            houseBlNo: Xml::scalar($row, 'hblNo'),
            productName: Xml::scalar($row, 'prnm'),
            cargoType: Xml::scalar($row, 'cargTp'),
            vesselName: Xml::scalar($row, 'shipNm'),
            voyage: Xml::scalar($row, 'vydf'),
            carrierCode: Xml::scalar($row, 'shcoFlcoSgn'),
            carrierName: Xml::scalar($row, 'shcoFlco'),
            forwarderCode: Xml::scalar($row, 'frwrSgn'),
            forwarderName: Xml::scalar($row, 'frwrEntsConm'),
            agency: Xml::scalar($row, 'agnc'),
            loadingPortCode: Xml::scalar($row, 'ldprCd'),
            loadingPortName: Xml::scalar($row, 'ldprNm'),
            loadingCountryCode: Xml::scalar($row, 'lodCntyCd'),
            dischargePortCode: Xml::scalar($row, 'dsprCd'),
            dischargePortName: Xml::scalar($row, 'dsprNm'),
            entryCustoms: Xml::scalar($row, 'etprCstm'),
            entryDate: Xml::scalar($row, 'etprDt'),
            blType: Xml::scalar($row, 'blPt'),
            blTypeName: Xml::scalar($row, 'blPtNm'),
            shipNationality: Xml::scalar($row, 'shipNat'),
            shipNationalityName: Xml::scalar($row, 'shipNatNm'),
            containerNo: Xml::scalar($row, 'cntrNo'),
            containerCount: $container === null ? null : (int) $container,
            packageCount: $package === null ? null : (int) $package,
            packageUnit: Xml::scalar($row, 'pckUt'),
            weight: Xml::scalar($row, 'ttwg'),
            weightUnit: Xml::scalar($row, 'wghtUt'),
            volume: Xml::scalar($row, 'msrm'),
            processedAt: Xml::scalar($row, 'prcsDttm'),
            raw: $row,
        );
    }

    /**
     * 입항일자(etprDt, YYYYMMDD)를 Carbon 으로 변환.
     */
    public function entryDateAt(): ?Carbon
    {
        if ($this->entryDate === null || strlen($this->entryDate) !== 8) {
            return null;
        }

        return Carbon::createFromFormat('Ymd', $this->entryDate)->startOfDay();
    }

    /**
     * 원본 응답 필드를 그대로 조회한다.
     */
    public function field(string $key): ?string
    {
        return Xml::scalar($this->raw, $key);
    }
}
