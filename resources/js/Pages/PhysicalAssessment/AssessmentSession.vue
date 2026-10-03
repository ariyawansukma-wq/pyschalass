<template>
    <AppLayout>
        <!-- Back button + Test info bar -->
        <div class="flex items-center gap-4 mb-6">
            <button
                @click="handleBack"
                class="flex items-center gap-2 px-3 py-2 rounded-lg bg-dark-800 border border-white/5 text-slate-400 hover:text-white hover:border-white/10 transition-all duration-200 text-sm font-medium"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali
            </button>

            <div class="flex items-center gap-3">
                <span class="text-2xl">{{ props.test.icon }}</span>
                <div>
                    <h2 class="text-base font-bold text-white leading-tight">{{ props.test.name }}</h2>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full', categoryBadge]">
                            {{ props.test.category }}
                        </span>
                        <span class="text-xs text-slate-500">{{ props.test.unit }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save error notice -->
        <div v-if="saveError" class="mb-4 alert alert-error text-xs p-3 shadow-xs">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <span class="font-semibold">Gagal menyimpan hasil: {{ saveError }}</span>
        </div>

        <!-- Save success notice -->
        <div v-if="saveSuccess" class="mb-4 alert alert-success text-xs p-3 shadow-xs">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            <span class="font-semibold">Hasil assessment berhasil disimpan.</span>
        </div>

        <!-- Main layout: Camera (left) + Controls (right) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Camera area (2/3 width) -->
            <div class="lg:col-span-2">
                <div class="relative">
                    <CameraPreview
                        ref="cameraRef"
                        :is-assessing="sessionState === 'assessing' && !isCountingDown"
                        :elapsed-seconds="elapsedSeconds"
                        @camera-ready="onCameraReady"
                        @camera-stopped="onCameraStopped"
                        @camera-error="onCameraError"
                    >
                        <!-- Slot overlay: PoseDetector canvas ditumpuk di atas video -->
                        <template #overlay>
                            <PoseDetector
                                :video-element="activeVideoElement"
                                :active="sessionState === 'assessing' && !isCountingDown"
                                :skeleton-valid="poseSkeletonValid"
                                @pose-status="onPoseStatus"
                                @pose-update="onPoseUpdate"
                                @perf-update="onPerfUpdate"
                            />
                        </template>
                    </CameraPreview>

                    <!-- Countdown overlay -->
                    <transition name="countdown-fade">
                        <div v-if="isCountingDown"
                             class="absolute inset-0 flex flex-col items-center justify-center rounded-2xl bg-black/60 backdrop-blur-sm z-20 pointer-events-none">
                            <span class="text-8xl font-black text-white tabular-nums leading-none drop-shadow-2xl"
                                  style="text-shadow:0 0 40px rgba(99,102,241,0.8)">
                                {{ countdownValue }}
                            </span>
                            <p class="text-slate-300 text-sm font-semibold mt-3 tracking-widest uppercase">Bersiap...</p>
                        </div>
                    </transition>

                    <!-- Timer countdown bar (saat assessing) -->
                    <div v-if="sessionState === 'assessing' && !isCountingDown" class="mt-2">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs text-slate-500">Sisa Waktu</span>
                            <span class="text-sm font-mono font-bold"
                                  :class="remainingSeconds <= 10 ? 'text-red-400 animate-pulse' : remainingSeconds <= 20 ? 'text-yellow-400' : 'text-emerald-400'">
                                {{ remainingFormatted }}
                            </span>
                        </div>
                        <div class="w-full h-1.5 rounded-full bg-dark-800 overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-1000"
                                 :class="remainingSeconds <= 10 ? 'bg-red-500' : remainingSeconds <= 20 ? 'bg-yellow-500' : 'bg-emerald-500'"
                                 :style="{ width: `${assessmentDurationSec > 0 ? (remainingSeconds / assessmentDurationSec) * 100 : 0}%` }">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Camera control buttons -->
                <div class="flex items-center gap-3 mt-4">
                    <!-- Mute toggle -->
                    <button
                        @click="isMuted = !isMuted"
                        :title="isMuted ? 'Aktifkan Suara' : 'Matikan Suara'"
                        class="px-3 py-3 rounded-xl bg-dark-800 border border-white/5 text-slate-400 hover:text-white transition-all duration-200 text-sm flex-shrink-0"
                    >
                        <svg v-if="isMuted" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" clip-rule="evenodd"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
                        </svg>
                        <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.536 8.464a5 5 0 010 7.072M12 6v12m-4.243-9.757A8 8 0 005 12a8 8 0 002.757 5.757M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                        </svg>
                    </button>
                    <!-- Activate camera -->
                    <button
                        v-if="sessionState === 'idle'"
                        @click="activateCamera"
                        class="flex-1 flex items-center justify-center gap-2 py-3 px-6 rounded-xl
                               bg-primary hover:bg-primary/80 text-primary-content font-semibold text-sm
                               transition-all duration-200 shadow-lg"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.362a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        Aktifkan Kamera
                    </button>

                    <!-- Camera ready: Start Assessment -->
                    <template v-if="sessionState === 'cameraReady'">
                        <button
                            @click="startAssessment"
                            class="flex-1 flex items-center justify-center gap-2 py-3 px-6 rounded-xl
                                   bg-success hover:bg-success/80 text-success-content font-bold text-sm
                                   transition-all duration-200 shadow-lg"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Mulai Assessment
                        </button>
                        <button
                            @click="deactivateCamera"
                            class="px-4 py-3 rounded-xl bg-dark-800 hover:bg-dark-800/70 border border-white/5
                                   text-slate-400 hover:text-white transition-all duration-200 text-sm font-medium"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                        </button>
                    </template>

                    <!-- Assessment running: Stop -->
                    <template v-if="sessionState === 'assessing'">
                        <button
                            @click="stopAssessment"
                            class="flex-1 flex items-center justify-center gap-2 py-3 px-6 rounded-xl
                                   bg-error hover:bg-error/80 text-error-content font-bold text-sm
                                   transition-all duration-200 shadow-lg animate-pulse"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 10h6v4H9z"/>
                            </svg>
                            Stop Assessment
                        </button>
                    </template>

                    <!-- Stopped / finished -->
                    <template v-if="sessionState === 'stopped'">
                        <button
                            @click="restartSession"
                            class="flex-1 flex items-center justify-center gap-2 py-3 px-6 rounded-xl
                                   bg-primary hover:bg-primary/80 text-primary-content font-semibold text-sm
                                   transition-all duration-200"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Ulangi Tes
                        </button>
                        <button
                            @click="handleBack"
                            class="px-4 py-3 rounded-xl bg-dark-800 border border-white/5 text-slate-400 hover:text-white transition-all duration-200 text-sm font-medium"
                        >
                            Selesai
                        </button>
                    </template>
                </div>
            </div>

            <!-- Right panel: Info & Status -->
            <div class="flex flex-col gap-4">

                <!-- Athlete info card -->
                <div class="card bg-base-100 border border-base-300 p-4">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Atlet</h3>
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-primary/15 border border-primary/20 flex items-center justify-center text-primary text-sm font-bold flex-shrink-0">
                            {{ athleteInitials }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">{{ props.athlete.name }}</p>
                            <p v-if="props.athlete.athlete_number" class="text-xs text-slate-500">{{ props.athlete.athlete_number }}</p>
                        </div>
                    </div>
                </div>

                <!-- Session status card -->
                <div class="card bg-base-100 border border-base-300 p-5">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4">Status Sesi</h3>

                    <div class="space-y-3">
                        <!-- Session state -->
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-slate-500">Status</span>
                            <span :class="['text-xs font-bold px-2.5 py-1 rounded-full', sessionStateBadge.class]">
                                {{ sessionStateBadge.label }}
                            </span>
                        </div>

                        <!-- Duration -->
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-slate-500">Durasi</span>
                            <span class="text-xs font-mono font-bold text-white">{{ elapsedFormatted }}</span>
                        </div>

                        <!-- Camera status -->
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-slate-500">Kamera</span>
                            <div class="flex items-center gap-1.5">
                                <div :class="['w-1.5 h-1.5 rounded-full', cameraActive ? 'bg-emerald-500' : 'bg-slate-600']"></div>
                                <span class="text-xs font-medium" :class="cameraActive ? 'text-emerald-400' : 'text-slate-600'">
                                    {{ cameraActive ? 'Aktif' : 'Tidak Aktif' }}
                                </span>
                            </div>
                        </div>

                        <!-- Divider -->
                        <div class="border-t border-white/5 pt-3">
                            <!-- Pose detection status -->
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs text-slate-500">AI Pose</span>
                                <div class="flex items-center gap-1.5">
                                    <div :class="['w-1.5 h-1.5 rounded-full transition-colors duration-300', poseStatusDot]"></div>
                                    <span class="text-xs font-medium" :class="poseStatusTextColor">
                                        {{ poseStatusLabel }}
                                    </span>
                                </div>
                            </div>

                            <!-- Landmark counter (hanya tampil saat assessing) -->
                            <template v-if="sessionState === 'assessing'">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs text-slate-600">Landmark</span>
                                    <span class="text-xs font-mono font-bold" :class="poseDetected ? 'text-emerald-400' : 'text-slate-500'">
                                        {{ poseDetectedCount }}/{{ poseTotalTarget }}
                                    </span>
                                </div>

                                <!-- Landmark progress bar -->
                                <div class="w-full h-1.5 rounded-full bg-dark-800 overflow-hidden">
                                    <div
                                        class="h-full rounded-full transition-all duration-300"
                                        :class="poseDetected ? 'bg-emerald-500' : 'bg-slate-700'"
                                        :style="{ width: `${poseTotalTarget > 0 ? (poseDetectedCount / poseTotalTarget) * 100 : 0}%` }"
                                    ></div>
                                </div>

                                <!-- Visibility/Confidence -->
                                <div class="flex items-center justify-between mt-2">
                                    <span class="text-xs text-slate-600">Confidence</span>
                                    <span class="text-xs font-mono font-bold" :class="poseDetected ? 'text-cyan-400' : 'text-slate-500'">
                                        {{ poseVisibility }}%
                                    </span>
                                </div>
                            </template>

                            <template v-else>
                                <p class="text-xs text-slate-600 mt-1">
                                    Mulai assessment untuk mengaktifkan deteksi pose.
                                </p>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Pose Validation card (tampil hanya saat assessing) -->
                <div v-if="sessionState === 'assessing'" class="card bg-base-100 border border-base-300 p-5">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4">Validasi Posisi</h3>

                    <!-- Status utama -->
                    <div class="flex items-center gap-3 mb-4 p-3 rounded-xl bg-dark-900/60 border border-white/5">
                        <span class="text-xl leading-none">{{ poseValidationIcon }}</span>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold" :class="poseValidationColor">
                                {{ poseValidationMessage }}
                            </p>
                            <p v-if="invalidReason" class="text-xs text-slate-500 mt-0.5 leading-relaxed truncate" :title="invalidReason">
                                {{ invalidReason }}
                            </p>
                        </div>
                        <div :class="['w-2 h-2 rounded-full flex-shrink-0', poseValidationDot]"></div>
                    </div>

                    <!-- Status badge -->
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs text-slate-600">Status</span>
                        <span class="text-xs font-mono font-bold px-2 py-0.5 rounded"
                              :class="{
                                  'bg-slate-800 text-slate-400':      validationStatus === 'NO_BODY',
                                  'bg-yellow-500/10 text-yellow-400': validationStatus === 'BODY_DETECTED',
                                  'bg-orange-500/10 text-orange-400': validationStatus === 'POSITION_INVALID',
                                  'bg-emerald-500/10 text-emerald-400': validationStatus === 'READY',
                              }">
                            {{ validationStatus }}
                        </span>
                    </div>

                    <!-- Progress bar stabilisasi -->
                    <template v-if="validationStatus !== 'NO_BODY' && !poseIsReady">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs text-slate-600">Stabilisasi</span>
                            <span class="text-xs font-mono text-slate-500">{{ stabilizationProgress }}%</span>
                        </div>
                        <div class="w-full h-1.5 rounded-full bg-dark-800 overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-200 bg-emerald-500/60"
                                :style="{ width: `${stabilizationProgress}%` }"
                            ></div>
                        </div>
                        <p class="text-xs text-slate-600 mt-2">
                            Tahan posisi beberapa detik agar sistem stabil
                        </p>
                    </template>

                    <!-- READY state indicator -->
                    <template v-if="poseIsReady">
                        <div class="flex items-center gap-2 mt-1 p-2 rounded-lg bg-emerald-500/10 border border-emerald-500/20">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            <span class="text-xs font-semibold text-emerald-400">Posisi terkunci — sistem berjalan</span>
                        </div>
                    </template>
                </div>

                <!-- Movement Status card (tampil hanya saat assessing) -->
                <div v-if="sessionState === 'assessing'" class="card bg-base-100 border border-base-300 p-5">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4">Movement Status</h3>

                    <!-- Push Up -->
                    <template v-if="isPushUpTest">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-xs text-slate-500">Repetisi</span>
                            <span class="text-3xl font-black text-white font-mono leading-none">{{ pushUpCount }}</span>
                        </div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs text-slate-600">Phase</span>
                            <span class="text-xs font-bold font-mono" :class="pushUpPhaseColor">{{ pushUpPhaseLabel }}</span>
                        </div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs text-slate-600">Form</span>
                            <span :class="['text-xs font-bold px-2 py-0.5 rounded border', pushUpFormBadge]">{{ pushUpFormLabel }}</span>
                        </div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs text-slate-600">Elbow Angle</span>
                            <span class="text-xs font-mono font-bold text-cyan-400">{{ pushUpElbowAngle > 0 ? `${pushUpElbowAngle}°` : '—' }}</span>
                        </div>
                        <div class="p-2.5 rounded-lg bg-dark-900/60 border border-white/5 min-h-[40px]">
                            <p v-if="pushUpFeedback" class="text-xs leading-relaxed"
                               :class="{
                                   'text-emerald-400': pushUpFormStatus === 'GOOD_FORM',
                                   'text-red-400':     pushUpFormStatus === 'BAD_FORM',
                                   'text-yellow-400':  pushUpFormStatus === 'ADJUST_POSITION',
                                   'text-slate-400':   pushUpFormStatus === 'NO_DATA',
                               }">{{ pushUpFeedback }}</p>
                            <p v-else class="text-xs text-slate-600">Menunggu gerakan...</p>
                        </div>
                    </template>

                    <!-- Sit Up -->
                    <template v-else-if="isSitUpTest">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-xs text-slate-500">Repetisi</span>
                            <span class="text-3xl font-black text-white font-mono leading-none">{{ sitUpCount }}</span>
                        </div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs text-slate-600">Phase</span>
                            <span class="text-xs font-bold font-mono" :class="sitUpPhaseColor">{{ sitUpPhaseLabel }}</span>
                        </div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs text-slate-600">Hip Angle</span>
                            <span class="text-xs font-mono font-bold text-cyan-400">{{ sitUpHipAngle > 0 ? `${sitUpHipAngle}°` : '—' }}</span>
                        </div>
                        <div class="p-2.5 rounded-lg bg-dark-900/60 border border-white/5 min-h-[40px]">
                            <p v-if="sitUpFeedback" class="text-xs leading-relaxed text-slate-300">{{ sitUpFeedback }}</p>
                            <p v-else class="text-xs text-slate-600">Menunggu gerakan...</p>
                        </div>
                    </template>

                    <!-- Elbow Plank -->
                    <template v-else-if="isElbowPlankTest">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-xs text-slate-500">Durasi Plank</span>
                            <span class="text-3xl font-black font-mono leading-none"
                                  :class="plankIsHolding ? 'text-emerald-400' : 'text-white'">
                                {{ plankHoldDurationFormatted }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs text-slate-600">Status</span>
                            <span class="text-xs font-bold font-mono" :class="plankPhaseColor">{{ plankPhaseLabel }}</span>
                        </div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs text-slate-600">Body Angle</span>
                            <span class="text-xs font-mono font-bold text-cyan-400">{{ plankBodyAngle > 0 ? `${plankBodyAngle}°` : '—' }}</span>
                        </div>
                        <div class="p-2.5 rounded-lg bg-dark-900/60 border border-white/5 min-h-[40px]">
                            <p v-if="plankFeedback" class="text-xs leading-relaxed text-slate-300">{{ plankFeedback }}</p>
                            <p v-else class="text-xs text-slate-600">Ambil posisi plank untuk memulai...</p>
                        </div>
                        <div v-if="plankTotalDuration > 0" class="mt-3 flex items-center justify-between">
                            <span class="text-xs text-slate-600">Total sesi</span>
                            <span class="text-xs font-mono text-slate-400">{{ plankTotalDuration.toFixed(1) }}s</span>
                        </div>
                    </template>

                    <!-- Wall Sit -->
                    <template v-else-if="isWallSitTest">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-xs text-slate-500">Durasi Hold</span>
                            <span class="text-3xl font-black font-mono leading-none"
                                  :class="isHolding ? 'text-emerald-400' : 'text-white'">
                                {{ holdDurationFormatted }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs text-slate-600">Status</span>
                            <span class="text-xs font-bold font-mono" :class="wallSitPhaseColor">{{ wallSitPhaseLabel }}</span>
                        </div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs text-slate-600">Knee Angle</span>
                            <span class="text-xs font-mono font-bold"
                                  :class="kneeAngle >= 80 && kneeAngle <= 100 ? 'text-emerald-400' : 'text-cyan-400'">
                                {{ kneeAngle > 0 ? `${kneeAngle}°` : '—' }}
                            </span>
                        </div>
                        <div class="p-2.5 rounded-lg bg-dark-900/60 border border-white/5 min-h-[40px]">
                            <p v-if="wallSitFeedback" class="text-xs leading-relaxed text-slate-300">{{ wallSitFeedback }}</p>
                            <p v-else class="text-xs text-slate-600">Masuk posisi Wall Sit untuk memulai...</p>
                        </div>
                        <div v-if="wallSitTotalDuration > 0" class="mt-3 flex items-center justify-between">
                            <span class="text-xs text-slate-600">Total sesi</span>
                            <span class="text-xs font-mono text-slate-400">{{ wallSitTotalDuration.toFixed(1) }}s</span>
                        </div>
                    </template>

                    <!-- Static Balance -->
                    <template v-else-if="isStaticBalanceTest">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-xs text-slate-500">Durasi Balance</span>
                            <span class="text-3xl font-black font-mono leading-none"
                                  :class="isBalancing ? 'text-emerald-400' : 'text-white'">
                                {{ balanceDurationFormatted }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs text-slate-600">Status</span>
                            <span class="text-xs font-bold font-mono" :class="balancePhaseColor">{{ balancePhaseLabel }}</span>
                        </div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs text-slate-600">Kaki Tumpuan</span>
                            <span class="text-xs font-mono font-bold text-cyan-400">{{ standingLeg }}</span>
                        </div>
                        <div class="p-2.5 rounded-lg bg-dark-900/60 border border-white/5 min-h-[40px]">
                            <p v-if="balanceFeedback" class="text-xs leading-relaxed text-slate-300">{{ balanceFeedback }}</p>
                            <p v-else class="text-xs text-slate-600">Angkat satu kaki untuk memulai...</p>
                        </div>
                        <div v-if="balanceTotalDuration > 0" class="mt-3 flex items-center justify-between">
                            <span class="text-xs text-slate-600">Total sesi</span>
                            <span class="text-xs font-mono text-slate-400">{{ balanceTotalDuration.toFixed(1) }}s</span>
                        </div>
                    </template>

                    <!-- Squat Jump -->
                    <template v-else-if="isSquatJumpTest">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-xs text-slate-500">Repetisi</span>
                            <span class="text-3xl font-black text-white font-mono leading-none">{{ squatJumpCount }}</span>
                        </div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs text-slate-600">Phase</span>
                            <span class="text-xs font-bold font-mono" :class="squatJumpPhaseColor">{{ squatJumpPhaseLabel }}</span>
                        </div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs text-slate-600">Knee Angle</span>
                            <span class="text-xs font-mono font-bold text-cyan-400">{{ squatJumpKneeAngle > 0 ? `${squatJumpKneeAngle}°` : '—' }}</span>
                        </div>
                        <div class="p-2.5 rounded-lg bg-dark-900/60 border border-white/5 min-h-[40px]">
                            <p v-if="squatJumpFeedback" class="text-xs leading-relaxed text-slate-300">{{ squatJumpFeedback }}</p>
                            <p v-else class="text-xs text-slate-600">Lakukan squat lalu lompat...</p>
                        </div>
                    </template>

                    <!-- Deep Squat -->
                    <template v-else-if="isDeepSquatTest">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-xs text-slate-500">Repetisi</span>
                            <span class="text-3xl font-black text-white font-mono leading-none">{{ deepSquatCount }}</span>
                        </div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs text-slate-600">Phase</span>
                            <span class="text-xs font-bold font-mono" :class="deepSquatPhaseColor">{{ deepSquatPhaseLabel }}</span>
                        </div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs text-slate-600">Knee Angle</span>
                            <span class="text-xs font-mono font-bold text-cyan-400">{{ deepSquatKneeAngle > 0 ? `${deepSquatKneeAngle}°` : '—' }}</span>
                        </div>
                        <div class="p-2.5 rounded-lg bg-dark-900/60 border border-white/5 min-h-[40px]">
                            <p v-if="deepSquatFeedback" class="text-xs leading-relaxed text-slate-300">{{ deepSquatFeedback }}</p>
                            <p v-else class="text-xs text-slate-600">Lakukan gerakan squat untuk memulai...</p>
                        </div>
                    </template>

                    <!-- Sit and Reach -->
                    <template v-else-if="isSitAndReachTest">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-xs text-slate-500">Best Reach</span>
                            <span class="text-2xl font-black font-mono leading-none"
                                  :class="sitReachBestCm > 0 ? 'text-emerald-400' : sitReachBestCm < 0 ? 'text-yellow-400' : 'text-white'">
                                {{ sitReachBestCm !== 0 || sitReachBestDist < 999
                                    ? `${sitReachBestCm >= 0 ? '+' : ''}${sitReachBestCm} cm`
                                    : '—' }}
                            </span>
                        </div>
                        <div class="text-xs text-slate-600 -mt-2 mb-3 italic">⚠ Estimasi kasar — belum dikalibrasi</div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs text-slate-600">Status</span>
                            <span class="text-xs font-bold font-mono" :class="sitReachPhaseColor">{{ sitReachPhaseLabel }}</span>
                        </div>
                        <div class="p-2.5 rounded-lg bg-dark-900/60 border border-white/5 min-h-[40px]">
                            <p v-if="sitReachFeedback" class="text-xs leading-relaxed text-slate-300">{{ sitReachFeedback }}</p>
                            <p v-else class="text-xs text-slate-600">Duduk, luruskan kaki, lalu raih ujung kaki...</p>
                        </div>
                    </template>

                    <!-- Fallback -->
                    <template v-else>
                        <div class="flex flex-col items-center gap-2 py-4 text-center">
                            <span class="text-2xl">🚧</span>
                            <p class="text-xs text-slate-500 leading-relaxed">Movement detection belum tersedia untuk test ini.</p>
                        </div>
                    </template>
                </div>

                <!-- Result card (setelah stopped) -->
                <div v-if="sessionState === 'stopped' && assessmentResult"
                     class="card bg-base-100 border border-emerald-500/20 bg-emerald-500/5 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xs font-semibold text-emerald-400 uppercase tracking-wider">Hasil Assessment</h3>
                        <span v-if="isSaving" class="text-xs text-slate-500 animate-pulse">Menyimpan...</span>
                        <span v-else-if="saveSuccess" class="text-xs text-emerald-400">✓ Tersimpan</span>
                        <span v-else-if="saveError" class="text-xs text-red-400">✗ Gagal disimpan</span>
                    </div>

                    <div class="space-y-2 mb-4">
                        <div class="flex justify-between">
                            <span class="text-xs text-slate-500">Atlet</span>
                            <span class="text-sm font-semibold text-white">{{ props.athlete.name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-xs text-slate-500">Tes</span>
                            <span class="text-sm text-white">{{ props.test.name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-xs text-slate-500">Durasi Sesi</span>
                            <span class="text-xs font-mono text-slate-300">{{ formatDuration(assessmentResult.durationSec) }}</span>
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-dark-950/60 border border-white/5 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-slate-500">Nilai</span>
                            <div class="text-right">
                                <span class="text-2xl font-black font-mono text-emerald-400">{{ assessmentResult.resultDisplay }}</span>
                                <span v-if="assessmentResult.estimated" class="block text-xs text-yellow-500 italic">Estimasi</span>
                            </div>
                        </div>
                        <template v-if="assessmentResult.benchmarkSnapshot">
                            <div class="flex items-center justify-between pt-2 border-t border-white/5">
                                <span class="text-xs text-slate-500">Benchmark</span>
                                <span class="text-sm font-mono text-slate-300">
                                    {{ assessmentResult.benchmarkSnapshot.value }} {{ assessmentResult.benchmarkSnapshot.unit }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-slate-500">Pencapaian</span>
                                <span class="text-xl font-black font-mono"
                                      :class="achievementColor(assessmentResult.achievement)">
                                    {{ assessmentResult.achievement != null ? `${assessmentResult.achievement}%` : '—' }}
                                </span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-dark-800 overflow-hidden">
                                <div class="h-full rounded-full"
                                     :class="{
                                         'bg-emerald-500': assessmentResult.achievement >= 80,
                                         'bg-yellow-500':  assessmentResult.achievement >= 50 && assessmentResult.achievement < 80,
                                         'bg-red-500':     assessmentResult.achievement != null && assessmentResult.achievement < 50,
                                     }"
                                     :style="{ width: `${assessmentResult.achievement ?? 0}%` }">
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

            </div><!-- /right panel -->

        </div><!-- /grid -->
    </AppLayout>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import CameraPreview from '@/Components/CameraPreview.vue';
import PoseDetector from '@/Components/PoseDetector.vue';
import { usePoseValidation }         from '@/Composables/usePoseValidation.js';
import { usePushUpDetection,        PUSHUP_DEBUG    } from '@/Composables/usePushUpDetection.js';
import { useSitUpDetection,         SITUP_DEBUG     } from '@/Composables/useSitUpDetection.js';
import { useStaticBalanceDetection, BALANCE_DEBUG   } from '@/Composables/useStaticBalanceDetection.js';
import { useWallSitDetection,       WALLSIT_DEBUG   } from '@/Composables/useWallSitDetection.js';
import { useElbowPlankDetection,    PLANK_DEBUG     } from '@/Composables/useElbowPlankDetection.js';
import { useDeepSquatDetection,     DEEPSQUAT_DEBUG } from '@/Composables/useDeepSquatDetection.js';
import { useSquatJumpDetection,     SQUATJUMP_DEBUG } from '@/Composables/useSquatJumpDetection.js';
import { useSitAndReachDetection,   SITANDREACH_DEBUG } from '@/Composables/useSitAndReachDetection.js';
import { useAssessmentSettings }     from '@/Composables/useAssessmentSettings.js';

// ── Props ──────────────────────────────────────────────────────────────────
const props = defineProps({
    athlete: {
        type: Object,
        required: true,
        // { id, name, athlete_number, ... }
    },
    test: {
        type: Object,
        default: () => ({
            id: 1, number: 1,
            name: 'Keseimbangan Statis',
            icon: '🧍',
            category: 'Balance',
            unit: 'Detik (s)',
            duration: '2-3 menit',
            description: 'Mengukur kemampuan mempertahankan posisi seimbang.',
        }),
    },
});

// ── Session state ──────────────────────────────────────────────────────────
const cameraRef    = ref(null);
const sessionState = ref('idle');      // idle | cameraReady | assessing | stopped
const cameraActive = ref(false);
const cameraErrorMsg = ref('');
const elapsedSeconds = ref(0);

// ── Pose Detection State ───────────────────────────────────────────────────
const poseStatus        = ref('searching');
const poseDetectedCount = ref(0);
const poseTotalTarget   = ref(15);
const poseVisibility    = ref(0);

// ── Performance data ───────────────────────────────────────────────────────
const perfData = ref({
    loopFps: 0, inferenceFps: 0,
    loopAvgMs: 0, loopMinMs: 0, loopMaxMs: 0,
    preprocessAvgMs: 0, preprocessMinMs: 0, preprocessMaxMs: 0,
    sendAvgMs: 0, sendMinMs: 0, sendMaxMs: 0,
    drawAvgMs: 0, drawMinMs: 0, drawMaxMs: 0,
    emitAvgMs: 0, idleAvgMs: 0,
    totalFrames: 0, skippedFrames: 0, resultFrames: 0,
    videoWidth: 0, videoHeight: 0, videoReadyState: -1,
    inferenceWidth: 0, inferenceHeight: 0,
    devicePixelRatio: 1, tabVisible: true,
    droppedFrames: null, totalVideoFrames: null, corruptedFrames: null,
});

// ── Active video element ───────────────────────────────────────────────────
const activeVideoElement = computed(() => cameraRef.value?.videoRef ?? null);

// ── Pose Validation ────────────────────────────────────────────────────────
const {
    validationStatus,
    statusMessage:       poseValidationMessage,
    isReady:             poseIsReady,
    statusColor:         poseValidationColor,
    statusDotColor:      poseValidationDot,
    statusIcon:          poseValidationIcon,
    stabilizationProgress,
    invalidReason,
    processPoseFrame,
    resetValidation,
    setCustomEvaluate,
    debugValidationReadyCount,
} = usePoseValidation();

// ── Push Up ────────────────────────────────────────────────────────────────
const isPushUpTest = computed(() => props.test.name === 'Push Up');

// Push Up single-side validation
const PUSHUP_SIDE_VISIBILITY = 0.5;

function isPushUpSideValid(landmarks, side) {
    const idx = side === 'left' ? [11, 13, 15] : [12, 14, 16];
    return idx.every(i => {
        const lm = landmarks?.[i];
        return lm != null && typeof lm.x === 'number' && (lm.visibility ?? 0) >= PUSHUP_SIDE_VISIBILITY;
    });
}

const pushUpCountingSide = ref('—');
const pushUpEvalDiag = ref({
    evaluatorCalled: false, callCount: 0,
    lastLeftOk: false, lastRightOk: false,
    lastRawStatus: '—', lastReason: '—',
    rShoulder: 0, rElbow: 0, rWrist: 0,
    lShoulder: 0, lElbow: 0, lWrist: 0,
    readyCount: 0, posInvalidCount: 0, noBodyCount: 0, bodyDetectedCount: 0,
    consecValidFrames: 0,
});

function pushUpEvaluateFrame(landmarks, detectedCount, avgVisibility) {
    const diag = pushUpEvalDiag.value;
    diag.evaluatorCalled = true;
    diag.callCount++;
    const vis = (idx) => Math.round((landmarks?.[idx]?.visibility ?? 0) * 100);
    diag.rShoulder = vis(12); diag.rElbow = vis(14); diag.rWrist = vis(16);
    diag.lShoulder = vis(11); diag.lElbow = vis(13); diag.lWrist = vis(15);
    if (!landmarks || landmarks.length < 33) {
        diag.lastRawStatus = 'NO_BODY'; diag.lastReason = 'Tidak ada landmark terdeteksi'; diag.noBodyCount++;
        return { rawStatus: 'NO_BODY', reason: diag.lastReason };
    }
    const leftOk  = isPushUpSideValid(landmarks, 'left');
    const rightOk = isPushUpSideValid(landmarks, 'right');
    diag.lastLeftOk = leftOk; diag.lastRightOk = rightOk;
    if (!leftOk && !rightOk) {
        diag.lastRawStatus = 'POSITION_INVALID';
        diag.lastReason = `L✗R✗ — R(${diag.rShoulder}%/${diag.rElbow}%/${diag.rWrist}%) L(${diag.lShoulder}%/${diag.lElbow}%/${diag.lWrist}%)`;
        diag.posInvalidCount++;
        return { rawStatus: 'POSITION_INVALID', reason: diag.lastReason };
    }
    const rightShoulder = landmarks[12];
    const leftShoulder  = landmarks[11];
    const hasShoulder = (rightOk && (rightShoulder?.visibility ?? 0) >= PUSHUP_SIDE_VISIBILITY) ||
                        (leftOk  && (leftShoulder?.visibility  ?? 0) >= PUSHUP_SIDE_VISIBILITY);
    if (!hasShoulder) {
        diag.lastRawStatus = 'NO_BODY'; diag.lastReason = 'Tidak ada bahu yang terdeteksi'; diag.noBodyCount++;
        return { rawStatus: 'NO_BODY', reason: diag.lastReason };
    }
    if (detectedCount < 4) {
        diag.lastRawStatus = 'BODY_DETECTED';
        diag.lastReason = `Hanya ${detectedCount} landmark terdeteksi (min 4 untuk push-up)`;
        diag.bodyDetectedCount++;
        return { rawStatus: 'BODY_DETECTED', reason: diag.lastReason };
    }
    const countingSide = rightOk && leftOk ? 'BOTH' : rightOk ? 'RIGHT' : 'LEFT';
    diag.lastRawStatus = 'READY';
    diag.lastReason = `Single-side OK — ${countingSide} R(${diag.rShoulder}%/${diag.rElbow}%/${diag.rWrist}%)`;
    diag.readyCount++;
    return { rawStatus: 'READY', reason: diag.lastReason };
}

function installPushUpValidator()   { setCustomEvaluate(pushUpEvaluateFrame); }
function uninstallPushUpValidator() { setCustomEvaluate(null); }

const {
    repetitionCount:  pushUpCount,
    currentPhase:     pushUpPhase,
    formStatus:       pushUpFormStatus,
    elbowAngle:       pushUpElbowAngle,
    bodyAngle:        pushUpBodyAngle,
    feedback:         pushUpFeedback,
    isValidRep:       pushUpIsValidRep,
    formStatusLabel:  pushUpFormLabel,
    formStatusBadge:  pushUpFormBadge,
    phaseLabel:       pushUpPhaseLabel,
    phaseColor:       pushUpPhaseColor,
    debugFps:                 pushUpDebugFps,
    debugFrameCount:          pushUpDebugFrameCount,
    debugLandmarkReport:      pushUpDebugLandmarks,
    debugBodyAlignmentReport: pushUpDebugBodyAlign,
    debugInferenceStats:      pushUpDebugInference,
    debugStateMachine:        pushUpDebugSM,
    debugFrameHistory:        pushUpDebugHistory,
    debugRepCycle:            pushUpDebugRepCycle,
    debugRepCycleHistory:     pushUpDebugRepHistory,
    debugCountingLmReport:    pushUpDebugCountingLm,
    debugCountingPipeline:    pushUpDebugPipeline,
    debugPipelineCumulative:  pushUpDebugCumul,
    processPushUpFrame,
    resetPushUp,
} = usePushUpDetection();

// ── Sit Up ─────────────────────────────────────────────────────────────────
const isSitUpTest = computed(() => props.test.name === 'Sit Up');

const SITUP_SIDE_VISIBILITY = 0.5;
function isSitUpSideValid(landmarks, side) {
    const idx = side === 'left' ? [11, 23, 25] : [12, 24, 26];
    return idx.every(i => {
        const lm = landmarks?.[i];
        return lm != null && typeof lm.x === 'number' && (lm.visibility ?? 0) >= SITUP_SIDE_VISIBILITY;
    });
}
function sitUpEvaluateFrame(landmarks, detectedCount, avgVisibility) {
    if (!landmarks || landmarks.length < 33) return { rawStatus: 'NO_BODY', reason: 'Tidak ada landmark terdeteksi' };
    const leftOk  = isSitUpSideValid(landmarks, 'left');
    const rightOk = isSitUpSideValid(landmarks, 'right');
    if (!leftOk && !rightOk) return { rawStatus: 'POSITION_INVALID', reason: 'Kedua sisi — shoulder/hip/knee tidak terdeteksi dengan baik' };
    if (detectedCount < 4) return { rawStatus: 'BODY_DETECTED', reason: `Hanya ${detectedCount} landmark terdeteksi (min 4)` };
    const countingSide = rightOk && leftOk ? 'BOTH' : rightOk ? 'RIGHT' : 'LEFT';
    return { rawStatus: 'READY', reason: `Sit Up single-side OK — ${countingSide}` };
}
function installSitUpValidator()   { setCustomEvaluate(sitUpEvaluateFrame); }
function uninstallSitUpValidator() { setCustomEvaluate(null); }

const {
    repetitionCount:        sitUpCount,
    currentPhase:           sitUpPhase,
    formStatus:             sitUpFormStatus,
    hipAngle:               sitUpHipAngle,
    feedback:               sitUpFeedback,
    isValidRep:             sitUpIsValidRep,
    countingSide:           sitUpCountingSide,
    phaseLabel:             sitUpPhaseLabel,
    phaseColor:             sitUpPhaseColor,
    formStatusBadge:        sitUpFormBadge,
    debugFps:               sitUpDebugFps,
    debugLandmarkReport:    sitUpDebugLandmarks,
    debugCountingLmReport:  sitUpDebugCountingLm,
    debugCountingPipeline:  sitUpDebugPipeline,
    debugPipelineCumulative:sitUpDebugCumul,
    debugStateMachine:      sitUpDebugSM,
    debugFrameHistory:      sitUpDebugHistory,
    debugRepCycle:          sitUpDebugRepCycle,
    debugRepCycleHistory:   sitUpDebugRepHistory,
    processSitUpFrame,
    resetSitUp,
} = useSitUpDetection();

// ── Static Balance ─────────────────────────────────────────────────────────
const isStaticBalanceTest = computed(() => props.test.name === 'Keseimbangan Statis');

const BALANCE_SIDE_VISIBILITY = 0.5;
function balanceEvaluateFrame(landmarks, detectedCount) {
    if (!landmarks || landmarks.length < 33) return { rawStatus: 'NO_BODY', reason: 'Tidak ada landmark terdeteksi' };
    const required = [0, 11, 12, 23, 24];
    const allUpperBodyOk = required.every(i => {
        const lm = landmarks[i];
        return lm != null && typeof lm.x === 'number' && (lm.visibility ?? 0) >= BALANCE_SIDE_VISIBILITY;
    });
    if (!allUpperBodyOk) return { rawStatus: 'POSITION_INVALID', reason: 'Pastikan kepala, bahu, dan pinggul terlihat kamera' };
    const leftAnkle    = landmarks[27];
    const rightAnkle   = landmarks[28];
    const leftAnkleOk  = leftAnkle  != null && (leftAnkle.visibility  ?? 0) >= BALANCE_SIDE_VISIBILITY;
    const rightAnkleOk = rightAnkle != null && (rightAnkle.visibility ?? 0) >= BALANCE_SIDE_VISIBILITY;
    if (!leftAnkleOk && !rightAnkleOk) return { rawStatus: 'POSITION_INVALID', reason: 'Pastikan kaki terlihat — mundur dari kamera agar seluruh tubuh terlihat' };
    if (detectedCount < 6) return { rawStatus: 'BODY_DETECTED', reason: `Hanya ${detectedCount} landmark terdeteksi (min 6 untuk balance)` };
    return { rawStatus: 'READY', reason: `Balance OK — ankle: ${leftAnkleOk ? 'L✓' : 'L✗'} ${rightAnkleOk ? 'R✓' : 'R✗'}` };
}
function installBalanceValidator()   { setCustomEvaluate(balanceEvaluateFrame); }
function uninstallBalanceValidator() { setCustomEvaluate(null); }

const {
    currentPhase:              balancePhase,
    balanceDuration,
    bestDuration:              balanceBestDuration,
    totalDuration:             balanceTotalDuration,
    standingLeg,
    ankleDiff,
    feedback:                  balanceFeedback,
    isBalancing,
    phaseLabel:                balancePhaseLabel,
    phaseColor:                balancePhaseColor,
    standingLegLabel,
    balanceDurationFormatted,
    debugFps:               balanceDebugFps,
    debugLandmarkReport:    balanceDebugLandmarks,
    debugAnkleDetail:       balanceDebugAnkle,
    debugCountingPipeline:  balanceDebugPipeline,
    debugPipelineCumulative:balanceDebugCumul,
    debugStateMachine:      balanceDebugSM,
    debugFrameHistory:      balanceDebugHistory,
    debugBalanceEvents:     balanceDebugEvents,
    processBalanceFrame,
    resetBalance,
} = useStaticBalanceDetection();

// ── Wall Sit ───────────────────────────────────────────────────────────────
const isWallSitTest = computed(() => props.test.name === 'Wall Sit');

const WALLSIT_SIDE_VISIBILITY = 0.5;
function wallSitEvaluateFrame(landmarks, detectedCount) {
    if (!landmarks || landmarks.length < 33) return { rawStatus: 'NO_BODY', reason: 'Tidak ada landmark terdeteksi' };
    const lsOk = landmarks[11] != null && (landmarks[11].visibility ?? 0) >= WALLSIT_SIDE_VISIBILITY;
    const rsOk = landmarks[12] != null && (landmarks[12].visibility ?? 0) >= WALLSIT_SIDE_VISIBILITY;
    if (!lsOk && !rsOk) return { rawStatus: 'POSITION_INVALID', reason: 'Pastikan bahu terlihat kamera' };
    const leftLegOk  = [23, 25, 27].every(i => landmarks[i] != null && (landmarks[i].visibility ?? 0) >= WALLSIT_SIDE_VISIBILITY);
    const rightLegOk = [24, 26, 28].every(i => landmarks[i] != null && (landmarks[i].visibility ?? 0) >= WALLSIT_SIDE_VISIBILITY);
    if (!leftLegOk && !rightLegOk) return { rawStatus: 'POSITION_INVALID', reason: 'Pastikan kaki (pinggul, lutut, pergelangan) terlihat' };
    if (detectedCount < 6) return { rawStatus: 'BODY_DETECTED', reason: `Hanya ${detectedCount} landmark terdeteksi (min 6)` };
    const side = leftLegOk && rightLegOk ? 'BOTH' : leftLegOk ? 'LEFT' : 'RIGHT';
    return { rawStatus: 'READY', reason: `Wall Sit OK — leg: ${side}` };
}
function installWallSitValidator()   { setCustomEvaluate(wallSitEvaluateFrame); }
function uninstallWallSitValidator() { setCustomEvaluate(null); }

const {
    currentPhase:        wallSitPhase,
    holdDuration,
    bestDuration:        wallSitBestDuration,
    totalDuration:       wallSitTotalDuration,
    kneeAngle,
    countingSide:        wallSitCountingSide,
    feedback:            wallSitFeedback,
    isHolding,
    phaseLabel:          wallSitPhaseLabel,
    phaseColor:          wallSitPhaseColor,
    holdDurationFormatted,
    debugFps:            wallSitDebugFps,
    debugLandmarkReport: wallSitDebugLandmarks,
    debugKneeDetail:     wallSitDebugKnee,
    debugCountingPipeline: wallSitDebugPipeline,
    debugPipelineCumulative: wallSitDebugCumul,
    debugStateMachine:   wallSitDebugSM,
    debugFrameHistory:   wallSitDebugHistory,
    debugHoldEvents:     wallSitDebugEvents,
    processWallSitFrame,
    resetWallSit,
} = useWallSitDetection();

// ── Elbow Plank ────────────────────────────────────────────────────────────
const isElbowPlankTest = computed(() => props.test.name === 'Elbow Plank');

const PLANK_SIDE_VISIBILITY = 0.5;
function plankEvaluateFrame(landmarks, detectedCount) {
    if (!landmarks || landmarks.length < 33) return { rawStatus: 'NO_BODY', reason: 'Tidak ada landmark terdeteksi' };
    const leftOk  = [11, 23, 27].every(i => landmarks[i] != null && (landmarks[i].visibility ?? 0) >= PLANK_SIDE_VISIBILITY);
    const rightOk = [12, 24, 28].every(i => landmarks[i] != null && (landmarks[i].visibility ?? 0) >= PLANK_SIDE_VISIBILITY);
    if (!leftOk && !rightOk) return { rawStatus: 'POSITION_INVALID', reason: 'Pastikan bahu, pinggul, dan pergelangan kaki terlihat kamera' };
    if (detectedCount < 5) return { rawStatus: 'BODY_DETECTED', reason: `Hanya ${detectedCount} landmark terdeteksi (min 5)` };
    const side = leftOk && rightOk ? 'BOTH' : leftOk ? 'LEFT' : 'RIGHT';
    return { rawStatus: 'READY', reason: `Plank OK — side: ${side}` };
}
function installPlankValidator()   { setCustomEvaluate(plankEvaluateFrame); }
function uninstallPlankValidator() { setCustomEvaluate(null); }

const {
    currentPhase:        plankPhase,
    holdDuration:        plankHoldDuration,
    bestDuration:        plankBestDuration,
    totalDuration:       plankTotalDuration,
    bodyAngle:           plankBodyAngle,
    countingSide:        plankCountingSide,
    feedback:            plankFeedback,
    isHolding:           plankIsHolding,
    phaseLabel:          plankPhaseLabel,
    phaseColor:          plankPhaseColor,
    holdDurationFormatted: plankHoldDurationFormatted,
    debugFps:            plankDebugFps,
    debugLandmarkReport: plankDebugLandmarks,
    debugAlignmentDetail:plankDebugAlignment,
    debugCountingPipeline: plankDebugPipeline,
    debugPipelineCumulative: plankDebugCumul,
    debugStateMachine:   plankDebugSM,
    debugFrameHistory:   plankDebugHistory,
    debugHoldEvents:     plankDebugEvents,
    processPlankFrame,
    resetPlank,
} = useElbowPlankDetection();

// ── Deep Squat ─────────────────────────────────────────────────────────────
const isDeepSquatTest = computed(() => props.test.name === 'Deep Squat');

const DEEPSQUAT_SIDE_VISIBILITY = 0.5;
function deepSquatEvaluateFrame(landmarks, detectedCount) {
    if (!landmarks || landmarks.length < 33) return { rawStatus: 'NO_BODY', reason: 'Tidak ada landmark terdeteksi' };
    const leftOk  = [23, 25, 27].every(i => landmarks[i] != null && (landmarks[i].visibility ?? 0) >= DEEPSQUAT_SIDE_VISIBILITY);
    const rightOk = [24, 26, 28].every(i => landmarks[i] != null && (landmarks[i].visibility ?? 0) >= DEEPSQUAT_SIDE_VISIBILITY);
    if (!leftOk && !rightOk) return { rawStatus: 'POSITION_INVALID', reason: 'Pastikan pinggul, lutut, dan pergelangan kaki terlihat kamera' };
    if (detectedCount < 4) return { rawStatus: 'BODY_DETECTED', reason: `Hanya ${detectedCount} landmark terdeteksi (min 4)` };
    const side = leftOk && rightOk ? 'BOTH' : leftOk ? 'LEFT' : 'RIGHT';
    return { rawStatus: 'READY', reason: `Deep Squat OK — leg: ${side}` };
}
function installDeepSquatValidator()   { setCustomEvaluate(deepSquatEvaluateFrame); }
function uninstallDeepSquatValidator() { setCustomEvaluate(null); }

const {
    repetitionCount:        deepSquatCount,
    currentPhase:           deepSquatPhase,
    kneeAngle:              deepSquatKneeAngle,
    countingSide:           deepSquatCountingSide,
    feedback:               deepSquatFeedback,
    isValidRep:             deepSquatIsValidRep,
    phaseLabel:             deepSquatPhaseLabel,
    phaseColor:             deepSquatPhaseColor,
    debugFps:               deepSquatDebugFps,
    debugLandmarkReport:    deepSquatDebugLandmarks,
    debugCountingLmReport:  deepSquatDebugCountingLm,
    debugCountingPipeline:  deepSquatDebugPipeline,
    debugPipelineCumulative:deepSquatDebugCumul,
    debugStateMachine:      deepSquatDebugSM,
    debugFrameHistory:      deepSquatDebugHistory,
    debugRepCycle:          deepSquatDebugRepCycle,
    debugRepCycleHistory:   deepSquatDebugRepHistory,
    processDeepSquatFrame,
    resetDeepSquat,
} = useDeepSquatDetection();

// ── Squat Jump ─────────────────────────────────────────────────────────────
const isSquatJumpTest = computed(() => props.test.name === 'Squat Jump');

const SQUATJUMP_SIDE_VIS = 0.5;
function squatJumpEvaluateFrame(landmarks, detectedCount) {
    if (!landmarks || landmarks.length < 33) return { rawStatus: 'NO_BODY', reason: 'Tidak ada landmark terdeteksi' };
    const leftOk  = [23, 25, 27].every(i => landmarks[i] != null && (landmarks[i].visibility ?? 0) >= SQUATJUMP_SIDE_VIS);
    const rightOk = [24, 26, 28].every(i => landmarks[i] != null && (landmarks[i].visibility ?? 0) >= SQUATJUMP_SIDE_VIS);
    if (!leftOk && !rightOk) return { rawStatus: 'POSITION_INVALID', reason: 'Pastikan pinggul, lutut, dan pergelangan kaki terlihat kamera' };
    if (detectedCount < 4) return { rawStatus: 'BODY_DETECTED', reason: `Hanya ${detectedCount} landmark terdeteksi (min 4)` };
    const side = leftOk && rightOk ? 'BOTH' : leftOk ? 'LEFT' : 'RIGHT';
    return { rawStatus: 'READY', reason: `Squat Jump OK — leg: ${side}` };
}
function installSquatJumpValidator()   { setCustomEvaluate(squatJumpEvaluateFrame); }
function uninstallSquatJumpValidator() { setCustomEvaluate(null); }

const {
    repetitionCount:        squatJumpCount,
    currentPhase:           squatJumpPhase,
    kneeAngle:              squatJumpKneeAngle,
    countingSide:           squatJumpCountingSide,
    feedback:               squatJumpFeedback,
    isValidRep:             squatJumpIsValidRep,
    phaseLabel:             squatJumpPhaseLabel,
    phaseColor:             squatJumpPhaseColor,
    debugFps:               squatJumpDebugFps,
    debugLandmarkReport:    squatJumpDebugLandmarks,
    debugJumpDetail:        squatJumpDebugJump,
    debugCountingPipeline:  squatJumpDebugPipeline,
    debugPipelineCumulative:squatJumpDebugCumul,
    debugStateMachine:      squatJumpDebugSM,
    debugFrameHistory:      squatJumpDebugHistory,
    debugRepCycle:          squatJumpDebugRepCycle,
    debugRepCycleHistory:   squatJumpDebugRepHistory,
    processSquatJumpFrame,
    resetSquatJump,
} = useSquatJumpDetection();

// ── Sit and Reach ──────────────────────────────────────────────────────────
const isSitAndReachTest = computed(() => props.test.name === 'Sit and Reach');

const SITREACH_SIDE_VIS = 0.5;
function sitAndReachEvaluateFrame(landmarks, detectedCount) {
    if (!landmarks || landmarks.length < 33) return { rawStatus: 'NO_BODY', reason: 'Tidak ada landmark terdeteksi' };
    const leftOk  = [11, 23, 25].every(i => landmarks[i] != null && (landmarks[i].visibility ?? 0) >= SITREACH_SIDE_VIS);
    const rightOk = [12, 24, 26].every(i => landmarks[i] != null && (landmarks[i].visibility ?? 0) >= SITREACH_SIDE_VIS);
    if (!leftOk && !rightOk) return { rawStatus: 'POSITION_INVALID', reason: 'Pastikan bahu, pinggul, dan lutut terlihat kamera' };
    const leftFootOk  = landmarks[31] != null && (landmarks[31].visibility ?? 0) >= SITREACH_SIDE_VIS;
    const rightFootOk = landmarks[32] != null && (landmarks[32].visibility ?? 0) >= SITREACH_SIDE_VIS;
    if (!leftFootOk && !rightFootOk) return { rawStatus: 'POSITION_INVALID', reason: 'Pastikan kaki (ujung kaki) terlihat kamera — mundur agar seluruh tubuh tampak' };
    if (detectedCount < 5) return { rawStatus: 'BODY_DETECTED', reason: `Hanya ${detectedCount} landmark terdeteksi (min 5)` };
    return { rawStatus: 'READY', reason: 'Sit and Reach OK' };
}
function installSitAndReachValidator()   { setCustomEvaluate(sitAndReachEvaluateFrame); }
function uninstallSitAndReachValidator() { setCustomEvaluate(null); }

const {
    currentPhase:        sitReachPhase,
    reachDistance:       sitReachDistance,
    bestReachDistance:   sitReachBestDist,
    reachCm:             sitReachCm,
    bestReachCm:         sitReachBestCm,
    countingSide:        sitReachCountingSide,
    feedback:            sitReachFeedback,
    isReaching:          sitReachIsReaching,
    sittingValid:        sitReachSittingValid,
    legStraightValid:    sitReachLegStraightValid,
    phaseLabel:          sitReachPhaseLabel,
    phaseColor:          sitReachPhaseColor,
    bestReachLabel:      sitReachBestLabel,
    debugFps:            sitReachDebugFps,
    debugLandmarkReport: sitReachDebugLandmarks,
    debugReachDetail:    sitReachDebugDetail,
    debugCountingPipeline: sitReachDebugPipeline,
    debugPipelineCumulative: sitReachDebugCumul,
    debugStateMachine:   sitReachDebugSM,
    debugFrameHistory:   sitReachDebugHistory,
    debugBestReachHistory: sitReachDebugBestHistory,
    processSitAndReachFrame,
    resetSitAndReach,
} = useSitAndReachDetection();

// ── Assessment Settings (durasi, countdown, benchmark) ────────────────────
const { getDuration, getCountdown, getBenchmarkSnapshot, calculateAchievement } = useAssessmentSettings();

// ── Countdown state ────────────────────────────────────────────────────────
const countdownValue  = ref(0);
const isCountingDown  = ref(false);
let countdownInterval = null;

// ── Timer ──────────────────────────────────────────────────────────────────
const assessmentDurationSec = ref(60);
const remainingSeconds      = ref(60);
let   timerInterval         = null;

// ── Result & save state ────────────────────────────────────────────────────
const assessmentResult = ref(null);
const isSaving         = ref(false);
const saveSuccess      = ref(false);
const saveError        = ref('');

// ── Screenshot buffer (tangkap saat kesalahan) ─────────────────────────────
const errorScreenshots = ref([]); // [{timestamp, dataUrl, reason}]
const MAX_SCREENSHOTS  = 10;

// ── Audio state ────────────────────────────────────────────────────────────
const isMuted = ref(false);
let   _audioCtx = null;

function getAudioCtx() {
    if (!_audioCtx) _audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    return _audioCtx;
}

/**
 * Play tone via Web Audio API.
 * type: 'sine'|'square'|'sawtooth'
 */
function playTone(freq, type, duration, volume = 0.15) {
    if (isMuted.value) return;
    try {
        const ctx  = getAudioCtx();
        if (ctx.state === 'suspended') ctx.resume();
        const osc  = ctx.createOscillator();
        const gain = ctx.createGain();
        const now  = ctx.currentTime;
        osc.type            = type;
        osc.frequency.value = freq;
        gain.gain.setValueAtTime(volume, now);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + duration);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start(now);
        osc.stop(now + duration);
    } catch (_) {}
}

// Suara rep berhasil (ding pendek)
function playRepSuccess() {
    playTone(880, 'sine', 0.12, 0.12);
    setTimeout(() => playTone(1100, 'sine', 0.1, 0.1), 80);
}

// Suara kesalahan (buzz pendek)
function playError() {
    playTone(200, 'sawtooth', 0.25, 0.18);
}

// Suara klakson panjang (mulai tes)
function playKlakson() {
    playTone(440, 'square', 0.12, 0.15);
    setTimeout(() => playTone(440, 'square', 0.12, 0.15), 160);
    setTimeout(() => playTone(550, 'square', 0.6, 0.18), 350);
}

// ── Skeleton valid computed ────────────────────────────────────────────────
// null = sebelum assessing (neutral), true = valid (hijau), false = invalid (merah)
const poseSkeletonValid = computed(() => {
    if (sessionState.value !== 'assessing' || isCountingDown.value) return null;
    if (validationStatus.value === 'READY') return true;
    if (validationStatus.value === 'NO_BODY') return null;
    return false; // POSITION_INVALID, BODY_DETECTED
});

// ── Toleransi kesalahan per tes ────────────────────────────────────────────
// Balance: 3 kali kaki turun → auto stop
// Plank:   1 kali sudut < 160° konfirmasi → auto stop
// WallSit: 1 kali keluar zona → auto stop
const balanceErrorCount = ref(0);
const plankErrorCount   = ref(0);
const wallSitErrorCount = ref(0);
let   _prevBalancing    = false;
let   _prevPlankHolding = false;
let   _prevWallSitHolding = false;

// ── Voice helper ───────────────────────────────────────────────────────────
function speak(text) {
    try {
        if (!window.speechSynthesis) return;
        window.speechSynthesis.cancel();
        const utt = new SpeechSynthesisUtterance(text);
        utt.lang  = 'id-ID';
        utt.rate  = 0.9;
        window.speechSynthesis.speak(utt);
    } catch (_) { /* voice is optional — never crash assessment */ }
}

// ── Capture result snapshot ────────────────────────────────────────────────
function captureAssessmentResult() {
    const now        = new Date();
    const testName   = props.test.name;
    const category   = props.test.category;
    const unit       = props.test.unit;

    let resultValue   = null;
    let resultDisplay = '—';
    let extra         = {};

    if (isPushUpTest.value) {
        resultValue   = pushUpCount.value;
        resultDisplay = `${resultValue} rep`;
    } else if (isSitUpTest.value) {
        resultValue   = sitUpCount.value;
        resultDisplay = `${resultValue} rep`;
    } else if (isStaticBalanceTest.value) {
        resultValue   = parseFloat(balanceBestDuration.value.toFixed(1));
        const total   = parseFloat(balanceTotalDuration.value.toFixed(1));
        resultDisplay = `${resultValue} detik`;
        extra         = { totalDuration: total };
    } else if (isWallSitTest.value) {
        resultValue   = parseFloat(wallSitBestDuration.value.toFixed(1));
        const total   = parseFloat(wallSitTotalDuration.value.toFixed(1));
        resultDisplay = `${resultValue} detik`;
        extra         = { totalDuration: total };
    } else if (isElbowPlankTest.value) {
        resultValue   = parseFloat(plankBestDuration.value.toFixed(1));
        const total   = parseFloat(plankTotalDuration.value.toFixed(1));
        resultDisplay = `${resultValue} detik`;
        extra         = { totalDuration: total };
    } else if (isDeepSquatTest.value) {
        resultValue   = deepSquatCount.value;
        resultDisplay = `${resultValue} rep`;
    } else if (isSquatJumpTest.value) {
        resultValue   = squatJumpCount.value;
        resultDisplay = `${resultValue} rep`;
    } else if (isSitAndReachTest.value) {
        resultValue   = sitReachBestCm.value;
        resultDisplay = `${resultValue > 0 ? '+' : ''}${resultValue} cm`;
        extra         = {
            estimated:    true,
            bestDistNorm: sitReachBestDist.value < 999 ? sitReachBestDist.value : null,
        };
    }

    const benchmarkSnapshot = getBenchmarkSnapshot(testName);
    const achievement       = calculateAchievement(resultValue, benchmarkSnapshot?.value ?? null);

    return {
        // Meta
        athlete_id:         props.athlete.id,
        test_type:          testName,
        category,
        unit,
        // Result
        result_value:       resultValue,
        result_display:     resultDisplay,
        result_display_raw: resultDisplay,
        // Timing
        duration_sec:       elapsedSeconds.value,
        performed_at:       now.toISOString(),
        // Flags
        is_estimated:       extra.estimated ?? false,
        // Benchmark
        benchmark_snapshot: benchmarkSnapshot,
        achievement,
        // Screenshots saat kesalahan
        error_screenshots:  errorScreenshots.value.length > 0 ? errorScreenshots.value : null,
        // Notes
        notes:              null,
        // UI-only fields (tidak dikirim ke backend kecuali screenshots)
        testName,
        durationSec:  elapsedSeconds.value,
        resultDisplay,
        benchmarkSnapshot,
        ...extra,
    };
}

// ── POST ke camera-assessments.store ──────────────────────────────────────
async function saveResultToBackend(result) {
    isSaving.value    = true;
    saveSuccess.value = false;
    saveError.value   = '';

    const payload = {
        athlete_id:         result.athlete_id,
        test_type:          result.test_type,
        category:           result.category,
        result_value:       result.result_value,
        result_display:     result.result_display,
        unit:               result.unit,
        duration_sec:       result.duration_sec,
        is_estimated:       result.is_estimated,
        benchmark_snapshot: result.benchmark_snapshot,
        achievement:        result.achievement,
        performed_at:       result.performed_at,
        notes:              result.notes,
        error_screenshots:  result.error_screenshots,
    };

    try {
        await axios.post(route('camera-assessments.store'), payload);
        saveSuccess.value = true;
    } catch (err) {
        const msg = err?.response?.data?.message
            ?? err?.response?.data?.error
            ?? err?.message
            ?? 'Terjadi kesalahan saat menyimpan assessment.';
        saveError.value = msg;
        console.error('[AssessmentSession] POST camera-assessments.store failed:', err);
    } finally {
        isSaving.value = false;
    }
}

// ── Assessment flow ────────────────────────────────────────────────────────
function startAssessment() {
    assessmentResult.value = null;
    saveSuccess.value      = false;
    saveError.value        = '';
    errorScreenshots.value = [];
    balanceErrorCount.value  = 0;
    plankErrorCount.value    = 0;
    wallSitErrorCount.value  = 0;

    const testName = props.test.name;
    const duration = getDuration(testName);
    const cdSec    = getCountdown(testName);

    assessmentDurationSec.value = duration;
    remainingSeconds.value      = duration;
    elapsedSeconds.value        = 0;
    poseStatus.value            = 'searching';
    poseDetectedCount.value     = 0;
    poseVisibility.value        = 0;

    resetValidation();
    resetPushUp(); resetSitUp(); resetBalance(); resetWallSit();
    resetPlank(); resetDeepSquat(); resetSquatJump(); resetSitAndReach();
    pushUpCountingSide.value = '—';
    pushUpEvalDiag.value = {
        evaluatorCalled: false, callCount: 0,
        lastLeftOk: false, lastRightOk: false,
        lastRawStatus: '—', lastReason: '—',
        rShoulder: 0, rElbow: 0, rWrist: 0,
        lShoulder: 0, lElbow: 0, lWrist: 0,
        readyCount: 0, posInvalidCount: 0, noBodyCount: 0, bodyDetectedCount: 0,
        consecValidFrames: 0,
    };

    // Install custom validator sesuai tes
    if (isPushUpTest.value)             installPushUpValidator();
    else if (isSitUpTest.value)         installSitUpValidator();
    else if (isStaticBalanceTest.value) installBalanceValidator();
    else if (isWallSitTest.value)       installWallSitValidator();
    else if (isElbowPlankTest.value)    installPlankValidator();
    else if (isDeepSquatTest.value)     installDeepSquatValidator();
    else if (isSquatJumpTest.value)     installSquatJumpValidator();
    else if (isSitAndReachTest.value)   installSitAndReachValidator();
    else                                uninstallPushUpValidator();

    if (cdSec > 0) {
        isCountingDown.value  = true;
        countdownValue.value  = cdSec;
        sessionState.value    = 'assessing';

        // "Ready" — speak dulu
        speak('Siap');

        countdownInterval = setInterval(() => {
            countdownValue.value--;
            if (countdownValue.value > 0) {
                // tett tett per detik
                playTone(600, 'sine', 0.15, 0.2);
                speak(String(countdownValue.value));
            }
            if (countdownValue.value <= 0) {
                clearInterval(countdownInterval);
                countdownInterval    = null;
                isCountingDown.value = false;
                // Klakson panjang = mulai
                playKlakson();
                speak('Mulai');
                _beginAssessmentTimer();
            }
        }, 1000);
    } else {
        sessionState.value    = 'assessing';
        isCountingDown.value  = false;
        playKlakson();
        speak('Mulai');
        _beginAssessmentTimer();
    }
}

function _beginAssessmentTimer() {
    elapsedSeconds.value   = 0;
    remainingSeconds.value = assessmentDurationSec.value;
    timerInterval = setInterval(() => {
        elapsedSeconds.value++;
        remainingSeconds.value = Math.max(0, assessmentDurationSec.value - elapsedSeconds.value);
        if (remainingSeconds.value <= 0) {
            speak('Waktu selesai.');
            stopAssessment();
        }
    }, 1000);
}

function stopAssessment() {
    clearInterval(timerInterval);
    clearInterval(countdownInterval);
    timerInterval        = null;
    countdownInterval    = null;
    isCountingDown.value = false;

    // Capture result SEBELUM reset composable
    const result = captureAssessmentResult();
    assessmentResult.value = result;

    sessionState.value = 'stopped';

    resetPushUp();
    resetSitUp();
    resetBalance();
    resetWallSit();
    resetPlank();
    resetDeepSquat();
    resetSquatJump();
    resetSitAndReach();

    // POST ke backend — tidak memblokir UI
    saveResultToBackend(result);
}

function restartSession() {
    elapsedSeconds.value    = 0;
    poseStatus.value        = 'searching';
    poseDetectedCount.value = 0;
    poseVisibility.value    = 0;
    assessmentResult.value  = null;
    saveSuccess.value       = false;
    saveError.value         = '';
    errorScreenshots.value  = [];
    balanceErrorCount.value = 0;
    plankErrorCount.value   = 0;
    wallSitErrorCount.value = 0;
    resetValidation();
    resetPushUp();
    resetSitUp();
    resetBalance();
    resetWallSit();
    resetPlank();
    resetDeepSquat();
    resetSquatJump();
    resetSitAndReach();
    sessionState.value = cameraActive.value ? 'cameraReady' : 'idle';
}

// ── Pose Detection Handlers ────────────────────────────────────────────────
function onPoseStatus(status) {
    poseStatus.value = status;
}

function onPoseUpdate({ landmarks, detectedCount, totalTarget, visibility }) {
    poseDetectedCount.value = detectedCount  ?? 0;
    poseTotalTarget.value   = totalTarget    ?? 15;
    poseVisibility.value    = visibility     ?? 0;

    processPoseFrame({ landmarks, detectedCount, totalTarget, visibility });

    if (isPushUpTest.value) {
        if (landmarks && landmarks.length >= 33) {
            const leftOk  = isPushUpSideValid(landmarks, 'left');
            const rightOk = isPushUpSideValid(landmarks, 'right');
            pushUpCountingSide.value = rightOk && leftOk ? 'BOTH'
                                     : rightOk           ? 'RIGHT'
                                     : leftOk            ? 'LEFT'
                                     :                     'NONE';
        }
        const prevCount = pushUpCount.value;
        processPushUpFrame({ landmarks, validationStatus: validationStatus.value });
        if (pushUpCount.value > prevCount) playRepSuccess();
        if (pushUpFormStatus.value === 'BAD_FORM') playError();
    }
    if (isSitUpTest.value) {
        const prevCount = sitUpCount.value;
        processSitUpFrame({ landmarks, validationStatus: validationStatus.value });
        if (sitUpCount.value > prevCount) playRepSuccess();
    }
    if (isStaticBalanceTest.value) {
        const wasBalancing = isBalancing.value;
        processBalanceFrame({ landmarks, validationStatus: validationStatus.value });
        // Kaki turun / kehilangan balance setelah sebelumnya balancing = langsung selesai
        if (wasBalancing && !isBalancing.value && sessionState.value === 'assessing') {
            playError();
            captureErrorScreenshot('Kaki turun — Keseimbangan gagal');
            speak('Kaki turun. Tes selesai.');
            stopAssessment();
            return;
        }
    }
    if (isWallSitTest.value) {
        const wasHolding = isHolding.value;
        processWallSitFrame({ landmarks, validationStatus: validationStatus.value });
        // Keluar zona = langsung selesai
        if (wasHolding && !isHolding.value && sessionState.value === 'assessing') {
            wallSitErrorCount.value++;
            playError();
            captureErrorScreenshot('Keluar zona Wall Sit');
            speak('Posisi tidak valid. Tes selesai.');
            stopAssessment();
            return;
        }
    }
    if (isElbowPlankTest.value) {
        const wasHolding = plankIsHolding.value;
        processPlankFrame({ landmarks, validationStatus: validationStatus.value });
        // Keluar plank = langsung selesai
        if (wasHolding && !plankIsHolding.value && sessionState.value === 'assessing') {
            plankErrorCount.value++;
            playError();
            captureErrorScreenshot('Posisi Plank tidak valid');
            speak('Posisi tidak valid. Tes selesai.');
            stopAssessment();
            return;
        }
    }
    if (isDeepSquatTest.value) {
        const prevCount = deepSquatCount.value;
        processDeepSquatFrame({ landmarks, validationStatus: validationStatus.value });
        if (deepSquatCount.value > prevCount) playRepSuccess();
    }
    if (isSquatJumpTest.value) {
        const prevCount = squatJumpCount.value;
        processSquatJumpFrame({ landmarks, validationStatus: validationStatus.value });
        if (squatJumpCount.value > prevCount) playRepSuccess();
    }
    if (isSitAndReachTest.value && sessionState.value === 'assessing') {
        processSitAndReachFrame({ landmarks, validationStatus: validationStatus.value });
    }
}

// ── Screenshot saat kesalahan ──────────────────────────────────────────────
function captureErrorScreenshot(reason) {
    if (errorScreenshots.value.length >= MAX_SCREENSHOTS) return;
    // Ambil dari canvas PoseDetector (sudah ada skeleton overlay)
    try {
        const canvases = document.querySelectorAll('canvas');
        // Cari canvas yang paling besar (PoseDetector overlay)
        let targetCanvas = null;
        let maxArea = 0;
        canvases.forEach(c => {
            const area = c.width * c.height;
            if (area > maxArea) { maxArea = area; targetCanvas = c; }
        });
        if (!targetCanvas || maxArea === 0) return;
        const dataUrl = targetCanvas.toDataURL('image/jpeg', 0.6);
        errorScreenshots.value.push({
            timestamp: new Date().toISOString(),
            dataUrl,
            reason,
        });
    } catch (_) {}
}

function onPerfUpdate(data) {
    perfData.value = data;
}

// ── Camera Handlers ────────────────────────────────────────────────────────
function activateCamera() {
    cameraErrorMsg.value = '';
    cameraRef.value?.startCamera();
}

function deactivateCamera() {
    cameraRef.value?.stopCamera();
    sessionState.value = 'idle';
    cameraActive.value = false;
}

function onCameraReady() {
    sessionState.value   = 'cameraReady';
    cameraActive.value   = true;
    cameraErrorMsg.value = '';
}

function onCameraStopped() {
    cameraActive.value = false;
    if (sessionState.value !== 'stopped') {
        sessionState.value = 'idle';
    }
}

function onCameraError({ message }) {
    cameraErrorMsg.value = message;
    cameraActive.value   = false;
    sessionState.value   = 'idle';
}

// ── Navigation ─────────────────────────────────────────────────────────────
function handleBack() {
    stopAllAndClean();
    router.visit(route('physical-assessment.index'));
}

// ── Full cleanup ───────────────────────────────────────────────────────────
function stopAllAndClean() {
    clearInterval(timerInterval);
    clearInterval(countdownInterval);
    timerInterval        = null;
    countdownInterval    = null;
    isCountingDown.value = false;
    cameraRef.value?.stopCamera();
    uninstallPushUpValidator();
    uninstallSitUpValidator();
    uninstallBalanceValidator();
    uninstallWallSitValidator();
    uninstallPlankValidator();
    uninstallDeepSquatValidator();
    uninstallSquatJumpValidator();
    uninstallSitAndReachValidator();
    resetSitUp();
    resetBalance();
    resetWallSit();
    resetPlank();
    resetDeepSquat();
    resetSquatJump();
    resetSitAndReach();
}

onUnmounted(() => {
    stopAllAndClean();
});

// ── Computed UI helpers ────────────────────────────────────────────────────
const athleteInitials = computed(() => {
    const name = props.athlete?.name ?? '';
    if (!name) return 'AT';
    return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase();
});

const categoryBadgeMap = {
    Balance:     'bg-violet-500/10 text-violet-400 border border-violet-500/20',
    Endurance:   'bg-blue-500/10 text-blue-400 border border-blue-500/20',
    Strength:    'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
    Mobility:    'bg-cyan-500/10 text-cyan-400 border border-cyan-500/20',
    Power:       'bg-yellow-500/10 text-yellow-400 border border-yellow-500/20',
    Flexibility: 'bg-pink-500/10 text-pink-400 border border-pink-500/20',
};
const categoryBadge = computed(() =>
    categoryBadgeMap[props.test.category] ?? 'bg-slate-500/10 text-slate-400 border border-slate-500/20'
);

const sessionStateBadge = computed(() => {
    const map = {
        idle:        { label: 'Menunggu',    class: 'bg-slate-500/10 text-slate-400' },
        cameraReady: { label: 'Siap Mulai',  class: 'bg-primary/10 text-primary' },
        assessing:   { label: 'Berlangsung', class: 'bg-emerald-500/10 text-emerald-400' },
        stopped:     { label: 'Selesai',     class: 'bg-yellow-500/10 text-yellow-400' },
    };
    return map[sessionState.value] ?? map.idle;
});

const elapsedFormatted = computed(() => {
    const s = elapsedSeconds.value;
    return `${Math.floor(s / 60).toString().padStart(2, '0')}:${(s % 60).toString().padStart(2, '0')}`;
});

const remainingFormatted = computed(() => {
    const s = remainingSeconds.value;
    return `${Math.floor(s / 60).toString().padStart(2, '0')}:${(s % 60).toString().padStart(2, '0')}`;
});

const poseDetected = computed(() => poseStatus.value === 'detected');

const poseStatusLabel = computed(() => {
    if (sessionState.value !== 'assessing') return 'Tidak Aktif';
    return { searching: 'Mencari tubuh...', detected: 'Tubuh terdeteksi', lost: 'Tubuh tidak terdeteksi' }[poseStatus.value] ?? 'Mencari tubuh...';
});

const poseStatusDot = computed(() => {
    if (sessionState.value !== 'assessing') return 'bg-slate-700';
    return { searching: 'bg-yellow-500 animate-pulse', detected: 'bg-emerald-500', lost: 'bg-red-500' }[poseStatus.value] ?? 'bg-yellow-500 animate-pulse';
});

const poseStatusTextColor = computed(() => {
    if (sessionState.value !== 'assessing') return 'text-slate-600';
    return { searching: 'text-yellow-400', detected: 'text-emerald-400', lost: 'text-red-400' }[poseStatus.value] ?? 'text-yellow-400';
});

function achievementColor(val) {
    if (val == null) return 'text-slate-500';
    if (val >= 80)   return 'text-emerald-400';
    if (val >= 50)   return 'text-yellow-400';
    return 'text-red-400';
}

function formatDuration(sec) {
    if (!sec) return '—';
    const m = Math.floor(sec / 60);
    const s = sec % 60;
    return m > 0 ? `${m} menit ${s} detik` : `${s} detik`;
}
</script>

<style scoped>
.countdown-fade-enter-active, .countdown-fade-leave-active {
    transition: opacity 0.3s ease, transform 0.3s ease;
}
.countdown-fade-enter-from, .countdown-fade-leave-to {
    opacity: 0;
    transform: scale(0.9);
}
</style>
