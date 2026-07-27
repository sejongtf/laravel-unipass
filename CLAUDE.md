# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

관세청 UNI-PASS 공개 API 연계 Laravel 패키지 (standalone library, not an app). PHP >= 8.2, illuminate ^13.0. Currently wraps 4 of UNI-PASS's 54 APIs: API001 화물통관진행정보 (수입), API002 수출이행 내역 (수출), API021 입항보고(해상), API020 컨테이너내역.

All comments, docblocks, and exception messages are written in Korean — keep that convention.

## Commands

```bash
composer install
composer test              # phpunit (tests/ directory; suite currently empty)
vendor/bin/phpunit --filter TestName   # run a single test
```

There is no artisan binary here — the `unipass:track` command only runs inside a consuming Laravel app.

## Architecture

Layered SDK: the transport contract is shared by every API, while per-API differences (path, auth key, params, response shape) live in Resources/DTOs.

```
config('unipass.services')   service registry: key + path + cache_ttl per API
        │ resolve
Unipass (entry point, src/Unipass.php)
 ├─ cargoClearance() / exportFulfillment() / seaArrivalReport() / containers()
 │       └─> Resources\*  — owns its service's crkyCn key, path, param names, DTO mapping
 │               │ call()  (Resource base injects crkyCn, drops null/'' params)
 └─ client() ──> Contracts\Client — pure transport contract
                     └─ Http\Client: HTTP GET → XML decode → tCnt=-1 error → optional cache
```

- **Adding a new API** = one entry in `config/unipass.php` `services` + one Resource (extends `Resources\Resource`) + one or two DTOs + a method on `Unipass`. The transport layer never changes.
- **Per-API auth**: UNI-PASS issues a separate 인증키 (`crkyCn`) per service; `Resource::call()` injects it and throws `UnipassException` if the key is unset. Keys come from env vars (`UNIPASS_*_KEY`).
- **Resources depend only on `Contracts\Client`**, so tests can inject a fake client instead of hitting HTTP.
- Registered by `UnipassServiceProvider` as singletons (`unipass`, `unipass.client` aliases); accessible via the `Facades\Unipass` facade or the `unipass()` helper (`src/helpers.php`, autoloaded via composer `files`).

## UNI-PASS response quirks (encoded in Support\Xml and Http\Client)

- Responses are XML, decoded to arrays via SimpleXML→json round-trip.
- **All errors** come back as `tCnt = -1` with a Korean message in `ntceInfo` — `Http\Client::errorMessage()` converts these to `UnipassException`. Exception: an `ntceInfo` starting with `[N00]` is not an error but a multi-record (list) notice (API001).
- Empty XML elements decode as empty arrays → normalize to `null` with `Xml::scalar()`.
- Repeated elements decode as an assoc array when single, a list when multiple → always normalize with `Xml::listOf()`.
- DTOs are `readonly`, built via static `fromArray()`, and keep the original payload in `->raw` so unmapped fields stay reachable (`field('xxx')`).

## Caching

`cache_ttl` is per-service in the registry; `0` (the default for all current services) means no caching — 통관 진행상태 is volatile. Only give long TTLs to master-data APIs (선박회사, HS부호, 관세율 etc.) when adding them.

## API environment notes

- Production base URL `https://unipass.customs.go.kr:38010/ext/rest`, test environment `https://tunipass.customs.go.kr:38010/ext/rest` (`UNIPASS_BASE_URL`).
- Customs policy: domain-name calls only (no IP), TLS 1.2+; API001 data is limited to 3 years after 입항.
