<?php
require_once '../../../config/config.php';
require_once '../../../classes/DatabaseMongo.php';
require_once '../../../classes/Video.php';

// Get session ID from URL parameter
$sessionId = $_GET['id'] ?? '';

if (!$sessionId) {
    http_response_code(404);
    die('Session ID is required');
}

// Initialize database
try {
    $db = new DatabaseMongo();
    $videoManager = new Video($db);
    
    // Fetch session data from online_sessions collection
    $filter = ['_id' => DatabaseMongo::createObjectId($sessionId)];
    $session = $db->findOne('online_sessions', $filter);
    
    if (!$session) {
        http_response_code(404);
        die('Session not found');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    die('Error loading session: ' . htmlspecialchars($e->getMessage()));
}

// Get session details with safe defaults
$title = $session->session_title ?? $session->title ?? 'Untitled Session';
$description = $session->session_description ?? $session->description ?? '';
$meetingLink = $session->meeting_link ?? null;
$videoUrl = $session->video_url ?? null;
$sessionNumber = $session->sessionNumber ?? $session->session_number ?? null;
$accessControl = $session->accessControl ?? 'free'; // 'restricted' or 'free'
$requiredGrade = $session->grade ?? 'senior3';

// Process videos array for multi-video support
$videos = [];
if (isset($session->videos)) {
    $videosArray = $session->videos;
    if (is_object($videosArray)) {
        $videosArray = (array)$videosArray;
    }
    if (is_array($videosArray) && count($videosArray) > 0) {
        foreach ($videosArray as $video) {
            $videoData = [];
            $videoId = null;
            $videoSource = null;
            $videoUrl = null;
            $filePath = null;
            
            if (is_object($video)) {
                $videoId = $video->video_id ?? null;
                $videoSource = $video->source ?? null;
                $videoUrl = $video->url ?? null;
                $filePath = $video->file_path ?? null;
                $videoData['title'] = $video->title ?? 'Video';
                $videoData['description'] = $video->description ?? '';
            } elseif (is_array($video)) {
                $videoId = $video['video_id'] ?? null;
                $videoSource = $video['source'] ?? null;
                $videoUrl = $video['url'] ?? null;
                $filePath = $video['file_path'] ?? null;
                $videoData['title'] = $video['title'] ?? 'Video';
                $videoData['description'] = $video['description'] ?? '';
            }
            
            // If source is "upload" and we have a file_path, use it directly
            if ($videoSource === 'upload' && isset($filePath) && $filePath) {
                $videoUrl = '../../../uploads/videos/' . ltrim($filePath, '/');
            }
            // Fallback: Try to fetch from database using video_id
            elseif ($videoSource === 'upload' && $videoId && !$videoUrl) {
                try {
                    $videoRecord = $videoManager->getById($videoId);
                    if ($videoRecord && isset($videoRecord->video_file_path)) {
                        $videoUrl = '../../../uploads/videos/' . ltrim($videoRecord->video_file_path, '/');
                    }
                } catch (Exception $e) {
                    error_log("Error fetching video record: " . $e->getMessage());
                }
                
                // Last fallback: Direct ID-based path
                if (!$videoUrl) {
                    $videoUrl = '../../../uploads/videos/' . $videoId . '.mp4';
                }
            }
            
            $videoData['url'] = $videoUrl;
            $videoData['source'] = $videoSource;
            $videoData['video_id'] = $videoId;
            $videos[] = $videoData;
        }
    }
}

// Fallback to single video URL if no videos array
if (empty($videos) && !empty($session->video_url)) {
    $videos[] = [
        'url' => $session->video_url,
        'title' => $session->title ?? 'Video',
        'description' => '',
        'source' => 'link',
        'video_id' => null
    ];
}

// Get current video index from URL parameter
$currentVideoIndex = isset($_GET['video']) ? (int)$_GET['video'] : 0;
$currentVideoIndex = max(0, min($currentVideoIndex, count($videos) - 1));
$currentVideo = !empty($videos) ? $videos[$currentVideoIndex] : null;

// Set video URL from current video
if ($currentVideo && isset($currentVideo['url'])) {
    $videoUrl = $currentVideo['url'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="icon" class="circular-icon" type="image/png" href="../../../images/logo.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #ffffff;
            min-height: 100vh;
        }

        h1 {
            font-size: 28px;
            text-align: center;
            margin-top: 20px;
            color: #008080;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }

        .video-container {
            max-width: 800px;
            width: 100%;
            aspect-ratio: 16 / 9;
            overflow: hidden;
            margin: 20px auto;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .video-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .video-container video {
            width: 100%;
            height: 100%;
            background: #000;
        }

        .video-placeholder {
            width: 100%;
            height: 100%;
            background: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }

        /* Custom Video Player Styles */
        .custom-player {
            max-width: 300px;
            width: 100%;
            aspect-ratio: 16 / 9;
            overflow: hidden;
            margin: 30px auto;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 128, 128, 0.3);
            background: #000;
            position: relative;
        }

        .video-wrapper {
            position: relative;
            width: 100%;
            height: 100%;
            background: #000;
        }

        .video-wrapper video,
        .video-wrapper iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100% !important;
            height: 100% !important;
            border: none;
        }

        .player-controls {
            background: #1a1a1a;
            padding: 15px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
        }

        .player-controls button {
            padding: 8px 16px;
            background: #008080;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .player-controls button:hover {
            background: #006666;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0, 128, 128, 0.3);
        }

        .player-controls button:active {
            transform: translateY(0);
        }

        .video-info {
            background: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #008080;
        }

        .video-info p {
            margin: 5px 0;
            font-size: 14px;
            color: #555;
        }

        .video-info strong {
            color: #008080;
        }

        .controls {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .controls button {
            padding: 8px 16px;
            font-size: 16px;
            cursor: pointer;
            border: none;
            border-radius: 5px;
            background-color: #008080;
            color: #FFFFE0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            transition: transform 0.2s, background-color 0.3s;
        }

        .controls button:hover {
            background-color: #006666;
            transform: scale(1.05);
        }

        .video-info {
            max-width: 800px;
            margin: 15px auto;
            padding: 10px 15px;
            background-color: #f0f0f0;
            border-left: 4px solid #008080;
            font-size: 14px;
            color: #333;
        }

        .video-nav-buttons {
            max-width: 800px;
            margin: 10px auto;
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }

        .nav-button {
            padding: 8px 16px;
            font-size: 14px;
            cursor: pointer;
            border: none;
            border-radius: 5px;
            background-color: #008080;
            color: #FFFFE0;
            transition: background-color 0.3s;
        }

        .nav-button:hover:not(:disabled) {
            background-color: #006666;
        }

        .nav-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .question-box {
            max-width: 800px;
            margin: 20px auto;
            padding: 15px;
            background-color: #008080;
            color: #FFFFE0;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            cursor: pointer;
            transition: transform 0.2s, background-color 0.3s;
        }

        .question-box:hover {
            background-color: #006666;
            transform: scale(1.05);
        }

        .info-box {
            max-width: 800px;
            margin: 10px auto;
            padding: 15px;
            background-color: #fff3cd;
            color: #856404;
            text-align: center;
            font-size: 16px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .description-box {
            max-width: 800px;
            margin: 10px auto 30px;
            padding: 15px;
            background-color: white;
            color: #333;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            line-height: 1.6;
        }

        .video-title {
            font-size: 32px;
            font-weight: bold;
            color: #008080;
            text-align: center;
            margin: 20px auto;
            max-width: 800px;
            padding: 0 15px;
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 20px;
                margin-top: 15px;
            }

            .video-title {
                font-size: 24px;
            }

            .controls {
                gap: 10px;
            }

            .controls button {
                padding: 6px 12px;
                font-size: 14px;
            }
        }
    </style>
    <script src="../../../js/api-config.js"></script>
</head>
<body>
    <h1><?= htmlspecialchars($title) ?></h1>
    
    <?php if ($currentVideo && isset($currentVideo['title'])): ?>
    <div class="video-title">
        <?= htmlspecialchars($currentVideo['title']) ?>
    </div>
    <?php endif; ?>
    
    <?php
    // Convert YouTube URLs to embed format
    $embedUrl = $videoUrl ?? '';
    $isYouTube = false;
    if (!empty($embedUrl)) {
        if (strpos($embedUrl, 'youtu.be') !== false) {
            preg_match('/youtu\.be\/([a-zA-Z0-9_-]{11})/', $embedUrl, $matches);
            if (!empty($matches[1])) {
                $embedUrl = 'https://www.youtube.com/embed/' . $matches[1] . '?rel=0&modestbranding=1';
                $isYouTube = true;
            }
        } elseif (strpos($embedUrl, 'youtube.com/watch') !== false) {
            preg_match('/v=([a-zA-Z0-9_-]{11})/', $embedUrl, $matches);
            if (!empty($matches[1])) {
                $embedUrl = 'https://www.youtube.com/embed/' . $matches[1] . '?rel=0&modestbranding=1';
                $isYouTube = true;
            }
        } elseif (strpos($embedUrl, 'youtube.com/embed') !== false) {
            $isYouTube = true;
        }
    }
    ?>
    <div class="custom-player">
        <div class="video-wrapper">
            <?php if ($isYouTube && !empty($embedUrl)): ?>
                <iframe src="<?= htmlspecialchars($embedUrl) ?>" allowfullscreen></iframe>
            <?php elseif ($videoUrl && !$isYouTube): ?>
                <video id="videoPlayer" controls>
                    <source src="<?= htmlspecialchars($videoUrl) ?>" type="video/mp4">
                    Your browser doesn't support HTML5 video.
                </video>
            <?php else: ?>
                <div class="video-placeholder">
                    <div style="text-align: center;">
                        <div style="font-size: 48px; margin-bottom: 20px;">🎬</div>
                        <div>Session video coming soon</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="player-controls">
            <button onclick="toggleFullscreen(document.querySelector('.custom-player'))">📺 Fullscreen</button>
            <?php if (!empty($meetingLink)): ?>
                <button onclick="window.open('<?= htmlspecialchars($meetingLink) ?>', '_blank')">🔗 Join Meeting</button>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (count($videos) > 1): ?>
    <div class="video-nav-buttons">
        <a href="?id=<?= urlencode($sessionId) ?>&video=<?= $currentVideoIndex - 1 ?>" 
           class="nav-button" 
           <?php if ($currentVideoIndex == 0): ?>style="opacity: 0.5; pointer-events: none;"<?php endif; ?>>
            ← Previous Video
        </a>
        <a href="?id=<?= urlencode($sessionId) ?>&video=<?= $currentVideoIndex + 1 ?>" 
           class="nav-button"
           <?php if ($currentVideoIndex == count($videos) - 1): ?>style="opacity: 0.5; pointer-events: none;"<?php endif; ?>>
            Next Video →
        </a>
    </div>
    <?php endif; ?>

    <?php if ($videoUrl): ?>
    <div class="controls">
        <button onclick="toggleFullscreen()">📺 Fullscreen</button>
        <?php if ($meetingLink): ?>
        <button onclick="window.open('<?= htmlspecialchars($meetingLink) ?>', '_blank')">🎥 Join Meeting</button>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Access Control Overlay -->
    <div id="accessLoadingOverlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: white; z-index: 99999; display: flex; flex-direction: column; align-items: center; justify-content: center; font-family: 'Segoe UI', Arial, sans-serif;">
        <div style="width: 50px; height: 50px; border: 5px solid #f3f3f3; border-top: 5px solid #008080; border-radius: 50%; animation: spin 1s linear infinite;"></div>
        <p style="margin-top: 20px; color: #008080; font-weight: 500; font-size: 18px;">Verifying Access...</p>
    </div>

    <style>
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        .access-denied-card {
            background: white;
            padding: 50px 30px;
            border-radius: 20px;
            max-width: 550px;
            width: 90%;
            margin: auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            text-align: center;
            border-top: 5px solid #ff4757;
            animation: slideUp 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        
        .denied-icon {
            font-size: 80px;
            color: #ff4757;
            margin-bottom: 25px;
            animation: pulse-red 2s infinite;
        }
        
        @keyframes pulse-red {
            0% { transform: scale(1); filter: drop-shadow(0 0 0 rgba(255, 71, 87, 0)); }
            70% { transform: scale(1.1); filter: drop-shadow(0 0 15px rgba(255, 71, 87, 0.4)); }
            100% { transform: scale(1); filter: drop-shadow(0 0 0 rgba(255, 71, 87, 0)); }
        }
    </style>

    <script>
        // Check user access before displaying content
        async function checkUserAccess() {
            const overlay = document.getElementById('accessLoadingOverlay');
            const userPhone = localStorage.getItem('userPhone');
            const sessionNumber = <?= $sessionNumber ? $sessionNumber : 'null' ?>;
            const accessControl = '<?= htmlspecialchars($accessControl) ?>';
            
            console.log('=== Access Control Check ===');
            
            if (!userPhone) {
                window.location.href = '/login/index.html';
                return;
            }
            
            if (accessControl === 'free') {
                if (overlay) overlay.style.display = 'none';
                return;
            }
            
            if (accessControl === 'restricted' && sessionNumber) {
                try {
                    const response = await fetch(`${window.API_BASE_URL}sessions.php?action=check-access&session_number=${sessionNumber}&phone=${encodeURIComponent(userPhone)}`);
                    const data = await response.json();
                    
                    if (data.success && data.hasAccess) {
                        if (overlay) overlay.style.display = 'none';
                        return;
                    } else {
                        showAccessDenied(data.message || "You don't have access to this session.");
                    }
                } catch (error) {
                    console.error('Error checking access:', error);
                    if (overlay) overlay.style.display = 'none'; 
                }
            } else {
                if (overlay) overlay.style.display = 'none';
            }
        }
        
        function showAccessDenied(message) {
            const backLink = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1);
            document.body.style.background = '#f1f2f6';
            document.body.innerHTML = `
                <div style="display: flex; min-height: 100vh; align-items: center; justify-content: center; padding: 20px;">
                    <div class="access-denied-card">
                        <div class="denied-icon">🚫</div>
                        <h1 style="color: #2f3542; margin-bottom: 15px; font-size: 28px;">الدخول غير مصرح به</h1>
                        <p style="color: #57606f; font-size: 18px; margin-bottom: 30px; line-height: 1.6;">
                            نعتذر، هذه المحاضرة مخصصة للمشتركين فقط.<br>
                            <span style="font-weight: bold; color: #ff4757;">${message}</span>
                        </p>
                        <div style="display: flex; gap: 15px; justify-content: center;">
                            <a href="${backLink}" style="padding: 12px 25px; background: #008080; color: white; text-decoration: none; border-radius: 10px; font-weight: bold; transition: 0.3s; box-shadow: 0 4px 12px rgba(0, 128, 128, 0.2);">
                                العودة للمحاضرات
                            </a>
                            <a href="https://wa.me/201558145450" target="_blank" style="padding: 12px 25px; background: #25D366; color: white; text-decoration: none; border-radius: 10px; font-weight: bold; transition: 0.3s; box-shadow: 0 4px 12px rgba(37, 211, 102, 0.2);">
                                تواصل معنا
                            </a>
                        </div>
                    </div>
                </div>
            `;
        }
        
        function toggleFullscreen(element) {
            const customPlayer = element || document.querySelector('.custom-player');
            const videoPlayer = document.getElementById('videoPlayer');
            
            if (document.fullscreenElement) {
                document.exitFullscreen();
            } else {
                if (customPlayer && customPlayer.requestFullscreen) {
                    customPlayer.requestFullscreen().catch(err => {
                        console.log('Fullscreen request failed:', err);
                    });
                } else if (videoPlayer && videoPlayer.requestFullscreen) {
                    videoPlayer.requestFullscreen();
                }
            }
        }

        checkUserAccess();
    </script>
</body>
</html>
