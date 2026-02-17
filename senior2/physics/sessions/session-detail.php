<?php
require_once '../../../config/config.php';
require_once '../../../classes/DatabaseMongo.php';
require_once '../../../classes/Video.php';
require_once '../../../includes/video-utils.php';

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
$requiredGrade = $session->grade ?? 'senior2';
$requiredSubject = $session->subject ?? 'physics';
$targetCollection = 'senior2_physics';

// Update online_attendance for restricted sessions
if ($accessControl === 'restricted' && $sessionNumber) {
    $studentPhone = $_GET['student_phone'] ?? null;
    
    if ($studentPhone) {
        try {
            // Normalize phone number for consistent matching
            $phoneVariations = [
                $studentPhone,
                preg_replace('/^0/', '+20', $studentPhone),
                preg_replace('/^\+20/', '0', $studentPhone),
                preg_replace('/^20/', '+20', $studentPhone),
                preg_replace('/^\+/', '', $studentPhone)
            ];
            $phoneVariations = array_unique($phoneVariations);
            $sessionKey = 'session_' . $sessionNumber;
            
            $updated = false;
            // Target ONLY the correct collection for this subject
            $resultCount = $db->update($targetCollection, 
                [
                    'phone' => ['$in' => $phoneVariations],
                    $sessionKey . '.online_session' => true,
                    $sessionKey . '.online_attendance' => false
                ],
                ['$set' => [
                    $sessionKey . '.online_attendance' => true,
                    $sessionKey . '.online_attendance_completed_at' => date('Y-m-d\TH:i:s.v\Z')
                ]],
                ['multi' => false]
            );
            
            if ($resultCount > 0) {
                $updated = true;
                error_log("SUCCESS: Online attendance marked for $studentPhone in $targetCollection session $sessionNumber");
            }
            
            $logMsg = $updated ? "Attendance Marked: Success" : "Attendance Check: Already marked or Student not found";
            echo "<script>console.log('PHP Info: $logMsg');</script>";
            
        } catch (Exception $e) {
            error_log('Attendance Update error: ' . $e->getMessage());
        }
    }
}

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
            $videoUrlVal = null;
            $filePath = null;
            
            if (is_object($video)) {
                $videoId = $video->video_id ?? null;
                $videoSource = $video->source ?? null;
                $videoUrlVal = $video->url ?? null;
                $filePath = $video->file_path ?? null;
                $videoData['title'] = $video->title ?? 'Video';
                $videoData['description'] = $video->description ?? '';
            } elseif (is_array($video)) {
                $videoId = $video['video_id'] ?? null;
                $videoSource = $video['source'] ?? null;
                $videoUrlVal = $video['url'] ?? null;
                $filePath = $video['file_path'] ?? null;
                $videoData['title'] = $video['title'] ?? 'Video';
                $videoData['description'] = $video['description'] ?? '';
            }
            
            // If source is "upload" and we have a file_path, use it directly
            if ($videoSource === 'upload' && isset($filePath) && $filePath) {
                $videoUrlVal = '../../../uploads/videos/' . ltrim($filePath, '/');
            }
            // Fallback: Try to fetch from database using video_id
            elseif ($videoSource === 'upload' && $videoId && !$videoUrlVal) {
                try {
                    $videoRecord = $videoManager->getById($videoId);
                    if ($videoRecord && isset($videoRecord->video_file_path)) {
                        $videoUrlVal = '../../../uploads/videos/' . ltrim($videoRecord->video_file_path, '/');
                    }
                } catch (Exception $e) {
                    error_log("Error fetching video record: " . $e->getMessage());
                }
                
                // Last fallback: Direct ID-based path
                if (!$videoUrlVal) {
                    $videoUrlVal = '../../../uploads/videos/' . $videoId . '.mp4';
                }
            }
            
            // Normalize the video URL (handles Vimeo, YouTube, etc.)
            if ($videoUrlVal) {
                $normalized = VideoUtils::normalizeVideoUrl($videoUrlVal, $videoSource);
                $videoData['url'] = $normalized['url'];
                $videoData['source'] = $normalized['source'];
                $videoData['video_id'] = $normalized['video_id'];
            } else {
                $videoData['url'] = null;
                $videoData['source'] = 'unknown';
                $videoData['video_id'] = null;
            }
            
            $videos[] = $videoData;
        }
    }
}

