<?php
require_once '../includes/session_check.php';
requireLogin();

$db = new DatabaseMongo();
$homeworkManager = new Homework($db);

$homeworkId = $_GET['id'] ?? '';
if (!$homeworkId) {
    header('Location: ../senior2/mathematics/Homework/');
    exit;
}

$homework = $homeworkManager->getById($homeworkId);
if (!$homework) {
    die('Homework not found');
}

$studentId = getCurrentUserId();
$submission = $homeworkManager->getSubmission($homeworkId, $studentId);

$csrfToken = generateCSRFToken();
$message = '';
$messageType = '';

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRFToken();
    
    $submissionData = [
        'submission_text' => sanitizeInput($_POST['submission_text'] ?? ''),
        'submission_file_path' => null
    ];
    
    // Handle file upload if present
    if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/homework/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filename = uniqid('hw_') . '_' . basename($_FILES['submission_file']['name']);
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $filepath)) {
            $submissionData['submission_file_path'] = 'uploads/homework/' . $filename;
        }
    }
    
    try {
        $submissionId = $homeworkManager->submit($homeworkId, $studentId, $submissionData);
        $message = '✓ Homework submitted successfully!';
        $messageType = 'success';
        $submission = $homeworkManager->getSubmission($homeworkId, $studentId);
    } catch (Exception $e) {
        $message = '✗ ' . $e->getMessage();
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($homework->title ?? 'Homework') ?> - Student Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e3f2fd, #ffffff);
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, #1976d2, #1565c0);
            color: white;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .container {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .card h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }

        .homework-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
        }

        .meta-item i {
            color: #1976d2;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-closed {
            background: #f8d7da;
            color: #721c24;
        }

        .status-submitted {
            background: #fff3cd;
            color: #856404;
        }

        .status-graded {
            background: #d1ecf1;
            color: #0c5460;
        }

        .description {
            line-height: 1.6;
            color: #555;
            margin-bottom: 20px;
        }

        .instructions {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #1976d2;
            margin-bottom: 20px;
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
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: #1976d2;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 150px;
        }

        .file-upload {
            border: 2px dashed #1976d2;
            padding: 30px;
            text-align: center;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-upload:hover {
            background: #e3f2fd;
        }

        .file-upload input[type="file"] {
            display: none;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #1976d2;
            color: white;
        }

        .btn-primary:hover {
            background: #1565c0;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
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

        .submission-info {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .score-display {
            font-size: 2rem;
            font-weight: bold;
            color: #1976d2;
            text-align: center;
            margin: 20px 0;
        }

        .feedback {
            background: #fff3cd;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            opacity: 0.9;
            transition: opacity 0.3s;
        }

        .back-btn:hover {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="../senior2/mathematics/Homework/" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Homework List
        </a>
        <h1><?= htmlspecialchars($homework->title ?? 'Homework') ?></h1>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="message <?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2>Homework Details</h2>
            
            <div class="homework-meta">
                <div class="meta-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Due: <?= formatMongoDate($homework->due_date ?? null, 'M d, Y H:i') ?></span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-star"></i>
                    <span>Max Score: <?= $homework->max_score ?? 100 ?></span>
                </div>
                <div class="meta-item">
                    <span class="status-badge status-<?= $homework->status ?>">
                        <?= $homework->status ?? 'active' ?>
                    </span>
                </div>
            </div>

            <?php if ($homework->description): ?>
                <div class="description">
                    <?= nl2br(htmlspecialchars($homework->description)) ?>
                </div>
            <?php endif; ?>

            <?php if ($homework->instructions): ?>
                <div class="instructions">
                    <strong><i class="fas fa-list-check"></i> Instructions:</strong><br><br>
                    <?= nl2br(htmlspecialchars($homework->instructions)) ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($submission): ?>
            <div class="card">
                <h2>Your Submission</h2>
                
                <div class="submission-info">
                    <div class="meta-item">
                        <i class="fas fa-check-circle"></i>
                        <strong>Submitted on:</strong> <?= formatMongoDate($submission->submitted_at ?? null, 'M d, Y H:i') ?>
                    </div>
                    <div class="meta-item" style="margin-top:10px;">
                        <i class="fas fa-info-circle"></i>
                        <strong>Status:</strong> 
                        <span class="status-badge status-<?= $submission->status ?>">
                            <?= $submission->status ?>
                        </span>
                    </div>
                </div>

                <?php if (isset($submission->score)): ?>
                    <div class="score-display">
                        🎯 Score: <?= $submission->score ?> / <?= $homework->max_score ?? 100 ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($submission->feedback) && $submission->feedback): ?>
                    <div class="feedback">
                        <strong><i class="fas fa-comment"></i> Instructor Feedback:</strong><br><br>
                        <?=nl2br(htmlspecialchars($submission->feedback)) ?>
                    </div>
                <?php endif; ?>

                <div style="margin-top:20px;">
                    <strong>Your Answer:</strong><br>
                    <?= nl2br(htmlspecialchars($submission->submission_text ?? 'No text submitted')) ?>
                </div>

                <?php if ($submission->submission_file_path): ?>
                    <div style="margin-top:15px;">
                        <strong>Attached File:</strong><br>
                        <a href="/<?= htmlspecialchars($submission->submission_file_path) ?>" target="_blank" class="btn btn-secondary">
                            <i class="fas fa-file-download"></i> Download Submission
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif ($homework->status === 'active'): ?>
            <div class="card">
                <h2>Submit Your Work</h2>
                
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    
                    <div class="form-group">
                        <label for="submission_text">Your Answer *</label>
                        <textarea id="submission_text" name="submission_text" class="form-control" required placeholder="Type your answer here..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Attach File (Optional)</label>
                        <div class="file-upload" onclick="document.getElementById('submission_file').click()">
                            <input type="file" id="submission_file" name="submission_file" onchange="showFileName(this)">
                            <i class="fas fa-cloud-upload-alt" style="font-size:2rem;color:#1976d2;"></i><br>
                            Click to upload file (Max 10MB)<br>
                            <small>PDF, Word, Images</small>
                            <div id="selectedFile" style="margin-top:10px;color:#28a745;"></div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Homework
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="card">
                <p style="text-align:center;color:#999;padding:40px 0;">
                    <i class="fas fa-lock" style="font-size:3rem;opacity:0.3;"></i><br><br>
                    This homework is closed. Submissions are no longer accepted.
                </p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function showFileName(input) {
            const file = input.files[0];
            const selectedFile = document.getElementById('selectedFile');
            
            if (file) {
                const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                selectedFile.innerHTML = `<i class="fas fa-check-circle"></i> ${file.name} (${sizeMB} MB)`;
            }
        }
    </script>
</body>
</html>
