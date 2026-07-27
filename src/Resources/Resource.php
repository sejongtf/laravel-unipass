<?php

declare(strict_types=1);

namespace Sejongtf\LaravelUnipass\Resources;

use Illuminate\Support\Arr;
use Sejongtf\LaravelUnipass\Contracts\Client;
use Sejongtf\LaravelUnipass\Exceptions\UnipassException;

/**
 * 엔드포인트 리소스 베이스. 자기 서비스의 인증키(crkyCn)/경로/캐시 설정을 갖고,
 * 전송 계약(Client)에 위임한다.
 */
abstract class Resource
{
    /**
     * @param  array<string, mixed>  $service  config('unipass.services.{name}') + ['name' => ...]
     */
    public function __construct(
        protected Client $client,
        protected array $service,
    ) {
    }

    /**
     * 인증키를 주입해 서비스를 호출하고 디코드된 응답 배열을 반환한다.
     *
     * @param  array<string, string|int|null>  $params  요청 파라미터(crkyCn 제외)
     * @return array<string, mixed>
     *
     * @throws \Sejongtf\LaravelUnipass\Exceptions\UnipassException
     */
    protected function call(array $params): array
    {
        $key = (string) Arr::get($this->service, 'key', '');

        if ($key === '') {
            $name = (string) Arr::get($this->service, 'name', 'unknown');

            throw new UnipassException("UNI-PASS 서비스 [{$name}] 의 인증키가 설정되지 않았습니다. (config: unipass.services.{$name}.key)");
        }

        $query = array_merge(
            ['crkyCn' => $key],
            array_filter($params, static fn ($value) => $value !== null && $value !== ''),
        );

        return $this->client->get(
            (string) Arr::get($this->service, 'path', ''),
            $query,
            Arr::get($this->service, 'cache_ttl'),
        );
    }
}
