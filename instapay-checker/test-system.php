<?php
/**
 * System Verification & Test Script
 * Checks all requirements for Instapay Validator with Gemini integration
 */

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فحص النظام - Instapay Validator</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Cairo', Arial; background: #f5f5f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; margin-bottom: 30px; }
        .header h1 { font-size: 2em; margin-bottom: 10px; }
        .check { background: white; padding: 20px; margin-bottom: 15px; border-radius: 10px; border-right: 4px solid #999; }
        .check.success { border-right-color: #4caf50; }
        .check.error { border-right-color: #f44336; }
        .check-title { font-weight: 600; font-size: 1.1em; margin-bottom: 10px; }
        .check-status { display: flex; align-items: center; gap: 10px; }
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.85em; font-weight: 600; }
        .status-success { background: #c8e6c9; color: #1b5e20; }
        .status-error { background: #ffcdd2; color: #c62828; }
        .check-details { margin-top: 10px; font-size: 0.9em; color: #666; }
        .code { background: #f5f5f5; padding: 10px; border-radius: 5px; font-family: monospace; overflow-x: auto; margin-top: 10px; }
        .summary { background: white; padding: 20px; border-radius: 10px; margin-top: 30px; }
        .summary h2 { margin-bottom: 15px; }
        .summary-item { padding: 10px; margin-bottom: 10px; border-radius: 5px; }
        .summary-item.pass { background: #c8e6c9; color: #1b5e20; }
        .summary-item.fail { background: #ffcdd2; color: #c62828; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>فحص جاهزية النظام</h1>
            <p>التحقق من متطلبات Instapay Validator مع Gemini Vision API</p>
        </div>

        <?php
        $checks = [];
        $errors = [];

        // Check 1: PHP Version
        $phpVersion = phpversion();
        $check1 = version_compare($phpVersion, '7.4.0', '>=');
        $checks['php_version'] = $check1;
        ?>

        <div class="check <?php echo $check1 ? 'success' : 'error'; ?>">
            <div class="check-title">1. إصدار PHP</div>
            <div class="check-status">
                <span class="status-badge <?php echo $check1 ? 'status-success' : 'status-error'; ?>">
                    <?php echo $check1 ? '✓ صحيح' : '✗ خطأ'; ?>
                </span>
                <span><?php echo $phpVersion; ?></span>
            </div>
            <div class="check-details">
                مطلوب: PHP 7.4 أو أحدث
            </div>
        </div>

        <?php
        // Check 2: cURL Extension
        $curlEnabled = extension_loaded('curl');
        $checks['curl'] = $curlEnabled;
        ?>

        <div class="check <?php echo $curlEnabled ? 'success' : 'error'; ?>">
            <div class="check-title">2. مكتبة cURL</div>
            <div class="check-status">
                <span class="status-badge <?php echo $curlEnabled ? 'status-success' : 'status-error'; ?>">
                    <?php echo $curlEnabled ? '✓ مفعلة' : '✗ معطلة'; ?>
                </span>
            </div>
            <div class="check-details">
                مطلوبة للاتصال بـ Gemini API
                <?php if (!$curlEnabled) {
                    echo '<br>⚠ <strong>الحل:</strong> فعّل مكتبة cURL في php.ini';
                    $errors[] = 'cURL غير مفعل';
                } ?>
            </div>
        </div>

        <?php
        // Check 3: JSON Extension
        $jsonEnabled = extension_loaded('json');
        $checks['json'] = $jsonEnabled;
        ?>

        <div class="check <?php echo $jsonEnabled ? 'success' : 'error'; ?>">
            <div class="check-title">3. مكتبة JSON</div>
            <div class="check-status">
                <span class="status-badge <?php echo $jsonEnabled ? 'status-success' : 'status-error'; ?>">
                    <?php echo $jsonEnabled ? '✓ مفعلة' : '✗ معطلة'; ?>
                </span>
            </div>
            <div class="check-details">
                مطلوبة لمعالجة استجابات Gemini API
            </div>
        </div>

        <?php
        // Check 4: SQLite Extension
        $sqliteEnabled = extension_loaded('sqlite3') || extension_loaded('pdo_sqlite');
        $checks['sqlite'] = $sqliteEnabled;
        ?>

        <div class="check <?php echo $sqliteEnabled ? 'success' : 'error'; ?>">
            <div class="check-title">4. قاعدة بيانات SQLite</div>
            <div class="check-status">
                <span class="status-badge <?php echo $sqliteEnabled ? 'status-success' : 'status-error'; ?>">
                    <?php echo $sqliteEnabled ? '✓ متوفرة' : '✗ غير متوفرة'; ?>
                </span>
            </div>
            <div class="check-details">
                لتخزين بيانات المعاملات
                <?php if (!$sqliteEnabled) {
                    echo '<br>⚠ <strong>الحل:</strong> فعّل PDO_SQLite في php.ini';
                    $errors[] = 'SQLite غير متوفر';
                } ?>
            </div>
        </div>

        <?php
        // Check 5: GD Extension (optional)
        $gdEnabled = extension_loaded('gd');
        $checks['gd'] = $gdEnabled;
        ?>

        <div class="check <?php echo $gdEnabled ? 'success' : 'error'; ?>">
            <div class="check-title">5. مكتبة GD للصور (اختياري)</div>
            <div class="check-status">
                <span class="status-badge <?php echo $gdEnabled ? 'status-success' : 'status-error'; ?>">
                    <?php echo $gdEnabled ? '✓ متوفرة' : '⚠ غير متوفرة'; ?>
                </span>
            </div>
            <div class="check-details">
                لمعالجة متقدمة للصور (اختياري)
            </div>
        </div>

        <?php
        // Check 6: Writable Uploads Directory
        $uploadsDir = __DIR__ . '/uploads';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }
        $uploadsWritable = is_writable($uploadsDir);
        $checks['uploads_writable'] = $uploadsWritable;
        ?>

        <div class="check <?php echo $uploadsWritable ? 'success' : 'error'; ?>">
            <div class="check-title">6. مجلد التحميل</div>
            <div class="check-status">
                <span class="status-badge <?php echo $uploadsWritable ? 'status-success' : 'status-error'; ?>">
                    <?php echo $uploadsWritable ? '✓ قابل للكتابة' : '✗ غير قابل'; ?>
                </span>
            </div>
            <div class="check-details">
                المسار: <code><?php echo $uploadsDir; ?></code>
                <?php if (!$uploadsWritable) {
                    echo '<br>⚠ <strong>الحل:</strong> غيّر صلاحيات المجلد: chmod 755 uploads';
                    $errors[] = 'مجلد uploads غير قابل للكتابة';
                } ?>
            </div>
        </div>

        <?php
        // Check 7: Config File
        $configFile = __DIR__ . '/config.php';
        $configExists = file_exists($configFile);
        $checks['config_exists'] = $configExists;
        ?>

        <div class="check <?php echo $configExists ? 'success' : 'error'; ?>">
            <div class="check-title">7. ملف الإعدادات</div>
            <div class="check-status">
                <span class="status-badge <?php echo $configExists ? 'status-success' : 'status-error'; ?>">
                    <?php echo $configExists ? '✓ موجود' : '✗ مفقود'; ?>
                </span>
            </div>
            <div class="check-details">
                الملف: <code>config.php</code>
                <?php if (!$configExists) {
                    echo '<br>⚠ <strong>الحل:</strong> تأكد من وجود ملف config.php';
                    $errors[] = 'ملف config.php مفقود';
                } ?>
            </div>
        </div>

        <?php
        // Check 8: Gemini API Key
        if ($configExists) {
            include_once 'config.php';
            $apiKeyExists = defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY);
            $checks['api_key'] = $apiKeyExists;
            
            if ($apiKeyExists) {
                // Test API connection
                $testConnection = testGeminiConnection();
                $checks['api_connection'] = $testConnection['success'];
            }
        } else {
            $checks['api_key'] = false;
        }
        ?>

        <div class="check <?php echo ($checks['api_key'] ?? false) ? 'success' : 'error'; ?>">
            <div class="check-title">8. مفتاح Gemini API</div>
            <div class="check-status">
                <span class="status-badge <?php echo ($checks['api_key'] ?? false) ? 'status-success' : 'status-error'; ?>">
                    <?php echo ($checks['api_key'] ?? false) ? '✓ موجود' : '✗ مفقود'; ?>
                </span>
            </div>
            <div class="check-details">
                تم التحقق من المفتاح
                <?php if (($checks['api_key'] ?? false)) {
                    echo '<br>✓ API Key مُعرّف في config.php';
                } else {
                    echo '<br>⚠ <strong>الحل:</strong> أضف API Key إلى config.php';
                    $errors[] = 'مفتاح Gemini API مفقود';
                } ?>
            </div>
        </div>

        <?php
        if (($checks['api_key'] ?? false)) {
        ?>
        <div class="check <?php echo ($checks['api_connection'] ?? false) ? 'success' : 'error'; ?>">
            <div class="check-title">9. اختبار اتصال Gemini API</div>
            <div class="check-status">
                <span class="status-badge <?php echo ($checks['api_connection'] ?? false) ? 'status-success' : 'status-error'; ?>">
                    <?php echo ($checks['api_connection'] ?? false) ? '✓ متصل' : '✗ فشل'; ?>
                </span>
            </div>
            <div class="check-details">
                <?php 
                if ($checks['api_connection'] ?? false) {
                    echo '✓ تم الاتصال بـ Gemini API بنجاح';
                } else {
                    echo '✗ فشل الاتصال بـ Gemini API<br>';
                    echo '<strong>التفاصيل:</strong> ' . htmlspecialchars($testConnection['error'] ?? 'خطأ غير معروف');
                    $errors[] = 'فشل الاتصال بـ Gemini API: ' . ($testConnection['error'] ?? 'Unknown error');
                }
                ?>
            </div>
        </div>
        <?php } ?>

        <div class="summary">
            <h2>ملخص الفحص</h2>
            <?php
            $totalChecks = count($checks);
            $passedChecks = array_sum($checks);
            $passPercentage = ($passedChecks / $totalChecks) * 100;
            ?>
            
            <div class="summary-item <?php echo ($passPercentage == 100) ? 'pass' : 'fail'; ?>">
                <strong>النسبة الإجمالية:</strong> <?php echo number_format($passPercentage, 1); ?>% 
                (<?php echo $passedChecks; ?>/<?php echo $totalChecks; ?> فحوصات)
            </div>

            <?php if (empty($errors)): ?>
                <div class="summary-item pass">
                    <strong>✓ النتيجة:</strong> النظام جاهز تماماً للعمل!
                </div>
                <div class="check-details" style="margin-top: 15px;">
                    <strong>الخطوات التالية:</strong>
                    <ol style="margin: 10px 0; margin-right: 20px;">
                        <li>افتح <code>index.html</code> في المتصفح</li>
                        <li>جرب رفع لقطة شاشة إنستاباي</li>
                        <li>تحقق من استخراج البيانات</li>
                        <li>احفظ المعاملة في قاعدة البيانات</li>
                    </ol>
                </div>
            <?php else: ?>
                <div class="summary-item fail">
                    <strong>✗ المشاكل المكتشفة: <?php echo count($errors); ?></strong>
                </div>
                <div class="check-details" style="margin-top: 15px;">
                    <strong>الأخطاء:</strong>
                    <ul style="margin: 10px 0; margin-right: 20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <div style="text-align: center; margin-top: 30px; padding: 20px;">
            <a href="index.html" style="display: inline-block; background: #667eea; color: white; padding: 12px 30px; border-radius: 5px; text-decoration: none; font-weight: 600;">
                ← العودة إلى الأداة الرئيسية
            </a>
        </div>
    </div>

    <?php
    function testGeminiConnection() {
        if (!defined('GEMINI_API_KEY') || !defined('GEMINI_API_URL')) {
            return ['success' => false, 'error' => 'API configuration not loaded'];
        }

        // Create a simple test request
        $requestBody = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => 'رد بـ "تم" فقط'
                        ]
                    ]
                ]
            ]
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => GEMINI_API_URL . '?key=' . GEMINI_API_KEY,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($requestBody),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json']
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            return ['success' => false, 'error' => $error];
        }

        if ($httpCode === 200) {
            return ['success' => true];
        } else {
            $responseData = json_decode($response, true);
            $errorMsg = $responseData['error']['message'] ?? $response;
            return ['success' => false, 'error' => "HTTP $httpCode: " . substr($errorMsg, 0, 100)];
        }
    }
    ?>
</body>
</html>
