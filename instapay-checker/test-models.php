<?php
/**
 * Pollinations Vision Model Benchmark
 * Tests all vision-capable Pollinations models on real Instapay transaction screenshots.
 *
 * Run from CLI:
 *   php test-models.php
 *
 * Output: instapay-vision-benchmark.md (analysis report)
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(3600); // 1 hour for full benchmark

// ─── Config ────────────────────────────────────────────────────────────────

const POLLINATIONS_ENDPOINT = 'https://text.pollinations.ai/openai/chat/completions';

/**
 * All vision-capable models on Pollinations as of 2026-04.
 * Marked paid_only=true are included for completeness — they may return 401/403.
 */
const VISION_MODELS = [
    // Free / Anonymous tier
    ['id' => 'openai',        'desc' => 'OpenAI GPT-5 Mini',                 'paid' => false],
    ['id' => 'openai-fast',   'desc' => 'OpenAI GPT-5 Nano (ultra-fast)',     'paid' => false],
    ['id' => 'gemini-fast',   'desc' => 'Google Gemini 2.5 Flash Lite',       'paid' => false],
    ['id' => 'gemini-search', 'desc' => 'Google Gemini 2.5 Flash + Search',   'paid' => false],
    ['id' => 'claude-fast',   'desc' => 'Anthropic Claude Haiku 4.5',         'paid' => false],
    ['id' => 'qwen-vision',   'desc' => 'Qwen3 VL Plus (vision+reasoning)',   'paid' => false],
    ['id' => 'polly',         'desc' => 'Polly — Pollinations AI Assistant',  'paid' => false],
    // Paid tier (tested but may fail with 401)
    ['id' => 'openai-large',  'desc' => 'OpenAI GPT-5.2 (paid)',              'paid' => true],
    ['id' => 'gemini',        'desc' => 'Google Gemini 3 Flash (paid)',        'paid' => true],
    ['id' => 'claude',        'desc' => 'Anthropic Claude Sonnet 4.6 (paid)', 'paid' => true],
    ['id' => 'kimi',          'desc' => 'Moonshot Kimi K2.5 (paid)',          'paid' => true],
];

const EXTRACT_PROMPT = <<<'PROMPT'
You are an OCR expert specialized in Egyptian Instapay transaction screenshots.
Analyze the image and extract ALL visible transaction fields. Return ONLY a JSON object (no markdown, no explanation):

{
  "amount": "string (e.g. 500.00)",
  "currency": "string (e.g. EGP)",
  "sender_account": "string (instapay address, e.g. name@instapay)",
  "sender_name": "string",
  "receiver_name": "string",
  "receiver_phone": "string (Egyptian mobile, e.g. 01XXXXXXXXX)",
  "reference_number": "string",
  "transaction_date": "string",
  "bank_name": "string",
  "transaction_type": "string",
  "is_fake_suspected": false
}

Return null for any field not visible. Return ONLY the JSON object.
PROMPT;

// ─── Screenshot discovery ───────────────────────────────────────────────────

$screenshotDir = realpath(__DIR__ . '/../screenshots');
$testImages    = [];

