<?php
// views/student/player.php

if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit;
}

$materialId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($materialId <= 0) {
    die("Invalid material ID.");
}

// Fetch user info for watermark
require_once __DIR__ . '/../../../api/db.php';
global $pdo;
$stmtUser = $pdo->prepare("SELECT child_name FROM users WHERE id = ?");
$stmtUser->execute([$_SESSION['user_id']]);
$userRow = $stmtUser->fetch();
$watermarkText = ($userRow && !empty($userRow['child_name'])) ? $userRow['child_name'] . ' - ID:' . $_SESSION['user_id'] : 'Student ID:' . $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT title, type, file_path, subject_id FROM materials WHERE id = ?");
$stmt->execute([$materialId]);
$material = $stmt->fetch();

if (!$material) {
    die("المحتوى غير موجود.");
}

$title = $material['title'];
$type = $material['type'];
$filePath = $material['file_path'];

// Robust YouTube ID Extractor
$youtubeId = null;
if ($type === 'video') {
    preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/\s]{11})%i', $filePath, $match);
    $youtubeId = $match[1] ?? null;
}
$isYoutube = ($youtubeId !== null);

require_once __DIR__ . '/../../../api/Security.php';
$mediaToken = Security::generateMediaToken($materialId, $_SESSION['user_id']);
$streamUrl = "/api/media/stream?id=" . $materialId . "&token=" . urlencode($mediaToken) . "&cb=" . time();
if ($isYoutube) {
    $streamUrl = "https://www.youtube.com/embed/" . $youtubeId;
}

