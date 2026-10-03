<template>
  <AppLayout>
    <div class="w-full space-y-6">
      <!-- Header Banner -->
      <div class="card bg-base-100 border border-base-200 text-base-content shadow-xs">
        <div class="card-body p-6 flex-row items-center justify-between flex-wrap gap-4">
          <div class="space-y-1">
            <div class="badge badge-primary badge-outline text-xs">
              <Link href="/athletes" class="hover:underline flex items-center gap-1">
                <v-icon name="hi-arrow-left" class="size-[1.2em]" />
                <span>Back to Directory</span>
              </Link>
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight">Athlete Progress & Test Comparison - {{ athlete.name }}</h1>
            <p class="text-xs opacity-75">Multi-session evaluation metrics and performance progression history</p>
          </div>
          <div class="flex items-center gap-2">
            <a :href="'/athletes/' + athlete.id + '/export-pdf?institution_id=' + defaultInstitutionId" target="_blank" class="btn btn-success btn-sm gap-1.5 shadow-xs">
              <v-icon name="hi-document-text" class="size-[1.2em]" />
              <span>Export PDF Report</span>
            </a>
            <Link :href="'/athletes/' + athlete.id + '/edit'" class="btn btn-primary btn-sm gap-1.5 shadow-xs">
              <v-icon name="hi-pencil" class="size-[1.2em]" />
              <span>Edit Assessment</span>
            </Link>
          </div>
        </div>
      </div>

      <!-- KPI Summary Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="stat bg-base-100 rounded-box border border-base-200 shadow-xs p-5">
          <div class="stat-figure text-primary">
            <v-icon name="hi-clock" class="w-7 h-7" />
          </div>
          <div class="stat-title text-xs font-bold uppercase tracking-wider text-base-content/60">Total Sessions</div>
          <div class="stat-value text-2xl font-black text-primary">{{ testSessions.length }}</div>
        </div>

        <div class="stat bg-base-100 rounded-box border border-base-200 shadow-xs p-5">
          <div class="stat-figure text-success">
            <v-icon name="hi-adjustments" class="w-7 h-7" />
          </div>
          <div class="stat-title text-xs font-bold uppercase tracking-wider text-base-content/60">Latest Session Score</div>
          <div class="stat-value text-2xl font-black text-success">
            {{ latestSessionScore !== null ? latestSessionScore + '%' : '-' }}
          </div>
        </div>

        <div class="stat bg-base-100 rounded-box border border-base-200 shadow-xs p-5">
          <div class="stat-figure text-secondary">
            <v-icon name="hi-trending-up" class="w-7 h-7" />
          </div>
          <div class="stat-title text-xs font-bold uppercase tracking-wider text-base-content/60">Overall Progress</div>
          <div class="stat-value text-2xl font-black text-secondary">
            {{ overallTrend }}
          </div>
        </div>
      </div>

      <!-- Comparison Matrix Table -->
      <div class="card bg-base-100 border border-base-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="table table-sm w-full text-xs">
            <thead>
              <tr class="bg-base-200 text-base-content font-bold">
                <th class="py-3.5 px-4 min-w-[180px]">Indicator / Parameter</th>
                <th v-for="sess in testSessions" :key="sess.id" class="py-3.5 px-4 text-center min-w-[140px] border-l border-base-300">
                  <p class="font-bold text-base-content leading-tight">{{ sess.name }}</p>
                  <p class="text-[10px] text-base-content/60 font-normal mt-0.5">{{ formatDate(sess.date_time) }}</p>
                </th>
                <th class="py-3.5 px-4 text-center min-w-[100px] border-l border-base-300">Progress</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-base-200 font-medium">
              <!-- Anthropometry Rows -->
              <tr class="bg-base-200/40 font-bold">
                <td colspan="100%" class="py-2 px-4 text-base-content text-[11px] uppercase tracking-wider">Anthropometry Measures</td>
              </tr>
              <tr class="hover">
                <td class="py-3 px-4 font-bold text-base-content">Height (cm)</td>
                <td v-for="sess in testSessions" :key="sess.id" class="py-3 px-4 text-center border-l border-base-200 font-semibold">
                  {{ anthropometryMatrix[sess.id]?.height || '-' }}
                </td>
                <td class="py-3 px-4 text-center border-l border-base-200 text-base-content/50">-</td>
              </tr>
              <tr class="hover">
                <td class="py-3 px-4 font-bold text-base-content">Weight (kg)</td>
                <td v-for="sess in testSessions" :key="sess.id" class="py-3 px-4 text-center border-l border-base-200 font-semibold">
                  {{ anthropometryMatrix[sess.id]?.weight || '-' }}
                </td>
                <td class="py-3 px-4 text-center border-l border-base-200 text-base-content/50">-</td>
              </tr>
              <tr class="hover">
                <td class="py-3 px-4 font-bold text-base-content">BMI (Body Mass Index)</td>
                <td v-for="sess in testSessions" :key="sess.id" class="py-3 px-4 text-center border-l border-base-200 font-bold">
                  {{ anthropometryMatrix[sess.id]?.bmi || '-' }}
                </td>
                <td class="py-3 px-4 text-center border-l border-base-200 text-base-content/50">-</td>
              </tr>

              <!-- Indicators Rows -->
              <tr class="bg-base-200/40 font-bold">
                <td colspan="100%" class="py-2 px-4 text-base-content text-[11px] uppercase tracking-wider">Physical Indicators Performance</td>
              </tr>
              <tr v-for="ind in indicators" :key="ind.id" class="hover">
                <td class="py-3.5 px-4 font-bold text-base-content">
                  {{ ind.name }}
                  <span v-if="ind.unit" class="text-[10px] text-base-content/60 font-semibold block">({{ ind.unit }})</span>
                </td>
                <td v-for="sess in testSessions" :key="sess.id" class="py-3.5 px-4 text-center border-l border-base-200">
                  <div v-if="rawMatrix[ind.id] && rawMatrix[ind.id][sess.id] !== undefined">
                    <span class="font-bold text-base-content text-sm block">{{ rawMatrix[ind.id][sess.id] }}</span>
                    <span class="text-[10px] font-bold block mt-0.5" :style="{ color: getScoreColor(comparisonMatrix[ind.id]?.[sess.id]) }">
                      Score: {{ comparisonMatrix[ind.id]?.[sess.id] }}%
                    </span>
                  </div>
                  <span v-else class="text-base-content/40">-</span>
                </td>
                <td class="py-3.5 px-4 text-center border-l border-base-200 font-bold text-xs">
                  <span v-if="indicatorTrends[ind.id] !== undefined" :class="indicatorTrends[ind.id] >= 0 ? 'text-success' : 'text-error'">
                    {{ indicatorTrends[ind.id] >= 0 ? '+' : '' }}{{ indicatorTrends[ind.id] }}%
                  </span>
                  <span v-else class="text-base-content/40">-</span>
                </td>
              </tr>

              <!-- Overall Session Summary -->
              <tr class="bg-base-200/80 font-bold border-t-2 border-base-300">
                <td class="py-4 px-4 text-base-content font-extrabold text-[11px]">Overall Session Score</td>
                <td v-for="sess in testSessions" :key="sess.id" class="py-4 px-4 text-center border-l border-base-300 text-base">
                  <span v-if="overallScores[sess.id] !== undefined" :style="{ color: getScoreColor(overallScores[sess.id]) }">
                    {{ overallScores[sess.id] }}%
                  </span>
                  <span v-else class="text-base-content/40 text-xs">-</span>
                </td>
                <td class="py-4 px-4 text-center border-l border-base-300 text-base">
                  <span v-if="overallChange !== null" :class="overallChange >= 0 ? 'text-success' : 'text-error'">
                    {{ overallChange >= 0 ? '+' : '' }}{{ overallChange }}%
                  </span>
                  <span v-else class="text-base-content/40 text-xs">-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  athlete: { type: Object, required: true },
  testSessions: { type: Array, default: () => [] },
  indicators: { type: Array, default: () => [] },
  comparisonMatrix: { type: Object, default: () => ({}) },
  rawMatrix: { type: Object, default: () => ({}) },
  anthropometryMatrix: { type: Object, default: () => ({}) },
  overallScores: { type: Object, default: () => ({}) },
  institutions: { type: Array, default: () => [] },
});

