<?php
/**
 * Transaction Management Dashboard
 * View, search, filter, and manage all stored transactions
 */

require_once 'db.php';

// Initialize database
initializeDatabase();

// Get view mode
$viewMode = isset($_GET['view_mode']) ? $_GET['view_mode'] : 'transactions';

// Handle delete action via POST
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    header('Content-Type: application/json');
    require_once 'db.php';
    $pdo = TransactionDatabase::getConnection();
    if ($pdo) {
        $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = :id");
        $ok = $stmt->execute([':id' => intval($_POST['id'])]);
        echo json_encode(['success' => $ok]);
    } else {
        echo json_encode(['success' => false, 'message' => 'DB error']);
    }
    exit;
}

// Get current page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 25;
$offset = ($page - 1) * $perPage;

// Get filter parameters
$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'all';
$filterType = isset($_GET['type']) ? $_GET['type'] : 'all';
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get data based on view mode
if ($viewMode === 'fraud') {
    $fraudAttempts = getRecentFraudAttempts(50);
    $transactions = [];
    $pendingCount = 0;
} elseif ($viewMode === 'analytics') {
    $analytics = getTransactionAnalytics();
    $transactions = [];
    $fraudAttempts = [];
    $pendingCount = 0;
} elseif ($viewMode === 'pending') {
    // Get pending approval transactions
    $criteria = ['status' => 'pending_approval'];
    $transactions = searchTransactionsInDB($criteria, $perPage);
    $fraudAttempts = [];
    $pendingCount = count($transactions);
} elseif ($searchTerm) {
    // Use search function when search term is provided
    $criteria = [
        'reference' => $searchTerm,
        'sender' => $searchTerm,
        'receiver' => $searchTerm
    ];
    $transactions = searchTransactionsInDB($criteria, $perPage);
    $fraudAttempts = [];
    $pendingCount = 0;
} else {
    $transactions = getAllTransactionsFromDB($perPage, $offset);
    $fraudAttempts = [];
    $pendingCount = 0;
}

// Get pending count for badge
$db = TransactionDatabase::getConnection();
if ($db) {
    try {
        $pendingCount = $db->count('instapay_transactions', ['status' => ['$in' => ['pending_approval', 'flagged']]]);
    } catch (Exception $e) {
        $pendingCount = 0;
    }
}

$stats = getStatistics();

// Handle data export
if (isset($_GET['export'])) {
    exportTransactions($_GET['export']);
}

