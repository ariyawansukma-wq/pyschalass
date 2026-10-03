<template>
  <AppLayout>
    <div class="w-full space-y-6">

      <!-- Header Banner -->
      <div class="card bg-base-100 border border-base-200 shadow-xs">
        <div class="card-body p-6">
          <div class="space-y-1">
            <div class="badge badge-primary badge-outline text-xs">Camera Assessment Module</div>
            <h1 class="text-2xl font-extrabold tracking-tight">Physical Assessment</h1>
            <p class="text-xs opacity-75">Pilih atlet dan jenis tes untuk memulai sesi penilaian fisik berbasis kamera.</p>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Panel 1: Pilih Atlet -->
        <div class="card bg-base-100 border border-base-200 shadow-xs">
          <div class="card-body p-5">
            <h2 class="text-sm font-bold text-base-content mb-4 flex items-center gap-2">
              <v-icon name="hi-users" class="w-4 h-4 text-primary" />
              Pilih Atlet
            </h2>

            <!-- Search -->
            <div class="relative mb-3">
              <input
                v-model="athleteSearch"
                type="text"
                placeholder="Cari nama atau nomor atlet..."
                class="input input-bordered input-sm w-full pl-8"
              />
              <v-icon name="hi-search" class="w-4 h-4 text-base-content/40 absolute left-2.5 top-2.5" />
            </div>

            <!-- Athlete list -->
            <div class="space-y-1 max-h-72 overflow-y-auto pr-1">
              <div
                v-if="filteredAthletes.length === 0"
                class="text-xs text-base-content/50 text-center py-6"
              >
                {{ props.athletes.length === 0 ? 'Belum ada atlet terdaftar.' : 'Tidak ada atlet yang cocok.' }}
              </div>

              <button
                v-for="athlete in filteredAthletes"
                :key="athlete.id"
                @click="selectAthlete(athlete)"
                :class="[
                  'w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-left transition-colors duration-150',
                  selectedAthlete?.id === athlete.id
                    ? 'bg-primary/10 border border-primary/30 text-primary'
                    : 'hover:bg-base-200 border border-transparent'
                ]"
              >
                <div class="w-8 h-8 rounded-full bg-primary/15 border border-primary/20 flex items-center justify-center text-primary text-xs font-bold flex-shrink-0">
                  {{ initials(athlete.name) }}
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-semibold truncate">{{ athlete.name }}</p>
                  <p v-if="athlete.athlete_number" class="text-xs opacity-60">{{ athlete.athlete_number }}</p>
                </div>
                <v-icon
                  v-if="selectedAthlete?.id === athlete.id"
                  name="hi-check-circle"
                  class="w-4 h-4 text-primary flex-shrink-0"
                />
              </button>
            </div>

            <!-- Selected indicator -->
            <div v-if="selectedAthlete" class="mt-3 flex items-center gap-2 p-2.5 rounded-lg bg-success/10 border border-success/20">
              <v-icon name="hi-check-circle" class="w-4 h-4 text-success flex-shrink-0" />
              <span class="text-xs font-semibold text-success">{{ selectedAthlete.name }} dipilih</span>
            </div>
            <div v-else class="mt-3 p-2.5 rounded-lg bg-base-200 border border-base-300">
              <span class="text-xs text-base-content/50">Pilih atlet dari daftar di atas</span>
            </div>
          </div>
        </div>

        <!-- Panel 2: Pilih Tes -->
        <div class="card bg-base-100 border border-base-200 shadow-xs">
          <div class="card-body p-5">
            <h2 class="text-sm font-bold text-base-content mb-4 flex items-center gap-2">
              <v-icon name="bi-laptop" class="w-4 h-4 text-primary" />
              Pilih Jenis Tes
            </h2>

            <div class="grid grid-cols-2 gap-2">
              <button
                v-for="test in props.testOptions"
                :key="test.id"
                @click="selectTest(test)"
                :class="[
                  'flex flex-col items-start gap-1 p-3 rounded-xl border text-left transition-all duration-150',
                  selectedTest?.id === test.id
                    ? 'bg-primary/10 border-primary/40 text-primary'
                    : 'bg-base-200/50 border-base-300 hover:bg-base-200'
                ]"
              >
                <span class="text-xl leading-none">{{ test.icon }}</span>
                <span class="text-xs font-bold leading-tight">{{ test.name }}</span>
                <span :class="['text-xs px-1.5 py-0.5 rounded-full font-medium', categoryBadge(test.category)]">
                  {{ test.category }}
                </span>
              </button>
            </div>

            <!-- Selected test indicator -->
            <div v-if="selectedTest" class="mt-3 flex items-center gap-2 p-2.5 rounded-lg bg-success/10 border border-success/20">
              <v-icon name="hi-check-circle" class="w-4 h-4 text-success flex-shrink-0" />
              <span class="text-xs font-semibold text-success">{{ selectedTest.name }} dipilih</span>
            </div>
            <div v-else class="mt-3 p-2.5 rounded-lg bg-base-200 border border-base-300">
              <span class="text-xs text-base-content/50">Pilih jenis tes dari daftar di atas</span>
            </div>
          </div>
        </div>

      </div>

      <!-- Start Assessment CTA -->
      <div class="card bg-base-100 border border-base-200 shadow-xs">
        <div class="card-body p-5 flex-row items-center justify-between flex-wrap gap-4">
          <div>
            <p class="text-sm font-semibold text-base-content">
              <template v-if="selectedAthlete && selectedTest">
                Siap memulai:
                <span class="text-primary">{{ selectedAthlete.name }}</span>
                —
                <span class="text-primary">{{ selectedTest.name }}</span>
              </template>
              <template v-else>
                Pilih atlet dan jenis tes untuk melanjutkan.
              </template>
            </p>
            <p class="text-xs text-base-content/50 mt-0.5">
              Pastikan kamera tersedia dan pencahayaan cukup sebelum memulai.
            </p>
          </div>
          <button
            @click="startAssessment"
            :disabled="!canStart"
            class="btn btn-primary btn-sm gap-2"
            :class="{ 'opacity-40 cursor-not-allowed': !canStart }"
          >
            <v-icon name="bi-laptop" class="w-4 h-4" />
            Mulai Assessment
          </button>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  athletes:    { type: Array, default: () => [] },
  testOptions: { type: Array, default: () => [] },
});

