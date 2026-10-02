# PHP GPU Tensors Benchmarks

Standalone benchmark suite for [PHP GPU Tensors](https://github.com/lcmialichi/php-cuda-ext).
It measures tensor arithmetic, reductions, shape operations, host-to-device
transfers, and matrix multiplication, producing readable HTML and structured
JSON reports for inspecting workload latency and runtime behavior.

## Requirements

- Linux with an NVIDIA GPU, compatible driver, and CUDA Toolkit
- PHP 8.1-8.5 NTS with the `cuda` extension enabled
- Composer

Install the extension first by following the main project's
[installation instructions](https://github.com/lcmialichi/php-cuda-ext#start-here),
then install the benchmark autoloader:

```bash
composer install
```

## Run

The full suite includes host transfers larger than PHP's common 128 MB CLI
memory limit. Run it with enough PHP memory for the selected workload:

```bash
php -d memory_limit=-1 run_benchmarks.php
```

Run selected groups:

```bash
php -d memory_limit=-1 run_benchmarks.php --matmul
php -d memory_limit=-1 run_benchmarks.php --import
```

If the extension is not enabled in the CLI configuration, specify its module
path explicitly:

```bash
php -n -d memory_limit=-1 -d extension=/path/to/cuda.so run_benchmarks.php --matmul
```

JSON and HTML reports are written to `benchmarks/reports/` and are ignored by
Git. They include PHP thread mode, host, CUDA driver/runtime, GPU model, and
device memory so runs can be interpreted later. The memory metric is PHP heap
delta, not GPU VRAM.

## Compare reports

Run the same selection before and after a change, keeping the host, GPU, CUDA
Toolkit, and benchmark options fixed. Save each generated JSON path, then
compare them:

```bash
BENCHMARK_REVISION=$(git rev-parse --short HEAD) php -d memory_limit=-1 run_benchmarks.php --matmul
BENCHMARK_REVISION=$(git rev-parse --short HEAD) php -d memory_limit=-1 run_benchmarks.php --matmul
php compare_reports.php \
	benchmarks/reports/<baseline>.json \
	benchmarks/reports/<candidate>.json \
	published-reports/<comparison-name>
```

The comparison pairs cases by benchmark and metadata, summarizes median
latency, percentage change, and speedup, and lists unmatched cases. Reports
also capture the source revision and hardware/runtime environment to make
results easier to interpret and reproduce.

## Published reports

The [full-suite report](published-reports/php85-nts-vs-zts-full/README.md)
covers 368 cases across five workload groups and includes the comparison plus
raw JSON/HTML reports for exploring latency and environment details. A focused
[runtime-mode example](published-reports/php85-nts-vs-zts/README.md) shows the
same report format for matrix multiplication and data transfers.