function exportTransactions($format) {
    $transactions = getAllTransactionsFromDB(1000, 0);
    
    if ($format === 'json') {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="transactions_' . date('Y-m-d_H-i-s') . '.json"');
        echo json_encode($transactions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } elseif ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="transactions_' . date('Y-m-d_H-i-s') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
        
        // Headers
        $headers = ['ID', 'المبلغ', 'المرسل', 'الرقم المرجع', 'الحالة', 'التاريخ'];
        fputcsv($output, $headers);
        
        // Data
        foreach ($transactions as $trans) {
            fputcsv($output, [
                $trans['id'],
                $trans['amount'] . ' ' . $trans['currency'],
                $trans['sender_account'],
                $trans['reference_number'],
                $trans['is_valid'],
                $trans['created_at']
            ]);
        }
        
        fclose($output);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المعاملات - Instapay Checker</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 2em;
        }

        .header .controls {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary {
            background: white;
            color: #667eea;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 2.5em;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #666;
            font-size: 0.95em;
        }

        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filters input,
        .filters select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: inherit;
            font-size: 0.95em;
        }

        .filters input[type="text"] {
            flex: 1;
            min-width: 200px;
        }

        .filters button {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }

        .filters button:hover {
            background: #5568d3;
        }

        .table-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f9f9f9;
            border-bottom: 2px solid #ddd;
        }

        th {
            padding: 15px;
            text-align: right;
            font-weight: 600;
            color: #333;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            color: #666;
        }

        tr:hover {
            background: #f9f9f9;
        }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .badge-valid {
            background: #c8e6c9;
            color: #1b5e20;
        }

        .badge-suspicious {
            background: #ffe0b2;
            color: #e65100;
        }

        .badge-invalid {
            background: #ffcdd2;
            color: #c62828;
        }

        .amount {
            font-weight: 600;
            color: #2e7d32;
        }

        .empty {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            padding: 20px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            color: #667eea;
        }

        .pagination a:hover {
            background: #667eea;
            color: white;
        }

        .pagination span {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .action-btn {
            padding: 5px 10px;
            margin: 0 5px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85em;
        }

        .action-btn:hover {
            background: #5568d3;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">← العودة إلى الفحص</a>

        <div class="header">
            <div>
                <h1>لوحة التحكم</h1>
                <p>إدارة جميع معاملات إنستاباي</p>
            </div>
            <div class="controls">
                <button class="btn btn-primary" onclick="exportData('json')">تحميل JSON</button>
                <button class="btn btn-primary" onclick="exportData('csv')">تحميل CSV</button>
            </div>
        </div>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">إجمالي المعاملات</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['valid']; ?></div>
                <div class="stat-label">معاملات صحيحة</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['suspicious']; ?></div>
                <div class="stat-label">معاملات مريبة</div>
            </div>
        </div>

        <div class="filters" style="padding: 0; background: transparent; box-shadow: none; margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="?view_mode=transactions" class="btn <?php echo $viewMode === 'transactions' ? 'btn-primary' : ''; ?>" style="text-decoration: none; border: 1px solid #667eea; padding: 10px 25px; <?php echo $viewMode === 'transactions' ? 'background: #667eea; color: white;' : 'background: white; color: #667eea'; ?>">المعاملات</a>
            <a href="?view_mode=pending" class="btn <?php echo $viewMode === 'pending' ? 'btn-primary' : ''; ?>" style="text-decoration: none; border: 1px solid #ff9800; padding: 10px 25px; position: relative; <?php echo $viewMode === 'pending' ? 'background: #ff9800; color: white;' : 'background: white; color: #ff9800'; ?>">
                بانتظار الموافقة
                <?php if ($pendingCount > 0): ?>
                    <span style="position: absolute; top: -8px; right: -8px; background: #f44336; color: white; border-radius: 50%; width: 22px; height: 22px; font-size: 12px; display: flex; align-items: center; justify-content: center;"><?php echo $pendingCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="?view_mode=analytics" class="btn <?php echo $viewMode === 'analytics' ? 'btn-primary' : ''; ?>" style="text-decoration: none; border: 1px solid #4caf50; padding: 10px 25px; <?php echo $viewMode === 'analytics' ? 'background: #4caf50; color: white;' : 'background: white; color: #4caf50'; ?>">التحليلات</a>
            <a href="?view_mode=fraud" class="btn <?php echo $viewMode === 'fraud' ? 'btn-primary' : ''; ?>" style="text-decoration: none; border: 1px solid #f44336; padding: 10px 25px; <?php echo $viewMode === 'fraud' ? 'background: #f44336; color: white;' : 'background: white; color: #f44336'; ?>">سجل الاحتيال</a>
        </div>

        <?php if ($viewMode === 'transactions' || $searchTerm): ?>
        <div class="filters">
            <input type="text" id="searchInput" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="ابحث برقم مرجع أو حساب...">
            <button onclick="search()">بحث</button>
            <a href="manage.php?view_mode=transactions" style="text-decoration: none;">
                <button type="button" style="background: #999;">مسح الفلاتر</button>
            </a>
        </div>
        <?php endif; ?>

        <div class="table-container">
            <?php if ($viewMode === 'analytics'): ?>
                <div style="padding: 20px;">
                    <h3 style="margin-bottom: 20px;">تحليلات المعاملات</h3>
                    <?php if (!empty($analytics)): ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px;">
                            <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <div class="stat-number" style="color: white;"><?php echo count($analytics['by_status']); ?></div>
                                <div class="stat-label" style="color: rgba(255,255,255,0.9);">حالات مختلفة</div>
                            </div>
                            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                <div class="stat-number" style="color: white;"><?php echo count($analytics['top_senders']); ?></div>
                                <div class="stat-label" style="color: rgba(255,255,255,0.9);">أكثر المرسلين</div>
                            </div>
                        </div>
                        <?php if (!empty($analytics['by_date'])): ?>
                            <h4 style="margin: 20px 0 10px;">المعاملات حسب اليوم</h4>
                            <table style="margin-bottom: 30px;">
                                <thead>
                                    <tr><th>التاريخ</th><th>العدد</th><th>المتوسط</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($analytics['by_date'] as $day): ?>
                                        <tr>
                                            <td><?php echo $day['_id']; ?></td>
                                            <td><?php echo $day['count']; ?></td>
                                            <td><?php echo number_format($day['avgAmount'] ?? 0, 2); ?> EGP</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                        <?php if (!empty($analytics['top_senders'])): ?>
                            <h4 style="margin: 20px 0 10px;">أكثر المرسلين نشاطاً</h4>
                            <table>
                                <thead>
                                    <tr><th>الحساب</th><th>عدد المعاملات</th><th>إجمالي المبالغ</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($analytics['top_senders'] as $sender): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($sender['_id'] ?? 'Unknown'); ?></td>
                                            <td><?php echo $sender['count']; ?></td>
                                            <td><?php echo number_format($sender['totalAmount'] ?? 0, 2); ?> EGP</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="no-data"><p>لا توجد بيانات تحليلية متاحة</p></div>
                    <?php endif; ?>
                </div>
            <?php elseif ($viewMode === 'fraud'): ?>
                <?php if (empty($fraudAttempts)): ?>
                    <div class="no-data">
                        <p>لا توجد محاولات احتيال مسجلة</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>المعرف</th>
                                <th>الرقم المرجع</th>
                                <th>السبب</th>
                                <th>IP Address</th>
                                <th>التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fraudAttempts as $attempt): ?>
                                <tr>
                                    <td><?php echo $attempt['id']; ?></td>
                                    <td><code><?php echo htmlspecialchars($attempt['reference_number']); ?></code></td>
                                    <td><span style="color: #d32f2f;"><?php echo htmlspecialchars($attempt['reason']); ?></span></td>
                                    <td><code><?php echo htmlspecialchars($attempt['ip_address']); ?></code></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($attempt['attempt_date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php else: ?>
            <?php if (empty($transactions)): ?>
                <div class="no-data">
                    <p>لا توجد معاملات محفوظة بعد</p>
                    <p style="font-size: 0.9em; margin-top: 10px;">ابدأ برفع لقطات شاشة وحفظ المعاملات</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>المعرف</th>
                            <th>المبلغ</th>
                            <th>المرسل / المستلم</th>
                            <th>الرقم المرجعي</th>
                            <th>حالة التحقق</th>
                            <th>المستخدم</th>
                            <th>تاريخ التقديم</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $trans): 
                            $isPending = ($trans['status'] ?? '') === 'pending_approval';
                            $isFlagged = ($trans['status'] ?? '') === 'flagged';
                            $validationIssues = $trans['validation_issues'] ?? [];
                            $validationWarnings = $trans['validation_warnings'] ?? [];
                            $submittedBy = $trans['submitted_by'] ?? ['username' => 'unknown', 'ip' => 'unknown'];
                        ?>
                            <tr data-id="<?php echo $trans['_id'] ?? $trans['id']; ?>" style="<?php echo $isFlagged ? 'background: #fff3e0;' : ($isPending ? 'background: #e3f2fd;' : ''); ?>">
                                <td><code><?php echo substr($trans['_id'] ?? $trans['id'], -8); ?></code></td>
                                <td class="amount" style="font-size: 1.1em;">
                                    <?php echo number_format($trans['amount'] ?? 0, 2); ?> EGP
                                    <?php if (($trans['amount'] ?? 0) < 50 || ($trans['amount'] ?? 0) > 1000): ?>
                                        <span style="color: #f44336; font-size: 0.8em;">⚠ خارج الحدود</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div><strong>من:</strong> <?php echo htmlspecialchars($trans['sender_account'] ?? 'N/A'); ?></div>
                                    <div><strong>إلى:</strong> <?php echo htmlspecialchars($trans['receiver_name'] ?? 'N/A'); ?></div>
                                    <?php if (!empty($trans['receiver_phone'])): ?>
                                        <div style="font-size: 0.85em; color: #666;"><?php echo htmlspecialchars($trans['receiver_phone']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code><?php echo htmlspecialchars($trans['reference_number'] ?? 'N/A'); ?></code>
                                    <?php if (!empty($trans['is_duplicate'])): ?>
                                        <div style="color: #f44336; font-size: 0.8em;">⚠ مكرر!</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $trans['is_valid'] ?? 'unknown'; ?>">
                                        <?php 
                                            $labels = ['valid' => '✓ صحيحة', 'suspicious' => '⚠ مريبة', 'invalid' => '✕ مزيفة', 'unknown' => '؟ غير معروف'];
                                            echo $labels[$trans['is_valid'] ?? 'unknown'] ?? $trans['is_valid'];
                                        ?>
                                    </span>
                                    <?php if (!empty($validationIssues)): ?>
                                        <div style="margin-top: 5px; font-size: 0.75em; color: #f44336;">
                                            <?php echo count($validationIssues); ?> خطأ
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($validationWarnings)): ?>
                                        <div style="font-size: 0.75em; color: #ff9800;">
                                            <?php echo count($validationWarnings); ?> تحذير
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-size: 0.85em;">
                                        <div><strong><?php echo htmlspecialchars($submittedBy['username']); ?></strong></div>
                                        <div style="color: #666; font-size: 0.8em;">IP: <?php echo htmlspecialchars($submittedBy['ip'] ?? 'unknown'); ?></div>
                                    </div>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($trans['created_at'])); ?></td>
                                <td>
                                    <?php if ($isPending || $isFlagged): ?>
                                        <button class="action-btn" onclick="approveTransaction('<?php echo $trans['_id'] ?? $trans['id']; ?>')" style="background: #4caf50;">✓ موافقة</button>
                                        <button class="action-btn" onclick="rejectTransaction('<?php echo $trans['_id'] ?? $trans['id']; ?>')" style="background: #f44336;">✕ رفض</button>
                                    <?php endif; ?>
                                    <button class="action-btn" onclick="viewDetails('<?php echo $trans['_id'] ?? $trans['id']; ?>')">عرض</button>
                                    <button class="action-btn" onclick="deleteTransaction('<?php echo $trans['_id'] ?? $trans['id']; ?>')" style="background: #999;">حذف</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            <?php endif; ?>

            <?php if ($viewMode === 'transactions'): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=1">أول</a>
                        <a href="?page=<?php echo $page - 1; ?>">السابق</a>
                    <?php endif; ?>
                    <span><?php echo "صفحة $page"; ?></span>
                    <a href="?page=<?php echo $page + 1; ?>">التالي</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function search() {
            const term = document.getElementById('searchInput').value.trim();
            if (term) {
                window.location.href = '?search=' + encodeURIComponent(term);
            }
        }

        function exportData(format) {
            window.location.href = '?export=' + format;
        }

        function viewDetails(id) {
            window.location.href = '?view=' + id;
        }

        function deleteTransaction(id) {
            if (!confirm('هل أنت متأكد من حذف المعاملة #' + id + '؟')) return;
            fetch('manage.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=delete&id=' + id
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.querySelector('tr[data-id="' + id + '"]')?.remove();
                    alert('تم حذف المعاملة بنجاح');
                    location.reload();
                } else {
                    alert('فشل حذف المعاملة');
                }
            })
            .catch(() => alert('خطأ في الاتصال'));
        }

        function approveTransaction(id) {
            if (!confirm('هل أنت متأكد من الموافقة على المعاملة #' + id + '؟')) return;
            fetch('api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=approve&id=' + id + '&notes=Approved via admin dashboard'
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('تمت الموافقة على المعاملة بنجاح');
                    location.reload();
                } else {
                    alert('فشل الموافقة: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(() => alert('خطأ في الاتصال'));
        }

        function rejectTransaction(id) {
            const reason = prompt('سبب الرفض (اختياري):');
            if (reason === null) return; // Cancelled
            
            if (!confirm('هل أنت متأكد من رفض المعاملة #' + id + '؟')) return;
            
            fetch('api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=reject&id=' + id + '&reason=' + encodeURIComponent(reason || 'manual_rejection') + '&notes=Rejected via admin dashboard'
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('تم رفض المعاملة');
                    location.reload();
                } else {
                    alert('فشل الرفض: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(() => alert('خطأ في الاتصال'));
        }

        // Auto-search on Enter key
        document.getElementById('searchInput')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                search();
            }
        });
    </script>
</body>
</html>
