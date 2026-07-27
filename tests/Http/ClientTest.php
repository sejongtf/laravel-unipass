<?php

declare(strict_types=1);

namespace Sejongtf\LaravelUnipass\Tests\Http;

use Illuminate\Container\Container;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\TestCase;
use Sejongtf\LaravelUnipass\Exceptions\UnipassException;
use Sejongtf\LaravelUnipass\Http\Client;

final class ClientTest extends TestCase
{
    protected function setUp(): void
    {
        // Http 파사드가 동작하도록 최소 컨테이너를 파사드 루트로 지정한다
        Facade::setFacadeApplication(new Container);
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
    }

    private function client(): Client
    {
        return new Client([
            'base_url' => 'https://unipass.customs.go.kr:38010/ext/rest',
            'timeout' => 15,
        ]);
    }

    public function test_decode_parses_xml_into_array(): void
    {
        $data = Client::decode(<<<'XML'
            <cargCsclPrgsInfoQryRtnVo>
                <tCnt>1</tCnt>
                <ntceInfo/>
                <cargCsclPrgsInfoQryVo>
                    <cargMtNo>00ANLU083N59007001</cargMtNo>
                    <prnm><![CDATA[TEST & GOODS]]></prnm>
                </cargCsclPrgsInfoQryVo>
            </cargCsclPrgsInfoQryRtnVo>
            XML);

        $this->assertSame('1', $data['tCnt']);
        $this->assertSame([], $data['ntceInfo']); // 빈 엘리먼트는 빈 배열
        $this->assertSame('00ANLU083N59007001', $data['cargCsclPrgsInfoQryVo']['cargMtNo']);
        $this->assertSame('TEST & GOODS', $data['cargCsclPrgsInfoQryVo']['prnm']);
    }

    public function test_decode_throws_on_invalid_xml(): void
    {
        $this->expectException(UnipassException::class);

        Client::decode('this is not xml');
    }

    public function test_error_message_detects_tcnt_minus_one(): void
    {
        $this->assertSame(
            '인증키가 존재하지 않습니다.',
            Client::errorMessage(['tCnt' => '-1', 'ntceInfo' => '인증키가 존재하지 않습니다.']),
        );
    }

    public function test_error_message_falls_back_when_ntce_info_is_empty(): void
    {
        $this->assertSame(
            'UNI-PASS 요청이 실패했습니다.',
            Client::errorMessage(['tCnt' => '-1', 'ntceInfo' => []]),
        );
    }

    public function test_error_message_returns_null_for_normal_response(): void
    {
        $this->assertNull(Client::errorMessage(['tCnt' => '1', 'ntceInfo' => []]));
        $this->assertNull(Client::errorMessage([]));
    }

    public function test_get_requests_service_path_with_query(): void
    {
        Http::fake([
            '*' => Http::response('<rtnVo><tCnt>1</tCnt><vo><cargMtNo>A</cargMtNo></vo></rtnVo>'),
        ]);

        $data = $this->client()->get('cargCsclPrgsInfoQry/retrieveCargCsclPrgsInfo', [
            'crkyCn' => 'test-key',
            'cargMtNo' => '00ANLU083N59007001',
        ]);

        $this->assertSame('A', $data['vo']['cargMtNo']);

        Http::assertSent(static function (Request $request): bool {
            return str_starts_with(
                $request->url(),
                'https://unipass.customs.go.kr:38010/ext/rest/cargCsclPrgsInfoQry/retrieveCargCsclPrgsInfo',
            )
                && $request['crkyCn'] === 'test-key'
                && $request['cargMtNo'] === '00ANLU083N59007001';
        });
    }

    public function test_get_throws_on_http_failure(): void
    {
        Http::fake(['*' => Http::response('server error', 500)]);

        $this->expectException(UnipassException::class);
        $this->expectExceptionMessage('HTTP 500');

        $this->client()->get('cargCsclPrgsInfoQry/retrieveCargCsclPrgsInfo');
    }

    public function test_get_throws_unipass_error_as_exception(): void
    {
        Http::fake([
            '*' => Http::response('<rtnVo><tCnt>-1</tCnt><ntceInfo>인증키가 존재하지 않습니다.</ntceInfo></rtnVo>'),
        ]);

        $this->expectException(UnipassException::class);
        $this->expectExceptionMessage('인증키가 존재하지 않습니다.');

        $this->client()->get('cargCsclPrgsInfoQry/retrieveCargCsclPrgsInfo');
    }
}
