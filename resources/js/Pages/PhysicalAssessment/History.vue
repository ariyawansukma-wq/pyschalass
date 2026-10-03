<template>
  <AppLayout>
    <div class="w-full space-y-6">

      <!-- Header Banner -->
      <div class="card bg-base-100 border border-base-200 shadow-xs">
        <div class="card-body p-6 flex-row items-center justify-between flex-wrap gap-4">
          <div class="space-y-1">
            <div class="badge badge-primary badge-outline text-xs">Assessment History</div>
            <h1 class="text-2xl font-extrabold tracking-tight">Riwayat Assessment</h1>
            <p class="text-xs opacity-75">Semua hasil physical assessment yang telah dilakukan</p>
          </div>
          <Link :href="route('physical-assessment.index')" class="btn btn-primary btn-sm gap-2 shadow-xs">
            <v-icon name="bi-laptop" class="size-[1.2em]" />
            <span>Assessment Baru</span>
          </Link>
        </div>
      </div>

      <!-- Filters -->
      <div class="card bg-base-100 border border-base-200 shadow-xs">
        <div class="card-body p-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">

            <!-- Filter: Athlete -->
            <div>
              <select
                v-model="filterAthlete"
                @change="onFilterChange"
                class="select select-bordered select-sm w-full text-xs"
              >
                <option value="">Semua Atlet</option>
                <option v-for="a in props.athletes" :key="a.id" :value="a.id">
                  {{ a.name }}{{ a.athlete_number ? ` (${a.athlete_number})` : '' }}
                </option>
              </select>
            </div>

            <!-- Filter: Jenis Tes -->
            <div>
              <select
                v-model="filterTestType"
                @change="onFilterChange"
                class="select select-bordered select-sm w-full text-xs"
              >
                <option value="">Semua Tes</option>
                <option v-for="t in testOptions" :key="t" :value="t">{{ t }}</option>
              </select>
            </div>

            <!-- Filter: Kategori -->
            <div>
              <select
                v-model="filterCategory"
                @change="onFilterChange"
                class="select select-bordered select-sm w-full text-xs"
              >
                <option value="">Semua Kategori</option>
                <option v-for="c in categoryOptions" :key="c" :value="c">{{ c }}</option>
              </select>
            </div>

            <!-- Filter: Per Page -->
            <div class="flex items-center gap-2">
              <span class="text-xs text-base-content/60 whitespace-nowrap">Per halaman:</span>
              <select
                v-model="filterPerPage"
                @change="onFilterChange"
                class="select select-bordered select-sm flex-1 text-xs"
              >
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="card bg-base-100 border border-base-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="table table-sm w-full">
            <thead>
              <tr class="border-b border-base-200 text-xs">
                <th class="font-semibold uppercase tracking-wider opacity-60">Atlet</th>
                <th class="font-semibold uppercase tracking-wider opacity-60">Tes</th>
                <th class="font-semibold uppercase tracking-wider opacity-60 hidden md:table-cell">Kategori</th>
                <th class="font-semibold uppercase tracking-wider opacity-60">Hasil</th>
                <th class="font-semibold uppercase tracking-wider opacity-60 hidden lg:table-cell">Pencapaian</th>
                <th class="font-semibold uppercase tracking-wider opacity-60 hidden sm:table-cell">Tanggal</th>
                <th class="font-semibold uppercase tracking-wider opacity-60 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <!-- Empty state -->
              <tr v-if="!props.assessments.data || props.assessments.data.length === 0">
                <td colspan="7" class="py-16 text-center">
                  <div class="flex flex-col items-center gap-3">
                    <v-icon name="hi-clock" class="w-10 h-10 opacity-20" />
                    <p class="text-sm font-semibold opacity-60">
                      {{ hasFilters ? 'Tidak ada hasil yang cocok' : 'Belum ada riwayat assessment' }}
                    </p>
                    <p class="text-xs opacity-40 max-w-xs">
                      {{ hasFilters
                        ? 'Coba ubah filter di atas.'
                        : 'Lakukan Physical Assessment dan hasilnya akan muncul di sini.' }}
                    </p>
                    <Link
                      v-if="!hasFilters"
                      :href="route('physical-assessment.index')"
                      class="btn btn-primary btn-sm mt-1 gap-2"
                    >
                      <v-icon name="bi-laptop" class="size-[1.2em]" />
                      Mulai Assessment
                    </Link>
                  </div>
                </td>
              </tr>

              <!-- Data rows -->
              <tr
                v-for="a in props.assessments.data"
                :key="a.id"
                class="hover:bg-base-200/40 transition-colors border-b border-base-200/50"
              >
                <!-- Atlet -->
                <td class="py-3">
                  <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-full bg-primary/15 border border-primary/20 flex items-center justify-center text-primary text-xs font-bold flex-shrink-0">
                      {{ initials(a.athlete?.name) }}
                    </div>
                    <div class="min-w-0">
                      <p class="text-xs font-semibold truncate">{{ a.athlete?.name ?? '—' }}</p>
                      <p v-if="a.athlete?.athlete_number" class="text-xs opacity-50">{{ a.athlete.athlete_number }}</p>
                    </div>
                  </div>
                </td>

                <!-- Tes -->
                <td class="py-3 text-xs font-medium">{{ a.test_type }}</td>

                <!-- Kategori -->
                <td class="py-3 hidden md:table-cell">
                  <span :class="['badge badge-xs font-semibold', categoryBadge(a.category)]">
                    {{ a.category ?? '—' }}
                  </span>
                </td>

                <!-- Hasil -->
                <td class="py-3">
                  <div class="flex items-center gap-1.5">
                    <span class="text-sm font-bold font-mono text-primary">{{ a.result_display ?? '—' }}</span>
                    <span v-if="a.is_estimated" class="badge badge-xs badge-warning">est.</span>
                  </div>
                </td>

                <!-- Pencapaian -->
                <td class="py-3 hidden lg:table-cell">
                  <template v-if="a.achievement != null">
                    <div class="flex items-center gap-2">
                      <span class="text-xs font-bold font-mono w-12 text-right"
                            :class="achievementColor(a.achievement)">
                        {{ a.achievement }}%
                      </span>
                      <div class="w-16 h-1.5 rounded-full bg-base-300 overflow-hidden">
                        <div
                          class="h-full rounded-full"
                          :class="achievementBarColor(a.achievement)"
                          :style="{ width: `${Math.min(a.achievement, 100)}%` }"
                        ></div>
                      </div>
                    </div>
                  </template>
                  <span v-else class="text-xs opacity-40">—</span>
                </td>

                <!-- Tanggal -->
                <td class="py-3 hidden sm:table-cell">
                  <div>
                    <p class="text-xs">{{ formatDate(a.performed_at) }}</p>
                    <p class="text-xs opacity-50">{{ formatTime(a.performed_at) }}</p>
                  </div>
                </td>

                <!-- Aksi -->
                <td class="py-3 text-right">
                  <div class="flex items-center justify-end gap-1">
                    <Link
                      :href="route('camera-assessments.show', a.id)"
                      class="btn btn-xs btn-ghost gap-1"
                      title="Lihat Detail"
                    >
                      <v-icon name="hi-eye" class="size-[1em]" />
                      <span class="hidden sm:inline">Detail</span>
                    </Link>
                    <button
                      @click="confirmDelete(a)"
                      class="btn btn-xs btn-ghost text-error gap-1"
                      title="Hapus"
                    >
                      <v-icon name="hi-trash" class="size-[1em]" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <Pagination
          v-if="props.assessments.links"
          :links="props.assessments.links"
          :meta="props.assessments"
        />
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
  assessments: { type: Object, required: true },   // paginated
  athletes:    { type: Array,  default: () => [] },
  filters:     { type: Object, default: () => ({}) },
});

