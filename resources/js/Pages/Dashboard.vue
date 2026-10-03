<template>
  <AppLayout>
    <div class="w-full space-y-6 pb-6">
      <!-- Header Banner -->
      <div class="relative overflow-hidden rounded-2xl p-6 sm:p-8 text-white shadow-md border border-slate-700/50">
        <!-- Background decorative ambient circles -->
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute right-1/3 -top-10 w-48 h-48 bg-teal-500/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
          <div class="space-y-2 max-w-2xl">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/15 text-xs font-semibold text-indigo-200">
              <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
              <span>Live Analytics Dashboard</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
              Physical Performance Overview
            </h1>
            <p class="text-xs sm:text-sm text-slate-300 font-medium leading-relaxed">
              Global statistics summary, athlete demographics, and physical test performance benchmarks.
            </p>
          </div>

          <div class="flex items-center gap-3 bg-white/10 backdrop-blur-md border border-white/15 rounded-xl p-3.5 sm:p-4 text-xs font-bold shrink-0">
            <div class="w-10 h-10 rounded-lg bg-indigo-500/20 border border-indigo-400/30 flex items-center justify-center text-indigo-300">
              <v-icon name="hi-users" class="w-5 h-5" />
            </div>
            <div>
              <span class="text-[10px] uppercase tracking-wider text-slate-300 block font-semibold">Registered Capacity</span>
              <span class="text-lg font-black text-white"><span class="text-emerald-400">{{ kpi.total_athletes || 0 }}</span> Athletes</span>
            </div>
          </div>
        </div>
      </div>

      <!-- 6 Tactile KPI Stat Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <!-- Card 1: Total Athletes -->
        <div class="group card bg-base-100 border border-base-200/80 rounded-2xl shadow-xs p-4 hover:-translate-y-1 hover:shadow-md hover:border-emerald-500/40 transition-all duration-200 relative overflow-hidden">
          <div class="absolute top-0 left-0 right-0 h-1 bg-emerald-500"></div>
          <div class="flex items-center justify-between gap-2 mb-2">
            <span class="text-[11px] font-extrabold uppercase tracking-wider text-base-content/60">Total Athletes</span>
            <div class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center group-hover:scale-110 transition-transform">
              <v-icon name="hi-users" class="w-5 h-5" />
            </div>
          </div>
          <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 tracking-tight">{{ kpi.total_athletes || 0 }}</div>
          <div class="text-[10px] text-base-content/50 font-semibold mt-1">All registered profiles</div>
        </div>

        <!-- Card 2: Male Athletes -->
        <div class="group card bg-base-100 border border-base-200/80 rounded-2xl shadow-xs p-4 hover:-translate-y-1 hover:shadow-md hover:border-sky-500/40 transition-all duration-200 relative overflow-hidden">
          <div class="absolute top-0 left-0 right-0 h-1 bg-sky-500"></div>
          <div class="flex items-center justify-between gap-2 mb-2">
            <span class="text-[11px] font-extrabold uppercase tracking-wider text-base-content/60">Male (L)</span>
            <div class="w-9 h-9 rounded-xl bg-sky-500/10 text-sky-600 dark:text-sky-400 flex items-center justify-center group-hover:scale-110 transition-transform">
              <v-icon name="hi-user-group" class="w-5 h-5" />
            </div>
          </div>
          <div class="text-2xl font-black text-sky-600 dark:text-sky-400 tracking-tight">{{ kpi.male_athletes || 0 }}</div>
          <div class="inline-flex items-center gap-1 text-[10px] font-bold text-sky-600 dark:text-sky-400 mt-1">
            <span>Avg:</span>
            <span>{{ kpi.male_avg_score ? Number(kpi.male_avg_score).toFixed(1) + '%' : '-' }}</span>
          </div>
        </div>

        <!-- Card 3: Female Athletes -->
        <div class="group card bg-base-100 border border-base-200/80 rounded-2xl shadow-xs p-4 hover:-translate-y-1 hover:shadow-md hover:border-pink-500/40 transition-all duration-200 relative overflow-hidden">
          <div class="absolute top-0 left-0 right-0 h-1 bg-pink-500"></div>
          <div class="flex items-center justify-between gap-2 mb-2">
            <span class="text-[11px] font-extrabold uppercase tracking-wider text-base-content/60">Female (P)</span>
            <div class="w-9 h-9 rounded-xl bg-pink-500/10 text-pink-600 dark:text-pink-400 flex items-center justify-center group-hover:scale-110 transition-transform">
              <v-icon name="hi-user-group" class="w-5 h-5" />
            </div>
          </div>
          <div class="text-2xl font-black text-pink-600 dark:text-pink-400 tracking-tight">{{ kpi.female_athletes || 0 }}</div>
          <div class="inline-flex items-center gap-1 text-[10px] font-bold text-pink-600 dark:text-pink-400 mt-1">
            <span>Avg:</span>
            <span>{{ kpi.female_avg_score ? Number(kpi.female_avg_score).toFixed(1) + '%' : '-' }}</span>
          </div>
        </div>

        <!-- Card 4: Overall Avg Score -->
        <div class="group card bg-base-100 border border-base-200/80 rounded-2xl shadow-xs p-4 hover:-translate-y-1 hover:shadow-md hover:border-violet-500/40 transition-all duration-200 relative overflow-hidden">
          <div class="absolute top-0 left-0 right-0 h-1 bg-violet-500"></div>
          <div class="flex items-center justify-between gap-2 mb-2">
            <span class="text-[11px] font-extrabold uppercase tracking-wider text-base-content/60">Overall Avg</span>
            <div class="w-9 h-9 rounded-xl bg-violet-500/10 text-violet-600 dark:text-violet-400 flex items-center justify-center group-hover:scale-110 transition-transform">
              <v-icon name="hi-adjustments" class="w-5 h-5" />
            </div>
          </div>
          <div class="text-2xl font-black text-violet-600 dark:text-violet-400 tracking-tight">
            {{ kpi.average_score ? Number(kpi.average_score).toFixed(1) + '%' : '0%' }}
          </div>
          <div class="text-[10px] text-base-content/50 font-semibold mt-1">Global mean score</div>
        </div>

        <!-- Card 5: Sport Branches -->
        <div class="group card bg-base-100 border border-base-200/80 rounded-2xl shadow-xs p-4 hover:-translate-y-1 hover:shadow-md hover:border-amber-500/40 transition-all duration-200 relative overflow-hidden">
          <div class="absolute top-0 left-0 right-0 h-1 bg-amber-500"></div>
          <div class="flex items-center justify-between gap-2 mb-2">
            <span class="text-[11px] font-extrabold uppercase tracking-wider text-base-content/60">Sports</span>
            <div class="w-9 h-9 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center group-hover:scale-110 transition-transform">
              <v-icon name="bi-trophy" class="w-5 h-5" />
            </div>
          </div>
          <div class="text-2xl font-black text-amber-600 dark:text-amber-400 tracking-tight">{{ kpi.total_sports || 0 }}</div>
          <div class="text-[10px] text-base-content/50 font-semibold mt-1">Active disciplines</div>
        </div>

        <!-- Card 6: Data Folders -->
        <div class="group card bg-base-100 border border-base-200/80 rounded-2xl shadow-xs p-4 hover:-translate-y-1 hover:shadow-md hover:border-cyan-500/40 transition-all duration-200 relative overflow-hidden">
          <div class="absolute top-0 left-0 right-0 h-1 bg-cyan-500"></div>
          <div class="flex items-center justify-between gap-2 mb-2">
            <span class="text-[11px] font-extrabold uppercase tracking-wider text-base-content/60">Folders</span>
            <div class="w-9 h-9 rounded-xl bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 flex items-center justify-center group-hover:scale-110 transition-transform">
              <v-icon name="hi-folder" class="w-5 h-5" />
            </div>
          </div>
          <div class="text-2xl font-black text-cyan-600 dark:text-cyan-400 tracking-tight">{{ kpi.total_folders || 0 }}</div>
          <div class="text-[10px] text-base-content/50 font-semibold mt-1">Test data sessions</div>
        </div>
      </div>

      <div class="space-y-6">
        <!-- Chart 1: Total Athletes per Sport Branch -->
        <div class="card bg-base-100 border border-base-200/80 rounded-2xl shadow-xs overflow-hidden">
          <div class="px-5 py-4 border-b border-base-200/60 bg-base-200/30 flex items-center justify-between gap-2 flex-wrap">
            <div class="flex items-center gap-2">
              <div class="w-2.5 h-2.5 rounded-full bg-emerald-500"></div>
              <h3 class="font-bold text-sm text-base-content">Total Athletes per Sport Branch</h3>
            </div>
            <span class="text-[10px] font-bold text-base-content/50 uppercase tracking-wider">Capacity Distribution</span>
          </div>
          <div class="p-5">
            <div v-if="hasSportCountData" class="h-64 sm:h-72">
              <canvas ref="chartCountRef"></canvas>
            </div>
            <div v-else class="h-64 sm:h-72 flex flex-col items-center justify-center text-center p-6 border border-dashed border-base-content/15 rounded-xl bg-base-200/20">
              <div class="w-12 h-12 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-3">
                <v-icon name="hi-users" class="w-6 h-6" />
              </div>
              <p class="text-sm font-bold text-base-content/80">No Athletes Registered Yet</p>
              <p class="text-xs text-base-content/50 max-w-xs mt-1">Capacity distribution will display here once athletes are assigned to sport branches.</p>
            </div>
          </div>
        </div>

        <!-- Chart 2: Average Performance per Sport Branch -->
        <div class="card bg-base-100 border border-base-200/80 rounded-2xl shadow-xs overflow-hidden">
          <div class="px-5 py-4 border-b border-base-200/60 bg-base-200/30 flex items-center justify-between gap-2 flex-wrap">
            <div class="flex items-center gap-2">
              <div class="w-2.5 h-2.5 rounded-full bg-blue-600"></div>
              <h3 class="font-bold text-sm text-base-content">Average Performance per Sport Branch</h3>
            </div>
            <span class="text-[10px] font-bold text-base-content/50 uppercase tracking-wider">Score (%)</span>
          </div>
          <div class="p-5">
            <div v-if="hasSportPerformanceData" class="h-64 sm:h-72">
              <canvas ref="chartCaborRef"></canvas>
            </div>
            <div v-else class="h-64 sm:h-72 flex flex-col items-center justify-center text-center p-6 border border-dashed border-base-content/15 rounded-xl bg-base-200/20">
              <div class="w-12 h-12 rounded-full bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center mb-3">
                <v-icon name="bi-trophy" class="w-6 h-6" />
              </div>
              <p class="text-sm font-bold text-base-content/80">No Sport Performance Data Available</p>
              <p class="text-xs text-base-content/50 max-w-xs mt-1">Performance scores will be calculated once physical test sessions are recorded.</p>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Chart 3: Average Performance by Gender -->
          <div class="card bg-base-100 border border-base-200/80 rounded-2xl shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-base-200/60 bg-base-200/30 flex items-center justify-between gap-2 flex-wrap">
              <div class="flex items-center gap-2">
                <div class="w-2.5 h-2.5 rounded-full bg-pink-500"></div>
                <h3 class="font-bold text-sm text-base-content">Average Performance by Gender</h3>
              </div>
              <span class="text-[10px] font-bold text-base-content/50 uppercase tracking-wider">Demographic Breakdown</span>
            </div>
            <div class="p-5">
              <div v-if="hasGenderData" class="h-64 sm:h-72">
                <canvas ref="chartGenderRef"></canvas>
              </div>
              <div v-else class="h-64 sm:h-72 flex flex-col items-center justify-center text-center p-6 border border-dashed border-base-content/15 rounded-xl bg-base-200/20">
                <div class="w-12 h-12 rounded-full bg-pink-500/10 text-pink-600 dark:text-pink-400 flex items-center justify-center mb-3">
                  <v-icon name="hi-user-group" class="w-6 h-6" />
                </div>
                <p class="text-sm font-bold text-base-content/80">No Gender Assessment Data Available</p>
                <p class="text-xs text-base-content/50 max-w-xs mt-1">Assessment scores will be visualized once athletes complete their physical tests.</p>
              </div>
            </div>
          </div>

          <!-- Chart 4: Average Performance by Benchmark Age Group -->
          <div class="card bg-base-100 border border-base-200/80 rounded-2xl shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-base-200/60 bg-base-200/30 flex items-center justify-between gap-2 flex-wrap">
              <div class="flex items-center gap-2">
                <div class="w-2.5 h-2.5 rounded-full bg-purple-600"></div>
                <h3 class="font-bold text-sm text-base-content">Average Performance by Benchmark Age Group</h3>
              </div>
              <span class="text-[10px] font-bold text-base-content/50 uppercase tracking-wider">Age Categories</span>
            </div>
            <div class="p-5">
              <div v-if="hasAgeGroupData" class="h-64 sm:h-72">
                <canvas ref="chartAgeRef"></canvas>
              </div>
              <div v-else class="h-64 sm:h-72 flex flex-col items-center justify-center text-center p-6 border border-dashed border-base-content/15 rounded-xl bg-base-200/20">
                <div class="w-12 h-12 rounded-full bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center mb-3">
                  <v-icon name="hi-adjustments" class="w-6 h-6" />
                </div>
                <p class="text-sm font-bold text-base-content/80">No Age Group Assessment Data Available</p>
                <p class="text-xs text-base-content/50 max-w-xs mt-1">Average benchmark scores by age group will appear here after physical test sessions.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import Chart from 'chart.js/auto';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  kpi: { type: Object, default: () => ({}) },
  performanceBySport: { type: Array, default: () => [] },
  performanceByGender: { type: Array, default: () => [] },
  athletesBySport: { type: Array, default: () => [] },
  performanceByAgeGroup: { type: Array, default: () => [] },
});

