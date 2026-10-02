<?php
declare(strict_types=1);

function readReport(string $path): array
{
    $contents = file_get_contents($path);
    if ($contents === false) {
        throw new RuntimeException("Unable to read report: {$path}");
    }

    $report = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    if (!isset($report['benchmarks']) || !is_array($report['benchmarks'])) {
        throw new RuntimeException("Invalid benchmark report: {$path}");
    }

    return $report;
}

function resultIndex(array $report): array
{
    $index = [];
    foreach ($report['benchmarks'] as $benchmark) {
        $benchmarkName = $benchmark['name'] ?? 'Unknown benchmark';
        foreach ($benchmark['results'] ?? [] as $result) {
            $metadata = $result['metadata'] ?? [];
            ksort($metadata);
            $metadataJson = json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $key = $benchmarkName . "\0" . ($result['name'] ?? 'Unknown operation') . "\0" . $metadataJson;
            $index[$key] = [
                'benchmark' => $benchmarkName,
                'name' => $result['name'] ?? 'Unknown operation',
                'metadata' => $metadata,
                'median_ms' => $result['time']['median'] ?? $result['time']['avg'] ?? null,
            ];
        }
    }

    return $index;
}

function reportLabel(array $report): string
{
    $environment = $report['environment'] ?? [];
    $php = $environment['php'] ?? [];
    $gpu = $environment['gpu']['name'] ?? $report['device'] ?? 'unknown GPU';

    return trim(($php['version'] ?? 'unknown PHP') . ' ' . ($php['thread_safety'] ?? 'unknown mode') . ' / ' . $gpu);
}

function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if ($argc !== 4) {
    fwrite(STDERR, "Usage: php compare_reports.php <baseline.json> <candidate.json> <output-prefix>\n");
    exit(2);
}

$baselinePath = $argv[1];
$candidatePath = $argv[2];
$outputPrefix = $argv[3];
$baseline = readReport($baselinePath);
$candidate = readReport($candidatePath);
$baselineIndex = resultIndex($baseline);
$candidateIndex = resultIndex($candidate);
$allKeys = array_unique(array_merge(array_keys($baselineIndex), array_keys($candidateIndex)));
$comparisons = [];
$faster = 0;
$slower = 0;
$unchanged = 0;

foreach ($allKeys as $key) {
    $before = $baselineIndex[$key] ?? null;
    $after = $candidateIndex[$key] ?? null;
    if ($before === null || $after === null) {
        $comparisons[] = [
            'benchmark' => $before['benchmark'] ?? $after['benchmark'],
            'name' => $before['name'] ?? $after['name'],
            'metadata' => $before['metadata'] ?? $after['metadata'],
            'status' => $before === null ? 'candidate-only' : 'baseline-only',
            'baseline_median_ms' => $before['median_ms'] ?? null,
            'candidate_median_ms' => $after['median_ms'] ?? null,
            'change_percent' => null,
            'speedup' => null,
        ];
        continue;
    }

    $baselineMedian = (float)$before['median_ms'];
    $candidateMedian = (float)$after['median_ms'];
    $changePercent = $baselineMedian > 0
        ? (($candidateMedian - $baselineMedian) / $baselineMedian) * 100
        : null;
    $speedup = $candidateMedian > 0 ? $baselineMedian / $candidateMedian : null;
    $status = $changePercent === null ? 'unavailable' : ($changePercent < -0.5 ? 'faster' : ($changePercent > 0.5 ? 'slower' : 'similar'));

    if ($status === 'faster') {
        $faster++;
    } elseif ($status === 'slower') {
        $slower++;
    } else {
        $unchanged++;
    }

    $comparisons[] = [
        'benchmark' => $before['benchmark'],
        'name' => $before['name'],
        'metadata' => $before['metadata'],
        'status' => $status,
        'baseline_median_ms' => $baselineMedian,
        'candidate_median_ms' => $candidateMedian,
        'change_percent' => $changePercent,
        'speedup' => $speedup,
    ];
}

usort($comparisons, static fn(array $a, array $b): int => [$a['benchmark'], $a['name'], json_encode($a['metadata'])] <=> [$b['benchmark'], $b['name'], json_encode($b['metadata'])]);

$comparison = [
    'schema_version' => 1,
    'generated_at' => gmdate(DATE_ATOM),
    'baseline' => [
        'label' => reportLabel($baseline),
        'source' => basename($baselinePath),
        'generated_at' => $baseline['generated_at'] ?? null,
        'environment' => $baseline['environment'] ?? null,
    ],
    'candidate' => [
        'label' => reportLabel($candidate),
        'source' => basename($candidatePath),
        'generated_at' => $candidate['generated_at'] ?? null,
        'environment' => $candidate['environment'] ?? null,
    ],
    'summary' => [
        'matched_cases' => count(array_intersect_key($baselineIndex, $candidateIndex)),
        'faster' => $faster,
        'slower' => $slower,
        'similar_or_unavailable' => $unchanged,
    ],
    'comparisons' => $comparisons,
];

$outputDirectory = dirname($outputPrefix);
if (!is_dir($outputDirectory) && !mkdir($outputDirectory, 0755, true) && !is_dir($outputDirectory)) {
    throw new RuntimeException("Unable to create output directory: {$outputDirectory}");
}