// ── Filter state ──────────────────────────────────────────────────────────
const filterAthlete  = ref(props.filters.athlete_id  ?? '');
const filterTestType = ref(props.filters.test_type   ?? '');
const filterCategory = ref(props.filters.category    ?? '');
const filterPerPage  = ref(props.filters.per_page    ?? '15');

let filterTimeout = null;

function onFilterChange() {
  clearTimeout(filterTimeout);
  filterTimeout = setTimeout(() => {
    router.get(
      route('camera-assessments.index'),
      {
        athlete_id: filterAthlete.value  || undefined,
        test_type:  filterTestType.value || undefined,
        category:   filterCategory.value || undefined,
        per_page:   filterPerPage.value  !== '15' ? filterPerPage.value : undefined,
      },
      { preserveState: true, replace: true }
    );
  }, 300);
}

const hasFilters = computed(() =>
  filterAthlete.value || filterTestType.value || filterCategory.value
);

// ── Static options ────────────────────────────────────────────────────────
const testOptions = [
  'Keseimbangan Statis', 'Elbow Plank', 'Wall Sit', 'Deep Squat',
  'Squat Jump', 'Push Up', 'Sit Up', 'Sit and Reach',
];

const categoryOptions = ['Balance', 'Endurance', 'Strength', 'Mobility', 'Power', 'Flexibility'];

// ── Delete ────────────────────────────────────────────────────────────────
async function confirmDelete(assessment) {
  const swal = (await import('sweetalert2')).default;
  const result = await swal.fire({
    icon: 'warning',
    title: 'Hapus Assessment',
    html: `Hapus hasil <b>${assessment.test_type}</b> milik <b>${assessment.athlete?.name ?? 'Atlet'}</b>?<br><small class="opacity-60">Tindakan ini tidak bisa dibatalkan.</small>`,
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor:  '#64748b',
    confirmButtonText:  'Ya, hapus',
    cancelButtonText:   'Batal',
  });
  if (result.isConfirmed) {
    router.delete(route('camera-assessments.destroy', assessment.id));
  }
}

// ── Helpers ───────────────────────────────────────────────────────────────
function initials(name) {
  if (!name) return '?';
  return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase();
}

function formatDate(dt) {
  if (!dt) return '—';
  return new Date(dt).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatTime(dt) {
  if (!dt) return '';
  return new Date(dt).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
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
</script>
