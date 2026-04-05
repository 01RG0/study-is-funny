<?php
/**
 * Transaction Management Dashboard
 * View, search, filter, and manage all stored transactions
 */

require_once 'db.php';

// Initialize database
initializeDatabase();

// Get current page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 25;
$offset = ($page - 1) * $perPage;

// Get filter parameters
$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'all';
$filterType = isset($_GET['type']) ? $_GET['type'] : 'all';
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get transactions (for now, get all)
$transactions = getAllTransactionsFromDB($perPage, $offset);
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
        <a href="index.html" class="back-link">← العودة إلى الفحص</a>

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

        <div class="filters">
            <input type="text" id="searchInput" placeholder="ابحث برقم مرجع أو حساب...">
            <button onclick="search()">بحث</button>
            <a href="manage.php" style="text-decoration: none;">
                <button type="button" style="background: #999;">مسح الفلاتر</button>
            </a>
        </div>

        <div class="table-container">
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
                            <th>المرسل</th>
                            <th>الرقم المرجع</th>
                            <th>الحالة</th>
                            <th>التاريخ</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $trans): ?>
                            <tr>
                                <td><?php echo $trans['id']; ?></td>
                                <td class="amount"><?php echo number_format($trans['amount'], 2); ?> <?php echo $trans['currency']; ?></td>
                                <td><?php echo htmlspecialchars($trans['sender_account']); ?></td>
                                <td><?php echo htmlspecialchars($trans['reference_number']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $trans['is_valid']; ?>">
                                        <?php 
                                            $labels = [
                                                'valid' => '✓ صحيحة',
                                                'suspicious' => '⚠ مريبة',
                                                'invalid' => '✕ مزيفة'
                                            ];
                                            echo $labels[$trans['is_valid']] ?? $trans['is_valid'];
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($trans['created_at'])); ?></td>
                                <td>
                                    <button class="action-btn" onclick="viewDetails(<?php echo $trans['id']; ?>)">عرض</button>
                                    <button class="action-btn" onclick="deleteTransaction(<?php echo $trans['id']; ?>)" style="background: #e53935;">حذف</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

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
            // Create a simple modal or redirect to details page
            alert('تفاصيل المعاملة #' + id + ' - قريباً');
        }

        function deleteTransaction(id) {
            if (confirm('هل أنت متأكد من حذف هذه المعاملة؟')) {
                alert('حذف المعاملة #' + id + ' - قريباً');
            }
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
