<?php
/**
 * Pollinations AI - Full API Test & Analysis Script
 * Run via CLI: php test-api.php
 * Run via browser for formatted HTML output
 */

$isCLI = php_sapi_name() === 'cli';

// ─── Helpers ────────────────────────────────────────────────────────────────

function h($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function out($text, $type = 'info') {
    global $isCLI;
    if ($isCLI) {
        $colors = ['ok' => "\033[32m", 'fail' => "\033[31m", 'warn' => "\033[33m",
                   'head' => "\033[1;36m", 'info' => "\033[37m", 'dim' => "\033[90m"];
        $reset = "\033[0m";
        echo ($colors[$type] ?? '') . $text . $reset . "\n";
    } else {
        $classes = ['ok' => 'ok', 'fail' => 'fail', 'warn' => 'warn',
                    'head' => 'head', 'info' => 'info', 'dim' => 'dim'];
        echo '<div class="line ' . ($classes[$type] ?? 'info') . '">' . h($text) . '</div>';
        ob_flush(); flush();
    }
}

function sep($char = '─', $len = 60) {
    out(str_repeat($char, $len), 'dim');
}

function httpGet($url, $timeout = 15) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $start    = microtime(true);
    $response = curl_exec($ch);
    $ms       = round((microtime(true) - $start) * 1000);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err      = curl_error($ch);
    curl_close($ch);
    return compact('response', 'code', 'ms', 'err');
}

function httpPost($url, $body, $timeout = 40) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_POSTFIELDS     => json_encode($body),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    ]);
    $start    = microtime(true);
    $response = curl_exec($ch);
    $ms       = round((microtime(true) - $start) * 1000);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err      = curl_error($ch);
    curl_close($ch);
    return compact('response', 'code', 'ms', 'err');
}

function extractChoice($responseJson) {
    $data = json_decode($responseJson, true);
    return $data['choices'][0]['message']['content'] ?? null;
}

// ─── HTML Header (browser only) ──────────────────────────────────────────────

