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

Run all benchmarks:

```bash
php run_benchmarks.php
```

Run selected groups:

```bash
php run_benchmarks.php --matmul
php run_benchmarks.php --import
```

If the extension is not enabled in the CLI configuration, specify its module
path explicitly:

```bash
php -n -d extension=/path/to/cuda.so run_benchmarks.php --matmul
```

JSON and HTML reports are written to `benchmarks/reports/` and are ignored by
Git. Include the PHP, CUDA Toolkit, driver, and GPU versions when comparing or
sharing results; timings are sensitive to those conditions and to data sizes.
