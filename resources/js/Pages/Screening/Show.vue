<template>
  <AppLayout>
    <div class="w-full max-w-2xl space-y-6">

      <!-- Back -->
      <div class="flex items-center gap-3">
        <Link :href="route('screening.index')" class="btn btn-sm btn-ghost gap-2">
          <v-icon name="hi-arrow-left" class="size-[1.2em]" />
          Riwayat Skrining
        </Link>
      </div>

      <!-- Header card -->
      <div class="card bg-base-100 border border-base-200 shadow-xs">
        <div class="card-body p-6">
          <h2 class="text-2xl font-extrabold mb-1">Laporan Skrining ISKAD-V</h2>
          <div class="grid grid-cols-2 gap-x-6 gap-y-3 mt-4 text-sm">
            <div>
              <p class="text-xs opacity-50 uppercase tracking-wider">Nama Anak</p>
              <p class="font-bold text-lg">{{ s.nama_anak }}</p>
            </div>
            <div>
              <p class="text-xs opacity-50 uppercase tracking-wider">Umur</p>
              <p class="font-bold">{{ s.umur_bulan }} Bulan</p>
            </div>
            <div>
              <p class="text-xs opacity-50 uppercase tracking-wider">Tanggal Tes</p>
              <p class="font-bold">{{ formatDateTime(s.created_at) }}</p>
            </div>
            <div>
              <p class="text-xs opacity-50 uppercase tracking-wider">Petugas / Kader</p>
              <p class="font-bold">{{ s.user?.name ?? '—' }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Hasil -->
      <div class="card border shadow-xs"
           :class="hasilBorderClass">
        <div class="card-body p-6 items-center text-center">
          <p class="text-xs opacity-50 uppercase tracking-wider mb-1">Kategori Hasil</p>
          <h3 class="text-3xl font-black" :class="kategoriColor(s.kategori)">{{ s.kategori }}</h3>
          <p class="text-base font-bold opacity-70 mt-1">Total Skor: {{ s.total_skor }} / 15</p>
        </div>
      </div>

      <!-- Radar Chart -->
      <div class="card bg-base-100 border border-base-200 shadow-xs">
        <div class="card-body p-5">
          <h3 class="font-bold text-sm mb-4 border-b border-base-200 pb-2">Peta Keseimbangan</h3>
          <div class="relative w-full max-w-xs mx-auto" style="height: 260px;">
            <canvas ref="radarCanvas"></canvas>
          </div>
          <p class="text-xs text-center opacity-40 mt-2 italic">Semakin grafik melebar ke luar, semakin baik keseimbangannya.</p>
        </div>
      </div>

      <!-- Detail per instrumen -->
      <div class="card bg-base-100 border border-base-200 shadow-xs">
        <div class="card-body p-5">
          <h3 class="font-bold text-sm mb-4 border-b border-base-200 pb-2">Detail per Instrumen</h3>
          <div v-if="s.chart_data && s.chart_data.length" class="space-y-3">
            <div
              v-for="(item, idx) in s.chart_data"
              :key="idx"
              class="flex items-center justify-between py-2 border-b border-base-200/50 last:border-0"
            >
              <span class="text-sm">{{ item.nama }}</span>
              <span class="badge font-bold" :class="skorBadge(item.skor)">Skor {{ item.skor }}</span>
            </div>
          </div>
          <p v-else class="text-xs opacity-40 italic">Data detail tidak tersedia.</p>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-between gap-3">
        <Link :href="route('screening.index')" class="btn btn-sm btn-ghost gap-2">
          <v-icon name="hi-arrow-left" class="size-[1.2em]" />
          Kembali
        </Link>
        <button @click="confirmDelete" class="btn btn-sm btn-error btn-outline gap-2">
          <v-icon name="hi-trash" class="size-[1.2em]" />
          Hapus Data
        </button>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  screening: { type: Object, required: true },
});

const s = props.screening;
const radarCanvas = ref(null);

// ── Chart ─────────────────────────────────────────────────────────────────
onMounted(async () => {
  if (!s.chart_data?.length || !radarCanvas.value) return;

  const { Chart, registerables } = await import('chart.js');
  Chart.register(...registerables);

  new Chart(radarCanvas.value, {
    type: 'radar',
    data: {
      labels: s.chart_data.map(d => d.nama.replace(' (Dilewati)', '')),
      datasets: [{
        label: 'Skor Per Instrumen',
        data:  s.chart_data.map(d => d.skor),
        backgroundColor: 'rgba(34, 197, 94, 0.2)',
        borderColor:     'rgba(21, 128, 61, 1)',
        pointBackgroundColor: 'rgba(220, 38, 38, 1)',
        borderWidth: 2,
      }],
    },
    options: {
      scales: {
        r: {
          min: 0, max: 3,
          ticks: { stepSize: 1, display: false },
          angleLines: { color: 'rgba(0,0,0,0.1)' },
          grid:       { color: 'rgba(0,0,0,0.1)' },
        },
      },
      plugins: { legend: { display: false } },
    },
  });
});

// ── Delete ────────────────────────────────────────────────────────────────
async function confirmDelete() {
  const swal = (await import('sweetalert2')).default;
  const result = await swal.fire({
    icon: 'warning',
    title: 'Hapus Data Skrining',
    html: `Hapus data skrining <b>${s.nama_anak}</b>?<br><small class="opacity-60">Tidak bisa dibatalkan.</small>`,
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor:  '#64748b',
    confirmButtonText: 'Ya, hapus',
    cancelButtonText:  'Batal',
  });
  if (result.isConfirmed) {
    router.delete(route('screening.destroy', s.id));
  }
}

// ── Helpers ───────────────────────────────────────────────────────────────
function formatDateTime(dt) {
  if (!dt) return '—';
  return new Date(dt).toLocaleString('id-ID', {
    day: '2-digit', month: 'long', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  });
}

function kategoriColor(k) {
  if (k === 'NORMAL')        return 'text-success';
  if (k === 'RISIKO RINGAN') return 'text-warning';
  return 'text-error';
}

const hasilBorderClass = computed(() => {
  if (s.kategori === 'NORMAL')        return 'bg-success/5 border-success/30';
  if (s.kategori === 'RISIKO RINGAN') return 'bg-warning/5 border-warning/30';
  return 'bg-error/5 border-error/30';
});

function skorBadge(skor) {
  if (skor === 3) return 'badge-success';
  if (skor === 2) return 'badge-info';
  if (skor === 1) return 'badge-warning';
  return 'badge-error';
}
</script>