if (!$isCLI) {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
    <title>Pollinations API Test</title>
    <style>
      body{background:#0d1117;color:#e6edf3;font-family:monospace;font-size:14px;padding:20px}
      .line{padding:2px 0;white-space:pre-wrap;word-break:break-all}
      .ok{color:#3fb950}.fail{color:#f85149}.warn{color:#d29922}
      .head{color:#58a6ff;font-weight:bold;font-size:15px}
      .info{color:#e6edf3}.dim{color:#484f58}
    </style></head><body>';
    ob_flush(); flush();
}

// ─── SECTION 1: Endpoint Discovery ───────────────────────────────────────────

out("", 'info');
out("╔══════════════════════════════════════════════════╗", 'head');
out("║   POLLINATIONS AI — FULL API TEST & ANALYSIS     ║", 'head');
out("╚══════════════════════════════════════════════════╝", 'head');
out("Started: " . date('Y-m-d H:i:s'), 'dim');
sep();

$endpoints = [
    'Legacy Text API'  => 'https://text.pollinations.ai',
    'New Auth API'     => 'https://enter.pollinations.ai',
];

out("\n[1] ENDPOINT REACHABILITY", 'head');
sep();

$reachable = [];
foreach ($endpoints as $label => $base) {
    $r = httpGet($base . '/models', 10);
    if ($r['err']) {
        out("  ✗ $label ($base) — CURL Error: " . $r['err'], 'fail');
    } elseif ($r['code'] === 200) {
        out("  ✓ $label — HTTP {$r['code']} ({$r['ms']}ms)", 'ok');
        $reachable[$label] = ['base' => $base, 'models_raw' => $r['response']];
    } else {
        out("  ~ $label — HTTP {$r['code']} ({$r['ms']}ms)", 'warn');
        $reachable[$label] = ['base' => $base, 'models_raw' => $r['response']];
    }
}

// ─── SECTION 2: Model Discovery ──────────────────────────────────────────────

out("\n[2] AVAILABLE MODELS", 'head');
sep();

$visionModels  = [];
$allModels     = [];

foreach ($reachable as $label => $info) {
    $models = json_decode($info['models_raw'], true);
    if (!is_array($models)) {
        out("  Could not parse model list from $label", 'warn');
        out("  Raw: " . substr($info['models_raw'], 0, 200), 'dim');
        continue;
    }

    out("  ── $label ──", 'info');
    foreach ($models as $m) {
        $name    = $m['name'] ?? $m['id'] ?? '(unknown)';
        $vision  = !empty($m['vision']) || in_array('image', $m['input_modalities'] ?? []);
        $inputM  = implode(', ', $m['input_modalities'] ?? ['text']);
        $vFlag   = $vision ? ' 👁 VISION' : '';
        $tier    = $m['tier'] ?? '';
        $aliases = implode(', ', $m['aliases'] ?? []);

        $line = "    • $name  [{$inputM}]{$vFlag}";
        if ($tier) $line .= "  tier:$tier";
        if ($aliases) $line .= "  aka:[$aliases]";

        out($line, $vision ? 'ok' : 'info');

        $allModels[] = ['label' => $label, 'base' => $info['base'], 'name' => $name,
                        'vision' => $vision, 'data' => $m];
        if ($vision) {
            $visionModels[] = ['base' => $info['base'], 'name' => $name];
        }
    }
}

if (empty($visionModels)) {
    out("\n  ⚠ No vision-capable models found in model list.", 'warn');
    out("  Will probe known model names manually in Section 4.", 'warn');
}

// ─── SECTION 3: Text API Smoke Tests ─────────────────────────────────────────

out("\n[3] TEXT API SMOKE TESTS", 'head');
sep();

$textTests = [
    ['base' => 'https://text.pollinations.ai', 'model' => 'openai-fast',  'label' => 'Legacy → openai-fast'],
    ['base' => 'https://text.pollinations.ai', 'model' => 'openai-large', 'label' => 'Legacy → openai-large (old)'],
    ['base' => 'https://text.pollinations.ai', 'model' => 'openai',       'label' => 'Legacy → openai (alias)'],
    ['base' => 'https://enter.pollinations.ai','model' => 'openai-large', 'label' => 'New → openai-large'],
    ['base' => 'https://enter.pollinations.ai','model' => 'openai',       'label' => 'New → openai'],
];

$workingTextModel = null;

foreach ($textTests as $t) {
    $body = [
        'model'    => $t['model'],
        'messages' => [['role' => 'user', 'content' => 'Reply with exactly: API_OK']],
        'temperature' => 0.1,
    ];
    $r = httpPost($t['base'] . '/openai/chat/completions', $body, 20);
    $content = extractChoice($r['response']);

    if ($r['err']) {
        out("  ✗ {$t['label']} — CURL: " . $r['err'], 'fail');
    } elseif ($r['code'] === 200 && $content) {
        out("  ✓ {$t['label']} — {$r['ms']}ms — reply: \"" . trim($content) . "\"", 'ok');
        if (!$workingTextModel) {
            $workingTextModel = ['base' => $t['base'], 'model' => $t['model']];
        }
    } else {
        $err = json_decode($r['response'], true)['error'] ?? substr($r['response'], 0, 120);
        out("  ✗ {$t['label']} — HTTP {$r['code']} — " . $err, 'fail');
    }
}

// ─── SECTION 4: Vision API Tests ─────────────────────────────────────────────

out("\n[4] VISION API TESTS (with test-receipt.png)", 'head');
sep();

$testImage = __DIR__ . '/test-receipt.png';
if (!file_exists($testImage)) {
    out("  ⚠ test-receipt.png not found — skipping vision tests", 'warn');
} else {
    $imageData   = base64_encode(file_get_contents($testImage));
    $imageInfo   = getimagesize($testImage);
    $mime        = $imageInfo['mime'] ?? 'image/png';
    $dataUri     = "data:$mime;base64,$imageData";
    $imageSizeKB = round(filesize($testImage) / 1024);

    out("  Image: test-receipt.png ({$imageSizeKB}KB, {$imageInfo[0]}x{$imageInfo[1]})", 'dim');

    $prompt = 'You are an Instapay receipt analyzer. Extract from this image: amount, currency, reference_number, transaction_date, sender_name, receiver_name. Return ONLY valid JSON, no extra text.';

    $visionCandidates = array_merge($visionModels, [
        ['base' => 'https://text.pollinations.ai', 'name' => 'openai-large'],
        ['base' => 'https://text.pollinations.ai', 'name' => 'gpt-4o'],
        ['base' => 'https://text.pollinations.ai', 'name' => 'openai'],
        ['base' => 'https://enter.pollinations.ai','name' => 'openai-large'],
        ['base' => 'https://enter.pollinations.ai','name' => 'gpt-4o'],
    ]);

    // Deduplicate
    $seen = [];
    $uniqueCandidates = [];
    foreach ($visionCandidates as $v) {
        $key = $v['base'] . '|' . $v['name'];
        if (!isset($seen[$key])) { $seen[$key] = true; $uniqueCandidates[] = $v; }
    }

    $workingVisionModel = null;

    foreach ($uniqueCandidates as $v) {
        $label = parse_url($v['base'], PHP_URL_HOST) . ' → ' . $v['name'];
        $body  = [
            'model'    => $v['name'],
            'messages' => [[
                'role'    => 'user',
                'content' => [
                    ['type' => 'text',      'text'      => $prompt],
                    ['type' => 'image_url', 'image_url' => ['url' => $dataUri]],
                ],
            ]],
            'temperature' => 0.1,
        ];

        $r       = httpPost($v['base'] . '/openai/chat/completions', $body, 40);
        $content = extractChoice($r['response']);

        if ($r['err']) {
            out("  ✗ $label — CURL: " . $r['err'], 'fail');
        } elseif ($r['code'] === 200 && $content) {
            // Try to parse JSON
            $cleaned = preg_replace('/```json\s*/', '', $content);
            $cleaned = preg_replace('/```\s*/', '', $cleaned);
            $parsed  = json_decode(trim($cleaned), true);

            if ($parsed) {
                out("  ✓ $label — {$r['ms']}ms — JSON parsed OK", 'ok');
                foreach ($parsed as $k => $val) {
                    out("      $k: $val", 'info');
                }
                if (!$workingVisionModel) {
                    $workingVisionModel = ['base' => $v['base'], 'name' => $v['name']];
                }
            } else {
                out("  ~ $label — {$r['ms']}ms — responded but non-JSON:", 'warn');
                out("      " . substr(trim($content), 0, 200), 'dim');
            }
        } else {
            $errMsg = json_decode($r['response'], true)['error'] ?? substr($r['response'], 0, 150);
            out("  ✗ $label — HTTP {$r['code']} — $errMsg", 'fail');
        }
    }
}

// ─── SECTION 5: Current Config Check ─────────────────────────────────────────

out("\n[5] CURRENT config.php SETTINGS", 'head');
sep();

$configFile = __DIR__ . '/config.php';
if (file_exists($configFile)) {
    $configContent = file_get_contents($configFile);
    preg_match("/define\('POLLINATIONS_API_URL',\s*'([^']+)'\)/", $configContent, $m1);
    preg_match("/define\('POLLINATIONS_MODEL',\s*'([^']+)'\)/", $configContent, $m2);
    $cfgUrl   = $m1[1] ?? '(not found)';
    $cfgModel = $m2[1] ?? '(not found)';
    out("  POLLINATIONS_API_URL : $cfgUrl", 'info');
    out("  POLLINATIONS_MODEL   : $cfgModel", 'info');
} else {
    out("  config.php not found", 'fail');
}

// ─── SECTION 6: Summary & Recommendation ─────────────────────────────────────

out("\n[6] SUMMARY & RECOMMENDATION", 'head');
sep();

if ($workingVisionModel) {
    $needsUpdate = false;
    $cfgUrl   = $cfgUrl ?? '';
    $cfgModel = $cfgModel ?? '';
    $newBase  = $workingVisionModel['base'];
    $newModel = $workingVisionModel['name'];
    $newUrl   = $newBase . '/openai/chat/completions';

    if (strpos($cfgUrl, $newBase) === false || $cfgModel !== $newModel) {
        $needsUpdate = true;
    }

    out("  ✓ Working vision model found:", 'ok');
    out("      Base : $newBase", 'ok');
    out("      Model: $newModel", 'ok');
    out("      URL  : $newUrl", 'ok');

    if ($needsUpdate) {
        out("\n  ⚠ config.php needs update:", 'warn');
        out("      Change POLLINATIONS_API_URL  → $newUrl", 'warn');
        out("      Change POLLINATIONS_MODEL    → $newModel", 'warn');
    } else {
        out("\n  ✓ config.php is already correct — no changes needed!", 'ok');
    }
} else {
    out("  ✗ No working vision model found across all tested endpoints.", 'fail');
    out("  The instapay receipt analysis WILL FAIL until a vision model is available.", 'fail');
    out("\n  Suggested alternatives:", 'warn');
    out("    • Use Google Gemini Free Tier (gemini-1.5-flash) — requires free API key", 'warn');
    out("    • Use OpenRouter.ai free vision models — requires free API key", 'warn');
    out("    • Use Groq (llama-3.2-11b-vision) — requires free API key", 'warn');
}

if ($workingTextModel) {
    out("\n  ✓ Text-only model working: {$workingTextModel['base']} → {$workingTextModel['model']}", 'ok');
} else {
    out("\n  ✗ No text model working either.", 'fail');
}

sep('═');
out("Test completed: " . date('Y-m-d H:i:s'), 'dim');
sep('═');

if (!$isCLI) {
    echo '</body></html>';
}
