<?php

namespace Benchmarks\Support;

use Benchmarks\Exporters\HtmlExporter;
use Benchmarks\Exporters\JsonExporter;

class BenchmarkReport implements \JsonSerializable
{
    private string $reportId;

    /**
     * @param array<BenchmarkClassResult> $results
     * @throws \Exception
     */
    public function __construct(private array $results)
    {
        $this->reportId = gmdate('Ymd-His') . '-' . hrtime(true);
    }

    public function getReportId(): string
    {
        return $this->reportId;
    }

    public function getResults(): array
    {
        return $this->results;
    }

    public function saveJSON(string $dir): string
    {
        $exporter = new JsonExporter();
        return $exporter->export($this, $dir);
    }

     public function saveHTML(string $dir): string
    {
        $exporter = new HtmlExporter();
        return $exporter->export($this, $dir);
    }

    public function getDevice(): string
    {
        return cuda_get_device_info()["name"] ?? "";
    }

    public function getEnvironment(): array
    {
        $device = cuda_get_device_info();
        $driver = cuda_get_driver_version();
        $runtime = cuda_get_runtime_version();

        return [
            "php" => [
                "version" => PHP_VERSION,
                "version_id" => PHP_VERSION_ID,
                "sapi" => PHP_SAPI,
                "thread_safety" => PHP_ZTS ? "ZTS" : "NTS",
                "integer_bits" => PHP_INT_SIZE * 8,
                "memory_limit" => ini_get("memory_limit"),
                "benchmark_revision" => getenv("BENCHMARK_REVISION") ?: null,
            ],
            "host" => [
                "os" => PHP_OS_FAMILY,
                "release" => php_uname("r"),
                "architecture" => php_uname("m"),
            ],
            "cuda" => [
                "driver_version" => $driver["version_string"] ?? "unknown",
                "runtime_version" => $runtime["version_string"] ?? "unknown",
            ],
            "gpu" => [
                "name" => $device["name"] ?? "unknown",
                "compute_capability" => sprintf(
                    "%d.%d",
                    $device["compute_capability_major"] ?? 0,
                    $device["compute_capability_minor"] ?? 0
                ),
                "memory_bytes" => $device["total_global_memory"] ?? null,
            ],
        ];
    }

    public function jsonSerialize(): array
    {
        $environment = $this->getEnvironment();

        return [
            "schema_version" => 2,
            "report_id" => $this->reportId,
            "generated_at" => gmdate(DATE_ATOM),
            "device" => $environment["gpu"]["name"],
            "environment" => $environment,
            "benchmarks" => $this->results
        ];
    }
}
