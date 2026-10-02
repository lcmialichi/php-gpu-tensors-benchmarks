# PHP GPU Tensors Benchmarks

Standalone benchmark suite for [PHP GPU Tensors](https://github.com/lcmialichi/php-cuda-ext).
It compares tensor arithmetic, reductions, shape operations, host-to-device
transfers, and matrix multiplication.

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

## Compare runs

Run the same selection in each PHP mode, keeping the host, GPU, CUDA Toolkit,
and benchmark options fixed. Save each generated JSON path, then compare them:

```bash
BENCHMARK_REVISION=$(git rev-parse --short HEAD) php -d memory_limit=-1 run_benchmarks.php --matmul
BENCHMARK_REVISION=$(git rev-parse --short HEAD) php -d memory_limit=-1 run_benchmarks.php --matmul
php compare_reports.php \
	benchmarks/reports/<nts-report>.json \
	benchmarks/reports/<zts-report>.json \
	published-reports/php85-nts-vs-zts/matmul
```

The first file is the baseline; the second is the candidate. The comparison
HTML/JSON pairs cases by benchmark and metadata, uses median latency, and
reports percentage change and speedup. Unmatched cases are listed separately.
Set `BENCHMARK_REVISION` to the benchmark repository commit when generating
reports so each result records its source revision.

## Published comparison

The [PHP 8.5 NTS vs ZTS report](published-reports/php85-nts-vs-zts/README.md)
includes raw JSON/HTML runs and a matched comparison for matrix multiplication
and data transfers on an RTX A2000.
