<?php
require_once 'includes/session_check.php';
requireLogin();

$db = new DatabaseMongo();
$videoManager = new Video($db);

$videoId = $_GET['id'] ?? '';
if (!$videoId) {
    die('Video ID required');
}

$video = $videoManager->getById($videoId);
if (!$video) {
    die('Video not found');
}

// Increment view count
$videoManager->incrementViewCount($videoId);

$videoPath = $videoManager->getFilePath($videoId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($video->video_title ?? 'Video') ?> - Watch</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #000;
            color: #fff;
        }

        .video-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .video-player-wrapper {
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 20px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.5);
        }

        video {
            width: 100%;
            display: block;
            max-height: 80vh;
        }

        .video-info {
            padding: 20px;
        }

        .video-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .video-meta {
            display: flex;
            gap: 25px;
            flex-wrap: wrap;
            margin-bottom: 20px;
            color: #aaa;
            font-size: 0.95rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .meta-item i {
            color: #667eea;
        }

        .video-description {
            background: #1a1a1a;
            padding: 20px;
            border-radius: 8px;
            line-height: 1.6;
            color: #ccc;
        }

        .video-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: #1a1a1a;
            border-radius: 8px;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
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
            background: #444;
            color: white;
        }

        .btn-secondary:hover {
            background: #555;
        }

        .playback-controls {
            display: flex;
            gap: 10px;
        }

        .speed-btn {
            background: #333;
            color: white;
            border: 1px solid #555;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .speed-btn.active {
            background: #667eea;
            border-color: #667eea;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #667eea;
            text-decoration: none;
            margin-bottom: 20px;
            font-size: 1.1rem;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: #764ba2;
        }
    </style>
</head>
<body>
    <div class="video-container">
        <a href="student/videos.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Library
        </a>

        <div class="video-player-wrapper">
            <video id="videoPlayer" controls preload="metadata">
                <source src="/api/stream-video.php?id=<?= $videoId ?>" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </div>

        <div class="video-info">
            <h1 class="video-title"><?= htmlspecialchars($video->video_title ?? 'Untitled Video') ?></h1>
            
            <div class="video-meta">
                <div class="meta-item">
                    <i class="fas fa-eye"></i>
                    <span><?= number_format($video->view_count ?? 0) ?> views</span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-hdd"></i>
                    <span><?= $video->file_size_mb ?? 0 ?> MB</span>
                </div>
                <?php if (isset($video->duration_seconds)): ?>
                <div class="meta-item">
                    <i class="fas fa-clock"></i>
                    <span><?= gmdate("H:i:s", $video->duration_seconds) ?></span>
                </div>
                <?php endif; ?>
                <div class="meta-item">
                    <i class="fas fa-calendar"></i>
                    <span><?= formatMongoDate($video->createdAt ?? null, 'M d, Y') ?></span>
                </div>
            </div>

            <?php if (isset($video->video_description) && $video->video_description): ?>
            <div class="video-description">
                <strong><i class="fas fa-info-circle"></i> Description</strong><br><br>
                <?= nl2br(htmlspecialchars($video->video_description)) ?>
            </div>
            <?php endif; ?>

            <div class="video-controls">
                <div class="playback-controls">
                    <strong style="margin-right:10px;">Playback Speed:</strong>
                    <button class="speed-btn" onclick="setSpeed(0.5)">0.5x</button>
                    <button class="speed-btn active" onclick="setSpeed(1)">1x</button>
                    <button class="speed-btn" onclick="setSpeed(1.25)">1.25x</button>
                    <button class="speed-btn" onclick="setSpeed(1.5)">1.5x</button>
                    <button class="speed-btn" onclick="setSpeed(2)">2x</button>
                </div>
                <div>
                    <button class="btn btn-secondary" onclick="toggleFullscreen()">
                        <i class="fas fa-expand"></i> Fullscreen
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const player = document.getElementById('videoPlayer');

        function setSpeed(speed) {
            player.playbackRate = speed;
            
            // Update UI
            document.querySelectorAll('.speed-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                player.requestFullscreen().catch(err => {
                    console.error('Error attempting to enable fullscreen:', err);
                });
            } else {
                document.exitFullscreen();
            }
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            switch(e.key) {
                case ' ':
                    e.preventDefault();
                    if (player.paused) {
                        player.play();
                    } else {
                        player.pause();
                    }
                    break;
                case 'ArrowLeft':
                    player.currentTime -= 10;
                    break;
                case 'ArrowRight':
                    player.currentTime += 10;
                    break;
                case 'f':
                    toggleFullscreen();
                    break;
            }
        });

        // Save progress
        player.addEventListener('timeupdate', function() {
            if (player.currentTime > 0) {
                localStorage.setItem('video_<?= $videoId ?>_progress', player.currentTime);
            }
        });

        // Restore progress
        const savedProgress = localStorage.getItem('video_<?= $videoId ?>_progress');
        if (savedProgress) {
            player.currentTime = parseFloat(savedProgress);
        }
    </script>
</body>
</html>