// Fallback to single video URL if no videos array
if (empty($videos) && !empty($videoUrl)) {
    // Normalize the fallback URL properly
    $normalized = VideoUtils::normalizeVideoUrl($videoUrl, null);
    $videos[] = [
        'url' => $normalized['url'],
        'title' => $title ?? 'Video',
        'description' => '',
        'source' => $normalized['source'],
        'video_id' => $normalized['video_id'],
        'embed_type' => $normalized['embed_type'] ?? 'video'
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
    <title><?= htmlspecialchars($title) ?> | Study is Funny</title>
    
    <!-- Premium Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Style -->
    <link rel="stylesheet" href="../../../css/session-detail.css">
    <link rel="icon" type="image/png" href="../../../images/logo.png">
    
    <script src="../../../js/api-config.js"></script>
</head>
<body>
    <!-- Premium Header -->
    <header class="hero-header">
        <div class="logo-group">
            <img src="../../../images/logo.png" alt="Logo">
            <h2>Study is Funny</h2>
        </div>
        <div class="header-actions">
            <a href="https://wa.me/201000733148" target="_blank" class="btn-premium btn-ghost header-btn" title="Get Help">
                <i class="fab fa-whatsapp"></i> <span>Support</span>
            </a>
            <a href="../" class="btn-premium btn-primary header-btn">
                <i class="fas fa-arrow-left"></i> <span>Back</span>
            </a>
        </div>
    </header>

    <main class="app-container">
        <!-- Content Area -->
        <div class="player-section animate-fade">
            <!-- Professional Video Container with Custom Player -->
            <div class="modern-player-container">
                <?php
                // Prepare video data for the custom player using universal load() method
                $playerData = null;
                $currentVideo = !empty($videos) ? $videos[$currentVideoIndex] : null;
                
                if ($currentVideo && $currentVideo['url']) {
                    // Create normalized video data object for the player
                    $playerData = [
                        'url' => $currentVideo['url'],
                        'source' => $currentVideo['source'],
                        'video_id' => $currentVideo['video_id'],
                        'embed_type' => $currentVideo['embed_type'] ?? 'video'
                    ];
                }
                $playerDataJson = $playerData ? json_encode($playerData, JSON_HEX_QUOT | JSON_HEX_TAG) : 'null';
                ?>
                
                <!-- Include custom player HTML -->
                <?php include '../../../includes/custom-player.html'; ?>
                
                <!-- Store player data in window for initialization -->
                <script>
                    window.sessionVideoData = <?= $playerDataJson ?>;
                </script>
            </div>

            <!-- Player Quick Controls -->
            <div class="player-actions">
                <?php if ($meetingLink): ?>
                    <a href="<?= htmlspecialchars($meetingLink) ?>" target="_blank" class="btn-premium btn-ghost">
                        <i class="fas fa-video"></i> Live Meeting
                    </a>
                <?php endif; ?>
            </div>

            <!-- Content Details Card -->
            <div class="content-card">
                <span class="badge <?= $accessControl === 'free' ? 'badge-free' : 'badge-vip' ?>">
                    <i class="fas <?= $accessControl === 'free' ? 'fa-unlock' : 'fa-star' ?>"></i> 
                    <?= $accessControl === 'free' ? 'Free Access' : 'Premium Session' ?>
                </span>
                <h1 style="margin-top: 1rem;"><?= htmlspecialchars($currentVideo['title'] ?? $title) ?></h1>
                
                <div class="meta-info">
                    <span><i class="fas fa-calendar-alt"></i> <?= date('F j, Y') ?></span>
                    <span><i class="fas fa-layer-group"></i> <?= strtoupper($requiredGrade) ?> Physics</span>
                    <?php if ($sessionNumber): ?>
                        <span><i class="fas fa-hashtag"></i> Session #<?= $sessionNumber ?></span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($description)): ?>
                <div class="description-text">
                    <p><?= nl2br(htmlspecialchars($description)) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Professional Sidebar Playlist -->
        <aside class="playlist-sidebar animate-fade" style="animation-delay: 0.2s;">
            <div class="playlist-card">
                <div class="playlist-header">
                    <h4><i class="fas fa-list-ul"></i> Session Playlist</h4>
                    <small><?= count($videos) ?> Videos Available</small>
                </div>
                <div class="playlist-items">
                    <?php if (empty($videos)): ?>
                        <div style="padding: 2rem; text-align: center; color: var(--text-muted);">
                            No videos found in this session.
                        </div>
                    <?php else: ?>
                        <?php foreach ($videos as $index => $video): ?>
                            <a href="?id=<?= htmlspecialchars($sessionId) ?>&video=<?= $index ?>" 
                               class="playlist-item <?= ($index === $currentVideoIndex) ? 'active' : '' ?>">
                                <div class="video-thumb">
                                    <i class="fas <?= ($index === $currentVideoIndex) ? 'fa-play' : 'fa-lock' ?>"></i>
                                </div>
                                <div class="video-meta">
                                    <h4><?= htmlspecialchars($video['title']) ?></h4>
                                    <p>Part <?= $index + 1 ?></p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
    </main>

    <!-- Refined Access Control Overlay -->
    <div id="accessLoadingOverlay" class="access-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 99999; display: flex; flex-direction: column; align-items: center; justify-content: center;">
        <div style="width: 60px; height: 60px; border: 4px solid #f3f3f3; border-top: 4px solid var(--primary); border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
        <p style="margin-top: 1.5rem; color: var(--primary); font-weight: 600; letter-spacing: 1px;">AUTHENTICATING ACCESS...</p>
    </div>

    <script>
        // Check user access
        async function checkUserAccess() {
            const overlay = document.getElementById('accessLoadingOverlay');
            const userPhone = localStorage.getItem('userPhone');
            const sessionNumber = <?= $sessionNumber ? $sessionNumber : 'null' ?>;
            const accessControl = '<?= htmlspecialchars($accessControl) ?>';
            
            if (!userPhone) {
                window.location.href = '/login/index.html';
                return;
            }
            
            if (accessControl === 'free') {
                fadeOutOverlay();
                return;
            }
            
            if (accessControl === 'restricted' && sessionNumber) {
                try {
                    const grade = '<?= $requiredGrade ?>';
                    const subject = '<?= $requiredSubject ?>';
                    const response = await fetch(`${window.API_BASE_URL}sessions.php?action=check-access&session_number=${sessionNumber}&phone=${encodeURIComponent(userPhone)}&grade=${encodeURIComponent(grade)}&subject=${encodeURIComponent(subject)}`);
                    const data = await response.json();
                    
                    if (data.success && data.hasAccess) {
                        fadeOutOverlay();
                    } else if (data.success) {
                        showAccessDenied(data.message || "Your subscription has expired or is invalid for this session.", data.student);
                    } else {
                        showAccessDenied(data.message || "Error checking access.");
                    }
                } catch (error) {
                    console.error('Access check failed:', error);
                    showAccessDenied("Failed to verify access. Please check your internet connection and try again.");
                }
            } else {
                fadeOutOverlay();
            }
        }
        
        async function purchaseSession() {
            const userPhone = localStorage.getItem('userPhone');
            const sessionNumber = <?= $sessionNumber ? $sessionNumber : 'null' ?>;
            const grade = '<?= $requiredGrade ?>';
            const subject = '<?= $requiredSubject ?>';
            
            const btn = document.getElementById('purchaseBtn');
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<span class="loading-spinner"></span> Processing...';
            btn.disabled = true;

            try {
                const response = await fetch(`${window.API_BASE_URL}sessions.php?action=purchase-session&session_number=${sessionNumber}&phone=${encodeURIComponent(userPhone)}&grade=${encodeURIComponent(grade)}&subject=${encodeURIComponent(subject)}`);
                const data = await response.json();
                
                if (data.success) {
                    alert('Session purchased successfully! Page will reload.');
                    window.location.reload();
                } else {
                    alert(data.message || 'Purchase failed.');
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                }
            } catch (error) {
                alert('An error occurred during purchase. Please try again or contact support.');
                btn.innerHTML = originalContent;
                btn.disabled = false;
            }
        }

        function fadeOutOverlay() {
            const overlay = document.getElementById('accessLoadingOverlay');
            if (overlay) {
                overlay.style.opacity = '0';
                overlay.style.transition = 'opacity 0.5s ease';
                setTimeout(() => overlay.style.display = 'none', 500);
            }
        }
        
        function showAccessDenied(message, student = null) {
            let purchaseSection = '';
            
            if (student) {
                const balance = parseFloat(student.balance || 0);
                const cost = parseFloat(student.paymentAmount ?? 80);
                
                if (balance >= cost) {
                    purchaseSection = `
                        <div style="background: rgba(0, 128, 128, 0.05); border: 2px dashed #008080; border-radius: 15px; padding: 1.5rem; margin-bottom: 2rem;">
                            <div style="color: #008080; font-weight: bold; font-size: 1.2rem; margin-bottom: 0.5rem;">Quick Unlock</div>
                            <div style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;">
                                Your Balance: <b>${balance} EGP</b><br>
                                Session Cost: <b>${cost} EGP</b>
                            </div>
                            <button id="purchaseBtn" onclick="if(confirm('Spend ${cost} EGP to unlock this session?')) purchaseSession()" class="btn-premium btn-primary" style="width: 100%; justify-content: center;">
                                Purchase & Watch Now
                            </button>
                        </div>
                    `;
                } else {
                    purchaseSection = `
                        <div style="background: rgba(214, 48, 49, 0.05); border: 2px dashed #d63031; border-radius: 15px; padding: 1.5rem; margin-bottom: 2rem;">
                            <div style="color: #d63031; font-weight: bold; font-size: 1.2rem; margin-bottom: 0.5rem;">Insufficient Balance</div>
                            <div style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;">
                                Your Balance: <b>${balance} EGP</b><br>
                                Session Cost: <b>${cost} EGP</b>
                            </div>
                            <p style="color: #d63031; font-weight: 500; font-size: 0.9rem;">Please top up your balance to unlock this session.</p>
                        </div>
                    `;
                }
            }

            document.body.innerHTML = `
                <div style="display: flex; min-height: 100vh; align-items: center; justify-content: center; padding: 20px; background: #f8f9fa;">
                    <div class="content-card premium-denied animate-fade" style="max-width: 500px; text-align: center; padding: 2.5rem;">
                        <div style="font-size: 4rem; margin-bottom: 1.5rem;">🔒</div>
                        <h1 style="font-size: 1.8rem; color: #2d3436; margin-bottom: 1rem;">Content Restricted</h1>
                        <p style="color: var(--text-muted); margin-bottom: 2rem; font-size: 1rem; line-height: 1.6;">
                            ${message}
                        </p>
                        
                        ${purchaseSection}

                        <div style="display: flex; gap: 1rem; justify-content: center;">
                            <a href="../" class="btn-premium btn-ghost" style="flex: 1; justify-content: center;">Back</a>
                            <a href="https://wa.me/201000733148" class="btn-premium btn-primary" style="flex: 1; justify-content: center; background: #25D366; border-color: #25D366;">
                                Support
                            </a>
                        </div>
                    </div>
                </div>
            `;
        }
        
        async function checkUserAccess() {
            return new Promise((resolve, reject) => {
                const overlay = document.getElementById('accessLoadingOverlay');
                const userPhone = localStorage.getItem('userPhone');
                const sessionNumber = <?= $sessionNumber ? $sessionNumber : 'null' ?>;
                const accessControl = '<?= htmlspecialchars($accessControl) ?>';
                
                if (!userPhone) {
                    window.location.href = '/login/index.html';
                    reject('No user phone');
                    return;
                }
                
                if (accessControl === 'free') {
                    fadeOutOverlay();
                    resolve();
                    return;
                }
                
                if (accessControl === 'restricted' && sessionNumber) {
                    (async () => {
                        try {
                            const grade = '<?= $requiredGrade ?>';
                            const subject = '<?= $requiredSubject ?>';
                            const response = await fetch(`${window.API_BASE_URL}sessions.php?action=check-access&session_number=${sessionNumber}&phone=${encodeURIComponent(userPhone)}&grade=${encodeURIComponent(grade)}&subject=${encodeURIComponent(subject)}`);
                            const data = await response.json();
                            
                            if (data.success && data.hasAccess) {
                                fadeOutOverlay();
                                resolve();
                            } else if (data.success) {
                                showAccessDenied(data.message || "Your subscription has expired or is invalid for this session.", data.student);
                                reject('Access denied');
                            } else {
                                showAccessDenied(data.message || "Error checking access.");
                                reject('Access check failed');
                            }
                        } catch (error) {
                            console.error('Access check failed:', error);
                            showAccessDenied("Failed to verify access. Please check your internet connection and try again.");
                            reject(error);
                        }
                    })();
                } else {
                    fadeOutOverlay();
                    resolve();
                }
            });
        }
        
        function initializePlayer() {
            if (window.sessionVideoData) {
                // Wait for player to be ready, then load video using universal method
                setTimeout(() => {
                    if (window.customPlayer && window.sessionVideoData) {
                        window.customPlayer.load(window.sessionVideoData);
                    }
                }, 100);
            } else {
                // No video to play
                console.log('No video content available');
            }
        }
    
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Add student phone to URL if not already present
            const userPhone = localStorage.getItem('userPhone');
            const urlParams = new URLSearchParams(window.location.search);
            
            if (userPhone && !urlParams.has('student_phone')) {
                urlParams.set('student_phone', userPhone);
                const newUrl = window.location.pathname + '?' + urlParams.toString();
                window.location.href = newUrl;
                return;
            }
            
            // Check access and initialize player after access is verified
            checkUserAccess().then(() => {
                initializePlayer();
            }).catch(() => {
                console.error('Failed to verify access');
            });
        });
    </script>

    <style>
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</body>
</html>
