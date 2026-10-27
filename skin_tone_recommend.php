<?php
session_start();
// header.php will be included in the body
?>
<!DOCTYPE html>
<html lang="<?php echo isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($lang['skin_tone_finder']) ? $lang['skin_tone_finder'] : 'Skin Tone Finder'; ?> | StyleMate</title>
    
    <!-- Standard Site Assets -->
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <!-- MediaPipe -->
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/drawing_utils"></script>
    <!-- Face API (Includes TensorFlow.js) -->
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    
    <style>
        /* Fix for fixed header overlapping content */
        body {
            padding-top: 140px; /* Space for fixed header */
        }

        :root {
            /* Remapped to Standard Black/White Theme */
            --skin-50: #fcfcfc;
            --skin-100: #f5f5f5;
            --skin-200: #e5e5e5;
            --skin-300: #d4d4d4;
            --skin-500: #000000; /* Primary Black */
            --skin-600: #333333; /* Dark Gray */
            --skin-700: #111111; /* Almost Black */
            --skin-800: #000000; /* Pure Black */
            
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 10px 20px rgba(0,0,0,0.08);
            --shadow-lg: 0 20px 40px rgba(0,0,0,0.12);
            --shadow-premium: 0 30px 60px rgba(0,0,0,0.05);
            
            --radius-lg: 4px;
            --radius-md: 4px;
            --radius-sm: 4px;
            --radius-full: 9999px;
        }

        /* Preserve existing inline styles */
        @keyframes scanline {
            0% { top: 0%; opacity: 0; }
            50% { opacity: 1; }
            100% { top: 100%; opacity: 0; }
        }
        .scanning-line {
            position: absolute;
            width: 100%;
            height: 4px;
            background: linear-gradient(to bottom, transparent, var(--skin-500), transparent);
            box-shadow: 0 0 15px var(--skin-500);
            z-index: 10;
            animation: scanline 3s linear infinite;
            display: none;
        }
        .face-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }
        .gender-btn:hover {
            border-color: var(--skin-500) !important;
            background: var(--skin-50) !important;
            transform: translateY(-5px);
        }
        .product-card-ai:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
        }
        .filter-tab {
            background: var(--skin-200);
            color: var(--skin-700);
            border: none;
            padding: 10px 20px;
            border-radius: var(--radius-full);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .filter-tab.active {
            background: var(--skin-500) !important;
            color: #fff !important;
            box-shadow: var(--shadow-md);
        }
        .skin-tone-hero {
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
            padding: 100px 40px;
            border-radius: 24px;
            margin-bottom: 60px;
            text-align: center;
            color: #fff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .analyzer-card {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.08);
            padding: 60px 40px;
            border: 1px solid rgba(0,0,0,0.03);
            transition: all 0.4s ease;
        }
        
        /* New Process Steps */
        .steps-container {
            display: flex;
            justify-content: center;
            gap: 50px;
            margin-bottom: 80px;
            flex-wrap: wrap;
            padding: 0 20px;
        }
        .step-item {
            text-align: center;
            max-width: 280px;
            position: relative;
        }
        .step-icon {
            width: 70px;
            height: 70px;
            background: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 28px;
            color: var(--skin-500);
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            border: 1px solid var(--skin-100);
            transition: transform 0.3s ease;
        }
        .step-item:hover .step-icon {
            transform: translateY(-5px);
            color: #000;
            border-color: #000;
        }
        .step-title {
            font-weight: 800;
            margin-bottom: 10px;
            font-size: 18px;
            color: var(--skin-800);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .step-desc {
            font-size: 14px;
            color: var(--skin-600);
            line-height: 1.6;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .analyzer-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        .upload-area {
            border: 2px dashed var(--skin-300);
            border-radius: var(--radius-md);
            padding: 50px 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--skin-50);
            text-align: center;
        }
        .upload-area:hover {
            border-color: var(--skin-500);
            background: var(--skin-100);
        }
        .camera-icon-circle {
            width: 80px;
            height: 80px;
            background: var(--skin-800);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: var(--shadow-md);
        }
        .btn-premium {
            background: var(--skin-800);
            color: #fff;
            border: none;
            padding: 15px 40px;
            border-radius: var(--radius-full);
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .btn-premium:hover {
            background: var(--skin-600);
            transform: scale(1.02);
            box-shadow: var(--shadow-md);
        }
        .btn-capture {
            background: var(--skin-500);
            color: #fff;
            border: none;
            padding: 15px;
            border-radius: var(--radius-sm);
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        .btn-capture:hover {
            background: var(--skin-600);
            box-shadow: var(--shadow-md);
        }

        /* Outfit Combination Styles */
        .outfit-section {
            margin-top: 60px;
            padding-top: 40px;
            border-top: 1px solid var(--skin-200);
        }
        .outfit-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        .outfit-card {
            background: #fff;
            border-radius: 24px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.03);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .outfit-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
        }
        .outfit-items {
            display: flex;
            gap: 15px;
            margin: 20px 0;
            align-items: center;
            justify-content: center;
        }
        .outfit-item-single {
            flex: 1;
            text-align: center;
            min-width: 0; /* Allow shrinking */
        }
        .outfit-item-single img {
            width: 100%;
            height: 200px;
            object-fit: contain;
            background: #fdfdfd;
            padding: 12px;
            border-radius: 16px;
            margin-bottom: 12px;
            border: 1px solid #f0f0f0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .outfit-item-single:hover img {
            transform: translateY(-3px);
            background: #fff;
            box-shadow: 0 8px 20px rgba(0,0,0,0.06);
        }
        .outfit-plus {
            font-size: 20px;
            font-weight: 300;
            color: #ccc;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f8f8;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .outfit-badge {
            display: inline-block;
            background: var(--skin-800);
            color: #fff;
            padding: 5px 15px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <?php require_once 'header.php'; ?>

    <div class="skin-tone-container" style="max-width: 1200px; margin: 0 auto; padding: 40px 20px;">
        <div class="skin-tone-hero">
            <div style="position: absolute; top: -50px; right: -50px; width: 300px; height: 300px; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%); border-radius: 50%;"></div>
            <div style="position: absolute; bottom: -50px; left: -50px; width: 250px; height: 250px; background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, rgba(255,255,255,0) 70%); border-radius: 50%;"></div>
            
            <h1 style="font-size: 48px; margin-bottom: 25px; color: #fff; letter-spacing: -1px;"><?php echo isset($lang['skin_tone_hero_title']) ? $lang['skin_tone_hero_title'] : 'Discover Your Perfect Colors'; ?></h1>
            <p style="color: rgba(255,255,255,0.8); font-size: 20px; max-width: 650px; margin: 0 auto; line-height: 1.6; font-weight: 300; font-family: 'Plus Jakarta Sans', sans-serif;"><?php echo isset($lang['skin_tone_hero_desc']) ? $lang['skin_tone_hero_desc'] : 'Let AI analyze your unique skin tone to recommend the most flattering colors and styles for you.'; ?></p>
        </div>

        <!-- Process Steps -->
        <div class="steps-container">
            <div class="step-item">
                <div class="step-icon"><i class='bx bx-camera'></i></div>
                <div class="step-title">1. Activate Camera</div>
                <div class="step-desc">Enable your camera in a well-lit room for the best results.</div>
            </div>
            <div class="step-item">
                <div class="step-icon"><i class='bx bx-scan'></i></div>
                <div class="step-title">2. AI Analysis</div>
                <div class="step-desc">Our smart technology detects your unique skin undertones.</div>
            </div>
            <div class="step-item">
                <div class="step-icon"><i class='bx bxs-t-shirt'></i></div>
                <div class="step-title">3. Get Matched</div>
                <div class="step-desc">Instantly see clothing colors that make you shine.</div>
            </div>
        </div>

        <div id="analysis-options" style="display: grid; grid-template-columns: 1fr; max-width: 700px; margin: 0 auto 80px; gap: 40px; align-items: start;">

            <div class="analyzer-card camera-card">
                <div id="camera-init-section">
                    <div class="section-title" style="text-align: center; margin-bottom: 40px;">
                        <h2 style="font-size: 32px; margin-top: 5px; letter-spacing: -0.5px;"><?php echo isset($lang['live_analysis']) ? $lang['live_analysis'] : 'Live Camera Analysis'; ?></h2>
                        <p style="color: var(--skin-600); margin-top: 10px;">Ready to find your perfect match?</p>
                    </div>
                    <div style="background: var(--skin-50); border-radius: var(--radius-md); padding: 40px; text-align: center; border: 1px solid var(--skin-200);">
                        <div class="camera-icon-circle">
                            <i class='bx bx-camera' style="font-size: 32px; color: #fff;"></i>
                        </div>
                        <p style="color: var(--skin-700); font-size: 14px; margin-bottom: 25px; line-height: 1.6; font-family: 'Plus Jakarta Sans', sans-serif;"><?php echo isset($lang['camera_desc']) ? $lang['camera_desc'] : 'Use your device camera for real-time skin tone detection.'; ?></p>
                        <button id="open-camera-btn" class="btn-premium">
                            <i class='bx bx-play-circle' style="font-size: 20px;"></i> <?php echo isset($lang['activate_camera']) ? $lang['activate_camera'] : 'Activate Camera'; ?>
                        </button>
                    </div>
                </div>

                <div id="camera-active-section" style="display: none;">
        <div style="position: relative; border-radius: var(--radius-md); overflow: hidden; background: #000; margin-bottom: 20px; box-shadow: var(--shadow-lg);">
            <video id="video" width="100%" height="auto" autoplay playsinline style="transform: scaleX(-1);"></video>
            <div class="scanning-line"></div>
            <canvas id="face-mesh-canvas" class="face-overlay"></canvas>
            <div id="camera-loading" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; text-align: center; display: none;">
                <i class='bx bx-loader-alt bx-spin' style="font-size: 40px;"></i>
                <p style="margin-top: 10px;">Starting Camera...</p>
            </div>
        </div>
        <div style="display: flex; gap: 10px;">
                        <button id="capture-btn" class="btn-capture" style="flex: 2;">
                            <i class='bx bx-camera-movie'></i> <?php echo isset($lang['start_ai_scan']) ? $lang['start_ai_scan'] : 'Start AI Scan'; ?>
                        </button>
                        <button id="close-camera-btn" style="flex: 1; padding: 15px; border-radius: var(--radius-sm); background: var(--skin-200); color: var(--skin-800); border: none; font-weight: 600; cursor: pointer;">
                            <?php echo isset($lang['cancel']) ? $lang['cancel'] : 'Cancel'; ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="analysis-status" style="display: none; text-align: center; padding: 80px 0;">
            <div class="loader-container" style="position: relative; width: 100px; height: 100px; margin: 0 auto 30px;">
                <div class="loader-ring" style="position: absolute; width: 100%; height: 100%; border: 4px solid var(--skin-200); border-top-color: var(--skin-500); border-radius: 50%; animation: spin 1s cubic-bezier(0.55, 0.1, 0.15, 0.9) infinite;"></div>
            </div>
            <h2 style="font-size: 28px; margin-bottom: 10px;"><?php echo isset($lang['analyzing']) ? $lang['analyzing'] : 'Analyzing Skin Profile...'; ?></h2>
            <p style="color: var(--skin-500); font-family: 'Plus Jakarta Sans', sans-serif;"><?php echo isset($lang['analyzing_desc']) ? $lang['analyzing_desc'] : 'AI is processing your skin undertones and lighting conditions.'; ?></p>
        </div>

        <canvas id="canvas" style="display: none;"></canvas>

        <div id="results-section" style="display: none; animation: fadeInUp 0.8s ease;">
            <div class="results-header" style="margin-bottom: 40px; text-align: left; display: flex; justify-content: space-between; align-items: flex-end;">
                <div>
                    <span style="color: var(--skin-500); font-weight: 800; font-size: 12px; letter-spacing: 2px; text-transform: uppercase; font-family: 'Plus Jakarta Sans', sans-serif;"><?php echo isset($lang['analysis_complete']) ? $lang['analysis_complete'] : 'Analysis Complete'; ?></span>
                    <h2 style="font-size: 36px; margin-top: 5px;"><?php echo isset($lang['style_profile_title']) ? $lang['style_profile_title'] : 'Your Personal Style Profile'; ?></h2>
                </div>
                <button onclick="sessionStorage.removeItem('skinToneResults'); location.reload()" style="background: var(--skin-200); border: none; padding: 12px 25px; border-radius: var(--radius-sm); font-weight: 700; cursor: pointer; color: var(--skin-800); display: flex; align-items: center; gap: 8px; font-family: 'Plus Jakarta Sans', sans-serif;">
                    <i class='bx bx-refresh' style="font-size: 20px;"></i> <?php echo isset($lang['new_analysis']) ? $lang['new_analysis'] : 'New Analysis'; ?>
                </button>
            </div>

            <div class="result-grid" style="display: grid; grid-template-columns: 350px 1fr; gap: 40px; margin-bottom: 60px;">
                <div class="profile-card" style="background: white; border-radius: var(--radius-lg); padding: 30px; box-shadow: var(--shadow-premium); border: 1px solid var(--skin-200);">
                    <div style="display: flex; align-items: center; margin-bottom: 25px;">
                        <div id="skin-color-preview" style="width: 60px; height: 60px; border-radius: var(--radius-sm); box-shadow: var(--shadow-sm); margin-right: 20px;"></div>
                        <div style="text-align: left;">
                            <div style="font-size: 12px; color: var(--skin-500); text-transform: uppercase; letter-spacing: 1px; font-weight: 700; font-family: 'Plus Jakarta Sans', sans-serif;"><?php echo isset($lang['detected_profile']) ? $lang['detected_profile'] : 'Detected Profile'; ?></div>
                            <div id="detected-tone" style="font-size: 24px; font-weight: 800; color: var(--skin-800); font-family: 'Playfair Display', serif;">...</div>
                        </div>
                    </div>
                    
                    <div id="style-insights" style="background: var(--skin-50); border-radius: var(--radius-md); padding: 25px; border: 1px solid var(--skin-200);">
                        <h3 style="font-size: 16px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                            <i class='bx bxs-magic-wand' style="color: var(--skin-500);"></i> <?php echo isset($lang['ai_insights']) ? $lang['ai_insights'] : 'AI Fashion Insights'; ?>
                        </h3>
                        <ul id="insight-list" style="list-style: none; padding: 0; margin: 0; font-size: 14px; color: var(--skin-700); line-height: 1.8; font-family: 'Plus Jakarta Sans', sans-serif;">
                        </ul>
                    </div>
                </div>

                <div class="recommendations-area">
                    <!-- New Combinations Section -->
                    <div id="outfits-container-wrapper" style="display: none; margin-bottom: 50px;">
                        <h3 style="font-size: 20px; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; font-family: 'Plus Jakarta Sans', sans-serif;">
                            <i class='bx bxs-layout' style="color: var(--skin-500);"></i> Recommended Style Combinations
                        </h3>
                        <div id="outfits-container" class="outfit-grid">
                        </div>
                    </div>

                    <h3 style="font-size: 20px; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; font-family: 'Plus Jakarta Sans', sans-serif;">
                        <i class='bx bxs-grid-alt' style="color: var(--skin-500);"></i> Individual Curated Picks
                    </h3>
                    <div id="recommendations-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 30px;">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    let selectedGender = null;
    let detectedGender = 'female'; // Default
    let faceMesh = null;
    let camera = null;
    let isScanning = false;
    let lastResults = null;

    // Load FaceAPI Models
    async function loadFaceAPI() {
        const btn = document.getElementById('open-camera-btn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Loading AI Models...';
        
        try {
            // Try loading from local models folder first (Best for reliability)
            const modelPath = './models';
            await faceapi.nets.tinyFaceDetector.loadFromUri(modelPath);
            await faceapi.nets.ageGenderNet.loadFromUri(modelPath);
            console.log("FaceAPI Models Loaded from Local Storage");
            
            btn.disabled = false;
            btn.innerHTML = originalText;
        } catch (e) {
            console.warn("Local model load failed, trying CDN fallback...", e);
            try {
                // Fallback to GitHub Raw CDN
                const cdnPath = 'https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights';
                await faceapi.nets.tinyFaceDetector.loadFromUri(cdnPath);
                await faceapi.nets.ageGenderNet.loadFromUri(cdnPath);
                console.log("FaceAPI Models Loaded from CDN");
                
                btn.disabled = false;
                btn.innerHTML = originalText;
            } catch (cdnError) {
                console.error("All model sources failed", cdnError);
                btn.innerHTML = '<i class="bx bx-error"></i> AI Load Error (Refresh)';
            }
        }
    }
    // Check for saved results on load - Optimized to prevent flicker
    window.addEventListener('DOMContentLoaded', () => {
        const saved = sessionStorage.getItem('skinToneResults');
        if (saved) {
            try {
                const data = JSON.parse(saved);
                
                // Restore UI immediately
                document.getElementById('camera-init-section').style.display = 'none';
                document.getElementById('analysis-options').style.display = 'none';
                
                // Populate data
                document.getElementById('detected-tone').innerText = data.tone;
                document.getElementById('skin-color-preview').style.backgroundColor = `rgb(${data.rgb.r}, ${data.rgb.g}, ${data.rgb.b})`;
                
                // Show results
                document.getElementById('results-section').style.display = 'block';
                
                // Load insights
                const insights = getAIInsights(data.tone, data.gender);
                document.getElementById('insight-list').innerHTML = insights.map(i => `<li><i class='bx bx-check-circle' style="color: var(--skin-500); margin-right: 8px;"></i>${i}</li>`).join('');
                
                // Initialize gender buttons
                 document.querySelectorAll('.gender-switch-btn').forEach(btn => {
                    btn.style.background = 'white';
                    btn.style.color = 'var(--skin-800)';
                    btn.style.borderColor = 'var(--skin-300)';
                    btn.style.fontWeight = '400';
                });
                const activeId = (data.gender === 'male' || data.gender === 'boy' || data.gender === 'men') ? 'btn-male' : 'btn-female';
                const activeBtn = document.getElementById(activeId);
                if(activeBtn) {
                    activeBtn.style.background = 'var(--skin-800)';
                    activeBtn.style.color = 'white';
                    activeBtn.style.borderColor = 'var(--skin-800)';
                    activeBtn.style.fontWeight = '600';
                }
                
                // Set global gender
                detectedGender = data.gender;
                
                // Use cached recommendations if available (Instant load)
                if (data.recommendations && data.recommendations.length > 0) {
                    window.allRecs = data.recommendations;
                    window.allOutfits = data.outfits || [];
                    renderRecs(window.allRecs);
                    renderOutfits(window.allOutfits);
                } else {
                    fetchAIRecommendations(data.tone, data.gender);
                }

                // Skip loading FaceAPI models if we already have results
                return; 
            } catch(e) {
                console.error("Error restoring state", e);
                sessionStorage.removeItem('skinToneResults');
            }
        }
        
        // Only load FaceAPI if no saved results found
        loadFaceAPI();
    });

    function selectGender(gender) {
        selectedGender = gender;
        document.getElementById('gender-selection').style.display = 'none';
        document.getElementById('analysis-options').style.display = 'grid';
    }

    async function initFaceMesh() {
        faceMesh = new FaceMesh({locateFile: (file) => {
            return `https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/${file}`;
        }});

        faceMesh.setOptions({
            maxNumFaces: 1,
            refineLandmarks: true,
            minDetectionConfidence: 0.5,
            minTrackingConfidence: 0.5
        });

        faceMesh.onResults((results) => {
            lastResults = results;
            const canvasElement = document.getElementById('face-mesh-canvas');
            const canvasCtx = canvasElement.getContext('2d');
            const videoElement = document.getElementById('video');

            canvasElement.width = videoElement.videoWidth;
            canvasElement.height = videoElement.videoHeight;

            canvasCtx.save();
            canvasCtx.clearRect(0, 0, canvasElement.width, canvasElement.height);
            
            if (results.multiFaceLandmarks && results.multiFaceLandmarks.length > 0) {
                drawConnectors(canvasCtx, results.multiFaceLandmarks[0], FACEMESH_TESSELATION,
                              {color: '#C0C0C070', lineWidth: 1});
                
                const landmarks = results.multiFaceLandmarks[0];
                [10, 234, 454].forEach(idx => {
                    const pt = landmarks[idx];
                    canvasCtx.beginPath();
                    canvasCtx.arc(pt.x * canvasElement.width, pt.y * canvasElement.height, 5, 0, 2 * Math.PI);
                    canvasCtx.fillStyle = 'var(--skin-500)';
                    canvasCtx.fill();
                });
            }
            canvasCtx.restore();
        });
    }

    document.getElementById('open-camera-btn').addEventListener('click', async () => {
        document.getElementById('camera-init-section').style.display = 'none';
        document.getElementById('camera-active-section').style.display = 'block';
        document.getElementById('camera-loading').style.display = 'block';
        
        const videoElement = document.getElementById('video');
        
        try {
            await initFaceMesh();

            camera = new Camera(videoElement, {
                onFrame: async () => {
                    await faceMesh.send({image: videoElement});
                    document.getElementById('camera-loading').style.display = 'none';
                },
                width: 640,
                height: 480
            });
            await camera.start();
        } catch (e) {
            console.error("Camera start error:", e);
            alert("Unable to access camera. Please allow camera permissions and ensure you are using HTTPS or localhost.");
            document.getElementById('camera-loading').style.display = 'none';
            document.getElementById('close-camera-btn').click();
        }
    });

    document.getElementById('capture-btn').addEventListener('click', () => {
        isScanning = true;
        document.querySelector('.scanning-line').style.display = 'block';
        // Reduced simulated scanning time for better UX
        setTimeout(() => performAIScan(), 800);
    });

    document.getElementById('close-camera-btn').addEventListener('click', () => {
        if (camera) {
            camera.stop();
        }
        document.getElementById('camera-active-section').style.display = 'none';
        document.getElementById('camera-init-section').style.display = 'block';
    });

    async function performAIScan() {
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0);

        // FaceAPI Gender Detection
        try {
            const detections = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions()).withAgeAndGender();
            if (detections) {
                let g = detections.gender;
                const age = detections.age;
                // Age-based classification for Boys/Girls
                if (g === 'male' && age < 16) g = 'boy';
                if (g === 'female' && age < 16) g = 'girl';
                
                detectedGender = g;
                console.log("Detected Gender:", detectedGender, "Age:", age);
            }
        } catch (e) {
            console.warn("Gender detection failed, using default", e);
        }

        let r=0, g=0, b=0;
        let validSamples = 0;
        
        if (lastResults && lastResults.multiFaceLandmarks && lastResults.multiFaceLandmarks.length > 0) {
            const landmarks = lastResults.multiFaceLandmarks[0];
            // Extended sampling points: Forehead(10), Cheeks(234, 454), Chin(152), Nose Bridge(6)
            const pointIndices = [10, 234, 454, 152, 6, 109, 338]; 
            
            pointIndices.forEach(idx => {
            const pt = landmarks[idx];
            if (pt.x >= 0 && pt.x <= 1 && pt.y >= 0 && pt.y <= 1) {
                const x = Math.floor(pt.x * canvas.width);
                const y = Math.floor(pt.y * canvas.height);
                
                // Sample a small 3x3 area for more robust color sampling
                const radius = 1;
                let areaR = 0, areaG = 0, areaB = 0, areaCount = 0;
                
                try {
                    const imageData = ctx.getImageData(x - radius, y - radius, 3, 3).data;
                    for (let i = 0; i < imageData.length; i += 4) {
                        areaR += imageData[i];
                        areaG += imageData[i+1];
                        areaB += imageData[i+2];
                        areaCount++;
                    }
                    if (areaCount > 0) {
                        r += (areaR / areaCount);
                        g += (areaG / areaCount);
                        b += (areaB / areaCount);
                        validSamples++;
                    }
                } catch(e) {
                    // Fallback to single pixel if edge case
                    const pixel = ctx.getImageData(x, y, 1, 1).data;
                    r += pixel[0]; g += pixel[1]; b += pixel[2];
                    validSamples++;
                }
            }
        });
        }
        
        // Fallback to center crop if no face landmarks or few samples
        if (validSamples < 3) {
             const pixel = ctx.getImageData(canvas.width/2, canvas.height/2, 1, 1).data;
             r = pixel[0]; g = pixel[1]; b = pixel[2];
             validSamples = 1;
        } else {
            r /= validSamples; g /= validSamples; b /= validSamples;
        }
        
        // Convert to LAB for better analysis
        const lab = rgb2lab([r, g, b]);
        
        // Lighting Check
        if (lab[0] < 20) {
            alert("Lighting is too dark. Please move to a brighter area for accurate results.");
            return; // Stop processing
        } else if (lab[0] > 90) {
            alert("Lighting is too bright/washed out. Please avoid direct strong light.");
            return;
        }

        const tone = classifySkinTone(r, g, b);
        showResults(tone, {r, g, b}, detectedGender);
    }

    // Convert RGB to LAB color space
    function rgb2lab(rgb) {
        let r = rgb[0] / 255, g = rgb[1] / 255, b = rgb[2] / 255;
        r = (r > 0.04045) ? Math.pow((r + 0.055) / 1.055, 2.4) : r / 12.92;
        g = (g > 0.04045) ? Math.pow((g + 0.055) / 1.055, 2.4) : g / 12.92;
        b = (b > 0.04045) ? Math.pow((b + 0.055) / 1.055, 2.4) : b / 12.92;
        
        let x = (r * 0.4124 + g * 0.3576 + b * 0.1805) / 0.95047;
        let y = (r * 0.2126 + g * 0.7152 + b * 0.0722) / 1.00000;
        let z = (r * 0.0193 + g * 0.1192 + b * 0.9505) / 1.08883;
        
        x = (x > 0.008856) ? Math.pow(x, 1/3) : (7.787 * x) + 16/116;
        y = (y > 0.008856) ? Math.pow(y, 1/3) : (7.787 * y) + 16/116;
        z = (z > 0.008856) ? Math.pow(z, 1/3) : (7.787 * z) + 16/116;
        
        return [(116 * y) - 16, 500 * (x - y), 200 * (y - z)];
    }

    function classifySkinTone(r, g, b) {
        // Use ITA (Individual Typology Angle) for scientific skin tone classification
        let lab = rgb2lab([r, g, b]);
        let L = lab[0];
        const B = lab[2];
        
        // Indoor Lighting Compensation: Webcams often underexpose faces.
        // If L is low, we apply a progressive boost to bring it closer to 
        // a standard exposure level for analysis.
        if (L < 60) {
            const boost = (60 - L) * 0.4; // Boost L by 40% of the deficit
            L += boost;
        }

        // ITA Calculation: arctan((L-50)/b) * 180/PI
        const ita = (Math.atan2(L - 50, B) * 180) / Math.PI;
        
        console.log("Skin Analysis - Original RGB:", r, g, b, "LAB:", lab, "Compensated L:", L, "ITA:", ita);

        // Adjusted ITA Classifications for real-world webcam conditions
        // These thresholds are shifted to compensate for typical indoor lighting
        if (ita > 40) return 'Fair';      
        if (ita > 25) return 'Light';     
        if (ita > 10) return 'Medium';    
        if (ita > -10) return 'Tan';       
        return 'Deep';                    
    }

    async function showResults(tone, rgb, gender) {
        // Save to Session Storage
        sessionStorage.setItem('skinToneResults', JSON.stringify({
            tone: tone,
            rgb: rgb,
            gender: gender,
            timestamp: new Date().getTime()
        }));

        document.getElementById('analysis-options').style.display = 'none';
        document.getElementById('analysis-status').style.display = 'block';
        document.querySelector('#analysis-status h2').innerText = "Processing AI Model...";
        document.querySelector('#analysis-status p').innerText = `Identifying gender and matching ${tone} skin tone patterns...`;
        
        // Reduced processing wait time
        await new Promise(r => setTimeout(r, 1000));
        
        document.getElementById('analysis-status').style.display = 'none';
        document.getElementById('results-section').style.display = 'block';
        
        document.getElementById('detected-tone').innerText = tone;
        document.getElementById('skin-color-preview').style.backgroundColor = `rgb(${rgb.r}, ${rgb.g}, ${rgb.b})`;
        
        const insights = getAIInsights(tone, gender);
        document.getElementById('insight-list').innerHTML = insights.map(i => `<li><i class='bx bx-check-circle' style="color: var(--skin-500); margin-right: 8px;"></i>${i}</li>`).join('');
        
        fetchAIRecommendations(tone, gender);
    }

    function getAIInsights(tone, gender) {
        const genderText = gender ? (gender.charAt(0).toUpperCase() + gender.slice(1)) : 'Detected';
        const specific = {
            'Fair': ['Cool undertones detected.', 'Jewel tones will make your features pop.', 'Avoid overly pale pastels.'],
            'Light': ['Neutral-warm balance found.', 'Earth tones and soft blues are perfect.', 'Gold accessories recommended.'],
            'Medium': ['Warm olive undertones identified.', 'Rich oranges and deep greens suit you.', 'Mixed metal jewelry works great.'],
            'Tan': ['Golden radiance detected.', 'White and bright turquoises look stunning.', 'Bold patterns complement your tone.'],
            'Deep': ['Rich cool-deep profile.', 'Silver and bright fuchsia are your power colors.', 'High contrast outfits recommended.']
        };
        return [`Subject identified as ${genderText}.`, `Skin profile: ${tone}`, ...(specific[tone] || [])];
    }

    async function fetchAIRecommendations(tone, gender) {
        const container = document.getElementById('recommendations-container');
        container.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: var(--skin-500);">Loading AI Curated Picks...</p>';
        try {
            const response = await fetch(`get_skin_tone_recommendations.php?skin_tone=${tone}&gender=${gender}`);
            const data = await response.json();
            if (data.success) {
                window.allRecs = data.recommendations;
                window.allOutfits = data.outfits || [];
                
                // Cache recommendations to sessionStorage to prevent reloading on back navigation
                const saved = sessionStorage.getItem('skinToneResults');
                if (saved) {
                    try {
                        const parsed = JSON.parse(saved);
                        parsed.recommendations = data.recommendations;
                        parsed.outfits = data.outfits;
                        sessionStorage.setItem('skinToneResults', JSON.stringify(parsed));
                    } catch (e) { console.error("Failed to cache recommendations", e); }
                }

                renderRecs(window.allRecs);
                renderOutfits(window.allOutfits);
            } else {
                container.innerHTML = `<p style="grid-column: 1/-1; text-align: center; color: var(--skin-500);">Error: ${data.error}</p>`;
            }
        } catch (e) {
            container.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: var(--skin-500);">Error loading recommendations. Please try again.</p>';
        }
    }

    // Filter function removed as per request for auto-display based on scan

    function changeGender(newGender) {
        detectedGender = newGender; // Update the global variable
        const tone = document.getElementById('detected-tone').innerText;

        // Update session storage
        const saved = sessionStorage.getItem('skinToneResults');
        if (saved) {
            try {
                const data = JSON.parse(saved);
                data.gender = newGender;
                sessionStorage.setItem('skinToneResults', JSON.stringify(data));
            } catch (e) { console.error(e); }
        }
        
        // Update active button state
        
        // Refresh content
        const insights = getAIInsights(tone, newGender);
        document.getElementById('insight-list').innerHTML = insights.map(i => `<li><i class='bx bx-check-circle' style="color: var(--skin-500); margin-right: 8px;"></i>${i}</li>`).join('');
        fetchAIRecommendations(tone, newGender);
    }
    
    function renderOutfits(outfits) {
        const wrapper = document.getElementById('outfits-container-wrapper');
        const container = document.getElementById('outfits-container');
        container.innerHTML = '';
        
        if (!outfits || outfits.length === 0) {
            wrapper.style.display = 'none';
            return;
        }

        wrapper.style.display = 'block';
        
        outfits.forEach(outfit => {
            const card = document.createElement('div');
            card.className = 'outfit-card';
            
            let itemsHtml = '';
            outfit.items.forEach((item, index) => {
                itemsHtml += `
                    <div class="outfit-item-single" onclick="window.location.href='product_detail.php?id=${item.Products_ID}'" style="cursor: pointer;">
                        <img src="images/products/${item.Image_URL}" alt="${item.Products_Name}">
                        <div style="padding: 0 10px;">
                            <div style="font-size: 10px; font-weight: 800; color: var(--skin-500); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">${item.Categories_name}</div>
                            <div style="font-size: 14px; font-weight: 700; color: #333; line-height: 1.3; height: 36px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">${item.Products_Name}</div>
                        </div>
                    </div>
                `;
                if (index < outfit.items.length - 1) {
                    itemsHtml += `<div class="outfit-plus">+</div>`;
                }
            });

            card.innerHTML = `
                <div class="outfit-badge">${outfit.title}</div>
                <div class="outfit-items">
                    ${itemsHtml}
                </div>
                <div style="margin-top: auto; padding-top: 20px; border-top: 1px solid #f5f5f5; display: flex; justify-content: space-between; align-items: center;">
                    <div style="color: #999; font-size: 12px; font-weight: 500; letter-spacing: 0.3px;">Curated for your skin tone</div>
                    <div style="font-weight: 800; font-size: 20px; color: #000; font-family: 'Plus Jakarta Sans', sans-serif;">₹${outfit.items.reduce((sum, item) => sum + parseFloat(item.Price), 0).toFixed(2)}</div>
                </div>
            `;
            container.appendChild(card);
        });
    }

    function renderRecs(recs) {
        const container = document.getElementById('recommendations-container');
        container.innerHTML = '';
        
        if (recs.length === 0) {
            container.innerHTML = '<p style="grid-column: 1/-1; text-align: center;">No matching products found.</p>';
            return;
        }

        recs.forEach(product => {
            const card = document.createElement('div');
            card.className = 'product-card'; // Use standard class
            card.style.cursor = 'pointer';
            card.onclick = (e) => {
                // Prevent navigation if clicking on interactive elements
                if (e.target.closest('a') || e.target.closest('button') || e.target.closest('.wishlist-btn') || e.target.closest('.add-to-cart')) return;
                window.location.href = `product_detail.php?id=${product.Products_ID}`;
            };

            // Determine if size selection is needed (simple logic matching PHP)
            
            card.innerHTML = `
                <div class="product-image">
                    <span style="position: absolute; top: 10px; right: 10px; background: #000; color: #fff; padding: 4px 10px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; z-index: 2;">
                        ${product.Categories_name || product.Category_Group || 'Fashion'}
                    </span>
                    <img src="images/products/${product.Image_URL}" alt="${product.Products_Name}">
                </div>
                <div class="product-details">
                    <h3>${product.Products_Name}</h3>
                    <div class="product-price">₹${product.Price}</div>
                    <div style="margin-top: auto; padding-top: 10px; border-top: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 11px; font-weight: 700; color: #666; letter-spacing: 0.05em;">VIEW ITEM</span>
                        <i class='bx bx-right-arrow-alt' style="font-size: 18px; color: #000;"></i>
                    </div>
                </div>
            `;
            container.appendChild(card);
        });
    }
    </script>
    
    <?php require_once 'footer.php'; ?>
</body>
</html>