const chartCaborRef = ref(null);
const chartGenderRef = ref(null);
const chartCountRef = ref(null);
const chartAgeRef = ref(null);

const hasGenderData = computed(() => {
  return props.performanceByGender?.some(d => Number(d.average_score) > 0 && Number(d.athlete_count) > 0);
});

const hasSportPerformanceData = computed(() => {
  return props.performanceBySport?.some(d => Number(d.average_score) > 0);
});

const hasSportCountData = computed(() => {
  return props.athletesBySport?.some(d => Number(d.athletes_count) > 0);
});

const hasAgeGroupData = computed(() => {
  return props.performanceByAgeGroup?.some(d => Number(d.average_score) > 0);
});

function wrapLabel(label, maxLen = 12) {
  if (Array.isArray(label)) return label;
  if (!label || typeof label !== 'string') return label || '';
  if (label.length <= maxLen) return label;

  const words = label.trim().split(/\s+/);
  if (words.length <= 1) {
    return [label.slice(0, maxLen), label.slice(maxLen)];
  }

  const line1 = [];
  const line2 = [];
  let currentLen = 0;

  for (let i = 0; i < words.length; i++) {
    if (i === 0 || (currentLen + words[i].length + 1 <= maxLen && line2.length === 0)) {
      line1.push(words[i]);
      currentLen += words[i].length + (i > 0 ? 1 : 0);
    } else {
      line2.push(words[i]);
    }
  }

  if (line2.length === 0) return label;
  return [line1.join(' '), line2.join(' ')];
}

