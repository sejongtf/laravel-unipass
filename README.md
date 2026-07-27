# sejongtf/laravel-unipass

관세청 **UNI-PASS 공개 API** 연계 패키지. 현재 지원 서비스:

| 서비스 | API | 용도 |
| --- | --- | --- |
| `cargoClearance()` | API001 화물통관 진행정보 | **수입** 통관 진행상태·이력 |
| `exportFulfillment()` | API002 수출신고번호별 수출이행 내역 | **수출** 이행·잔량 |
| `seaArrivalReport()` | API021 입항보고내역(해상) | 해상 입항 정보 |
| `containers()` | API020 컨테이너내역 | 컨테이너번호·규격·봉인번호 |

> 💡 수입 화물은 **API001**, 수출 화물은 **API002** 로 추적한다(서로 다른 서비스).

## 설치 / 설정

UNI-PASS Open API 는 **서비스(API)마다 별도 인증키(crkyCn)** 가 발급된다. 사용하는 서비스의 키만 `.env` 에 넣으면 된다.

```dotenv
UNIPASS_CARGO_CLEARANCE_KEY=...      # API001 화물통관 진행정보 (수입)
UNIPASS_EXPORT_FULFILLMENT_KEY=...   # API002 수출이행 내역 (수출)
UNIPASS_SEA_ARRIVAL_REPORT_KEY=...   # API021 입항보고(해상)
UNIPASS_CONTAINER_KEY=...            # API020 컨테이너내역

# (선택) 운영/개발 환경 전환. 기본값은 운영.
# 개발(테스트)환경: https://tunipass.customs.go.kr:38010/ext/rest
UNIPASS_BASE_URL=https://unipass.customs.go.kr:38010/ext/rest
UNIPASS_TIMEOUT=15
```

설정 파일 퍼블리시(선택): `php artisan vendor:publish --tag=unipass-config`

> ⚠️ 관세청 정책상 **IP 가 아닌 도메인명으로만 호출 가능**하며 **TLS 1.2 이상**이 필요하다.
> 인증키는 UNI-PASS 포털 `My메뉴 > 서비스관리 > OpenAPI 사용관리` 에서 API 별로 신청·발급한다.
> 화물통관진행정보는 **입항 후 3년 이내** 데이터만 조회 가능하다.

## 구조

SDK 형태의 계층 구조. 모든 API 가 전송 규격(인증·XML 봉투·`tCnt=-1` 오류)을 공유하므로
전송 계층을 1곳에 모으고, API 별 차이(경로·키·파라미터·VO)는 리소스/DTO 로 분리한다.

```
config('unipass.services')   서비스 레지스트리 (key + path + cache_ttl)
        │  resolve
Unipass (진입점)
 ├─ cargoClearance()    ─┐
 ├─ exportFulfillment() ─┤
 ├─ seaArrivalReport()  ─┼─> Resources\*  (자기 서비스의 key/path/params/VO 매핑 소유)
 ├─ containers()        ─┘        │  call()
 └─ client() ──────────────> Contracts\Client  (순수 전송 계약)
                                  └ Http\Client: HTTP·XML 디코드·tCnt=-1 오류·캐시
```

새 API 추가 = `config/unipass.php` 의 `services` 한 줄 + Resource 1개 + DTO 1~2개.
`Contracts\Client` 에만 의존하므로 테스트에서 페이크 클라이언트를 주입하기 쉽다.

## 사용법

```php
use Sejongtf\LaravelUnipass\Facades\Unipass;

// API001 수입 통관 진행정보
$cargo = Unipass::cargoClearance()->byCargoNo('00ANLU083N59007001');
$cargo = Unipass::cargoClearance()->byHouseBl('605118340404', 2026);
$cargo->status();                 // 통관진행상태
$cargo->summary()->containerNo;   // 컨테이너번호
$cargo->summary()->entryDateAt(); // 입항일자(Carbon)
foreach ($cargo->events as $e) { $e->processType; $e->shedName; }

// API002 수출이행 내역
$exp = Unipass::exportFulfillment()->byDeclarationNo('15210100057248');
$exp = Unipass::exportFulfillment()->byBl('SJBL0389056');
$exp->isCompleted();              // 선적완료 여부
$exp->summary?->exporterName;     // 수출자상호
foreach ($exp->shipments as $s) { $s->blNo; $s->departureDate; }

// API021 입항보고(해상)
$arr = Unipass::seaArrivalReport()->byCallSign('3FBO6');
$arr = Unipass::seaArrivalReport()->bySubmissionNo('16SRDJA0023');
$arr->first()?->vesselName;
$arr->first()?->arrivalAtCarbon();

// API020 컨테이너내역
$ctn = Unipass::containers()->byCargoNo('00ANLU083N59007001');
foreach ($ctn->containers as $c) { $c->number; $c->sizeTypeCode; $c->sealNumbers; }

// 미구현 API 임시 직접 호출 (crkyCn 은 직접 포함)
$raw = Unipass::client()->get('someQry/retrieveSome', ['crkyCn' => '...', 'foo' => 'bar']);
```

`isEmpty()` 로 빈 결과를, `isList()`(API001)로 다건 목록을 판별한다. 매핑되지 않은 원본
필드는 `field('xxx')` 또는 `->raw` 로 접근한다. 오류(존재하지 않는 인증키 등)는
`Sejongtf\LaravelUnipass\Exceptions\UnipassException` 으로 던져진다.

## 점검 커맨드

CLI 에서 바로 조회할 수 있는 `unipass:track` 커맨드를 제공한다(앱 연동 후 사용 가능).

```bash
php artisan unipass:track cargo     <화물관리번호>
php artisan unipass:track cargo     <HBL> --by=hbl --year=2026   # --by=mbl 도 가능
php artisan unipass:track export    <B/L>                        # --by=declaration 이면 수출신고번호로
php artisan unipass:track arrival   <선박호출부호>               # --by=submission 이면 입출항제출번호로
php artisan unipass:track container <화물관리번호>
```

결과는 표로 출력되며, 빈 결과는 안내 메시지, 인증/파라미터 오류는 0 이 아닌 종료코드로 끝난다.

## 캐시

통관 진행상태처럼 가변 데이터는 캐시하지 않는 게 기본(`cache_ttl => 0`). 선박회사·HS부호·
관세율 같은 마스터성 API 를 추가할 때는 해당 서비스의 `cache_ttl` 을 길게 주면 된다.

## 지원 예정 API

UNI-PASS 는 54종 API 를 제공한다(수입신고필증검증 API022, 선박회사 API026/027, 관세율 API030,
HS부호 API018 등). 전송 규격이 동일하므로 `config/unipass.php` 의 `services` 에 등록하고
Resource/DTO 만 추가하면 된다.
