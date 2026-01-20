<?php
require_once '../includes/session_check.php';
requireLogin();

$db = new DatabaseMongo();
$videoManager = new Video($db);

// Get videos
$videos = $videoManager->getAll([], 100);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Library - Student Portal</title>
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
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .filters {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-input {
            flex: 1;
            min-width: 200px;
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
        }

        .filter-input:focus {
            outline: none;
            border-color: #1976d2;
        }

        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }

        .video-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s;
            cursor: pointer;
        }

        .video-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }

        .video-thumbnail {
            width: 100%;
            height: 180px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .video-thumbnail i {
            font-size: 3rem;
            color: white;
            opacity: 0.8;
        }

        .video-duration {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
        }

        .video-info {
            padding: 20px;
        }

        .video-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .video-description {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 12px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .video-meta {
            display: flex;
            gap: 15px;
            font-size: 0.85rem;
            color: #999;
        }

        .video-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 5rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .empty-state h2 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: white;
            color: #1976d2;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .back-btn:hover {
            background: #e3f2fd;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="index.html" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        <h1><i class="fas fa-video"></i> Video Library</h1>
        <p>Browse and watch educational videos</p>
    </div>

    <div class="container">
        <div class="filters">
            <input type="text" id="searchInput" class="filter-input" placeholder="🔍 Search videos...">
            <select id="sortSelect" class="filter-input" style="flex:0 0 200px;">
                <option value="recent">Most Recent</option>
                <option value="popular">Most Popular</option>
                <option value="title">Title A-Z</option>
            </select>
        </div>

        <div class="video-grid" id="videoGrid">
            <?php if (empty($videos)): ?>
                <div class="empty-state" style="grid-column: 1/-1;">
                    <i class="fas fa-video-slash"></i>
                    <h2>No Videos Available</h2>
                    <p>Check back later for new content</p>
                </div>
            <?php else: ?>
                <?php foreach ($videos as $video): ?>
                    <div class="video-card" onclick="playVideo('<?= (string)$video->_id ?>')">
                        <div class="video-thumbnail">
                            <i class="fas fa-play-circle"></i>
                            <?php if (isset($video->duration_seconds)): ?>
                                <div class="video-duration">
                                    <?= gmdate("i:s", $video->duration_seconds) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="video-info">
                            <div class="video-title">
                                <?= htmlspecialchars($video->video_title ?? 'Untitled Video') ?>
                            </div>
                            <div class="video-description">
                                <?= htmlspecialchars($video->video_description ?? 'No description available') ?>
                            </div>
                            <div class="video-meta">
                                <span>
                                    <i class="fas fa-eye"></i>
                                    <?= number_format($video->view_count ?? 0) ?>
                                </span>
                                <span>
                                    <i class="fas fa-hdd"></i>
                                    <?= $video->file_size_mb ?? 0 ?> MB
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function playVideo(videoId) {
            window.location.href = '/stream-video.php?id=' + videoId;
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const search = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('.video-card');
            
            cards.forEach(card => {
                const title = card.querySelector('.video-title').textContent.toLowerCase();
                const description = card.querySelector('.video-description').textContent.toLowerCase();
                
                if (title.includes(search) || description.includes(search)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Sort functionality
        document.getElementById('sortSelect').addEventListener('change', function(e) {
            const sortBy = e.target.value;
            const grid = document.getElementById('videoGrid');
            const cards = Array.from(document.querySelectorAll('.video-card'));
            
            cards.sort((a, b) => {
                if (sortBy === 'title') {
                    const titleA = a.querySelector('.video-title').textContent;
                    const titleB = b.querySelector('.video-title').textContent;
                    return titleA.localeCompare(titleB);
                } else if (sortBy === 'popular') {
                    const viewsA = parseInt(a.querySelector('.video-meta span:first-child').textContent.replace(/,/g, ''));
                    const viewsB = parseInt(b.querySelector('.video-meta span:first-child').textContent.replace(/,/g, ''));
                    return viewsB - viewsA;
                }
                return 0; // recent (default order)
            });
            
            cards.forEach(card => grid.appendChild(card));
        });
    </script>
</body>
</html>
