# PHP 8.5 NTS vs ZTS

Comparison generated on 2026-10-02 from benchmark revision `0cfc076`.

## Environment and method

- PHP 8.5.11, NTS baseline vs ZTS candidate
- CUDA Toolkit/runtime 12.6.3; driver 582.08 (CUDA driver API 13.0)
- NVIDIA RTX A2000 12 GB
- Matmul: 8 shapes, 50 measured iterations per case, 10 warmups
- Transfer/import: 20 cases across five APIs and four sizes, 10 measured iterations per case, 10 warmups

Each row compares median latency; negative percentage means ZTS was faster.
These are one NTS and one ZTS run on a shared workstation, so scheduling and
GPU clock/load can affect small cases. Repeat before using the numbers as a
performance guarantee.

## Results

| Profile | Matched cases | ZTS faster | ZTS slower | Similar |
| --- | ---: | ---: | ---: | ---: |
| Matmul | 8 | 2 | 6 | 0 |
| Transfer/import | 20 | 6 | 13 | 1 |

| Operation | NTS median | ZTS median | Change |
| --- | ---: | ---: | ---: |
| Matmul 1024x768 by 768x512 | 0.2383 ms | 0.2735 ms | +14.8% |
| Matmul 8x64x256x256 batch | 3.9542 ms | 4.0157 ms | +1.6% |
| PHP array import, 1,048,576 float32 | 4.9473 ms | 4.8922 ms | -1.1% |
| Pinned HostArray transfer, 1,048,576 float32 | 0.3188 ms | 0.2517 ms | -21.0% |

The overall result is mixed; there is no uniform ZTS performance penalty or
benefit. Very small transfers are dominated by host/runtime overhead and show
the largest relative variance. The memory figure in raw reports is PHP heap
delta, not GPU VRAM.

## Comparisons

- [Matmul comparison (HTML)](matmul.html) and [JSON](matmul.json)
- [Transfer/import comparison (HTML)](import.html) and [JSON](import.json)
- Raw per-mode JSON and HTML reports are included alongside the comparisons.

The HTML files are self-contained and can be opened after cloning or
downloaded from GitHub. The JSON reports include the full runtime, driver, GPU,
workload, timing samples, and source revision metadata.# PHP 8.5 NTS vs ZTS

Comparison generated on 2026-10-02 from benchmark revision `0cfc076`.

## Environment

- PHP 8.5.11, NTS baseline vs ZTS candidate
- CUDA Toolkit/runtime 12.6.3; driver 582.08 (CUDA driver API 13.0)
- NVIDIA RTX A2000 12 GB
- Matmul: 8 shapes, 50 measured iterations per case, 10 warmups
- Transfer/import: 20 cases across five APIs and four sizes, 10 measured iterations per case, 10 warmups

## Results

| Profile | Matched cases | ZTS faster | ZTS slower | Similar |
| --- | ---: | ---: | ---: | ---: |
| Matmul | 8 | 2 | 6 | 0 |
| Transfer/import | 20 | 6 | 13 | 1 |

Selected median latencies:

| Operation | NTS | ZTS | Change |
| --- | ---: | ---: | ---: |
| Matmul 1024x768 by 768x512 | 0.2383 ms | 0.2735 ms | +14.8% |
| Matmul 8x64x256x256 batch | 3.9542 ms | 4.0157 ms | +1.6% |
| PHP array import, 1,048,576 float32 | 4.9473 ms | 4.8922 ms | -1.1% |
| Pinned HostArray transfer, 1,048,576 float32 | 0.3188 ms | 0.2517 ms | -21.0% |

Negative change means the ZTS candidate was faster. These are single NTS and
ZTS runs; per-case medians use repeated iterations, but small operations are
still sensitive to scheduling and GPU clock state. The results do not show a
uniform ZTS penalty or benefit and should not be generalized beyond this host.

## Reports

- [Matmul comparison (HTML)](matmul.html) and [JSON](matmul.json)
- [Transfer/import comparison (HTML)](import.html) and [JSON](import.json)
- Raw per-mode JSON and HTML reports are included alongside the comparisons.

The HTML files can be opened after cloning or downloaded from GitHub. The JSON
files include the full environment, raw timings, and comparison metadata.