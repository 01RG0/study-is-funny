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
    
    // Fetch session data
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
$requiredGrade = $session->grade ?? 'senior2';

// Process videos array for multi-video support
$videos = [];
if (isset($session->videos) && is_array($session->videos) && count($session->videos) > 0) {
    foreach ($session->videos as $video) {
        $videoData = [];
        $videoId = null;
        $videoSource = null;
        $videoUrl = null;
        
        if (is_object($video)) {
            $videoId = $video->video_id ?? null;
            $videoSource = $video->source ?? null;
            $videoUrl = $video->url ?? null;
            $videoData['title'] = $video->title ?? 'Video';
            $videoData['description'] = $video->description ?? '';
        } elseif (is_array($video)) {
            $videoId = $video['video_id'] ?? null;
            $videoSource = $video['source'] ?? null;
            $videoUrl = $video['url'] ?? null;
            $videoData['title'] = $video['title'] ?? 'Video';
            $videoData['description'] = $video['description'] ?? '';
        }
        
        // If source is "upload" and we have a video_id, fetch the actual file path
        if ($videoSource === 'upload' && $videoId && !$videoUrl) {
            try {
                $videoRecord = $videoManager->getById($videoId);
                if ($videoRecord && isset($videoRecord->video_file_path)) {
                    $videoUrl = '/uploads/videos/' . ltrim($videoRecord->video_file_path, '/');
                }
            } catch (Exception $e) {
                error_log("Error fetching video record: " . $e->getMessage());
            }
        }
        
        $videoData['url'] = $videoUrl;
        $videoData['source'] = $videoSource;
        $videoData['video_id'] = $videoId;
        $videos[] = $videoData;
    }
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
</head>
<body>
    <h1><?= htmlspecialchars($title) ?></h1>
    
    <?php if ($currentVideo && isset($currentVideo['title'])): ?>
    <div class="video-info">
        <strong>Video <?= $currentVideoIndex + 1 ?> of <?= count($videos) ?>:</strong> <?= htmlspecialchars($currentVideo['title']) ?>
    </div>
    <?php endif; ?>
    
    <div class="video-container">
        <?php if ($videoUrl): ?>
            <?php 
                // Check if it's an iframe-based URL (like Bunny CDN)
                $isIframe = strpos($videoUrl, 'iframe') !== false || 
                           strpos($videoUrl, 'youtube') !== false || 
                           strpos($videoUrl, 'mediadelivery') !== false ||
                           strpos($videoUrl, 'embed') !== false;
            ?>
            <?php if ($isIframe): ?>
                <iframe id="videoFrame" src="<?= htmlspecialchars($videoUrl) ?>" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>
            <?php else: ?>
                <video id="videoPlayer" controls style="width: 100%; height: 100%;">
                    <source src="<?= htmlspecialchars($videoUrl) ?>" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            <?php endif; ?>
        <?php else: ?>
            <div class="video-placeholder">
                <div style="text-align: center;">
                    <div style="font-size: 48px; margin-bottom: 20px;">🎬</div>
                    <div>Session video coming soon</div>
                </div>
            </div>
        <?php endif; ?>
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

    <?php if ($currentVideo && isset($currentVideo['description']) && $currentVideo['description']): ?>
    <div class="description-box">
        <strong><?= htmlspecialchars($currentVideo['title']) ?>:</strong><br>
        <?= htmlspecialchars($currentVideo['description']) ?>
    </div>
    <?php endif; ?>

    <?php if ($description): ?>
    <div class="description-box">
        <strong>Session Description:</strong><br>
        <?= htmlspecialchars($description) ?>
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
                    const response = await fetch(`/api/sessions.php?action=check-access&session_number=${sessionNumber}&phone=${encodeURIComponent(userPhone)}`);
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
                        <a href="/senior2/physics/sessions/" style="display: inline-block; padding: 10px 20px; background: #008080; color: white; text-decoration: none; border-radius: 5px;">
                            Back to Sessions
                        </a>
                    </div>
                </div>
            `;
        }
        
        // Check access on page load
        window.addEventListener('load', checkUserAccess);
        
        function toggleFullscreen() {
            const videoContainer = document.querySelector('.video-container');
            const videoPlayer = document.getElementById('videoPlayer');
            const videoFrame = document.getElementById('videoFrame');
            
            if (document.fullscreenElement) {
                document.exitFullscreen();
            } else {
                if (videoPlayer) {
                    videoPlayer.requestFullscreen().catch(err => {
                        videoContainer.requestFullscreen();
                    });
                } else if (videoFrame) {
                    videoContainer.requestFullscreen();
                }
            }
        }
    </script>
</body>
</html>
