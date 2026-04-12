<?php
/**
 * Admin Payment Review Dashboard
 * Tier 2: Review and approve/reject pending transactions
 */

session_start();

// Verify admin access
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login/');
    exit;
}

require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/../api/payment-schema.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مراجعة التحويلات - لوحة الإدارة</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            direction: rtl;
        }

        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-header h1 {
            font-size: 24px;
            margin: 0;
        }

        .admin-header .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e0e0e0;
        }

        .tab-btn {
            padding: 12px 20px;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            border-bottom: 3px solid transparent;
            color: #666;
            transition: all 0.3s;
        }

        .tab-btn.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .tab-content {
            display: none;
        }

        .tab-content.show {
            display: block;
        }

        .transactions-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f9f9f9;
            padding: 15px;
            text-align: right;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
            font-size: 13px;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 13px;
        }

        tr:hover {
            background: #f9f9f9;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .score {
            text-align: center;
            font-weight: 600;
        }

        .score.high {
            color: #4caf50;
        }

        .score.medium {
            color: #ff9800;
        }

        .score.low {
            color: #f44336;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-view {
            background: #e3f2fd;
            color: #1976d2;
        }

        .btn-view:hover {
            background: #1976d2;
            color: white;
        }

        .btn-approve {
            background: #d4edda;
            color: #155724;
        }

        .btn-approve:hover {
            background: #155724;
            color: white;
        }

        .btn-reject {
            background: #f8d7da;
            color: #721c24;
        }

        .btn-reject:hover {
            background: #721c24;
            color: white;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 8px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            padding: 30px;
        }

        .modal-header {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .detail-label {
            font-weight: 600;
            color: #666;
        }

        .detail-value {
            color: #333;
            text-align: left;
        }

        .screenshot-preview {
            width: 100%;
            max-width: 400px;
            border-radius: 4px;
            margin: 20px 0;
            border: 1px solid #e0e0e0;
        }

        .decision-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
        }

        .decision-textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 13px;
            resize: vertical;
            min-height: 100px;
            margin-bottom: 15px;
            font-family: inherit;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .no-data-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .fraud-attempt {
            background: white;
            border-right: 4px solid #f44336;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .fraud-type {
            display: inline-block;
            background: #f44336;
            color: white;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .fraud-details {
            color: #666;
            font-size: 13px;
            margin: 10px 0;
        }

        .search-box {
            margin-bottom: 20px;
        }

        .search-box input {
            width: 100%;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 13px;
        }

        .filter-select {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-select select {
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 13px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <h1><i class="fas fa-tachometer-alt"></i> لوحة إدارة التحويلات</h1>
        <div class="user-info">
            <span><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></span>
            <a href="../logout/" style="color: white; text-decoration: none;">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>

    <div class="container">
        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('pending')">
                <i class="fas fa-clock"></i> التحويلات المعلقة
            </button>
            <button class="tab-btn" onclick="switchTab('fraud')">
                <i class="fas fa-shield-alt"></i> محاولات الاحتيال
            </button>
            <button class="tab-btn" onclick="switchTab('approved')">
                <i class="fas fa-check-circle"></i> الموافقات
            </button>
        </div>

        <!-- Pending Transactions Tab -->
        <div class="tab-content show" id="pending">
            <div class="search-box">
                <input type="text" id="searchPending" placeholder="ابحث عن رقم الطالب أو الهاتف..." onkeyup="filterTransactions()">
            </div>
            
            <div class="transactions-table">
                <table id="pendingTable">
                    <thead>
                        <tr>
                            <th>معرف</th>
                            <th>الطالب</th>
                            <th>رقم الهاتف</th>
                            <th>المبلغ</th>
                            <th>درجة الثقة</th>
                            <th>التاريخ</th>
                            <th>الإجراء</th>
                        </tr>
                    </thead>
                    <tbody id="pendingBody">
                        <tr><td colspan="7" class="no-data">جاري التحميل...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Fraud Attempts Tab -->
        <div class="tab-content" id="fraud">
            <div class="filter-select">
                <select id="fraudTypeFilter" onchange="filterFraudAttempts()">
                    <option value="all">جميع أنواع الاحتيال</option>
                    <option value="duplicate_screenshot">صورة مكررة</option>
                    <option value="screenshot_reuse">إعادة استخدام الصورة</option>
                    <option value="phone_not_registered">الهاتف غير مسجل</option>
                    <option value="fake_detection_high">احتمال تزويز عالي</option>
                    <option value="amount_mismatch">عدم طابقة المبلغ</option>
                </select>
            </div>

            <div id="fraudContainer"></div>
        </div>

        <!-- Approved Transactions Tab -->
        <div class="tab-content" id="approved">
            <div class="search-box">
                <input type="text" id="searchApproved" placeholder="ابحث عن رقم الطالب...">
            </div>

            <div class="transactions-table">
                <table>
                    <thead>
                        <tr>
                            <th>معرف</th>
                            <th>الطالب</th>
                            <th>المبلغ</th>
                            <th>تاريخ الموافقة</th>
                            <th>الموافق</th>
                        </tr>
                    </thead>
                    <tbody id="approvedBody">
                        <tr><td colspan="5" class="no-data">جاري التحميل...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Transaction Review Modal -->
    <div class="modal" id="reviewModal">
        <div class="modal-content">
            <div class="modal-header">
                <span>مراجعة التحويل</span>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>

            <div id="reviewDetails"></div>

            <div class="decision-section">
                <h3 style="margin-bottom: 15px;">قرار الإدارة</h3>
                <textarea id="adminNotes" class="decision-textarea" placeholder="اكتب ملاحظاتك (اختياري)..."></textarea>

                <div style="display: flex; gap: 10px;">
                    <button class="btn btn-approve" onclick="approveTransaction()">
                        <i class="fas fa-check"></i> الموافقة
                    </button>
                    <button class="btn btn-reject" onclick="rejectTransaction()">
                        <i class="fas fa-times"></i> الرفض
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentTransactionId = null;
        let transactions = [];
        let fraudAttempts = [];

        // Load data on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadPendingTransactions();
            loadFraudAttempts();
        });

        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('show'));
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            
            document.getElementById(tabName).classList.add('show');
            event.target.closest('.tab-btn').classList.add('active');
        }

        async function loadPendingTransactions() {
            try {
                const response = await fetch('../api/payment.php?action=get_pending_transactions');
                const result = await response.json();

                if (result.success) {
                    transactions = result.data.transactions;
                    renderPendingTransactions(transactions);
                } else {
                    renderError('failed');
                }
            } catch (error) {
                renderError('pending', 'خطأ في تحميل البيانات: ' + error.message);
            }
        }

        async function loadFraudAttempts() {
            try {
                const response = await fetch('../api/payment.php?action=get_fraud_attempts');
                const result = await response.json();

                if (result.success) {
                    fraudAttempts = result.data.fraud_attempts;
                    renderFraudAttempts(fraudAttempts);
                }
            } catch (error) {
                console.error('Error loading fraud attempts:', error);
            }
        }

        function renderPendingTransactions(data) {
            const tbody = document.getElementById('pendingBody');
            
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="no-data"><div class="no-data-icon"><i class="fas fa-check-circle"></i></div>لا توجد تحويلات معلقة</td></tr>';
                return;
            }

            tbody.innerHTML = data.map(t => `
                <tr>
                    <td>${t.id}</td>
                    <td>${t.student_id}</td>
                    <td>${t.student_phone}</td>
                    <td>${t.instapay_amount || 'N/A'} EGP</td>
                    <td><span class="score ${t.validation_score >= 80 ? 'high' : t.validation_score >= 60 ? 'medium' : 'low'}">${t.validation_score}%</span></td>
                    <td>${new Date(t.submission_date).toLocaleDateString('ar-EG')}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-view" onclick="viewTransaction(${t.id})"><i class="fas fa-eye"></i> عرض</button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        function renderFraudAttempts(data) {
            const container = document.getElementById('fraudContainer');
            
            if (data.length === 0) {
                container.innerHTML = '<div class="no-data"><div class="no-data-icon"><i class="fas fa-shield-alt"></i></div>لا توجد محاولات احتيال</div>';
                return;
            }

            container.innerHTML = data.map(fraud => `
                <div class="fraud-attempt">
                    <div class="fraud-type">${fraud.fraud_type}</div>
                    <div class="fraud-details">
                        <strong>الطالب:</strong> ${fraud.student_name} (${fraud.student_phone})<br>
                        <strong>السبب:</strong> ${fraud.fraud_reason}<br>
                        <strong>درجة الثقة:</strong> ${fraud.confidence_score}%<br>
                        <strong>التاريخ:</strong> ${new Date(fraud.detected_date).toLocaleDateString('ar-EG')}
                    </div>
                    ${fraud.admin_reviewed ? '<p style="color: #4caf50; margin-top: 10px;"><i class="fas fa-check"></i> تمت المراجعة</p>' : '<p style="color: #ff9800; margin-top: 10px;"><i class="fas fa-clock"></i> بانتظار المراجعة</p>'}
                </div>
            `).join('');
        }

        async function viewTransaction(id) {
            const transaction = transactions.find(t => t.id === id);
            if (!transaction) return;

            currentTransactionId = id;

            const detailsHtml = `
                <div class="detail-row">
                    <span class="detail-label">معرف التحويل:</span>
                    <span class="detail-value">${transaction.id}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">رقم الطالب:</span>
                    <span class="detail-value">${transaction.student_id}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">رقم الهاتف:</span>
                    <span class="detail-value">${transaction.student_phone}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">المبلغ المطلوب:</span>
                    <span class="detail-value">${transaction.amount_requested} EGP</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">مبلغ التحويل:</span>
                    <span class="detail-value">${transaction.instapay_amount || 'لم يتم استخراجه'} EGP</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">حساب إنستاباي:</span>
                    <span class="detail-value">${transaction.instapay_sender_account || 'لم يتم استخراجه'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">المستقبل:</span>
                    <span class="detail-value">${transaction.instapay_receiver_name || 'لم يتم استخراجه'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">رقم المرجع:</span>
                    <span class="detail-value">${transaction.instapay_reference_number || 'لم يتم استخراجه'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">درجة الثقة:</span>
                    <span class="detail-value">${transaction.validation_score}% (${transaction.confidence_level})</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">حالة التحويل:</span>
                    <span class="detail-value"><span class="status-badge status-${transaction.status}">${transaction.status}</span></span>
                </div>
                ${transaction.screenshot_path ? `<img src="${transaction.screenshot_path}" alt="Screenshot" class="screenshot-preview">` : ''}
            `;

            document.getElementById('reviewDetails').innerHTML = detailsHtml;
            document.getElementById('reviewModal').classList.add('show');
        }

        async function approveTransaction() {
            if (!currentTransactionId) return;

            const notes = document.getElementById('adminNotes').value;
            const formData = new FormData();
            formData.append('action', 'approve_transaction');
            formData.append('transaction_id', currentTransactionId);
            formData.append('notes', notes);

            try {
                const response = await fetch('../api/payment.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    alert('تمت الموافقة على التحويل');
                    closeModal();
                    loadPendingTransactions();
                } else {
                    alert('خطأ: ' + result.error);
                }
            } catch (error) {
                alert('خطأ في التواصل: ' + error.message);
            }
        }

        async function rejectTransaction() {
            if (!currentTransactionId) return;

            const reason = prompt('أدخل سبب الرفض:');
            if (!reason) return;

            const formData = new FormData();
            formData.append('action', 'reject_transaction');
            formData.append('transaction_id', currentTransactionId);
            formData.append('reason', reason);

            try {
                const response = await fetch('../api/payment.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    alert('تم رفض التحويل');
                    closeModal();
                    loadPendingTransactions();
                } else {
                    alert('خطأ: ' + result.error);
                }
            } catch (error) {
                alert('خطأ في التواصل: ' + error.message);
            }
        }

        function filterTransactions() {
            const searchTerm = document.getElementById('searchPending').value.toLowerCase();
            const filtered = transactions.filter(t => 
                t.student_id.toString().includes(searchTerm) || 
                t.student_phone.includes(searchTerm)
            );
            renderPendingTransactions(filtered);
        }

        function filterFraudAttempts() {
            const filter = document.getElementById('fraudTypeFilter').value;
            const filtered = filter === 'all' ? fraudAttempts : fraudAttempts.filter(f => f.fraud_type === filter);
            renderFraudAttempts(filtered);
        }

        function closeModal() {
            document.getElementById('reviewModal').classList.remove('show');
            currentTransactionId = null;
            document.getElementById('adminNotes').value = '';
        }

        function renderError(tabId, message) {
            const bodyId = tabId + 'Body' || tabId + 'Container';
            const elem = document.getElementById(bodyId);
            if (elem) {
                elem.innerHTML = `<tr><td colspan="7" class="no-data">${message || 'خطأ في التحميل'}</td></tr>`;
            }
        }
    </script>
</body>
</html>
