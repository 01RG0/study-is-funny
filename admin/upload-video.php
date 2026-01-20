<?php
require_once '../includes/session_check.php';
requireTeacher();

$db = new DatabaseMongo();
$videoManager = new Video($db);

$csrfToken = generateCSRFToken();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['video'])) {
    requireCSRFToken();
    
    $metadata = [
        'title' => sanitizeInput($_POST['title'] ?? ''),
        'description' => sanitizeInput($_POST['description'] ?? ''),
        'subject_id' => $_POST['subject_id'] ?? null,
        'lesson_id' => $_POST['lesson_id'] ?? null,
        'duration_seconds' => $_POST['duration'] ?? null,
        'uploaded_by' => getCurrentUserId()
    ];
    
    $result = $videoManager->upload($_FILES['video'], $metadata);
    
    if ($result['success']) {
        $message = '✓ ' . $result['message'];
        $messageType = 'success';
        logActivity('UPLOAD_VIDEO', 'video', $result['video_id'], 'Uploaded: ' . $metadata['title']);
    } else {
        $message = '✗ ' . $result['message'];
        $messageType = 'error';
    }
}

// Get all videos for display
$videos = $videoManager->getAll([], 50);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Video - Admin Panel</title>
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
            max-width: 1200px;
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
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
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

        .file-upload {
            border: 3px dashed #667eea;
            padding: 40px;
            text-align: center;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-upload:hover {
            background: #f8f9ff;
        }

        .file-upload input[type="file"] {
            display: none;
        }

        .file-upload-label {
            display: block;
            color: #667eea;
            font-size: 1.1rem;
        }

        .file-info {
            margin-top: 15px;
            color: #666;
            font-size: 0.9rem;
        }

        .selected-file {
            margin-top: 15px;
            padding: 10px;
            background: #e8f5e9;
            border-radius: 8px;
            color: #2e7d32;
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
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .video-list {
            margin-top: 30px;
        }

        .video-item {
            background: #f8f9fa;
            padding: 15px 20px;
            margin-bottom: 10px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .video-item-info {
            flex: 1;
        }

        .video-item-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .video-item-meta {
            font-size: 0.85rem;
            color: #666;
        }

        .video-item-actions {
            display: flex;
            gap: 10px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
        }

        .progress-bar {
            width: 100%;
            height: 30px;
            background: #e0e0e0;
            border-radius: 15px;
            overflow: hidden;
            margin-top: 15px;
            display: none;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            width: 0%;
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-video"></i> Video Upload</h1>
            <a href="dashboard.html" class="btn btn-secondary" >← Back to Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div class="message <?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2>Upload New Video</h2>
            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                
                <div class="form-group">
                    <div class="file-upload" onclick="document.getElementById('videoFile').click()">
                        <input type="file" id="videoFile" name="video" accept="video/*" required onchange="showFileName(this)">
                        <label class="file-upload-label">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 3rem;"></i><br>
                            Click to upload video or drag and drop
                        </label>
                        <div class="file-info">
                            Maximum file size: 500MB<br>
                            Supported formats: MP4, WebM, AVI, MOV
                        </div>
                        <div id="selectedFile" class="selected-file" style="display:none;"></div>
                    </div>
                    <div class="progress-bar" id="progressBar">
                        <div class="progress-bar-fill" id="progressFill">0%</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="title">Video Title *</label>
                    <input type="text" id="title" name="title" class="form-control" required placeholder="Enter video title">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" placeholder="Enter video description"></textarea>
                </div>

                <div class="form-group">
                    <label for="duration">Duration (seconds)</label>
                    <input type="number" id="duration" name="duration" class="form-control" placeholder="e.g., 3600 for 1 hour">
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Upload Video
                </button>
            </form>
        </div>

        <div class="card">
            <h2>Recent Videos (<?= count($videos) ?>)</h2>
            <div class="video-list">
                <?php if (empty($videos)): ?>
                    <p style="text-align:center;color:#999;">No videos uploaded yet</p>
                <?php else: ?>
                    <?php foreach ($videos as $video): ?>
                        <div class="video-item">
                            <div class="video-item-info">
                                <div class="video-item-title"><?= htmlspecialchars($video->video_title ?? 'Untitled') ?></div>
                                <div class="video-item-meta">
                                    <?= $video->file_size_mb ?? 0 ?> MB · 
                                    <?= $video->view_count ?? 0 ?> views · 
                                    <?= formatMongoDate($video->createdAt ?? null, 'M d, Y') ?>
                                </div>
                            </div>
                            <div class="video-item-actions">
                                <a href="/stream-video.php?id=<?= (string)$video->_id ?>" class="btn btn-sm btn-primary" target="_blank">
                                    <i class="fas fa-play"></i> Play
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function showFileName(input) {
            const file = input.files[0];
            const selectedFile = document.getElementById('selectedFile');
            
            if (file) {
                const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                selectedFile.innerHTML = `<i class="fas fa-file-video"></i> ${file.name} (${sizeMB} MB)`;
                selectedFile.style.display = 'block';
            }
        }

        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const progressBar = document.getElementById('progressBar');
            const progressFill = document.getElementById('progressFill');
            
            // Show progress bar when uploading
            progressBar.style.display = 'block';
            
            // Simulate progress (in real implementation, use XMLHttpRequest for actual progress)
            let progress = 0;
            const interval = setInterval(() => {
                progress += 5;
                if (progress >= 95) {
                    clearInterval(interval);
                }
                progressFill.style.width = progress + '%';
                progressFill.textContent = progress + '%';
            }, 200);
        });
    </script>
</body>
</html>