$jsonPath = $outputPrefix . '.json';
$htmlPath = $outputPrefix . '.html';
file_put_contents($jsonPath, json_encode($comparison, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL);

$rows = '';
foreach ($comparisons as $row) {
    $metadata = [];
    foreach ($row['metadata'] as $name => $value) {
        if (is_scalar($value)) {
            $metadata[] = $name . ': ' . (string)$value;
        }
    }
    $details = implode(' | ', $metadata);
    $class = $row['status'];
    $change = $row['change_percent'] === null ? 'n/a' : sprintf('%+.1f%%', $row['change_percent']);
    $speedup = $row['speedup'] === null ? 'n/a' : sprintf('%.3fx', $row['speedup']);
    $baselineTime = $row['baseline_median_ms'] === null ? '--' : sprintf('%.4f', $row['baseline_median_ms']);
    $candidateTime = $row['candidate_median_ms'] === null ? '--' : sprintf('%.4f', $row['candidate_median_ms']);

    $rows .= '<tr class="' . escapeHtml($class) . '">'
        . '<td><strong>' . escapeHtml($row['benchmark']) . '</strong><span class="operation">' . escapeHtml($row['name']) . '</span></td>'
        . '<td>' . escapeHtml($details) . '</td>'
        . '<td class="numeric">' . $baselineTime . ' ms</td>'
        . '<td class="numeric">' . $candidateTime . ' ms</td>'
        . '<td class="numeric change">' . $change . '</td>'
        . '<td class="numeric">' . $speedup . '</td>'
        . '</tr>';
}

$html = '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
    . '<title>NTS vs ZTS benchmark comparison</title><style>'
    . ':root{color-scheme:dark;--bg:#11191d;--panel:#1a262b;--line:#34464d;--text:#e6eff0;--muted:#9aafb2;--accent:#62d3c2;--good:#64d98b;--bad:#ff967d;}'
    .'*{box-sizing:border-box}body{margin:0;background:linear-gradient(145deg,#11191d,#18272d);color:var(--text);font:15px/1.5 system-ui,sans-serif;}'
    .'main{max-width:1280px;margin:0 auto;padding:40px 24px}h1{font-size:32px;margin:0 0 8px}p{margin:0}.eyebrow{color:var(--accent);font-weight:700;text-transform:uppercase;font-size:12px}.meta{color:var(--muted);margin-top:8px}.summary{display:flex;gap:12px;flex-wrap:wrap;margin:28px 0}.summary div{background:var(--panel);border:1px solid var(--line);border-radius:8px;padding:14px 18px;min-width:150px}.summary strong{display:block;font-size:24px}.summary span{color:var(--muted);font-size:12px}.table-wrap{overflow:auto;border:1px solid var(--line);border-radius:8px;background:var(--panel)}table{width:100%;border-collapse:collapse;min-width:900px}th,td{text-align:left;padding:12px 14px;border-bottom:1px solid var(--line)}th{color:var(--accent);font-size:12px;text-transform:uppercase;position:sticky;top:0;background:var(--panel)}td strong,td .operation{display:block}.operation{color:var(--muted);font-size:13px;margin-top:3px}.numeric{text-align:right;font-variant-numeric:tabular-nums}.faster .change{color:var(--good)}.slower .change{color:var(--bad)}.similar .change{color:var(--muted)}.candidate-only,.baseline-only{opacity:.65}footer{color:var(--muted);font-size:13px;margin-top:20px}code{color:var(--accent)}'
    .'</style></head><body><main><p class="eyebrow">PHP GPU Tensors · reproducible comparison</p><h1>'
    . escapeHtml($comparison['baseline']['label']) . ' <span style="color:var(--accent)">vs</span> ' . escapeHtml($comparison['candidate']['label'])
    . '</h1><p class="meta">Baseline: ' . escapeHtml($comparison['baseline']['source']) . ' · Candidate: ' . escapeHtml($comparison['candidate']['source'])
    . ' · Median latency per operation; negative change means the candidate is faster.</p><section class="summary">'
    . '<div><strong>' . count(array_filter($comparisons, static fn(array $row): bool => $row['baseline_median_ms'] !== null && $row['candidate_median_ms'] !== null)) . '</strong><span>matched cases</span></div>'
    . '<div><strong>' . $faster . '</strong><span>candidate faster</span></div><div><strong>' . $slower . '</strong><span>candidate slower</span></div>'
    . '<div><strong>' . $unchanged . '</strong><span>similar / unavailable</span></div></section><div class="table-wrap"><table><thead><tr><th>Benchmark</th><th>Case</th><th class="numeric">Baseline median</th><th class="numeric">Candidate median</th><th class="numeric">Change</th><th class="numeric">Speedup</th></tr></thead><tbody>'
    . $rows . '</tbody></table></div><footer>Timings are specific to these runs and hardware. PHP heap delta, when present in source reports, is not GPU VRAM.</footer></main></body></html>';

file_put_contents($htmlPath, $html);
fwrite(STDOUT, "JSON: {$jsonPath}\nHTML: {$htmlPath}\nMatched cases: " . $comparison['summary']['matched_cases'] . "\n");