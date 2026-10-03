<template>
  <AppLayout>
    <div class="w-full space-y-6 max-w-3xl">

      <!-- Back button -->
      <div class="flex items-center gap-3">
        <Link
          :href="route('camera-assessments.index')"
          class="btn btn-sm btn-ghost gap-2"
        >
          <v-icon name="hi-arrow-left" class="size-[1.2em]" />
          Riwayat Assessment
        </Link>
      </div>

      <!-- Header card: athlete + test info -->
      <div class="card bg-base-100 border border-base-200 shadow-xs">
        <div class="card-body p-6">
          <div class="flex items-start justify-between flex-wrap gap-4">

            <!-- Athlete info -->
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-full bg-primary/15 border border-primary/20 flex items-center justify-center text-primary text-lg font-bold flex-shrink-0">
                {{ initials(a.athlete?.name) }}
              </div>
              <div>
                <h1 class="text-xl font-extrabold tracking-tight">{{ a.athlete?.name ?? '—' }}</h1>
                <p v-if="a.athlete?.athlete_number" class="text-xs opacity-50 mt-0.5">
                  {{ a.athlete.athlete_number }}
                </p>
              </div>
            </div>

            <!-- Test + date -->
            <div class="text-right">
              <p class="text-sm font-bold">{{ a.test_type }}</p>
              <span :class="['badge badge-sm font-semibold mt-1', categoryBadge(a.category)]">
                {{ a.category ?? '—' }}
              </span>
              <p class="text-xs opacity-50 mt-1.5">{{ formatDateTime(a.performed_at) }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Result summary cards -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        <!-- Hasil -->
        <div class="card bg-base-100 border border-primary/30 shadow-xs">
          <div class="card-body p-5 items-center text-center gap-1">
            <p class="text-xs font-bold uppercase tracking-widest opacity-50">HASIL</p>
            <p class="text-3xl font-black font-mono text-primary leading-tight">
              {{ a.result_display ?? '—' }}
            </p>
            <span v-if="a.is_estimated" class="badge badge-xs badge-warning">Estimasi</span>
          </div>
        </div>

        <!-- Benchmark -->
        <div class="card bg-base-100 border border-base-200 shadow-xs">
          <div class="card-body p-5 items-center text-center gap-1">
            <p class="text-xs font-bold uppercase tracking-widest opacity-50">BENCHMARK</p>
            <template v-if="a.benchmark_snapshot">
              <p class="text-2xl font-black font-mono leading-tight">
                {{ a.benchmark_snapshot.value }}
              </p>
              <p class="text-xs opacity-50">{{ a.benchmark_snapshot.unit }}</p>
            </template>
            <p v-else class="text-sm opacity-30 italic">Tidak tersedia</p>
          </div>
        </div>

        <!-- Pencapaian -->
        <div class="card bg-base-100 border border-base-200 shadow-xs">
          <div class="card-body p-5 items-center text-center gap-1">
            <p class="text-xs font-bold uppercase tracking-widest opacity-50">PENCAPAIAN</p>
            <template v-if="a.achievement != null">
              <p class="text-3xl font-black font-mono leading-tight"
                 :class="achievementColor(a.achievement)">
                {{ a.achievement }}%
              </p>
              <p class="text-xs font-semibold" :class="achievementColor(a.achievement)">
                {{ performanceLabel(a.achievement) }}
              </p>
            </template>
            <p v-else class="text-sm opacity-30 italic">—</p>
          </div>
        </div>
      </div>

      <!-- Performance bar (hanya jika ada achievement) -->
      <div v-if="a.achievement != null" class="card bg-base-100 border border-base-200 shadow-xs">
        <div class="card-body p-5">
          <p class="text-xs font-bold uppercase tracking-widest opacity-50 mb-3">PERFORMANCE</p>
          <div class="flex items-baseline gap-3 mb-3">
            <span class="text-4xl font-black font-mono" :class="achievementColor(a.achievement)">
              {{ a.achievement }}%
            </span>
            <span class="text-xs opacity-50">
              dari benchmark {{ a.benchmark_snapshot?.value }} {{ a.benchmark_snapshot?.unit }}
            </span>
          </div>
          <div class="w-full h-3 rounded-full bg-base-300 overflow-hidden">
            <div
              class="h-full rounded-full transition-all"
              :class="achievementBarColor(a.achievement)"
              :style="{ width: `${Math.min(a.achievement, 100)}%` }"
            ></div>
          </div>
          <div class="flex justify-between text-xs opacity-30 mt-1">
            <span>0%</span><span>25%</span><span>50%</span><span>75%</span><span>100%</span>
          </div>
        </div>
      </div>

      <!-- Detail info -->
      <div class="card bg-base-100 border border-base-200 shadow-xs">
        <div class="card-body p-5">
          <p class="text-xs font-bold uppercase tracking-widest opacity-50 mb-4">DETAIL SESI</p>
          <div class="space-y-3">

            <div class="flex justify-between items-center text-sm border-b border-base-200/50 pb-3">
              <span class="opacity-60">Durasi Sesi</span>
              <span class="font-semibold font-mono">{{ formatDuration(a.duration_sec) }}</span>
            </div>

            <div class="flex justify-between items-center text-sm border-b border-base-200/50 pb-3">
              <span class="opacity-60">Jenis Tes</span>
              <span class="font-semibold">{{ a.test_type }}</span>
            </div>

            <div class="flex justify-between items-center text-sm border-b border-base-200/50 pb-3">
              <span class="opacity-60">Kategori</span>
              <span :class="['badge badge-sm font-semibold', categoryBadge(a.category)]">
                {{ a.category ?? '—' }}
              </span>
            </div>

            <div class="flex justify-between items-center text-sm border-b border-base-200/50 pb-3">
              <span class="opacity-60">Satuan</span>
              <span class="font-semibold">{{ a.unit ?? '—' }}</span>
            </div>

            <div class="flex justify-between items-center text-sm border-b border-base-200/50 pb-3">
              <span class="opacity-60">Status Hasil</span>
              <span class="font-semibold">
                {{ a.is_estimated ? 'Estimasi (belum dikalibrasi)' : 'Terukur' }}
              </span>
            </div>

            <div class="flex justify-between items-center text-sm border-b border-base-200/50 pb-3">
              <span class="opacity-60">Tanggal & Waktu</span>
              <span class="font-semibold">{{ formatDateTime(a.performed_at) }}</span>
            </div>

            <div class="flex justify-between items-center text-sm border-b border-base-200/50 pb-3">
              <span class="opacity-60">Dicatat oleh</span>
              <span class="font-semibold">{{ a.user?.name ?? '—' }}</span>
            </div>

            <div v-if="a.notes" class="flex justify-between items-start text-sm">
              <span class="opacity-60">Catatan</span>
              <span class="font-semibold text-right max-w-xs">{{ a.notes }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Screenshots kesalahan -->
      <div v-if="a.error_screenshots && a.error_screenshots.length > 0" class="card bg-base-100 border border-error/30 shadow-xs">
        <div class="card-body p-5">
          <p class="text-xs font-bold uppercase tracking-widest text-error mb-4 flex items-center gap-2">
            <v-icon name="hi-exclamation-circle" class="w-4 h-4" />
            Bukti Kesalahan Postur ({{ a.error_screenshots.length }} foto)
          </p>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <div
              v-for="(shot, idx) in a.error_screenshots"
              :key="idx"
              class="relative rounded-lg overflow-hidden border border-error/20"
            >
              <img
                :src="shot.dataUrl"
                :alt="`Kesalahan ${idx + 1}`"
                class="w-full h-32 object-cover"
              />
              <div class="absolute bottom-0 inset-x-0 bg-black/60 px-2 py-1">
                <p class="text-[10px] text-error font-bold truncate">{{ shot.reason }}</p>
                <p class="text-[9px] text-white/60">{{ formatShortTime(shot.timestamp) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-between gap-3">
        <Link
          :href="route('camera-assessments.index')"
          class="btn btn-sm btn-ghost gap-2"
        >
          <v-icon name="hi-arrow-left" class="size-[1.2em]" />
          Kembali ke Riwayat
        </Link>

        <button
          @click="confirmDelete"
          class="btn btn-sm btn-error btn-outline gap-2"
        >
          <v-icon name="hi-trash" class="size-[1.2em]" />
          Hapus Assessment
        </button>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  assessment: { type: Object, required: true },
});

// Shorthand
const a = props.assessment;

// ── Delete ────────────────────────────────────────────────────────────────
async function confirmDelete() {
  const swal = (await import('sweetalert2')).default;
  const result = await swal.fire({
    icon: 'warning',
    title: 'Hapus Assessment',
    html: `Hapus hasil <b>${a.test_type}</b> milik <b>${a.athlete?.name ?? 'Atlet'}</b>?<br>
           <small class="opacity-60">Tindakan ini tidak bisa dibatalkan.</small>`,
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor:  '#64748b',
    confirmButtonText:  'Ya, hapus',
    cancelButtonText:   'Batal',
  });
  if (result.isConfirmed) {
    router.delete(route('camera-assessments.destroy', a.id));
  }
}

// ── Helpers ───────────────────────────────────────────────────────────────
function initials(name) {
  if (!name) return '?';
  return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase();
}

function formatDateTime(dt) {
  if (!dt) return '—';
  return new Date(dt).toLocaleString('id-ID', {
    day: '2-digit', month: 'long', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  });
}

function formatShortTime(ts) {
  if (!ts) return '';
  return new Date(ts).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

function formatDuration(sec) {
  if (!sec) return '—';
  const m = Math.floor(sec / 60);
  const s = sec % 60;
  return m > 0 ? `${m} menit ${s} detik` : `${s} detik`;
}

function categoryBadge(cat) {
  const map = {
    Balance:     'badge-info',
    Endurance:   'badge-primary',
    Strength:    'badge-success',
    Mobility:    'badge-accent',
    Power:       'badge-warning',
    Flexibility: 'badge-secondary',
  };
  return map[cat] ?? 'badge-ghost';
}

function achievementColor(val) {
  if (val == null) return 'opacity-40';
  if (val >= 80)  return 'text-success';
  if (val >= 50)  return 'text-warning';
  return 'text-error';
}

function achievementBarColor(val) {
  if (val >= 80) return 'bg-success';
  if (val >= 50) return 'bg-warning';
  return 'bg-error';
}

function performanceLabel(val) {
  if (val >= 80) return 'Sangat Baik';
  if (val >= 60) return 'Baik';
  if (val >= 40) return 'Cukup';
  return 'Perlu Peningkatan';
}
</script>
