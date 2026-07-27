<?php

declare(strict_types=1);

namespace Sejongtf\LaravelUnipass\Support;

/**
 * UNI-PASS 응답 XML 을 배열로 디코드할 때 발생하는 구조 차이를 정규화하는 헬퍼.
 *
 * - 빈 엘리먼트(<vydf/>)는 빈 배열로 디코드되므로 null 로 정규화한다.
 * - 반복 엘리먼트는 단건일 때 연관배열, 다건일 때 리스트로 디코드되므로
 *   항상 리스트로 정규화한다.
 */
final class Xml
{
    /**
     * 스칼라 값을 추출한다. 빈 값/누락/빈 엘리먼트는 모두 null.
     */
    public static function scalar(array $row, string $key): ?string
    {
        $value = $row[$key] ?? null;

        if ($value === null || is_array($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * 반복 엘리먼트를 항상 0-base 리스트로 반환한다.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function listOf(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        if (! is_array($value) || $value === []) {
            return [];
        }

        // 단건(연관배열) → [단건], 다건(리스트) → 그대로
        return array_is_list($value) ? $value : [$value];
    }
}