// ── State ──────────────────────────────────────────────────────────────────
const selectedAthlete = ref(null);
const selectedTest    = ref(null);
const athleteSearch   = ref('');

// ── Computed ───────────────────────────────────────────────────────────────
const filteredAthletes = computed(() => {
  const q = athleteSearch.value.toLowerCase().trim();
  if (!q) return props.athletes;
  return props.athletes.filter(a =>
    a.name.toLowerCase().includes(q) ||
    (a.athlete_number ?? '').toLowerCase().includes(q)
  );
});

const canStart = computed(() => selectedAthlete.value !== null && selectedTest.value !== null);

// ── Handlers ───────────────────────────────────────────────────────────────
function selectAthlete(athlete) {
  selectedAthlete.value = athlete;
}

function selectTest(test) {
  selectedTest.value = test;
}

function startAssessment() {
  if (!canStart.value) return;
  router.visit(route('physical-assessment.start', {
    athlete: selectedAthlete.value.id,
    testId:  selectedTest.value.id,
  }));
}

// ── Helpers ────────────────────────────────────────────────────────────────
function initials(name) {
  if (!name) return '?';
  return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase();
}

function categoryBadge(cat) {
  const map = {
    Balance:     'bg-violet-500/20 text-violet-300',
    Endurance:   'bg-blue-500/20 text-blue-300',
    Strength:    'bg-emerald-500/20 text-emerald-300',
    Mobility:    'bg-cyan-500/20 text-cyan-300',
    Power:       'bg-yellow-500/20 text-yellow-300',
    Flexibility: 'bg-pink-500/20 text-pink-300',
  };
  return map[cat] ?? 'bg-base-300 text-base-content/60';
}
</script>