onMounted(async () => {
  await nextTick();

  if (chartCaborRef.value && hasSportPerformanceData.value) {
    new Chart(chartCaborRef.value, {
      type: 'bar',
      plugins: [ChartDataLabels],
      data: {
        labels: props.performanceBySport.map(d => wrapLabel(d.name)),
        datasets: [{
          label: 'Average Score (%)',
          data: props.performanceBySport.map(d => d.average_score),
          backgroundColor: '#2563eb',
          borderRadius: 6,
          maxBarThickness: 45,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: { top: 22 } },
        plugins: {
          legend: {
            labels: { color: '#f8fafc' }
          },
          datalabels: {
            anchor: 'end',
            align: 'top',
            formatter: (v) => v !== null && v !== undefined ? Number(v).toFixed(1) + '%' : '',
            font: { weight: 'bold', size: 10 },
            color: '#ffffff'
          }
        },
        scales: {
          y: { 
            min: 0, 
            max: 100,
            grid: { color: 'rgba(248, 250, 252, 0.08)' },
            ticks: { color: '#cbd5e1', font: { size: 10 } }
          },
          x: {
            grid: { display: false },
            ticks: { 
              color: '#cbd5e1',
              font: { size: 9.5 },
              autoSkip: false,
              maxRotation: 45,
              minRotation: 0
            }
          }
        }
      }
    });
  }

  if (chartGenderRef.value && hasGenderData.value) {
    new Chart(chartGenderRef.value, {
      type: 'doughnut',
      plugins: [ChartDataLabels],
      data: {
        labels: props.performanceByGender.map(d => d.gender),
        datasets: [{
          data: props.performanceByGender.map(d => d.average_score),
          backgroundColor: ['#0284c7', '#ec4899'],
          borderWidth: 2,
          borderColor: '#0d2545',
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            labels: { color: '#f8fafc' }
          },
          datalabels: {
            formatter: (v, ctx) => {
              const label = ctx.chart.data.labels[ctx.dataIndex];
              const score = v !== null && v !== undefined ? Number(v).toFixed(1) + '%' : '';
              return `${label}\n${score}`;
            },
            textAlign: 'center',
            font: { weight: 'bold', size: 11 },
            color: '#ffffff'
          }
        }
      }
    });
  }

  if (chartCountRef.value && hasSportCountData.value) {
    new Chart(chartCountRef.value, {
      type: 'bar',
      plugins: [ChartDataLabels],
      data: {
        labels: props.athletesBySport.map(d => wrapLabel(d.name)),
        datasets: [{
          label: 'Athlete Count',
          data: props.athletesBySport.map(d => d.athletes_count),
          backgroundColor: '#10b981',
          borderRadius: 6,
          maxBarThickness: 45,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: { top: 22 } },
        plugins: {
          legend: {
            labels: { color: '#f8fafc' }
          },
          datalabels: {
            anchor: 'end',
            align: 'top',
            formatter: (v) => v !== null && v !== undefined ? v : '',
            font: { weight: 'bold', size: 10 },
            color: '#ffffff'
          }
        },
        scales: {
          y: { 
            grid: { color: 'rgba(248, 250, 252, 0.08)' },
            ticks: { color: '#cbd5e1', font: { size: 10 } }
          },
          x: {
            grid: { display: false },
            ticks: { 
              color: '#cbd5e1',
              font: { size: 9.5 },
              autoSkip: false,
              maxRotation: 45,
              minRotation: 0
            }
          }
        }
      }
    });
  }

  if (chartAgeRef.value && hasAgeGroupData.value) {
    new Chart(chartAgeRef.value, {
      type: 'bar',
      plugins: [ChartDataLabels],
      data: {
        labels: props.performanceByAgeGroup.map(d => wrapLabel(d.age_group)),
        datasets: [{
          label: 'Average Score (%)',
          data: props.performanceByAgeGroup.map(d => d.average_score),
          backgroundColor: '#8b5cf6',
          borderRadius: 6,
          maxBarThickness: 45,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: { top: 22 } },
        plugins: {
          legend: {
            labels: { color: '#f8fafc' }
          },
          datalabels: {
            anchor: 'end',
            align: 'top',
            formatter: (v) => v !== null && v !== undefined ? Number(v).toFixed(1) + '%' : '',
            font: { weight: 'bold', size: 10 },
            color: '#ffffff'
          }
        },
        scales: {
          y: { 
            min: 0, 
            max: 100,
            grid: { color: 'rgba(248, 250, 252, 0.08)' },
            ticks: { color: '#cbd5e1', font: { size: 10 } }
          },
          x: {
            grid: { display: false },
            ticks: { 
              color: '#cbd5e1',
              font: { size: 9.5 },
              autoSkip: false,
              maxRotation: 45,
              minRotation: 0
            }
          }
        }
      }
    });
  }
});
</script>