if ($screenshotDir && is_dir($screenshotDir)) {
    $files = glob($screenshotDir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
    foreach ($files as $f) {
        $testImages[] = $f;
    }
}

// Also use bundled test image as fallback
$bundledTest = __DIR__ . '/test-receipt.png';
if (file_exists($bundledTest)) {
    array_unshift($testImages, $bundledTest);
}

if (empty($testImages)) {
    die("❌ No test images found. Place screenshots in screenshots/ or test-receipt.png in instapay-checker/\n");
}

echo "📸 Found " . count($testImages) . " test image(s).\n";
echo "🤖 Testing " . count(VISION_MODELS) . " vision models...\n\n";

// ─── Field weight map for scoring ──────────────────────────────────────────

const FIELD_WEIGHTS = [
    'amount'           => 30,  // Most critical
    'reference_number' => 25,  // Critical for duplicate detection
    'receiver_phone'   => 15,
    'transaction_date' => 10,
    'sender_account'   => 10,
    'receiver_name'    => 5,
    'sender_name'      => 5,
];

// ─── Core API call ──────────────────────────────────────────────────────────

function callVisionModel(string $modelId, string $imagePath): array {
    $imageData  = file_get_contents($imagePath);
    if (!$imageData) return ['error' => 'Cannot read image', 'http' => 0, 'time' => 0, 'raw' => ''];

    $base64     = base64_encode($imageData);
    $ext        = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
    $mime       = match($ext) {
        'png'  => 'image/png',
        'webp' => 'image/webp',
        'gif'  => 'image/gif',
        default => 'image/jpeg',
    };

    $body = json_encode([
        'model'       => $modelId,
        'temperature' => 0.1,
        'messages'    => [[
            'role'    => 'user',
            'content' => [
                ['type' => 'text',      'text'      => EXTRACT_PROMPT],
                ['type' => 'image_url', 'image_url' => ['url' => "data:{$mime};base64,{$base64}"]],
            ],
        ]],
    ]);

    $maxRetries = 3;
    $retryCount = 0;
    $backoff    = 2; // seconds

    do {
        $start = microtime(true);
        $ch    = curl_init(POLLINATIONS_ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_TIMEOUT        => 90,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $raw      = curl_exec($ch);
        $http     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);
        $elapsed  = round(microtime(true) - $start, 2);

        if ($http === 429 && $retryCount < $maxRetries) {
            echo " [429-retry-{$retryCount}] ";
            sleep($backoff * ($retryCount + 1));
            $retryCount++;
            continue;
        }
        break;
    } while (true);

    return [
        'raw'    => $raw ?: '',
        'http'   => $http,
        'time'   => $elapsed,
        'error'  => $curlErr ?: null,
    ];
}

// ─── Response parser ────────────────────────────────────────────────────────

function parseModelResponse(string $raw): ?array {
    if (!$raw) return null;

    // Unwrap OpenAI-style envelope
    $envelope = json_decode($raw, true);
    if (is_array($envelope) && isset($envelope['choices'][0]['message']['content'])) {
        $content = $envelope['choices'][0]['message']['content'];
    } else {
        $content = $raw; // raw text fallback
    }

    // Strip markdown fences
    $content = preg_replace('/^```(?:json)?\s*/m', '', $content);
    $content = preg_replace('/```\s*$/m', '', $content);
    $content = trim($content);

    $data = json_decode($content, true);
    return is_array($data) ? $data : null;
}

// ─── Scoring ────────────────────────────────────────────────────────────────

function scoreExtraction(?array $data): array {
    if (!$data) return ['score' => 0, 'filled' => 0, 'total' => count(FIELD_WEIGHTS), 'breakdown' => []];

    $score     = 0;
    $filled    = 0;
    $breakdown = [];

    foreach (FIELD_WEIGHTS as $field => $weight) {
        $val = $data[$field] ?? null;
        $ok  = ($val !== null && $val !== '' && $val !== 'null');
        if ($ok) {
            $score += $weight;
            $filled++;
        }
        $breakdown[$field] = ['value' => $val, 'weight' => $weight, 'ok' => $ok];
    }

    return ['score' => $score, 'filled' => $filled, 'total' => count(FIELD_WEIGHTS), 'breakdown' => $breakdown];
}

// ─── Main benchmark loop ────────────────────────────────────────────────────

$allResults = [];  // [modelId => ['model'=>..., 'images'=>[...]]]

foreach (VISION_MODELS as $model) {
    $modelId   = $model['id'];
    $modelDesc = $model['desc'];
    $isPaid    = $model['paid'];

    echo str_pad("  🔬 {$modelId}", 35) . ($isPaid ? '[paid] ' : '[free]  ') . "→ ";
    flush();

    $imageResults = [];
    $totalScore   = 0;
    $totalTime    = 0;
    $failures     = 0;

    // Test up to 2 images per model for faster initial validation
    $sampled = array_slice($testImages, 0, 2);

    foreach ($sampled as $imgPath) {
        $imgName = basename($imgPath);
        $resp    = callVisionModel($modelId, $imgPath);
        $parsed  = null;
        $scoring = ['score' => 0, 'filled' => 0, 'total' => 7, 'breakdown' => []];
        $status  = 'ok';

        if ($resp['error'] || $resp['http'] === 0) {
            $status = 'curl_error';
            $failures++;
        } elseif ($resp['http'] === 401 || $resp['http'] === 403) {
            $status = 'auth_required';
            $failures++;
        } elseif ($resp['http'] !== 200) {
            $status = "http_{$resp['http']}";
            $failures++;
        } else {
            $parsed  = parseModelResponse($resp['raw']);
            $scoring = scoreExtraction($parsed);
            if (!$parsed) { $status = 'parse_failed'; $failures++; }
        }

        $totalScore += $scoring['score'];
        $totalTime  += $resp['time'];

        $imageResults[] = [
            'image'   => $imgName,
            'status'  => $status,
            'http'    => $resp['http'],
            'time'    => $resp['time'],
            'score'   => $scoring['score'],
            'filled'  => $scoring['filled'],
            'total'   => $scoring['total'],
            'data'    => $parsed,
            'breakdown' => $scoring['breakdown'],
        ];
    }

    $imgCount = count($sampled);
    $avgScore = $imgCount > 0 ? round($totalScore / $imgCount) : 0;
    $avgTime  = $imgCount > 0 ? round($totalTime  / $imgCount, 2) : 0;
    $succRate = $imgCount > 0 ? round((($imgCount - $failures) / $imgCount) * 100) : 0;

    echo "avg_score={$avgScore}% time={$avgTime}s success={$succRate}%\n";
    flush();

    // Small break between models to let the Pollinations queue breathe
    sleep(2);

    $allResults[$modelId] = [
        'model'       => $model,
        'avg_score'   => $avgScore,
        'avg_time'    => $avgTime,
        'success_rate'=> $succRate,
        'failures'    => $failures,
        'images'      => $imageResults,
    ];
}

echo "\n✅ Benchmark complete. Generating report...\n";

// ─── Markdown report ────────────────────────────────────────────────────────

// Sort by avg_score DESC, then avg_time ASC
function allResults_models(array $r, bool $paid): array {
    return array_filter($r, fn($m) => $m['model']['paid'] === $paid);
}

uasort($allResults, fn($a, $b) =>
    $b['avg_score'] <=> $a['avg_score'] ?: $a['avg_time'] <=> $b['avg_time']
);

$now         = date('Y-m-d H:i:s');
$totalImages = count($testImages);
$tested      = count(allResults_models($allResults, false));
$testedP     = count(allResults_models($allResults, true));

$md  = "# 🔬 Pollinations Vision Model Benchmark\n\n";
$md .= "> **Generated:** {$now}  \n";
$md .= "> **Test images:** {$totalImages} real Instapay transaction screenshots  \n";
$md .= "> **Endpoint:** `https://text.pollinations.ai/openai/chat/completions`  \n";
$md .= "> **Prompt language:** Arabic + English (bilingual extraction)\n\n";

// ── Summary Table ──────────────────────────────────────────────────────────
$md .= "---\n\n## 📊 Summary Rankings\n\n";
$md .= "| Rank | Model | Description | Tier | Avg Score | Avg Time | Success |\n";
$md .= "|------|-------|-------------|------|-----------|----------|---------|\n";

$rank = 1;
foreach ($allResults as $id => $r) {
    $tier    = $r['model']['paid'] ? '🔒 Paid' : '🆓 Free';
    $score   = $r['avg_score'];
    $time    = $r['avg_time'];
    $succ    = $r['success_rate'];
    $emoji   = match(true) {
        $rank === 1 => '🥇',
        $rank === 2 => '🥈',
        $rank === 3 => '🥉',
        default     => "#{$rank}",
    };
    $scoreBar = str_repeat('█', (int)($score / 10)) . str_repeat('░', 10 - (int)($score / 10));
    $md .= "| {$emoji} | `{$id}` | {$r['model']['desc']} | {$tier} | **{$score}%** `{$scoreBar}` | {$time}s | {$succ}% |\n";
    $rank++;
}

// ── Per-model detail ─────────────────────────────────────────────────────
$md .= "\n---\n\n## 🔍 Per-Model Detail\n\n";

foreach ($allResults as $id => $r) {
    $tier  = $r['model']['paid'] ? '🔒 Paid tier' : '🆓 Free / Anonymous';
    $succ  = $r['success_rate'];
    $score = $r['avg_score'];
    $time  = $r['avg_time'];
    $fail  = $r['failures'];

    $md .= "### `{$id}` — {$r['model']['desc']}\n\n";
    $md .= "- **Tier:** {$tier}\n";
    $md .= "- **Avg extraction score:** {$score}% (weighted by field criticality)\n";
    $md .= "- **Avg latency:** {$time}s\n";
    $md .= "- **Success rate:** {$succ}%  ({$fail} failure(s) out of " . count($r['images']) . " images)\n\n";

    // Per-image table
    $md .= "| Image | Status | HTTP | Time | Score | Amount | Ref# | Phone | Date |\n";
    $md .= "|-------|--------|------|------|-------|--------|------|-------|------|\n";

    foreach ($r['images'] as $img) {
        $statusIcon = match(true) {
            $img['status'] === 'ok'            => '✅',
            $img['status'] === 'parse_failed'  => '⚠️',
            $img['status'] === 'auth_required' => '🔒',
            default                             => '❌',
        };
        $d       = $img['data'] ?? [];
        $amount  = $d['amount']           ?? '—';
        $ref     = $d['reference_number'] ?? '—';
        $phone   = $d['receiver_phone']   ?? '—';
        $date    = $d['transaction_date'] ?? '—';
        // Truncate long values
        $trunc   = fn($v) => mb_strlen($v) > 20 ? mb_substr($v, 0, 18) . '…' : $v;

        $md .= "| `{$img['image']}` | {$statusIcon} `{$img['status']}` | {$img['http']} | {$img['time']}s | {$img['score']}% | {$trunc($amount)} | {$trunc($ref)} | {$trunc($phone)} | {$trunc($date)} |\n";
    }

    // Field breakdown from last successful image
    foreach ($r['images'] as $img) {
        if ($img['status'] === 'ok' && !empty($img['breakdown'])) {
            $md .= "\n**Field extraction breakdown (sample image: `{$img['image']}`):**\n\n";
            $md .= "| Field | Extracted Value | Weight | Status |\n";
            $md .= "|-------|----------------|--------|--------|\n";
            foreach ($img['breakdown'] as $field => $b) {
                $val  = $b['value'] !== null ? htmlspecialchars_decode((string)$b['value']) : '*(not found)*';
                $icon = $b['ok'] ? '✅' : '❌';
                $md .= "| `{$field}` | {$val} | {$b['weight']}pts | {$icon} |\n";
            }
            break;
        }
    }

    $md .= "\n---\n\n";
}

// ── Insights ──────────────────────────────────────────────────────────────
$md .= "## 💡 Insights & Recommendation\n\n";

$freeModels = array_filter($allResults, fn($r) => !$r['model']['paid']);
$bestFree   = !empty($freeModels) ? array_key_first($freeModels) : null;

// Best score overall
$bestOverall = array_key_first($allResults);
$bestScore   = $allResults[$bestOverall]['avg_score'] ?? 0;

// Fastest with >0% score
$fastest = null;
foreach ($allResults as $id => $r) {
    if ($r['avg_score'] > 0 && ($fastest === null || $r['avg_time'] < $allResults[$fastest]['avg_time'])) {
        $fastest = $id;
    }
}

$md .= "### 🏆 Best Overall Accuracy\n";
$md .= "`{$bestOverall}` — **{$bestScore}% average extraction score**\n\n";

if ($bestFree) {
    $freeScore = $allResults[$bestFree]['avg_score'];
    $md .= "### 🆓 Best Free Model\n";
    $md .= "`{$bestFree}` — **{$freeScore}%** extraction score, no API key required.\n\n";
}

if ($fastest) {
    $fTime = $allResults[$fastest]['avg_time'];
    $md .= "### ⚡ Fastest Model (with results)\n";
    $md .= "`{$fastest}` — **{$fTime}s** average response time.\n\n";
}

$md .= "### 🔧 Current System Configuration\n\n";
$md .= "The system is configured to use:\n\n";
$md .= "```php\n";
$md .= "define('POLLINATIONS_API_URL', 'https://text.pollinations.ai/openai/chat/completions');\n";
$md .= "define('POLLINATIONS_MODEL', 'openai-large');\n";
$md .= "```\n\n";

if ($bestFree && $bestFree !== 'openai-large') {
    $freeScore = $allResults[$bestFree]['avg_score'];
    $md .= "> **Suggestion:** Based on this benchmark, consider switching to `{$bestFree}` if you need the best free-tier performance ({$freeScore}% score).\n";
    $md .= "> To change, edit `instapay-checker/config.php` → `POLLINATIONS_MODEL`.\n\n";
}

$md .= "### ⚠️ Known Limitations\n\n";
$md .= "- Images compressed by WhatsApp may reduce OCR accuracy for small text\n";
$md .= "- Arabic date formats vary across banks — `transaction_date` extraction is less reliable\n";
$md .= "- `reference_number` extraction depends on whether the screenshot shows the full number\n";
$md .= "- `paid_only` models will return `auth_required` errors when called without a paid token\n\n";

$md .= "---\n\n";
$md .= "*Report generated by `instapay-checker/test-models.php` on {$now}*\n";

// ─── Write report ──────────────────────────────────────────────────────────
$reportPath = __DIR__ . '/instapay-vision-benchmark.md';
file_put_contents($reportPath, $md);

// Also save raw JSON
$jsonPath = __DIR__ . '/benchmark_results.json';
file_put_contents($jsonPath, json_encode($allResults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "📄 Report saved → instapay-checker/instapay-vision-benchmark.md\n";
echo "📦 Raw data  → instapay-checker/benchmark_results.json\n";
echo "\n🏆 Rankings:\n";
$rank = 1;
foreach ($allResults as $id => $r) {
    $paid = $r['model']['paid'] ? '[paid]' : '[free]';
    echo "  {$rank}. {$id} {$paid} — score={$r['avg_score']}% time={$r['avg_time']}s\n";
    $rank++;
}
