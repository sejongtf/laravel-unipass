<?php

declare(strict_types=1);

namespace Sejongtf\LaravelUnipass\Tests\Support;

use PHPUnit\Framework\TestCase;
use Sejongtf\LaravelUnipass\Support\Xml;

final class XmlTest extends TestCase
{
    public function test_scalar_returns_trimmed_value(): void
    {
        $this->assertSame('ABC', Xml::scalar(['key' => '  ABC  '], 'key'));
        $this->assertSame('0', Xml::scalar(['key' => 0], 'key'));
    }

    public function test_scalar_returns_null_for_missing_key(): void
    {
        $this->assertNull(Xml::scalar([], 'key'));
    }

    public function test_scalar_returns_null_for_empty_string(): void
    {
        $this->assertNull(Xml::scalar(['key' => ''], 'key'));
        $this->assertNull(Xml::scalar(['key' => '   '], 'key'));
    }

    public function test_scalar_normalizes_empty_element_to_null(): void
    {
        // 빈 엘리먼트(<vydf/>)는 빈 배열로 디코드된다
        $this->assertNull(Xml::scalar(['key' => []], 'key'));
    }

    public function test_list_of_returns_empty_for_missing_or_scalar_value(): void
    {
        $this->assertSame([], Xml::listOf([], 'key'));
        $this->assertSame([], Xml::listOf(['key' => 'scalar'], 'key'));
        $this->assertSame([], Xml::listOf(['key' => []], 'key'));
    }

    public function test_list_of_wraps_single_assoc_row(): void
    {
        // 반복 엘리먼트가 단건이면 연관배열로 디코드된다 → [단건] 으로 정규화
        $row = ['cargMtNo' => 'A', 'prnm' => 'B'];

        $this->assertSame([$row], Xml::listOf(['vo' => $row], 'vo'));
    }

    public function test_list_of_keeps_multi_row_list(): void
    {
        $rows = [['cargMtNo' => 'A'], ['cargMtNo' => 'B']];

        $this->assertSame($rows, Xml::listOf(['vo' => $rows], 'vo'));
    }
}