const defaultInstitutionId = computed(() => props.institutions[0]?.id || '');

function formatDate(dStr) {
  if (!dStr) return '-';
  const d = new Date(dStr);
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

function getScoreColor(v) {
  if (v === null || v === undefined) return '#68758A';
  if (v >= 85) return '#06A77D';
  if (v >= 70) return '#5B8DEF';
  if (v >= 55) return '#F4A100';
  return '#E63946';
}

const latestSessionScore = computed(() => {
  if (!props.testSessions.length) return null;
  const lastId = props.testSessions[props.testSessions.length - 1].id;
  return props.overallScores[lastId] !== undefined ? props.overallScores[lastId] : null;
});

const overallChange = computed(() => {
  if (props.testSessions.length < 2) return null;
  const firstId = props.testSessions[0].id;
  const lastId = props.testSessions[props.testSessions.length - 1].id;
  const firstScore = props.overallScores[firstId];
  const lastScore = props.overallScores[lastId];
  if (firstScore === undefined || lastScore === undefined) return null;
  return Math.round((lastScore - firstScore) * 10) / 10;
});

const overallTrend = computed(() => {
  const change = overallChange.value;
  if (change === null) return 'N/A';
  if (change > 0) return `+${change}% (Improved)`;
  if (change < 0) return `${change}% (Declined)`;
  return '0% (Stable)';
});

const indicatorTrends = computed(() => {
  const trends = {};
  if (props.testSessions.length < 2) return trends;
  const firstId = props.testSessions[0].id;
  const lastId = props.testSessions[props.testSessions.length - 1].id;

  props.indicators.forEach(ind => {
    const firstVal = props.comparisonMatrix[ind.id]?.[firstId];
    const lastVal = props.comparisonMatrix[ind.id]?.[lastId];
    if (firstVal !== undefined && lastVal !== undefined && firstVal !== null && lastVal !== null) {
      trends[ind.id] = Math.round((lastVal - firstVal) * 10) / 10;
    }
  });

  return trends;
});
</script>
