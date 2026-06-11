# Conformance

Scenario-by-scenario status of this SDK against the LogTide SDK contract.
Each scenario ID is stable across all official SDKs; "n/a" entries explain
why a scenario does not apply. TODO entries are tracked work.

| ID | Scenario | Status | Test reference |
|---|---|---|---|
| C01 | basic log: one POST to /api/v1/ingest with X-API-Key, {logs:[...]} body, RFC 3339 time, metadata.sdk | ✅ | `tests/Unit/Transport/*`, `tests/Unit/SdkMetadataTest.php` |
| C02 | batch by size: batchSize entries flush automatically, order preserved | ✅ | `tests/Unit/Transport/BatchTransportTest.php` |
| C03 | batch by interval: entries delivered without explicit flush | n/a | request-scoped PHP: flush on shutdown handler instead of timers |
| C04 | wire format strictness: SDK fields nested in metadata, only contract fields top-level | ✅ | `tests/Unit/EventTest.php` (metadata object shape, nesting) |
| C05 | exception capture: structured metadata.exception with type/message/language/frames/cause | ✅ | `tests/Unit/Serializer/ErrorSerializerTest.php` |
| C06 | exception chain cap: cause depth ≤ 10, no infinite loop on cycles | ✅ | getPrevious() chain handling |
| C07 | retry on 5xx with growing backoff | ✅ | `BatchTransportTest` (retry with backoff) |
| C08 | no retry on permanent 4xx (400/401/403/413) | TODO | audit `sendWithRetry` status classification |
| C09 | Retry-After overrides computed backoff | TODO | Retry-After not honoured |
| C10 | circuit breaker opens after threshold failures | ✅ | `tests/Unit/Util/CircuitBreakerTest.php` |
| C11 | circuit breaker half-open probe and recovery | ✅ | `CircuitBreakerTest` (half-open) |
| C12 | buffer cap: drops beyond maxBufferSize, counted, never throws | ✅ | max_buffer_size drop policy |
| C13 | flush on close; capture after close is a silent no-op | ✅ | `LogtideSdkTest` (shutdown flush) |
| C14 | DSN parsing incl. base path; invalid DSN fails at init | ✅ | `tests/Unit/DsnTest.php` |
| C15 | inbound traceparent lands on entry trace_id | ✅ | `PropagationContext` tests (traceparent) |
| C16 | no PII by default; API key never logged | ✅ | send_default_pii flag |
| C17 | serialisation robustness: circular/unserialisable values never throw | partial | json_encode fallbacks |
| C18 | timestamp fidelity: time reflects capture, not delivery | ✅ | time stamped at Event creation (UTC Z) |
| C20 | scope isolation across concurrent requests | ✅ | `tests/Unit/State/ScopeTest.php`, Hub tests |
| C21 | breadcrumb ring buffer eviction, oldest first | ✅ | `tests/Unit/Breadcrumb/BreadcrumbBufferTest.php` |
| C22 | beforeSend can mutate or drop entries | ✅ | beforeSend/beforeBreadcrumb options |
| C23 | sampling: rate 0 sends nothing (logs) / no-op spans (traces) | ✅ | `tests/Unit/SamplingTest.php` (logs) + `traces_sample_rate` (spans) |
| C24 | OTLP span export with service.name resource | ✅ | `tests/Unit/Transport/OtlpHttpTransportTest.php` |
| C25 | outbound traceparent injection on instrumented HTTP clients | ✅ | traceparent generation (`PropagationContext`) |
| C26 | log/trace correlation: active span ids on entries | ✅ | Span/trace ids on events |
| C27 | middleware error capture rethrows after logging | ✅ | framework middleware (Slim tests; Laravel/WordPress untested) |
| C28 | logging-bridge level mapping and scope context | ✅ | `tests/Unit/Monolog/LogtideHandlerTest.php` |
