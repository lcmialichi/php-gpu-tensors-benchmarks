# PHP 8.5 NTS vs ZTS: Full Suite

Full-suite comparison generated on 2026-10-02 with benchmark revision `1ff536d`.

## Environment and method

- PHP 8.5.11, NTS baseline vs ZTS candidate
- CUDA Toolkit/runtime 12.6; driver 582.08 (CUDA driver API 13.0)
- NVIDIA RTX A2000 12 GB, compute capability 8.6
- Linux x86_64 under WSL2; PHP `memory_limit=-1`
- Same extension source, benchmark revision, GPU, driver, and workload settings
- 368 matched cases across basic operations, shape manipulation, reductions, memory copies, and linear algebra

Each comparison uses per-case median latency. A change below -0.5% is marked
faster, above +0.5% slower, and values in between similar. Negative change
means ZTS was faster. The full suite has workload-specific iteration counts;
see the raw JSON reports for each case's count and timing statistics.

## Results

| Profile | Matched cases | ZTS faster | ZTS slower | Similar |
| --- | ---: | ---: | ---: | ---: |
| Basic operations | 188 | 105 | 60 | 23 |
| Shape and manipulation | 31 | 0 | 31 | 0 |
| Reductions | 80 | 33 | 38 | 9 |
| Memory copy | 61 | 24 | 31 | 6 |
| Linear algebra and multi-tensor | 8 | 4 | 4 | 0 |
| **Total** | **368** | **166** | **164** | **38** |

The overall result is mixed. Most paired cases are close, but some reductions
and shape operations deserve follow-up. For example, the 64x64x64 global
`min()` case measured 0.2615 ms on NTS and 3.4689 ms on ZTS (+1226.7%), while
the 64x64x64 global `prod()` case measured 0.2575 ms and 2.5361 ms (+884.8%).
These are notable results from one run per mode, not established regressions;
repeat them before drawing conclusions. Small operations and GPU clock/load
can also affect comparisons on a shared workstation.

The memory metric in raw reports is PHP heap delta, not GPU VRAM.

## Reports

- [Full-suite comparison (HTML)](full-suite.html) and [JSON](full-suite.json)
- [NTS raw report (HTML)](nts-raw.html) and [JSON](nts-raw.json)
- [ZTS raw report (HTML)](zts-raw.html) and [JSON](zts-raw.json)

The HTML files are self-contained. The JSON reports retain per-case metadata,
timing summaries, environment details, and source revision.
