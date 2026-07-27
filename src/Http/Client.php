<?php

declare(strict_types=1);

namespace Sejongtf\LaravelUnipass\Http;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Sejongtf\LaravelUnipass\Contracts\Client as ClientContract;
use Sejongtf\LaravelUnipass\Exceptions\UnipassException;
use Sejongtf\LaravelUnipass\Support\Xml;
use SimpleXMLElement;

/**
 * UNI-PASS REST 전송 구현. 모든 API 가 공유하는 동작만 담는다:
 * HTTP GET → XML→배열 디코드 → 공통 오류(tCnt = -1) 처리 → 선택적 응답 캐시.
 * 서비스별 인증키/경로/파라미터는 리소스가 책임진다.
 */
final class Client implements ClientContract
{
    /**
     * @param  array<string, mixed>  $config  config('unipass') (base_url, timeout, cache)
     */
    public function __construct(private array $config = [])
    {
    }

    public function get(string $path, array $query = [], ?int $cacheTtl = null): array
    {
        $data = $this->fetch($path, $query, $cacheTtl);

        if (($message = self::errorMessage($data)) !== null) {
            throw new UnipassException($message);
        }

        return $data;
    }

    /**
     * 오류 응답이면 메시지를, 아니면 null 을 반환한다.
     *
     * UNI-PASS 는 모든 오류를 tCnt = -1 + ntceInfo(한글 메시지)로 표현한다.
     * ('[N00]' 으로 시작하는 ntceInfo 는 오류가 아니라 다건(목록) 안내이다)
     *
     * @param  array<string, mixed>  $data
     */
    public static function errorMessage(array $data): ?string
    {
        if (Xml::scalar($data, 'tCnt') !== '-1') {
            return null;
        }

        return Xml::scalar($data, 'ntceInfo') ?? 'UNI-PASS 요청이 실패했습니다.';
    }

    /**
     * UNI-PASS 응답 XML 을 연관배열로 디코드한다.
     *
     * @return array<string, mixed>
     *
     * @throws \Sejongtf\LaravelUnipass\Exceptions\UnipassException
     */
    public static function decode(string $body): array
    {
        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body, SimpleXMLElement::class, LIBXML_NOCDATA | LIBXML_NOBLANKS);
        libxml_use_internal_errors($previous);

        if ($xml === false) {
            throw new UnipassException('UNI-PASS 응답 XML 을 해석할 수 없습니다.');
        }

        return json_decode((string) json_encode($xml), true) ?: [];
    }

    /**
     * 캐시 설정에 따라 요청을 수행한다.
     *
     * @param  array<string, string|int|float|null>  $query
     * @return array<string, mixed>
     */
    private function fetch(string $path, array $query, ?int $cacheTtl): array
    {
        if (! $cacheTtl || $cacheTtl <= 0) {
            return $this->request($path, $query);
        }

        $cache = (array) Arr::get($this->config, 'cache', []);
        $store = Cache::store(Arr::get($cache, 'store'));
        $key = Arr::get($cache, 'prefix', 'unipass:').$path.':'.md5((string) json_encode($query));

        $json = $store->remember($key, $cacheTtl, fn () => json_encode($this->request($path, $query)));

        return json_decode((string) $json, true) ?: [];
    }

    /**
     * 실제 HTTP 호출.
     *
     * @param  array<string, string|int|float|null>  $query
     * @return array<string, mixed>
     *
     * @throws \Sejongtf\LaravelUnipass\Exceptions\UnipassException
     */
    private function request(string $path, array $query): array
    {
        $url = rtrim((string) Arr::get($this->config, 'base_url', ''), '/').'/'.ltrim($path, '/');
        $timeout = (int) Arr::get($this->config, 'timeout', 15);

        $response = Http::timeout($timeout)->get($url, $query);

        if ($response->failed()) {
            throw new UnipassException("UNI-PASS API 호출에 실패했습니다. (HTTP {$response->status()})");
        }

        return self::decode($response->body());
    }
}
