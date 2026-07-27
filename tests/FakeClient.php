<?php

declare(strict_types=1);

namespace Sejongtf\LaravelUnipass\Tests;

use Sejongtf\LaravelUnipass\Contracts\Client;

/**
 * 테스트용 페이크 전송 클라이언트. 미리 준비한 응답을 반환하고 호출 내역을 기록한다.
 */
final class FakeClient implements Client
{
    /** @var array<int, array{path: string, query: array<string, mixed>, cacheTtl: int|null}> */
    public array $calls = [];

    /**
     * @param  array<string, mixed>  $response  get() 이 반환할 디코드된 응답 배열
     */
    public function __construct(private array $response = [])
    {
    }

    public function get(string $path, array $query = [], ?int $cacheTtl = null): array
    {
        $this->calls[] = ['path' => $path, 'query' => $query, 'cacheTtl' => $cacheTtl];

        return $this->response;
    }

    /**
     * 마지막 호출 내역.
     *
     * @return array{path: string, query: array<string, mixed>, cacheTtl: int|null}|null
     */
    public function lastCall(): ?array
    {
        return $this->calls === [] ? null : $this->calls[array_key_last($this->calls)];
    }
}
