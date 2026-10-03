<!doctype html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Skrining ISKAD-V — PhysicalScore</title>
    @auth
    {{-- User authenticated, page can proceed --}}
    @else
    <meta http-equiv="refresh" content="0;url={{ route('login') }}">
    @endauth
    <script src="/mediapipe/pose/pose.js" crossorigin="anonymous"></script>
    <script src="/mediapipe/camera_utils/camera_utils.js" crossorigin="anonymous"></script>
    <script src="/mediapipe/drawing_utils/drawing_utils.js" crossorigin="anonymous"></script>
    <script src="/js/chart.umd.js"></script>
    <style>
        /* Global Styles */
        :root {
            --primary-green: #16a34a;
            --secondary-green: #40916c;
            --carrot-orange: #f9844a;
            --danger-red: #d90429;
            --bg-light: #f8f9fa;
            --text-dark: #1b4332;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--bg-light);
            color: var(--text-dark);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .screen {
            width: 100%;
            background: white;
            padding: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0 auto;
        }

        @media (min-width: 768px) {
            body {
                padding: 20px;
            }

            .screen {
                min-height: auto;
                border-radius: 20px;
            }

            #screen-home {
                max-width: 600px;
            }

            #screen-recording {
                max-width: 1100px;
            }

            #screen-result {
                max-width: 800px;
            }
        }

        .hidden {
            display: none !important;
        }

        .btn-icon {
            background: var(--secondary-green);
            padding: 5px 10px;
            font-size: 0.8rem;
            width: auto;
            border-radius: 5px;
        }

        /* SCREEN 1: HOME */
        header {
            text-align: center;
            margin-bottom: 30px;
        }

        .hero-box {
            background-color: #16a34a;
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 25px;
        }

        #form-anak input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .info-box {
            background: #e9ecef;
            padding: 15px;
            border-radius: 10px;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        .info-box ul {
            margin-left: 20px;
            margin-top: 5px;
        }

        button {
            width: 100%;
            padding: 15px;
            background: var(--primary-green);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: var(--secondary-green);
        }

        /* SCREEN 2: RECORDING (Landscape Layout) */
        .recording-header {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            font-weight: bold;
            background: #eee;
            border-radius: 8px;
        }

        .camera-container {
            position: relative;
            width: 100%;
            aspect-ratio: 16 / 9;
            background: #000;
            margin: 10px 0;
            border-radius: 12px;
            overflow: hidden;
        }

        #output_canvas {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .ai-status-panel {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.9);
            padding: 10px;
            border-radius: 10px;
            width: 180px;
            font-size: 0.8rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #ddd;
            border-radius: 4px;
            margin-top: 5px;
        }

        #stability-progress {
            height: 100%;
            background: var(--secondary-green);
            width: 0%;
            border-radius: 4px;
            transition: 0.3s;
        }

        /* OVERLAY KONTROL TRANSPARAN */
        .controls-overlay {
            position: absolute;
            bottom: 15px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            gap: 8px;
            padding: 0 15px;
            z-index: 50;
        }

        .controls-overlay button {
            background: rgba(22, 163, 74, 0.65);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            color: white;
            padding: 10px 12px;
            font-size: 0.8rem;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }

        .controls-overlay button:hover {
            background: rgba(22, 163, 74, 0.85);
        }

        #btn-rekam {
            background: rgba(217, 4, 41, 0.7);
        }

        #btn-skip,
        #btn-prev {
            background: rgba(108, 117, 125, 0.7) !important;
        }

        #btn-ulangi {
            background: rgba(245, 158, 11, 0.7) !important;
            color: black;
        }

        /* SCREEN 3: RESULT */
        .result-card {
            background: #fff;
            border: 2px solid var(--primary-green);
            padding: 20px;
            border-radius: 15px;
            margin-top: 20px;
        }

        .test-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .summary-box {
            margin-top: 20px;
            padding: 20px;
            background: #ffc300;
            border-radius: 10px;
            text-align: center;
        }

        .disclaimer {
            font-size: 0.75rem;
            color: #666;
            margin-top: 15px;
            font-style: italic;
            text-align: center;
        }
    </style>
</head>

