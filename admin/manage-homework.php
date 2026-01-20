<?php
require_once '../includes/session_check.php';
requireTeacher();

$db = new DatabaseMongo();
$homeworkManager = new Homework($db);

$csrfToken = generateCSRFToken();
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRFToken();
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $homeworkData = [
            'title' => sanitizeInput($_POST['title'] ?? ''),
            'description' => sanitizeInput($_POST['description'] ?? ''),
            'instructions' => sanitizeInput($_POST['instructions'] ?? ''),
            'subject_id' => $_POST['subject_id'] ?? null,
            'lesson_id' => $_POST['lesson_id'] ?? null,
            'due_date' => $_POST['due_date'] ?? null,
            'max_score' => (int)($_POST['max_score'] ?? 100),
            'created_by' => getCurrentUserId(),
            'status' => 'active'
        ];
        
        try {
            $homeworkId = $homeworkManager->create($homeworkData);
            $message = '✓ Homework created successfully!';
            $messageType = 'success';
            logActivity('CREATE_HOMEWORK', 'homework', (string)$homeworkId, 'Created: ' . $homeworkData['title']);
        } catch (Exception $e) {
            $message = '✗ Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    } elseif ($action === 'grade') {
        $homeworkId = $_POST['homework_id'] ?? '';
        $studentId = $_POST['student_id'] ?? '';
        $score = (int)($_POST['score'] ?? 0);
        $feedback = sanitizeInput($_POST['feedback'] ?? '');
        
        try {
            $homeworkManager->grade($homeworkId, $studentId, $score, $feedback, getCurrentUserId());
            $message = '✓ Homework graded successfully!';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = '✗ Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Get all homework
$allHomework = $homeworkManager->getAll([], 50);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homework Management - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #333;
            font-size: 1.8rem;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .card h2 {
            margin-bottom: 20px;
            color: #333;
            font-size: 1.5rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
        }

        .homework-list {
            margin-top: 20px;
        }

        .homework-item {
            background: #f8f9fa;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .homework-item.closed {
            border-left-color: #dc3545;
            opacity: 0.8;
        }

        .homework-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .homework-meta {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 10px;
        }

        .homework-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-active {
            background: #d4edda;
            color: #155724;
        }

        .badge-closed {
            background: #f8d7da;
            color: #721c24;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #999;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 1rem;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-tasks"></i> Homework Management</h1>
            <a href="dashboard.html" class="btn btn-secondary">← Back to Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div class="message <?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2>Create New Homework</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label for="title">Homework Title *</label>
                    <input type="text" id="title" name="title" class="form-control" required placeholder="e.g., Chapter 3 Problems">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" placeholder="Brief description of the homework"></textarea>
                </div>

                <div class="form-group">
                    <label for="instructions">Instructions</label>
                    <textarea id="instructions" name="instructions" class="form-control" placeholder="Detailed instructions for students"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="due_date">Due Date *</label>
                        <input type="datetime-local" id="due_date" name="due_date" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="max_score">Maximum Score</label>
                        <input type="number" id="max_score" name="max_score" class="form-control" value="100" min="1">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Homework
                </button>
            </form>
        </div>

        <div class="card">
            <div class="tabs">
                <button class="tab active" onclick="showTab('all')">All Homework (<?= count($allHomework) ?>)</button>
                <button class="tab" onclick="showTab('active')">Active</button>
                <button class="tab" onclick="showTab('closed')">Closed</button>
            </div>

            <div id="tab-all" class="tab-content active">
                <div class="homework-list">
                    <?php if (empty($allHomework)): ?>
                        <p style="text-align:center;color:#999;">No homework created yet</p>
                    <?php else: ?>
                        <?php foreach ($allHomework as $hw): ?>
                            <div class="homework-item <?= $hw->status === 'closed' ? 'closed' : '' ?>">
                                <div class="homework-title">
                                    <?= htmlspecialchars($hw->title ?? 'Untitled') ?>
                                    <span class="badge badge-<?= $hw->status ?>">
                                        <?= $hw->status ?? 'active' ?>
                                    </span>
                                </div>
                                <div class="homework-meta">
                                    📅 Due: <?= formatMongoDate($hw->due_date ?? null, 'M d, Y H:i') ?> · 
                                    🎯 Max Score: <?= $hw->max_score ?? 100 ?> · 
                                    ✏️ Created: <?= formatMongoDate($hw->createdAt ?? null, 'M d, Y') ?>
                                </div>
                                <div><?= htmlspecialchars($hw->description ?? '') ?></div>
                                <div class="homework-actions">
                                    <button onclick="viewSubmissions('<?= (string)$hw->_id ?>')" class="btn btn-sm btn-primary">
                                        <i class="fas fa-list"></i> View Submissions
                                    </button>
                                    <?php if ($hw->status === 'active'): ?>
                                        <button onclick="closeHomework('<?= (string)$hw->_id ?>')"  class="btn btn-sm btn-secondary">
                                            <i class="fas fa-lock"></i> Close
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div id="tab-active" class="tab-content"></div>
            <div id="tab-closed" class="tab-content"></div>
        </div>
    </div>

    <div id="submissionsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Homework Submissions</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div id="submissionsList">
                Loading...
            </div>
        </div>
    </div>

    <script>
        function showTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            event.target.classList.add('active');
            document.getElementById('tab-' + tab).classList.add('active');
        }

        function viewSubmissions(homeworkId) {
            document.getElementById('submissionsModal').classList.add('active');
            document.getElementById('submissionsList').innerHTML = 'Loading submissions...';
            
            fetch('/api/homework.php?action=submissions&homework_id=' + homeworkId)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.submissions.length > 0) {
                        let html = '';
                        data.submissions.forEach(sub => {
                            html += `
                                <div style="padding:15px;background:#f8f9fa;margin-bottom:10px;border-radius:8px;">
                                    <strong>Student:</strong> ${sub.student_id}<br>
                                    <strong>Status:</strong> ${sub.status}<br>
                                    <strong>Submitted:</strong> ${new Date(sub.submitted_at).toLocaleString()}<br>
                                    ${sub.score ? `<strong>Score:</strong> ${sub.score}<br>` : ''}
                                    ${sub.feedback ? `<strong>Feedback:</strong> ${sub.feedback}<br>` : ''}
                                </div>
                            `;
                        });
                        document.getElementById('submissionsList').innerHTML = html;
                    } else {
                        document.getElementById('submissionsList').innerHTML = '<p style="text-align:center;color:#999;">No submissions yet</p>';
                    }
                });
        }

        function closeModal() {
            document.getElementById('submissionsModal').classList.remove('active');
        }

        function closeHomework(homeworkId) {
            if (confirm('Are you sure you want to close this homework? Students will no longer be able to submit.')) {
                fetch('/api/homework.php', {
                    method: 'PUT',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'update', homework_id: homeworkId, status: 'closed'})
                }).then(() => location.reload());
            }
        }
    </script>
</body>
</html>
