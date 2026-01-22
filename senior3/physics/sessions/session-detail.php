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

// Get all videos from the session
$videos = [];
if (isset($session->videos) && is_array($session->videos)) {
    foreach ($session->videos as $video) {
        $videoData = [];
        if (is_object($video)) {
            $videoData['video_id'] = $video->video_id ?? null;
            $videoData['title'] = $video->title ?? 'Video';
            $videoData['description'] = $video->description ?? '';
            $videoData['source'] = $video->source ?? 'upload';
            $videoData['url'] = $video->url ?? null;
            $videoData['file_path'] = $video->file_path ?? null;
        } elseif (is_array($video)) {
            $videoData = $video;
        }
        
        // If source is "upload" and we have a file_path, use it directly
        if ($videoData['source'] === 'upload' && isset($videoData['file_path']) && $videoData['file_path']) {
            $videoData['url'] = '/study-is-funny/uploads/videos/' . ltrim($videoData['file_path'], '/');
        }
        // Fallback: Try to fetch from database using video_id
        elseif ($videoData['source'] === 'upload' && isset($videoData['video_id']) && $videoData['video_id']) {
            try {
                $videoRecord = $videoManager->getById($videoData['video_id']);
                if ($videoRecord && isset($videoRecord->video_file_path)) {
                    $videoData['url'] = '/study-is-funny/uploads/videos/' . ltrim($videoRecord->video_file_path, '/');
                }
            } catch (Exception $e) {
                error_log("Error fetching video record: " . $e->getMessage());
            }
            
            // Last fallback: Direct ID-based path
            if (!$videoData['url']) {
                $videoData['url'] = '/study-is-funny/uploads/videos/' . $videoData['video_id'] . '.mp4';
            }
        }
        
        $videos[] = $videoData;
    }
}

// Get current video index from URL or default to 0
$currentVideoIndex = isset($_GET['video']) ? (int)$_GET['video'] : 0;
if ($currentVideoIndex < 0 || $currentVideoIndex >= count($videos)) {
    $currentVideoIndex = 0;
}

$currentVideo = !empty($videos) ? $videos[$currentVideoIndex] : null;
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

        .video-info {
            max-width: 800px;
            margin: 10px auto;
            padding: 15px;
            background-color: #e8f5f0;
            color: #008080;
            text-align: center;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
        }

        .video-nav-buttons {
            max-width: 800px;
            margin: 15px auto;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .nav-button {
            padding: 10px 20px;
            font-size: 14px;
            cursor: pointer;
            border: none;
            border-radius: 5px;
            background-color: #008080;
            color: #FFFFE0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .nav-button:hover:not(:disabled) {
            background-color: #006666;
            transform: scale(1.05);
        }

        .nav-button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
            opacity: 0.6;
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
    
    <?php if (!empty($videos) && $currentVideo): ?>
    <div class="video-title">
        <?= htmlspecialchars($currentVideo['title'] ?? 'Video') ?>
    </div>
    <?php endif; ?>
    
    <?php
    // Convert YouTube URLs to embed format
    $embedUrl = ($currentVideo && $currentVideo['url']) ? $currentVideo['url'] : '';
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
            <?php elseif ($currentVideo && $currentVideo['url'] && !$isYouTube): ?>
                <video id="videoPlayer" controls>
                    <source src="<?= htmlspecialchars($currentVideo['url']) ?>" type="video/mp4">
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
    </div>
    
    <?php if ($currentVideo): ?>
    <div class="controls">
        <button onclick="toggleFullscreen()">📺 Fullscreen</button>
        <?php if ($meetingLink): ?>
        <button onclick="window.open('<?= htmlspecialchars($meetingLink) ?>', '_blank')">🎥 Join Meeting</button>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($videos)): ?>
    <div class="video-nav-buttons">
        <a href="?id=<?= htmlspecialchars($sessionId) ?>&video=<?= max(0, $currentVideoIndex - 1) ?>" 
           class="nav-button" 
           style="<?= ($currentVideoIndex === 0) ? 'opacity: 0.5; cursor: not-allowed;' : '' ?>">
            ⬅️ Previous Video
        </a>
        <a href="?id=<?= htmlspecialchars($sessionId) ?>&video=<?= min(count($videos) - 1, $currentVideoIndex + 1) ?>" 
           class="nav-button"
           style="<?= ($currentVideoIndex === count($videos) - 1) ? 'opacity: 0.5; cursor: not-allowed;' : '' ?>">
            Next Video ➡️
        </a>
    </div>
    <?php endif; ?>

    <script>
        // Check user access before displaying content
        async function checkUserAccess() {
            const userPhone = localStorage.getItem('userPhone');
            const sessionId = '<?= htmlspecialchars($sessionId) ?>';
            const sessionNumber = <?= $sessionNumber ? $sessionNumber : 'null' ?>;
            const accessControl = '<?= htmlspecialchars($accessControl) ?>';
            const requiredGrade = '<?= htmlspecialchars($requiredGrade) ?>';
            
            console.log('=== Access Control Check ===');
            console.log('User Phone:', userPhone);
            console.log('Session ID:', sessionId);
            console.log('Session Number:', sessionNumber);
            console.log('Access Control:', accessControl);
            console.log('Required Grade:', requiredGrade);
            
            // Check if user is logged in
            if (!userPhone) {
                console.log('No user phone found - redirecting to login');
                window.location.href = '/login/index.html';
                return;
            }
            
            // If access is "free for all", allow access
            if (accessControl === 'free') {
                console.log('Free access enabled for all students');
                return;
            }
            
            // If access is "restricted", check if student purchased this session
            if (accessControl === 'restricted' && sessionNumber) {
                console.log('Restricted access - checking if student paid for session', sessionNumber);
                
                try {
                    // Call API to check subscription
                    const response = await fetch(`${window.API_BASE_URL}sessions.php?action=check-access&session_number=${sessionNumber}&phone=${encodeURIComponent(userPhone)}`);
                    const data = await response.json();
                    
                    console.log('Access check response:', data);
                    
                    if (!data.success || !data.hasAccess) {
                        console.log('Student does not have access to this session');
                        showAccessDenied();
                        return;
                    }
                    
                    console.log('Student has access to session', sessionNumber);
                } catch (error) {
                    console.error('Error checking access:', error);
                    // Continue anyway if API fails
                }
            }
        }
        
        function showAccessDenied() {
            document.body.innerHTML = `
                <div style="text-align: center; padding: 50px; font-family: Arial; background: #ffffff; min-height: 100vh;">
                    <div style="background: white; padding: 40px; border-radius: 10px; max-width: 500px; margin: 100px auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <h2 style="color: #d32f2f; margin-bottom: 20px;">🔒 Access Denied</h2>
                        <p style="color: #666; font-size: 16px; margin-bottom: 20px;">
                            This session requires a paid subscription. You don't have access to this lecture.
                        </p>
                        <p style="color: #999; font-size: 14px; margin-bottom: 30px;">
                            Please contact your instructor or purchase this session to view the content.
                        </p>
                        <a href="/senior3/physics/sessions/" style="display: inline-block; padding: 10px 20px; background: #008080; color: white; text-decoration: none; border-radius: 5px;">
                            Back to Sessions
                        </a>
                    </div>
                </div>
            `;
        }
        
        // Check access on page load
        window.addEventListener('load', checkUserAccess);
        
        // Updated fullscreen function for custom player
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


    </script>
</body>
</html>