<body>
    <!-- SCREEN 1: HOME -->
    <div id="screen-home" class="screen">
        <header>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <a href="{{ route('screening.index') }}"
                   style="font-size:0.85rem; color:#16a34a; text-decoration:none; font-weight:bold; display:flex; align-items:center; gap:4px;">
                    ← Kembali
                </a>
                <span style="font-size:0.7rem; color:#888;">PhysicalScore</span>
            </div>
            <h1>Skrining ISKAD-V</h1>
        </header>
        <main>
            <div class="hero-box">
                <h2>Deteksi Dini Keseimbangan Anak</h2>
            </div>
            <form id="form-anak">
                <input type="text" id="nama-anak" placeholder="Nama Anak" required />
                <input type="number" id="usia-anak" placeholder="Usia (Tahun)" required />
                <input type="text" id="nama-kader" placeholder="Nama Kader" required />
                <div class="info-box">
                    <strong>Panduan Posisi Kamera:</strong>
                    <ul>
                        <li>Resolusi min 720p</li>
                        <li>Posisi Depan/Samping</li>
                        <li>Jarak 2-3 meter</li>
                        <li>Wajib Tripod</li>
                        <li>Wajib Orientasi Handphone Landscape</li>
                    </ul>
                </div>
                <button type="button" onclick="startApp()">START: MULAI PETUALANGAN</button>
            </form>
        </main>
    </div>

    <!-- SCREEN 2: RECORDING (LANDSCAPE) -->
    <div id="screen-recording" class="screen hidden">
        <div class="recording-header">
            <span id="test-title">Tes 1: Tes Berdiri Satu Kaki</span>
            <button id="btn-switch-camera" onclick="switchCamera()" class="btn-icon">
                🔄 Ganti Kamera
            </button>
            <span id="timer">00:10</span>
        </div>
        <div id="test-instruction" class="instruction-text">
            "Instruksi akan muncul di sini"
        </div>
        <div class="camera-container">
            <video id="input_video" style="display: none"></video>
            <canvas id="output_canvas"></canvas>

            <div class="ai-status-panel">
                <div class="status-item">Deteksi: <span id="status-deteksi">Mencari...</span></div>
                <div class="status-item">
                    Stabilitas: <span id="status-label">-</span>
                    <div class="progress-bar">
                        <div id="stability-progress"></div>
                    </div>
                </div>
            </div>

            <div class="controls-overlay">
                <button id="btn-prev" onclick="prevTest()" class="hidden">⏮️ KEMBALI</button>
                <button id="btn-rekam" onclick="startRecording()">REKAM</button>
                <button id="btn-skip" onclick="skipTest()">LEWATI ⏭️</button>
                <button id="btn-ulangi" class="hidden" onclick="resetTest()">ULANGI</button>
                <button id="btn-next" class="hidden" onclick="handleNext()">NEXT ➔</button>
            </div>
        </div>
    </div>

    <!-- SCREEN 3: RESULT -->
    <div id="screen-result" class="screen hidden">
        <h2>HASIL SKRINING</h2>
        <div class="result-card">
            <p><strong>Nama:</strong> <span id="res-nama"></span></p>
            <div style="width: 100%; max-width: 400px; margin: 0 auto 20px auto">
                <canvas id="radarChart"></canvas>
            </div>
            <div id="test-list">
                <!-- Rincian Skor per Item -->
            </div>
            <div class="summary-box">
                <h3>TOTAL SKOR: <span id="total-skor">0</span> / 15</h3>
                <p>Kategori: <strong id="kategori-hasil">-</strong></p>
            </div>
            <p class="disclaimer">Hasil ini adalah alat skrining awal, bukan diagnosis medis.</p>
        </div>
        <button onclick="location.reload()">SIMPAN & SELESAI</button>
    </div>

    <script>
        const SENSITIVITAS = {
            TINGGI: 0.004,
            SEDANG: 0.007,
            RENDAH: 0.01,
        };
        let ambangBatasGoyangan = SENSITIVITAS.RENDAH;

        const audioContext = new(window.AudioContext || window.webkitAudioContext)();

        function playTone(freq, type, duration, startTimeOffset = 0) {
            if (audioContext.state === "suspended") audioContext.resume();

            const osc = audioContext.createOscillator();
            const gain = audioContext.createGain();
            const startTime = audioContext.currentTime + startTimeOffset;

            osc.type = type;
            osc.frequency.value = freq;

            gain.gain.setValueAtTime(0.1, startTime);
            gain.gain.exponentialRampToValueAtTime(0.001, startTime + duration);

            osc.connect(gain);
            gain.connect(audioContext.destination);

            osc.start(startTime);
            osc.stop(startTime + duration);
        }

        const SoundFX = {
            siap: () => { playTone(600, "sine", 0.2, 0); playTone(800, "sine", 0.3, 0.15); },
            error: () => { playTone(300, "sawtooth", 0.25, 0); playTone(200, "sawtooth", 0.4, 0.25); },
            penalti: () => { playTone(150, "square", 0.3, 0); },
            mulai: () => { playTone(1000, "sine", 0.6, 0); },
            selesai: () => { playTone(800, "sine", 0.2, 0); playTone(600, "sine", 0.4, 0.2); },
            persiapan: () => { playTone(500, "sine", 0.3, 0); },
        };

        // ==========================================
        // 1. DAFTAR TES YANG SUDAH DIURUTKAN ULANG
        // ==========================================
        const DAFTAR_TES = [
            {
                id: "tandem",
                nama: "Tes Jalan Garis Lurus (Tandem Walk)",
                instruksi: '"Anak berjalan di garis lurus (heel-to-toe) sepanjang ±3 meter"',
                durasi: 5, // Diubah menjadi 5 detik
                hitungSkor: (data) => {
                    console.group(`📊 HASIL TES: JEMBATAN AJAIB (TANDEM WALK)`);
                    console.log(`Jumlah Keluar Garis / Goyah (UnstableCount): ${data.unstableCount}`);
                    console.log(`Jumlah Jatuh (FallCount): ${data.fallCount}`);
                    if (data.fallCount > 0) return 0;
                    if (data.unstableCount === 0) return 3;
                    if (data.unstableCount <= 2) return 2;
                    console.groupEnd();
                    return 1;
                },
            },
            {
                id: "statis",
                nama: "Tes Berdiri Satu Kaki (Static Balance)",
                instruksi: '"Anak berdiri dengan satu kaki selama mungkin (maks 10 detik)"',
                durasi: 10,
                hitungSkor: (data) => {
                    console.group(`📊 HASIL TES: STATIS`);
                    console.log(`Total Frame: ${data.total}`);
                    const stabPercentage = (data.stable / data.total) * 100;
                    console.log(`Frame Stabil: ${data.stable} (${stabPercentage.toFixed(2)}%)`);
                    console.log(`Kaki Turun / Jatuh: ${data.fallCount > 0 ? "Ya" : "Tidak"}`);
                    if (data.fallCount > 0 == true) return 0;
                    if (stabPercentage >= 80) return 3;
                    if (stabPercentage >= 40) return 2;
                    if (stabPercentage > 0) return 1;
                    console.groupEnd();
                    return 0;
                },
            },
            {
                id: "sit_to_stand",
                nama: "Tes Duduk ke Berdiri (Sit-to-Stand)",
                instruksi: '"Dari duduk ke berdiri tanpa bantuan tangan"',
                durasi: 10,
                hitungSkor: (data) => {
                    console.group(`📊 HASIL TES: DUDUK KE BERDIRI`);
                    console.log(`Berhasil Berdiri (HasStoodUp): ${data.hasStoodUp}`);
                    console.log(`Bantuan Tangan / Goyah (UnstableCount): ${data.unstableCount}`);
                    let skor = 0;
                    if (!data.hasStoodUp || data.fallCount > 0) skor = 0;
                    else if (data.unstableCount >= 3) skor = 1;
                    else if (data.unstableCount > 0) skor = 2;
                    else skor = 3;
                    console.groupEnd();
                    return skor;
                },
            },
            {
                id: "vestibular",
                nama: "Tes Putar Badan (Turning Balance)",
                instruksi: '"Anak berputar 360° lalu berhenti (Satu kali kanan, Satu kali kiri)"',
                durasi: 0, // Tanpa batas waktu (Otomatis)
                hitungSkor: (data) => {
                    console.group(`📊 HASIL TES: PUTAR DAN BERHENTI`);
                    console.log(`Rotasi Terdeteksi (HasRotated): ${data.hasRotated}`);
                    console.log(`Limbung Setelah Putar (UnstableCount): ${data.unstableCount}`);
                    console.log(`Hasil (fallCount): ${data.fallCount}`);
                    let skor = 0;
                    if (!data.hasRotated || data.fallCount > 0) skor = 0;
                    else if (data.unstableCount >= 3) skor = 1;
                    else if (data.unstableCount > 0) skor = 2;
                    else skor = 3;
                    console.groupEnd();
                    return skor;
                },
            },
            {
                id: "lompat",
                nama: "Tes Melompat Dua Kaki (Jumping Balance)",
                instruksi: '"Melompat ke depan 3 kali "',
                durasi: 0, // Tanpa batas waktu (Otomatis)
                hitungSkor: (data) => {
                    console.group(`📊 HASIL TES: LOMPAT KE PULAU WORTEL`);
                    console.log(`Jumlah Lompatan: ${data.jumpCount}`);
                    console.log(`Goyah: ${data.unstableCount}`);
                    let skor = 0;
                    if (data.jumpCount === 0 || data.fallCount > 0) skor = 0;
                    else if (data.jumpCount < 3) skor = 1; 
                    else if (data.unstableCount >= 3) skor = 1;
                    else if (data.unstableCount > 0) skor = 2;
                    else skor = 3;
                    console.groupEnd();
                    return skor;
                },
            }
        ];

        // 2. STATE APLIKASI
        let indexTesSekarang = 0;
        let isRecording = false;
        let isBodyReady = false;
        let lastBodyReadyState = null;
        let riwayatHasil = [];
        let frameData = {
            stable: 0, unstable: 0, total: 0, unstableCount: 0, fallCount: 0,
            hasStoodUp: false, jumpCount: 0, hasRotated: false, history: [], startTime: 0,
            rotationCount: 0, isFacingBack: false // Variabel baru untuk putaran otomatis
        };
        let prevY = 0;
        let prevX = 0;
        let isWasUnstable = false;

        let mediaRecorder;
        let recordedChunks = [];

        // State Kamera
        let cameraInstance = null;
        let currentFacingMode = "user"; 
        const LANDMARK_TUBUH_UTUH = [0, 11, 12, 23, 24, 27, 28, 31, 32];

        // 3. ELEMEN UI
        const videoElement = document.getElementById("input_video");
        const canvasElement = document.getElementById("output_canvas");
        const canvasCtx = canvasElement.getContext("2d");
        const labelStatus = document.getElementById("status-label");
        const progressStabilitas = document.getElementById("stability-progress");
        const btnRekam = document.getElementById("btn-rekam");

        // 4. INISIALISASI MEDIAPIPE POSE
        const pose = new Pose({ locateFile: (file) => `/mediapipe/pose/${file}` });

        pose.setOptions({
            modelComplexity: 1,
            smoothLandmarks: true,
            minDetectionConfidence: 0.5,
            minTrackingConfidence: 0.5,
        });

        pose.onResults((results) => {
            // Resize canvas sesuai ukuran container agar tidak gelap/blank
            const container = canvasElement.parentElement;
            if (container && (canvasElement.width !== container.clientWidth || canvasElement.height !== container.clientHeight)) {
                canvasElement.width  = container.clientWidth;
                canvasElement.height = container.clientHeight;
            }
            canvasCtx.save();
            canvasCtx.clearRect(0, 0, canvasElement.width, canvasElement.height);
            canvasCtx.drawImage(results.image, 0, 0, canvasElement.width, canvasElement.height);

            if (results.poseLandmarks) {
                isBodyReady = checkBodyVisibility(results.poseLandmarks);

                if (isBodyReady !== lastBodyReadyState && !isRecording) {
                    if (isBodyReady) {
                        document.getElementById("status-deteksi").innerText = "SIAP MULAI ✅";
                        document.getElementById("status-deteksi").style.color = "#00FF00";
                        if (!isRecording) btnRekam.disabled = false;
                        SoundFX.siap();
                    } else {
                        document.getElementById("status-deteksi").innerText = "TUBUH BELUM UTUH ⚠️";
                        document.getElementById("status-deteksi").style.color = "#FFCC00";
                        btnRekam.disabled = true;
                        SoundFX.error();
                    }
                    lastBodyReadyState = isBodyReady;
                }

                if (DAFTAR_TES[indexTesSekarang].id === "tandem") {
                    drawSideGuideLines(canvasCtx);
                }

                // PERBAIKAN: Menipiskan kerangka tubuh (Titik & Garis)
                drawConnectors(canvasCtx, results.poseLandmarks, POSE_CONNECTIONS, {
                    color: isBodyReady ? "#00FF00" : "#FF0000",
                    lineWidth: 1, // Garis sangat tipis
                });
                drawLandmarks(canvasCtx, results.poseLandmarks, {
                    color: isBodyReady ? "#00FF00" : "#FF0000",
                    lineWidth: 1, 
                    radius: 1, // Titik sangat kecil
                });

                if (isRecording) {
                    prosesLogikaHAR(results.poseLandmarks);
                }
            } else {
                document.getElementById("status-deteksi").innerText = "Mencari Tubuh...";
                btnRekam.disabled = true;
            }
            canvasCtx.restore();
        });

        function checkBodyVisibility(landmarks) {
            return LANDMARK_TUBUH_UTUH.every((index) => {
                const lm = landmarks[index];
                return lm && lm.visibility > 0.5 && lm.x >= 0 && lm.x <= 1 && lm.y >= 0 && lm.y <= 1;
            });
        }

        function drawSideGuideLines(ctx) {
            const lineY = canvasElement.height * 0.75; 
            ctx.beginPath();
            ctx.strokeStyle = "rgba(255, 255, 0, 0.7)"; 
            ctx.lineWidth = 3;
            ctx.setLineDash([15, 15]);
            ctx.moveTo(0, lineY);
            ctx.lineTo(canvasElement.width, lineY);
            ctx.stroke();
            ctx.setLineDash([]);
        }

        let isJumping = false;
        let isRotating = false;

        function prosesLogikaHAR(landmarks) {
            const idTes = DAFTAR_TES[indexTesSekarang].id;
            const currentY = landmarks[11].y; 
            const currentX = landmarks[11].x;
            const delta = Math.abs(currentY - prevY);
            const deltaX = Math.abs(currentX - prevX);
            const threshold = ambangBatasGoyangan;

            let isOut = false;
            let isFootDown = false;
            let isFallen = false;

            const leftHip = landmarks[23];
            const leftKnee = landmarks[25];

            // 1. LOGIKA DETEKSI JATUH
            if (idTes !== "sit_to_stand" && leftHip.y >= leftKnee.y - 0.02) {
                isFallen = true;
            }

            // 2. LOGIKA KHUSUS PER TES
            if (idTes === "vestibular") {
                const pergerakanPutaran = Math.max(delta, deltaX);

                if (pergerakanPutaran > 0.015) {
                    isRotating = true;
                    frameData.hasRotated = true;
                } else if (pergerakanPutaran < 0.008) {
                    isRotating = false;
                }

                // ===============================================
                // PERBAIKAN: Hitung putaran menggunakan Bahu
                // Jika bahu kiri (11) di layar ada di sebelah kiri bahu kanan (12), 
                // maka objek sedang membelakangi kamera.
                // ===============================================
                const leftShoulder = landmarks[11];
                const rightShoulder = landmarks[12];
                const isBack = leftShoulder.x < rightShoulder.x; 

                if (isBack && !frameData.isFacingBack) {
                    frameData.isFacingBack = true; // Objek berputar membelakangi kamera
                } else if (!isBack && frameData.isFacingBack) {
                    frameData.isFacingBack = false; // Objek kembali menghadap depan = 1 putaran selesai
                    frameData.rotationCount++;
                    
                    document.getElementById("timer").innerText = `Putaran: ${frameData.rotationCount}/2`;
                    
                    // Berhenti otomatis jika sudah 2 putaran
                    if (frameData.rotationCount >= 2) {
                        clearInterval(window.testCountdown);
                        setTimeout(() => stopRecording(), 500); 
                    }
                }

            } else if (idTes === "sit_to_stand") {
                const leftWrist = landmarks[15].y;
                const rightWrist = landmarks[16].y;
                const leftHipY = landmarks[23].y;
                const leftKneeY = landmarks[25].y;

                if (leftHipY < leftKneeY - 0.15) {
                    frameData.hasStoodUp = true;
                }

                if (!frameData.hasStoodUp) {
                    if (leftHipY >= leftKneeY - 0.1) {
                        if (leftWrist > leftHipY + 0.1 || rightWrist > leftHipY + 0.1) {
                            if (!isWasUnstable) {
                                frameData.unstableCount++;
                                isWasUnstable = true;
                            }
                        }
                    }

                    if (deltaX > 0.015) {
                        if (!isWasUnstable) {
                            frameData.unstableCount++;
                            isWasUnstable = true;
                        }
                    }
                }

                if (frameData.hasStoodUp && delta > 0.05 && leftHipY > leftKneeY) {
                    isFallen = true;
                }
            } else if (idTes === "lompat") {
                const ankleY = landmarks[27].y;

                if (ankleY < 0.7) {
                    isJumping = true;
                } else if (isJumping && ankleY > 0.85) {
                    isJumping = false;
                    frameData.jumpCount++;

                    // Perbarui teks UI
                    document.getElementById("timer").innerText = `Lompatan: ${frameData.jumpCount}/3`;

                    if (delta > 0.015) {
                        frameData.unstableCount++;
                    }

                    // PERBAIKAN: Otomatis berhenti setelah 3 lompatan
                    if (frameData.jumpCount >= 3) {
                        clearInterval(window.testCountdown);
                        setTimeout(() => stopRecording(), 500); 
                    }
                }
            } else if (idTes === "statis") {
                const rightAnkleY = landmarks[28].y;
                const leftAnkleY = landmarks[27].y;
                const selisihTinggiKaki = Math.abs(leftAnkleY - rightAnkleY);

                if (selisihTinggiKaki < 0.04) {
                    isFootDown = true;
                }
            } else if (idTes === "tandem") {
                const kakiMenapakY = Math.max(landmarks[27].y, landmarks[28].y);

                if (kakiMenapakY < 0.70 || kakiMenapakY > 0.80) {
                    isOut = true;
                }
            }

            // 3. EVALUASI STABILITAS
            let isCurrentlyStable = false;

            if (idTes === "tandem") {
                isCurrentlyStable = !isOut && !isFallen;
            } else if (idTes === "sit_to_stand") {
                isCurrentlyStable = deltaX < 0.015 && !isFallen;
            } else {
                isCurrentlyStable = delta < threshold && !isOut && !isFootDown && !isFallen && !isRotating;
            }

            // 4. UPDATE UI & STATE
            if (isCurrentlyStable) {
                if (isWasUnstable) {
                    isWasUnstable = false; 
                }
                if (idTes === "sit_to_stand" && !frameData.hasStoodUp) {
                    labelStatus.innerText = "TRANSISI (EXEC)";
                    labelStatus.style.color = "#00BFFF";
                } else {
                    labelStatus.innerText = "STABLE";
                    labelStatus.style.color = "#00FF00";
                }

                frameData.stable++;
                isWasUnstable = false;
            } else {
                if (isFallen) {
                    labelStatus.innerText = "JATUH/GAGAL!";
                    if (!isWasUnstable) {
                        frameData.fallCount = (frameData.fallCount || 0) + 1;
                        SoundFX.error(); 
                        isWasUnstable = true;
                    }
                } else if (idTes === "lompat" && isJumping) {
                    labelStatus.innerText = "MELAYANG...";
                    labelStatus.style.color = "#00BFFF";
                } else if (idTes === "vestibular" && isRotating) {
                    labelStatus.innerText = "ROTASI (EXEC)";
                    labelStatus.style.color = "#00BFFF";
                } else if (isFootDown) {
                    labelStatus.innerText = "FAIL (Kaki Turun)";
                    if (!isWasUnstable) {
                        SoundFX.error(); 
                        isWasUnstable = true;
                    }
                } else if (isOut) {
                    labelStatus.innerText = "KELUAR GARIS!";
                } else {
                    labelStatus.innerText = idTes === "sit_to_stand" ? "BANTUAN/GOYAH!" : "UNSTABLE (Goyah)";
                }

                if (!isJumping && !isRotating) {
                    labelStatus.style.color = "#FF0000";
                }

                frameData.unstable++;

                if (!isWasUnstable && !isJumping && !isRotating && !isFallen && !isFootDown) {
                    if (idTes === "tandem" && isOut) {
                        frameData.unstableCount++;
                        SoundFX.penalti(); 
                    } else if (idTes !== "lompat" && idTes !== "sit_to_stand" && !isOut) {
                        frameData.unstableCount++;
                        SoundFX.penalti(); 
                    }
                    isWasUnstable = true;
                }
            }

            frameData.total++;
            prevY = currentY;
            prevX = currentX;

            const stabilityPercentage = (frameData.stable / frameData.total) * 100;
            progressStabilitas.style.width = stabilityPercentage + "%";

            const detikBerjalan = (Date.now() - frameData.startTime) / 1000;

            if (frameData.total % 5 === 0) {
                frameData.history.push({
                    time: detikBerjalan.toFixed(1),
                    status: isCurrentlyStable ? 1 : 0,
                });
            }
        }

        async function startCamera() {
            if (cameraInstance) {
                await cameraInstance.stop();
            }

            cameraInstance = new Camera(videoElement, {
                onFrame: async () => {
                    await pose.send({ image: videoElement });
                },
                width: 1280,
                height: 720,
                facingMode: currentFacingMode,
            });
            cameraInstance.start();
        }

        function switchCamera() {
            currentFacingMode = currentFacingMode === "user" ? "environment" : "user";
            const btnSwitch = document.getElementById("btn-switch-camera");
            btnSwitch.innerText = currentFacingMode === "user" ? "🔄 Kamera Depan" : "🔄 Kamera Belakang";
            startCamera();
        }

        function startApp() {
            document.getElementById("screen-home").classList.add("hidden");
            document.getElementById("screen-recording").classList.remove("hidden");

            if (audioContext.state === "suspended") {
                audioContext.resume();
            }

            startCamera();
            muatTes(0);
        }

        function muatTes(index) {
            const data = DAFTAR_TES[index];
            document.getElementById("test-title").innerText = `Tes ${index + 1}: ${data.nama}`;
            document.getElementById("test-instruction").innerText = data.instruksi;
            
            // Format tampilan waktu berbeda untuk tes tanpa batas waktu
            if (data.id === "lompat") {
                document.getElementById("timer").innerText = `Lompatan: 0/3`;
            } else if (data.id === "vestibular") {
                document.getElementById("timer").innerText = `Putaran: 0/2`;
            } else {
                document.getElementById("timer").innerText = `00:${data.durasi.toString().padStart(2, "0")}`;
            }

            frameData = {
                stable: 0, unstable: 0, total: 0, unstableCount: 0, fallCount: 0,
                hasStoodUp: false, jumpCount: 0, hasRotated: false, history: [], startTime: 0,
                rotationCount: 0, isFacingBack: false
            };
            progressStabilitas.style.width = "0%";

            btnRekam.classList.remove("hidden");
            btnRekam.disabled = true;
            lastBodyReadyState = null; 

            if (index > 0) {
                document.getElementById("btn-prev").classList.remove("hidden");
            } else {
                document.getElementById("btn-prev").classList.add("hidden");
            }

            document.getElementById("btn-skip").classList.remove("hidden");
            document.getElementById("btn-next").classList.add("hidden");
            document.getElementById("btn-ulangi").classList.add("hidden");

            setTimeout(() => SoundFX.persiapan(), 500);
        }

        // Variabel global untuk hitung mundur agar bisa di-clear manual
        window.testCountdown = null;

        function startRecording() {
            isRecording = true;
            frameData.startTime = Date.now(); 

            let timer = DAFTAR_TES[indexTesSekarang].durasi;
            btnRekam.classList.add("hidden");

            document.getElementById("btn-skip").classList.add("hidden");
            document.getElementById("btn-prev").classList.add("hidden");
            SoundFX.mulai();

            const stream = canvasElement.captureStream(30);

            mediaRecorder = new MediaRecorder(stream, { mimeType: 'video/webm' });
            recordedChunks = [];

            mediaRecorder.ondataavailable = function(e) {
                if (e.data.size > 0) {
                    recordedChunks.push(e.data);
                }
            };

            mediaRecorder.onstop = function() {
                const blob = new Blob(recordedChunks, { type: 'video/webm' });
                const url = URL.createObjectURL(blob);

                const namaAnakInput = document.getElementById("nama-anak").value || "Anak";
                const namaAnak = namaAnakInput.replace(/[^a-zA-Z0-9]/g, '_'); 

                const a = document.createElement('a');
                document.body.appendChild(a);
                a.style = 'display: none';
                a.href = url;
                a.download = `Rekaman_${namaAnak}_Tes_${indexTesSekarang + 1}.webm`;
                a.click(); 

                window.URL.revokeObjectURL(url);
            };

            mediaRecorder.start();

            // Interval untuk hitung mundur hanya jalan jika ada durasi > 0
            window.testCountdown = setInterval(() => {
                const idTes = DAFTAR_TES[indexTesSekarang].id;
                
                // Jangan kurangi waktu jika tes Lompat atau Putar Badan
                if (idTes !== "lompat" && idTes !== "vestibular") {
                    timer--;
                    document.getElementById("timer").innerText = `00:${timer.toString().padStart(2, "0")}`;
                    if (timer <= 0) {
                        clearInterval(window.testCountdown);
                        stopRecording();
                    }
                }
            }, 1000);
        }

        function stopRecording() {
            isRecording = false;
            SoundFX.selesai();

            if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                mediaRecorder.stop(); 
            }

            const tesAktif = DAFTAR_TES[indexTesSekarang];
            riwayatHasil.push({
                nama: tesAktif.nama,
                skor: tesAktif.hitungSkor(frameData),
                historiGrafik: frameData.history, 
            });

            document.getElementById("btn-next").classList.remove("hidden");
            document.getElementById("btn-ulangi").classList.remove("hidden");
        }

        function skipTest() {
            const tesAktif = DAFTAR_TES[indexTesSekarang];
            riwayatHasil.push({
                nama: tesAktif.nama + " (Dilewati)",
                skor: 0,
                historiGrafik: [], 
            });
            document.getElementById("btn-skip").classList.add("hidden");
            handleNext();
        }

        function prevTest() {
            if (indexTesSekarang > 0) {
                indexTesSekarang--;
                riwayatHasil.pop(); 
                muatTes(indexTesSekarang);
            }
        }

        function handleNext() {
            indexTesSekarang++;
            if (indexTesSekarang < DAFTAR_TES.length) muatTes(indexTesSekarang);
            else tampilkanHasilAkhir();
        }

        function resetTest() {
            riwayatHasil.pop();
            muatTes(indexTesSekarang);
        }

        async function tampilkanHasilAkhir() {
            document.getElementById("screen-recording").classList.add("hidden");
            document.getElementById("screen-result").classList.remove("hidden");
            const namaAnak = document.getElementById("nama-anak").value || "Anak";
            document.getElementById("res-nama").innerText = namaAnak;
            const umurBulan = document.getElementById("usia-anak").value || 60;

            let totalSkor = 0;
            const listEl = document.getElementById("test-list");
            listEl.innerHTML = "";

            let labelRadar = [];
            let dataRadar = [];

            riwayatHasil.forEach((hasil, index) => {
                totalSkor += hasil.skor;
                labelRadar.push(hasil.nama);
                dataRadar.push(hasil.skor);

                listEl.innerHTML += `
      <div class="test-item" style="display:block; margin-bottom: 20px;">
          <div style="display:flex; justify-content:space-between;">
              <span>${hasil.nama}</span>
              <strong>Skor ${hasil.skor}</strong>
          </div>
          <div style="height: 100px; width: 100%; margin-top: 5px;">
              <canvas id="lineChart_${index}"></canvas>
          </div>
      </div>`;
            });

            document.getElementById("total-skor").innerText = totalSkor;
            let kategori = "RISIKO TINGGI";
            const katEl = document.getElementById("kategori-hasil");
            if (totalSkor >= 12) {
                katEl.innerText = "NORMAL";
                kategori = "NORMAL";
                katEl.className = "status-normal";
            } else if (totalSkor >= 8) {
                kategori = "RISIKO RINGAN";
                katEl.innerText = "RISIKO RINGAN";
                katEl.className = "status-warning";
            } else {
                kategori = "RISIKO TINGGI";
                katEl.innerText = "RISIKO TINGGI";
                katEl.className = "status-danger";
            }

            const skorStatis = riwayatHasil.find(r => r.nama.includes('Static'))?.skor || 0;
            const skorTandem = riwayatHasil.find(r => r.nama.includes('Tandem'))?.skor || 0;
            const skorLompat = riwayatHasil.find(r => r.nama.includes('Jumping'))?.skor || 0;
            const skorSitStand = riwayatHasil.find(r => r.nama.includes('Sit-to-Stand'))?.skor || 0;
            const skorVestibular = riwayatHasil.find(r => r.nama.includes('Turning'))?.skor || 0;

            const payload = {
                nama_anak: namaAnak,
                umur_bulan: umurBulan,
                skor_statis: skorStatis,
                skor_tandem: skorTandem,
                skor_lompat: skorLompat,
                skor_sit_to_stand: skorSitStand,
                skor_vestibular: skorVestibular,
                total_skor: totalSkor,
                kategori: kategori,
                chart_data: riwayatHasil 
            };

            try {
                let response = await fetch("{{ route('screening.store') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}" 
                    },
                    body: JSON.stringify(payload)
                });

                let result = await response.json();

                if (result.success) {
                    if (cameraInstance) cameraInstance.stop();
                    window.location.href = result.url_detail || "/screening/" + result.id;
                } else {
                    alert("Gagal menyimpan: " + result.message);
                    document.getElementById("screen-loading").classList.add("hidden");
                    document.getElementById("screen-home").classList.remove("hidden");
                }

            } catch (error) {
                console.error("Fetch Error:", error);
                alert("Terjadi kesalahan jaringan saat menyimpan data.");
            }

            new Chart(document.getElementById("radarChart"), {
                type: "radar",
                data: {
                    labels: labelRadar,
                    datasets: [{
                        label: "Skor Keseimbangan",
                        data: dataRadar,
                        backgroundColor: "rgba(64, 145, 108, 0.4)",
                        borderColor: "rgba(45, 106, 79, 1)",
                        pointBackgroundColor: "#d90429",
                    }, ],
                },
                options: {
                    scales: { r: { min: 0, max: 3, ticks: { stepSize: 1 } } }
                },
            });

            riwayatHasil.forEach((hasil, index) => {
                const ctx = document.getElementById(`lineChart_${index}`);
                const labelsWaktu = hasil.historiGrafik.map((h) => h.time + "s");
                const dataStabilitas = hasil.historiGrafik.map((h) => h.status);

                new Chart(ctx, {
                    type: "line",
                    data: {
                        labels: labelsWaktu,
                        datasets: [{
                            label: "1=Stabil, 0=Goyah",
                            data: dataStabilitas,
                            borderColor: "#f9844a",
                            borderWidth: 2,
                            fill: true,
                            backgroundColor: "rgba(249, 132, 74, 0.2)",
                            stepped: true, 
                        }, ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: { y: { min: 0, max: 1.2, ticks: { stepSize: 1 } } },
                        plugins: { legend: { display: false } },
                    },
                });
            });
        }

        document.getElementById("btn-next").onclick = handleNext;
    </script>
</body>
</html>