// Minimal header
?>
<div class="player-wrapper animate-fade-in bg-dark text-light" style="min-height: 80vh; border-radius: 1rem; position: relative;">
    <style>
        .player-wrapper {
            background-color: #0f0f13;
            color: white;
            user-select: none; /* Disable text selection */
        }
        .player-container {
            max-width: 1000px;
            margin: 2rem auto;
            border-radius: 20px;
            overflow: hidden;
            background: #1c1c24;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            position: relative;
        }
        
        /* Transparent overlay to block clicks on PDF/Images */
        .click-blocker {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10;
            background: transparent;
        }

        .video-error-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 20;
            background: rgba(0, 0, 0, 0.9);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 2rem;
            display: none;
        }

        video {
            width: 100%;
            border-radius: 20px 20px 0 0;
            background: #000;
        }
        
        embed, iframe {
            width: 100%;
            height: 80vh;
            border: none;
            background: #fff; /* PDF background */
        }

        .media-image {
            width: 100%;
            max-height: 80vh;
            object-fit: contain;
            pointer-events: none; /* Block right click / drag */
        }

        .player-header {
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .watermark {
            position: absolute;
            top: 10%;
            left: 10%;
            color: rgba(255, 255, 255, 0.65); /* Highly visible */
            text-shadow: 2px 2px 6px rgba(0,0,0,1); /* Extra strong outline */
            font-size: 2.2rem;
            font-weight: bold;
            pointer-events: none;
            z-index: 20;
            white-space: nowrap;
            animation: moveWatermark 25s linear infinite alternate;
        }

        @keyframes moveWatermark {
            0% { top: 5%; left: 5%; }
            25% { top: 5%; left: 60%; }
            50% { top: 70%; left: 60%; }
            75% { top: 70%; left: 5%; }
            100% { top: 40%; left: 30%; }
        }

        /* Fullscreen styles to ensure video fills container */
        .player-container:fullscreen {
            background: #000;
            display: block;
        }
        .player-container:fullscreen video {
            height: calc(100vh - 80px); /* Leave room for the header */
            width: 100%;
            object-fit: contain;
            border-radius: 0;
        }

        /* Hide download button in video controls (Chrome/Edge) */
        video::-webkit-media-controls-enclosure {
            overflow:hidden;
        }
        video::-webkit-media-controls-panel {
            width: calc(100% + 30px);
        }
    </style>

    <script>
        // IDM injects its download panel directly into the DOM (often as a div or iframe near the video)
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) { // Element node
                        // Check for common IDM signatures
                        const id = (node.id || '').toLowerCase();
                        const className = (node.className || '').toString().toLowerCase();
                        if (id.includes('idm') || className.includes('idm') || 
                            (node.tagName === 'DIV' && node.style.zIndex >= 999999) ||
                            (node.tagName === 'IFRAME' && node.src === '')) {
                            // High chance it's a download panel (like IDM)
                            node.style.display = 'none';
                            node.remove();
                        }
                        
                        // IDM also sometimes wraps things or adds specific attributes
                        if (node.hasAttribute('idm_id')) {
                            node.style.display = 'none';
                            node.remove();
                        }
                    }
                });
            });
        });
        
        // Start hunting!
        observer.observe(document.body, { childList: true, subtree: true });
        
        // Interval backup check just in case IDM sneaks it in without triggering mutations
        setInterval(() => {
            document.querySelectorAll('[id^="idm"], [class*="idm"], [idm_id]').forEach(el => {
                el.style.display = 'none';
                el.remove();
            });
            // IDM's specific panel often has inline styles with massive z-index and absolute positioning
            document.querySelectorAll('div').forEach(el => {
                if (el.style.zIndex == "2147483647" && el.style.position == "absolute") {
                    el.remove();
                }
            });
        }, 1000);
    </script>
    <!-- Watermark Container -->
    <div id="watermark-container">
        <button onclick="switchTab('materials&subject_id=<?= $material['subject_id'] ?? '' ?>')" class="btn btn-outline-light mt-4 rounded-pill">
            <i class="fa-solid fa-arrow-right me-2"></i> العودة للمواد
        </button>

        <div class="player-container animate-fade-in position-relative">
            
            <!-- Dynamic Watermark -->
            <div class="watermark" id="watermark">
                <?= htmlspecialchars($watermarkText) ?>
            </div>

            <!-- Error Overlay -->
            <div id="videoErrorOverlay" class="video-error-overlay">
                <i class="fa-solid fa-triangle-exclamation text-danger mb-3" style="font-size: 3rem;"></i>
                <h4 class="fw-bold">عذراً، تعذر تشغيل الفيديو</h4>
                <p class="text-muted" id="videoErrorMessage">الرجاء التأكد من اتصالك بالإنترنت، أو حاول تحديث الصفحة.</p>
                <button onclick="window.location.reload()" class="btn btn-outline-light mt-3">تحديث الصفحة</button>
            </div>

            <?php if ($type === 'video'): ?>
                <?php if ($isYoutube): ?>
                    <iframe src="<?= str_replace('watch?v=', 'embed/', htmlspecialchars($streamUrl)) ?>?rel=0&modestbranding=1" allowfullscreen></iframe>
                <?php else: ?>
                    <video id="secureVideo" class="secure-media" controls controlslist="nodownload nofullscreen noremoteplayback" disablePictureInPicture data-src="<?= str_replace('/api/media/', '/sw-media/', htmlspecialchars($streamUrl)) ?>" onerror="showVideoError()">
                        متصفحك لا يدعم تشغيل هذا الفيديو.
                    </video>
                <?php endif; ?>

            <?php elseif ($type === 'pdf'): ?>
                <div class="position-relative">
                    <embed data-src="<?= str_replace('/api/media/', '/sw-media/', htmlspecialchars($streamUrl)) ?>#toolbar=0&navpanes=0&scrollbar=0" type="application/pdf" class="secure-media">
                    <div class="click-blocker"></div> <!-- Blocks right clicking the PDF directly -->
                </div>
            <?php elseif ($type === 'image'): ?>
                <div class="position-relative text-center bg-black">
                    <img data-src="<?= str_replace('/api/media/', '/sw-media/', htmlspecialchars($streamUrl)) ?>" class="media-image secure-media" alt="Material">
                </div>
            <?php else: ?>
                <div class="p-5 text-center">
                    <i class="fa-solid fa-file-circle-exclamation fs-1 text-warning mb-3"></i>
                    <h4>هذا الملف لا يمكن عرضه مباشرة</h4>
                </div>
            <?php endif; ?>

            <div class="player-header">
                <h4 class="mb-0 fw-bold"><i class="fa-solid fa-shield-halved text-success me-2"></i> <?= htmlspecialchars($title) ?></h4>
                <div>
                    <button id="fullscreenBtn" class="btn btn-sm btn-outline-light me-3">
                        <i class="fa-solid fa-expand"></i> تكبير الشاشة
                    </button>
                    <span class="badge bg-danger bg-opacity-25 text-danger border border-danger rounded-pill px-3 py-2">
                        <i class="fa-solid fa-lock"></i> محتوى محمي
                    </span>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showVideoError() {
            const overlay = document.getElementById('videoErrorOverlay');
            if (overlay) {
                overlay.style.display = 'flex';
            }
        }

        // Extreme Anti-Theft JS
        
        // 1. Disable Right Click
        document.addEventListener('contextmenu', event => event.preventDefault());

        // 2. Disable Keyboard Shortcuts (Ctrl+S, F12, Ctrl+Shift+I, Ctrl+U)
        document.addEventListener('keydown', (e) => {
            if (e.key === 'F12' || 
               (e.ctrlKey && e.shiftKey && e.key === 'I') || 
               (e.ctrlKey && e.key === 'U') || 
               (e.ctrlKey && e.key === 'S')) {
                e.preventDefault();
                return false;
            }
        });

        // 3. Prevent dragging images/videos
        document.addEventListener('dragstart', (e) => {
            e.preventDefault();
        });

        // 4. Custom Fullscreen logic
        const fullscreenBtn = document.getElementById('fullscreenBtn');
        const playerContainer = document.querySelector('.player-container');
        if (fullscreenBtn && playerContainer) {
            fullscreenBtn.addEventListener('click', () => {
                if (!document.fullscreenElement) {
                    playerContainer.requestFullscreen().catch(err => {
                        console.log(`Error attempting to enable fullscreen: ${err.message}`);
                    });
                } else {
                    document.exitFullscreen();
                }
            });
            
            document.addEventListener('fullscreenchange', () => {
                if (document.fullscreenElement) {
                    fullscreenBtn.innerHTML = '<i class="fa-solid fa-compress"></i> تصغير الشاشة';
                } else {
                    fullscreenBtn.innerHTML = '<i class="fa-solid fa-expand"></i> تكبير الشاشة';
                }
            });
        }
    </script>
</div>
