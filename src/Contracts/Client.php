<?php

declare(strict_types=1);

namespace Sejongtf\LaravelUnipass\Contracts;

/**
 * UNI-PASS 전송 계층 추상화. HTTP 호출·XML 디코드·공통 오류(tCnt = -1) 처리만
 * 담당하는 순수 전송 계약. 인증키(crkyCn)는 호출 측(리소스)이 query 에 포함해 넘긴다.
 */
interface Client
{
    /**
     * 주어진 서비스 경로를 호출하고 디코드된 응답 배열을 반환한다.
     *
     * @param  string  $path  base_url 뒤에 붙는 서비스 경로
     * @param  array<string, string|int|float|null>  $query  요청 파라미터(crkyCn 포함)
     * @param  int|null  $cacheTtl  응답 캐시 기간(초). null|0 이면 캐시 안 함.
     * @return array<string, mixed>
     *
     * @throws \Sejongtf\LaravelUnipass\Exceptions\UnipassException
     */
    public function get(string $path, array $query = [], ?int $cacheTtl = null): array;
}
