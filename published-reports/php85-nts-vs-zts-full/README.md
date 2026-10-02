# Full-Suite Benchmark Report

Generated on 2026-10-02 with benchmark revision `1ff536d`.

## Environment and coverage

- PHP 8.5.11 NTS and ZTS runs
- CUDA Toolkit/runtime 12.6; driver 582.08 (CUDA driver API 13.0)
- NVIDIA RTX A2000 12 GB, compute capability 8.6
- Linux x86_64 under WSL2; PHP `memory_limit=-1`
- Same extension source, GPU, driver, and workload settings for both runs

The suite measures per-case latency across 368 matched workloads. The reports
include medians, timing distributions, workload metadata, and runtime/hardware
details; the raw JSON preserves the full per-case summaries.

| Workload group | Cases |
| --- | ---: |
| Basic operations | 188 |
| Shape and manipulation | 31 |
| Reductions | 80 |
| Memory copy | 61 |
| Linear algebra and multi-tensor | 8 |
| **Total** | **368** |

The NTS/ZTS comparison is one example of how the suite can be used. Each mode
was run once, so rerun on your own target hardware before making performance
decisions. The memory metric is PHP heap delta, not GPU VRAM.

## Reports

- [Full-suite comparison (HTML)](full-suite.html) and [JSON](full-suite.json)
- [NTS raw report (HTML)](nts-raw.html) and [JSON](nts-raw.json)
- [ZTS raw report (HTML)](zts-raw.html) and [JSON](zts-raw.json)

The HTML files are self-contained. The JSON reports retain per-case metadata,
timing summaries, environment details, and source revision.
