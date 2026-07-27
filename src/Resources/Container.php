<?php

declare(strict_types=1);

namespace Sejongtf\LaravelUnipass\Resources;

use Sejongtf\LaravelUnipass\DataObjects\ContainerList;

/**
 * 컨테이너내역(API020). 화물관리번호에 딸린 컨테이너번호/규격/봉인번호 조회.
 */
final class Container extends Resource
{
    /**
     * 화물관리번호(cargMtNo, 15~19자리)로 컨테이너 목록을 조회한다.
     */
    public function byCargoNo(string $cargoManagementNo): ContainerList
    {
        return ContainerList::fromArray($this->call(['cargMtNo' => $cargoManagementNo]));
    }
}
