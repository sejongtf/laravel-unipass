<?php

declare(strict_types=1);

namespace Sejongtf\LaravelUnipass\Resources;

use Sejongtf\LaravelUnipass\DataObjects\CargoClearanceProgress;

/**
 * 화물통관진행정보(API001). 수입화물의 통관 진행상태/진행이력 조회.
 */
final class CargoClearance extends Resource
{
    /**
     * 화물관리번호(cargMtNo, 15~19자리)로 조회. 보통 단건(상세 + 진행이력)을 반환한다.
     */
    public function byCargoNo(string $cargoManagementNo): CargoClearanceProgress
    {
        return $this->query(['cargMtNo' => $cargoManagementNo]);
    }

    /**
     * House B/L 번호 + 입항년도로 조회. 다건이면 목록(isList)이 반환된다.
     */
    public function byHouseBl(string $houseBlNo, int|string $blYear): CargoClearanceProgress
    {
        return $this->query(['hblNo' => $houseBlNo, 'blYy' => (string) $blYear]);
    }

    /**
     * Master B/L 번호 + 입항년도로 조회. 다건이면 목록(isList)이 반환된다.
     */
    public function byMasterBl(string $masterBlNo, int|string $blYear): CargoClearanceProgress
    {
        return $this->query(['mblNo' => $masterBlNo, 'blYy' => (string) $blYear]);
    }

    /**
     * 저수준 조회. cargMtNo | mblNo | hblNo | blYy 중 일부를 직접 전달한다.
     *
     * @param  array<string, string|int|null>  $params
     */
    public function query(array $params): CargoClearanceProgress
    {
        return CargoClearanceProgress::fromArray($this->call($params));
    }
}
