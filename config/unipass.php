<?php

return [

    /*
    |--------------------------------------------------------------------------
    | UNI-PASS 서비스 URL
    |--------------------------------------------------------------------------
    | 운영환경: https://unipass.customs.go.kr:38010/ext/rest
    | 개발환경: https://tunipass.customs.go.kr:38010/ext/rest
    |
    | ※ 관세청 정책상 IP 로는 호출 불가하며 반드시 도메인명으로 호출해야 한다.
    |    또한 TLS 1.2 이상이 요구된다.
    */
    'base_url' => env('UNIPASS_BASE_URL', 'https://unipass.customs.go.kr:38010/ext/rest'),

    /*
    |--------------------------------------------------------------------------
    | HTTP 타임아웃 (초)
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('UNIPASS_TIMEOUT', 15),

    /*
    |--------------------------------------------------------------------------
    | 응답 캐시 (공통)
    |--------------------------------------------------------------------------
    | 캐시 사용 여부/기간은 서비스별 cache_ttl 로 제어한다(0 이면 미사용).
    | store/prefix 는 전역 공통값.
    */
    'cache' => [
        'store' => env('UNIPASS_CACHE_STORE'),
        'prefix' => env('UNIPASS_CACHE_PREFIX', 'unipass:'),
    ],

    /*
    |--------------------------------------------------------------------------
    | 서비스 레지스트리
    |--------------------------------------------------------------------------
    | UNI-PASS Open API 는 서비스(API)마다 별도 인증키(crkyCn)가 발급된다.
    | 각 서비스를 key + path + cache_ttl 로 등록한다.
    |   - key       : 해당 API 용 crkyCn (포털에서 API 별로 신청/발급)
    |   - path      : base_url 뒤에 붙는 서비스 경로
    |   - cache_ttl : 응답 캐시 기간(초). 0 이면 캐시 안 함.
    |                 통관 진행상태 등 가변 데이터는 0, 마스터 데이터는 장기값 권장.
    */
    'services' => [

        // API001 화물통관 진행정보 (수입)
        'cargo_clearance' => [
            'key' => env('UNIPASS_CARGO_CLEARANCE_KEY', ''),
            'path' => 'cargCsclPrgsInfoQry/retrieveCargCsclPrgsInfo',
            'cache_ttl' => 0,
        ],

        // API002 수출신고번호별 수출이행 내역 (수출)
        'export_fulfillment' => [
            'key' => env('UNIPASS_EXPORT_FULFILLMENT_KEY', ''),
            'path' => 'expDclrNoPrExpFfmnBrkdQry/retrieveExpDclrNoPrExpFfmnBrkd',
            'cache_ttl' => 0,
        ],

        // API021 입항보고내역(해상)
        'sea_arrival_report' => [
            'key' => env('UNIPASS_SEA_ARRIVAL_REPORT_KEY', ''),
            'path' => 'etprRprtQryBrkdQry/retrieveetprRprtQryBrkd',
            'cache_ttl' => 0,
        ],

        // API020 컨테이너내역
        'container' => [
            'key' => env('UNIPASS_CONTAINER_KEY', ''),
            'path' => 'cntrQryBrkdQry/retrieveCntrQryBrkd',
            'cache_ttl' => 0,
        ],

    ],

];
