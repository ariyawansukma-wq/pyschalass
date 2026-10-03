<template>
  <div class="rpt" :style="{ width: paperStyles.w, minHeight: paperStyles.h }" style="page-break-after: always; page-break-inside: avoid;">
    <!-- Header -->
    <div class="rpt-head">
      <div class="rpt-logos-left flex items-center gap-2 shrink-0">
        <img 
          v-for="logo in allLogos" 
          :key="logo.id || logo.logo_path" 
          :src="'/storage/' + logo.logo_path" 
          alt="Logo" 
          :style="{ height: logo.height_px + 'px', maxHeight: '80px', objectFit: 'contain' }"
        />
      </div>
      <div class="rpt-title flex-1 text-right pl-3">
        <template v-if="institution && institution.header_lines">
          <h1 
            v-for="(line, lIdx) in institution.header_lines.split('\n')" 
            :key="'header-line-' + lIdx"
            class="text-sm font-bold text-[#0B2545] uppercase leading-tight"
          >
            {{ line }}
          </h1>
        </template>
        <h1 v-else class="text-sm font-bold text-[#0B2545] uppercase">
          {{ institution?.name || 'PhysicalScore' }}
        </h1>
        <div v-if="institution && institution.address" class="s1 text-[9px] text-slate-700 font-medium">
          {{ institution.address }}
        </div>
        <div class="s1 text-[9px] text-slate-600 font-medium">
          PhysicalScore - Physical Performance Assessment System
        </div>
      </div>
    </div>

    <!-- Banner -->
    <div class="rpt-banner">
      <div class="min-w-0 flex-1 pr-2">
        <h2 class="font-bold leading-tight" :style="{ fontSize: Math.max(9, 14 - Math.max(0, (data.athlete.name.length - 16) * 0.3)) + 'px' }">
          {{ data.athlete.name }}
        </h2>
        <div class="text-[10px] opacity-90 font-medium mt-0.5">
          {{ data.athlete.gender === 'M' ? 'Male' : 'Female' }}<template v-if="data.athlete.event_number"> - {{ data.athlete.event_number }}</template>
        </div>
      </div>
      <div 
          class="font-bold text-white uppercase text-right whitespace-nowrap shrink-0 mr-1" 
          :style="{ fontSize: Math.max(8.5, 11 - Math.max(0, ((data.athlete.sport_branch?.name || data.athlete.sport_branch_name || '').length - 15) * 0.2)) + 'px' }"
        >
          {{ data.athlete.sport_branch?.name || data.athlete.sport_branch_name }}
      </div>
    </div>

    <!-- Biocard -->
    <div class="biocard">
      <img 
        v-if="data.athlete.photo_path" 
        class="biophoto" 
        :src="'/storage/' + data.athlete.photo_path" 
        alt="Athlete Photo"
      />
      <div v-else class="biophoto flex items-center justify-center text-slate-400 font-bold text-lg">
        {{ data.athlete.name.charAt(0) }}
      </div>
      <div class="biogrid">
        <div>
          <b>Date of Birth</b>
          <span>{{ formatDob(data.athlete.date_of_birth) }}</span>
        </div>
        <div>
          <b>Age at Test</b>
          <span>{{ data.age_at_test?.formatted || '-' }}</span>
        </div>
        <div>
          <b>Height / Weight</b>
          <span>{{ data.latest_height || data.athlete.height || '-' }} cm / {{ data.latest_weight || data.athlete.weight || '-' }} kg</span>
        </div>
        <div>
          <b>Folder</b>
          <span>{{ data.folder?.name || data.athlete.folder?.name || 'All Test Data' }}</span>
        </div>
        <div>
          <b>Total Tests</b>
          <span>{{ data.sessions?.length || 0 }}x</span>
        </div>
        <div>
          <b>Athlete Number</b>
          <span>{{ data.athlete.athlete_number || '-' }}</span>
        </div>
        <div v-for="session in data.sessions" :key="session.id">
          <b>{{ session.name }} Date</b>
          <span>{{ formatSessionDate(session.date_time || session.date) || '-' }}</span>
        </div>
      </div>

      <!-- BMI Gauge -->
      <div class="text-center w-[52px] shrink-0">
        <div class="text-[7px] font-bold text-[#0B2545] tracking-wider mb-0.5">BMI</div>
        <template v-if="data.bmi !== null">
          <svg width="40" height="52" viewBox="0 0 100 155" class="block mx-auto">
            <circle cx="50" cy="18" r="16" :fill="data.bmi_color" />
            <rect x="43" y="32" width="14" height="10" :fill="data.bmi_color" />
            <path d="M28,42 Q50,32 72,42 L76,78 Q64,90 50,90 Q36,90 24,78 Z" :fill="data.bmi_color" />
            <path d="M26,44 Q14,58 17,86 Q18,95 27,97 L32,92 Q26,86 26,74 Q26,58 34,48 Z" :fill="data.bmi_color" opacity=".88" />
            <path d="M74,44 Q86,58 83,86 Q82,95 73,97 L68,92 Q74,86 74,74 Q74,58 66,48 Z" :fill="data.bmi_color" opacity=".88" />
            <path d="M33,88 Q30,120 27,152 Q26,158 34,158 L39,158 Q42,124 46,91 Z" :fill="data.bmi_color" opacity=".96" />
            <path d="M67,88 Q70,120 73,152 Q74,158 66,158 L61,158 Q58,124 54,91 Z" :fill="data.bmi_color" opacity=".96" />
          </svg>
          <div class="font-bold text-[11px] mt-0.5 text-center" :style="{ color: data.bmi_color }">
            {{ Number(data.bmi).toFixed(1) }}
          </div>
          <div class="text-[6.5px] font-bold text-slate-500 line-clamp-2 leading-none text-center">
            {{ data.bmi_category }}
          </div>
        </template>
        <div v-else class="text-[8px] text-slate-400 py-2.5">N/A</div>
      </div>

       <!-- Overall circular scores -->
       <div class="flex gap-1.5 shrink-0 items-center justify-center">
        <template v-for="(session, si) in data.sessions" :key="session.id">
          <div v-if="data.session_scores[session.id]?.overall !== null" class="relative w-12 h-12">
            <svg width="48" height="48" viewBox="0 0 48 48" class="block">
              <circle cx="24" cy="24" :r="20" fill="none" stroke="#EDF1F6" stroke-width="4" />
              <circle 
                cx="24" 
                cy="24" 
                :r="20" 
                fill="none" 
                :stroke="getSessionColor(session, si)" 
                stroke-width="4" 
                :stroke-dasharray="2 * Math.PI * 20" 
                :stroke-dashoffset="2 * Math.PI * 20 * (1 - Math.min(data.session_scores[session.id].overall, 100) / 100)" 
                stroke-linecap="round" 
                transform="rotate(-90 24 24)" 
              />
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center text-center pointer-events-none">
              <div class="text-[9.5px] font-bold leading-none" :style="{ color: getSessionColor(session, si) }">
                {{ Number(data.session_scores[session.id].overall).toFixed(1) }}%
              </div>
              <div class="text-[5px] font-bold text-slate-500 leading-none truncate max-w-[38px] mt-0.5">
                {{ session.name }}
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>

    <!-- Legend and Section Header -->
    <div class="flex justify-between items-center gap-1 mt-1">
      <div class="rpt-sec-title m-0 whitespace-nowrap">TEST INDICATORS PERFORMANCE</div>
      <div class="legend flex items-center gap-1.5 flex-nowrap text-[6.5px] whitespace-nowrap">
        <b>Legend:</b>
        <span class="inline-flex items-center gap-0.5 text-[#06A77D] font-extrabold"><v-icon name="hi-minus" class="w-2 h-2 shrink-0" /> ≥85% Excellent</span>
        <span class="inline-flex items-center gap-0.5 text-[#5B8DEF] font-extrabold"><v-icon name="hi-minus" class="w-2 h-2 shrink-0" /> 70–84% Good</span>
        <span class="inline-flex items-center gap-0.5 text-[#F4A100] font-extrabold"><v-icon name="hi-minus" class="w-2 h-2 shrink-0" /> 55–69% Fair</span>
        <span class="inline-flex items-center gap-0.5 text-[#E63946] font-extrabold"><v-icon name="hi-minus" class="w-2 h-2 shrink-0" /> &lt;55% Needs Imp.</span>
        <span class="inline-flex items-center gap-0.5 text-[#06A77D] font-extrabold"><v-icon name="hi-arrow-up" class="w-2 h-2 shrink-0" /> Up</span>
        <span class="inline-flex items-center gap-0.5 text-[#E63946] font-extrabold"><v-icon name="hi-arrow-down" class="w-2 h-2 shrink-0" /> Down</span>
        <span class="inline-flex items-center gap-0.5 text-[#68758A] font-extrabold"><v-icon name="hi-minus" class="w-2 h-2 shrink-0" /> Same</span>
      </div>
    </div>

    <!-- Scores Table -->
    <table class="rpttable mt-1">
      <thead>
        <tr>
          <th rowspan="2" style="vertical-align: middle; text-align: center;">Indicator (Unit)</th>
          <th 
            v-for="(session, si) in data.sessions" 
            :key="session.id" 
            :colspan="si === 0 ? 3 : 4" 
            class="session-col-divider text-white font-bold"
            :style="{ background: getSessionColor(session, si) + ' !important' }"
          >
            <span class="block">{{ session.name }}</span>
          </th>
        </tr>
        <tr>
          <template v-for="(session, si) in data.sessions" :key="session.id">
            <th>Benchmark</th>
            <th>Value</th>
            <th :class="{ 'session-col-divider': si === 0 }">Performance</th>
            <th v-if="si > 0" class="session-col-divider">Progress</th>
          </template>
        </tr>
      </thead>
      <tbody>
        <tr v-for="indicator in data.indicators" :key="indicator.id">
          <td style="text-align: left;">{{ indicator.name }} ({{ indicator.unit }})</td>
          <template v-for="(session, si) in data.sessions" :key="session.id">
            <!-- Benchmark val -->
            <td>
              <span class="text-[7.5px] text-slate-500 font-semibold">
                {{ getBenchmarkVal(session.id, indicator.id) }}
              </span>
            </td>
            <!-- Raw val -->
            <td>
              {{ formatRawVal(data.session_scores[session.id]?.indicators[indicator.id]?.value) }}
            </td>
            <!-- Performance pct -->
            <td 
              :class="[{ 'session-col-divider': si === 0 }, 'font-extrabold']"
              :style="{ color: getScoreColor(data.session_scores[session.id]?.indicators[indicator.id]?.score) }"
            >
              <div class="flex items-center justify-center gap-1.5">
                <div v-if="data.session_scores[session.id]?.indicators[indicator.id]?.score !== null" class="w-8 h-2 bg-slate-100 rounded-full overflow-hidden border border-slate-200/60 shrink-0">
                  <div 
                    class="h-full rounded-full transition-all" 
                    :style="{ width: Math.min(Math.max(data.session_scores[session.id]?.indicators[indicator.id]?.score, 0), 100) + '%', backgroundColor: getScoreColor(data.session_scores[session.id]?.indicators[indicator.id]?.score) }"
                  ></div>
                </div>
                <span>{{ formatPct(data.session_scores[session.id]?.indicators[indicator.id]?.score) }}</span>
              </div>
            </td>
            <!-- Progress relative to previous session -->
            <td v-if="si > 0" class="session-col-divider">
              <div class="flex items-center justify-center gap-1">
                <div v-if="data.session_scores[session.id]?.indicators[indicator.id]?.score !== null" class="w-8 h-2 bg-slate-100 rounded-full overflow-hidden border border-slate-200/60 shrink-0">
                  <div 
                    class="h-full rounded-full transition-all" 
                    :style="{ width: Math.min(Math.max(data.session_scores[session.id]?.indicators[indicator.id]?.score, 0), 100) + '%', backgroundColor: getProgress(si, indicator.id, data.session_scores[session.id]?.indicators[indicator.id]?.score).color }"
                  ></div>
                </div>
                <span 
                  class="font-bold text-[7.5px] inline-flex items-center justify-center gap-0.5"
                  :style="{ color: getProgress(si, indicator.id, data.session_scores[session.id]?.indicators[indicator.id]?.score).color }"
                >
                  <v-icon v-if="getProgress(si, indicator.id, data.session_scores[session.id]?.indicators[indicator.id]?.score).icon" :name="getProgress(si, indicator.id, data.session_scores[session.id]?.indicators[indicator.id]?.score).icon" class="w-2.5 h-2.5 shrink-0" />
                  <span>{{ getProgress(si, indicator.id, data.session_scores[session.id]?.indicators[indicator.id]?.score).label }}</span>
                </span>
              </div>
            </td>
          </template>
        </tr>

        <!-- Overall row -->
        <tr class="bg-[#F0F4F9] font-bold border-t border-slate-300">
          <td style="text-align: left;">AVERAGE SCORE</td>
          <template v-for="(session, si) in data.sessions" :key="session.id">
            <td></td>
            <td></td>
            <td 
              :class="[{ 'session-col-divider': si === 0 }, 'font-bold']"
              :style="{ color: getScoreColor(data.session_scores[session.id]?.overall) }"
            >
              <div class="flex items-center justify-center gap-1.5">
                <div v-if="data.session_scores[session.id]?.overall !== null" class="w-8 h-2 bg-slate-100 rounded-full overflow-hidden border border-slate-200/60 shrink-0">
                  <div 
                    class="h-full rounded-full transition-all" 
                    :style="{ width: Math.min(Math.max(data.session_scores[session.id]?.overall, 0), 100) + '%', backgroundColor: getScoreColor(data.session_scores[session.id]?.overall) }"
                  ></div>
                </div>
                <span>{{ formatPct(data.session_scores[session.id]?.overall) }}</span>
              </div>
            </td>
            <td v-if="si > 0" class="session-col-divider"></td>
          </template>
        </tr>
      </tbody>
    </table>

    <!-- Charts (if enough indicators) -->
    <div v-if="data.indicators && data.indicators.length >= 1" class="rpt-charts">
      <div class="flex items-start justify-start gap-2.5">
        <!-- Radar Chart Box -->
        <div v-if="data.indicators.length >= 1" class="rpt-chart-box w-[240px] shrink-0">
          <h4 class="text-center font-bold">Radar Performance</h4>
          <div class="w-[220px] h-[180px] mx-auto relative">
            <canvas ref="radarCanvas" width="220" height="180"></canvas>
          </div>
        </div>

        <!-- Bar Chart Box -->
        <div class="rpt-chart-box flex-1">
          <h4 class="text-center font-bold">Performance Comparison per Indicator</h4>
          <div class="w-full h-[180px] relative">
            <canvas ref="barCanvas" height="180"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Evaluation & Recommendation Table -->
    <div v-if="evaluations.length > 0" class="w-full text-left mt-5 text-xs" style="page-break-inside: avoid;">
      <h4 style="font-size: 8px; color: #0B2545; margin-bottom: 4px; font-weight: 800; font-family: 'Plus Jakarta Sans', sans-serif; text-transform: uppercase; text-align: center;">
        Evaluation & Training Recommendations
      </h4>
      <table class="rpttable w-full border border-slate-200" style="margin-top: 0;">
        <thead>
          <tr>
            <th style="padding: 4px 6px; text-align: center; background: #0B2545; color: #fff; border-right: 1px solid #1E3A8A; text-transform: uppercase;">Biomotor Component</th>
            <th style="padding: 4px 6px; text-align: center; background: #0B2545; color: #fff; text-transform: uppercase;">Training Recommendations</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="ind in evaluations" :key="ind.id" class="hover:bg-slate-50/50">
            <td style="padding: 4px 6px; text-align: left; font-weight: bold; color: #0B2545; border-right: 1px solid #EEF1F5; border-bottom: 1px solid #EEF1F5;">
              {{ ind.category || ind.name }}
              <div style="font-size: 6.5px; color: #68758A; font-weight: normal; margin-top: 1px;">
                (Score &lt; {{ ind.evaluation_threshold }}%)
              </div>
            </td>
            <td style="padding: 4px 6px; text-align: left; color: #68758A; font-weight: 500; font-size: 7.5px; border-bottom: 1px solid #EEF1F5; white-space: pre-line;">
              {{ ind.evaluation || 'Latihan peningkatan untuk komponen ini.' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Signblock -->
    <div v-if="institution && (signerName || signerTitle)" class="mt-5 text-xs" style="page-break-inside: avoid; display: block; clear: both;">
      <div class="signbox" style="margin-left: auto; margin-right: 0;">
        <div>
          {{ institution.signature_city ? institution.signature_city + ', ' : (institution.address ? institution.address + ', ' : '') }}{{ signatureDate }}
        </div>
        <div>Verified by,</div>
        <div style="font-weight: 600;">{{ signerTitle }}</div>
        <div style="height: 40px;"></div>
        <b style="display: inline-block; padding: 0 4px;">{{ signerName }}</b>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { getScoreColor } from '@/Composables/useReportPdf';
import Chart from 'chart.js/auto';
import ChartDataLabels from 'chartjs-plugin-datalabels';

const props = defineProps({
  data: { type: Object, required: true },
  institution: { type: Object, default: () => null },
  paperStyles: { type: Object, default: () => ({ w: '210mm', h: '297mm' }) },
});

const radarCanvas = ref(null);
const barCanvas = ref(null);
let radarChart = null;
let barChart = null;

const allLogos = computed(() => {
  const logos = [];
  if (props.institution && props.institution.logos) {
    props.institution.logos.forEach(l => logos.push(l));
  }
  return logos;
});

const signerName = computed(() => (props.institution?.signer_name || '').trim());
const signerTitle = computed(() => (props.institution?.signer_title || '').trim());
const signatureDate = computed(() => {
  return props.institution?.signature_date_formatted || '';
});

const evaluations = computed(() => {
  if (!props.data.sessions || props.data.sessions.length === 0 || !props.data.indicators) return [];
  
  // Find last session with scores
  let lastSession = null;
  for (let i = props.data.sessions.length - 1; i >= 0; i--) {
    const session = props.data.sessions[i];
    const scores = props.data.session_scores[session.id]?.indicators || {};
    const hasScores = Object.values(scores).some(s => s.score !== null && s.score !== undefined);
    if (hasScores) {
      lastSession = session;
      break;
    }
  }
  if (!lastSession) lastSession = props.data.sessions[props.data.sessions.length - 1];
  
  const lastSessionId = lastSession.id;
  const scores = props.data.session_scores[lastSessionId]?.indicators || {};
  
  return props.data.indicators.filter(ind => {
    const score = scores[ind.id]?.score;
    const threshold = ind.evaluation_threshold;
    if (score === null || score === undefined || threshold === null || threshold === undefined || threshold === '') {
      return false;
    }
    return parseFloat(score) <= parseFloat(threshold);
  });
});

function formatDob(dateStr) {
  if (!dateStr) return '-';
  const d = new Date(dateStr);
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
}

function formatSessionDate(dateStr) {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
}

function getSessionColor(session, index) {
  const sessionColors = ['#FF6B35', '#06A77D', '#5B8DEF', '#F4A100', '#E63946'];
  return session.color || sessionColors[index % sessionColors.length];
}

function getBenchmarkVal(sessionId, indicatorId) {
  const scoreData = props.data.session_scores[sessionId]?.indicators[indicatorId];
  const bm = scoreData?.benchmark;
  if (!bm || !bm.values) return '-';
  return bm.values[indicatorId] ?? bm.values[String(indicatorId)] ?? '-';
}

function formatRawVal(val) {
  if (val === null || val === undefined) return '-';
  return Number(val).toLocaleString('id-ID', { maximumFractionDigits: 2 });
}

function formatPct(score) {
  if (score === null || score === undefined) return '-';
  return `${Number(score).toFixed(1)}%`;
}

function getProgress(si, indicatorId, score) {
  if (si === 0 || score === null || score === undefined) return { label: '-', icon: '', color: '#94a3b8' };
  const prevSession = props.data.sessions[si - 1];
  const prevScore = props.data.session_scores[prevSession.id]?.indicators[indicatorId]?.score;
  if (prevScore === null || prevScore === undefined) return { label: '-', icon: '', color: '#94a3b8' };
  
  const delta = Math.round((score - prevScore) * 10) / 10;
  if (delta > 0) return { label: `+${delta}%`, icon: 'hi-arrow-up', color: '#06A77D' };
  if (delta < 0) return { label: `${delta}%`, icon: 'hi-arrow-down', color: '#E63946' };
  return { label: `0.0%`, icon: 'hi-minus', color: '#68758A' };
}

onMounted(() => {
  const labels = props.data.indicators.map(ind => ind.name);
  const activeSessions = props.data.sessions.filter(s => {
    return props.data.session_scores[s.id]?.overall !== null && props.data.session_scores[s.id]?.overall !== undefined;
  });
  
  const radarDatasets = [];
  const barDatasets = [];
  
  activeSessions.forEach((session, si) => {
    const color = getSessionColor(session, props.data.sessions.indexOf(session));
    const radarData = [];
    const barData = [];
    
    props.data.indicators.forEach(ind => {
      const score = props.data.session_scores[session.id]?.indicators[ind.id]?.score || 0;
      radarData.push(Math.round(score * 10) / 10);
      barData.push(Math.round(score * 10) / 10);
    });
    
    radarDatasets.push({
      label: session.name,
      data: radarData,
      borderColor: color,
      backgroundColor: color + '33',
      borderWidth: 2,
      pointBackgroundColor: color,
      pointBorderColor: color,
      pointHoverBackgroundColor: color,
      pointHoverBorderColor: color,
    });
    
    barDatasets.push({
      label: session.name,
      data: barData,
      backgroundColor: color,
      borderColor: color,
      borderRadius: 6,
      borderSkipped: false,
    });
  });

  if (radarCanvas.value && labels.length >= 1) {
    radarChart = new Chart(radarCanvas.value, {
      type: 'radar',
      data: { labels: labels, datasets: radarDatasets },
      options: {
        devicePixelRatio: 3,
        responsive: false,
        animation: false,
        plugins: {
          legend: { display: true, position: 'bottom', labels: { boxWidth: 8, font: { size: 8 } } }
        },
        scales: {
          r: {
            min: 0, max: 100, ticks: { display: false }, pointLabels: { font: { size: 7 } }
          }
        }
      }
    });
  }

  if (barCanvas.value && labels.length >= 1) {
    barChart = new Chart(barCanvas.value, {
      type: 'bar',
      plugins: [ChartDataLabels],
      data: { labels: labels, datasets: barDatasets },
      options: {
        devicePixelRatio: 3,
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        layout: { padding: { top: 15 } },
        plugins: {
          datalabels: {
            anchor: 'end',
            align: 'top',
            formatter: (v) => v !== null && v !== undefined ? v + '%' : '',
            font: { weight: 'bold', size: 7 },
            color: '#0B2545'
          },
          legend: { 
            display: true, 
            position: 'bottom', 
            labels: { 
              boxWidth: 8, 
              font: { size: 8 },
              generateLabels: (chart) => {
                return chart.data.datasets.map((dataset, i) => ({
                  text: dataset.label,
                  datasetIndex: i,
                  fillStyle: dataset.borderColor,
                  strokeStyle: dataset.borderColor,
                  lineWidth: 0,
                  hidden: !chart.isDatasetVisible(i),
                }));
              }
            } 
          }
        },
        scales: {
          y: { min: 0, max: 100, ticks: { font: { size: 7 } } },
          x: { ticks: { font: { size: 7 } } }
        }
      }
    });
  }
});
</script>

<style scoped>
body {
  overflow-y: hidden;
}
.report-wrap {
  margin: 70px auto 40px;

  /* Tailwind v4 color variables overrides to prevent dark-theme bleed into PDF */
  --color-slate-50: #f8fafc;
  --color-slate-100: #f1f5f9;
  --color-slate-200: #e2e8f0;
  --color-slate-300: #cbd5e1;
  --color-slate-400: #94a3b8;
  --color-slate-500: #64748b;
  --color-slate-600: #475569;
  --color-slate-700: #334155;
  --color-slate-800: #1e293b;
  --color-slate-900: #0f172a;
  --border-color: #cbd5e1;
  --color-border: #cbd5e1;

  --color-gray-50: #f9fafb;
  --color-gray-100: #f3f4f6;
  --color-gray-200: #e5e7eb;
  --color-gray-300: #d1d5db;
  --color-gray-400: #9ca3af;
  --color-gray-500: #6b7280;
  --color-gray-600: #4b5563;
  --color-gray-700: #374151;
  --color-gray-800: #1f2937;
  --color-gray-900: #111827;

  --color-zinc-50: #fafafa;
  --color-zinc-100: #f4f4f5;
  --color-zinc-200: #e4e4e7;
  --color-zinc-300: #d4d4d8;
  --color-zinc-400: #a1a1aa;
  --color-zinc-500: #71717a;
  --color-zinc-600: #52525b;
  --color-zinc-700: #3f3f46;
  --color-zinc-800: #27272a;
  --color-zinc-900: #18181b;

  --color-neutral-50: #fafafa;
  --color-neutral-100: #f5f5f5;
  --color-neutral-200: #e5e5e5;
  --color-neutral-300: #d4d4d4;
  --color-neutral-400: #a3a3a3;
  --color-neutral-500: #737373;
  --color-neutral-600: #525252;
  --color-neutral-700: #404040;
  --color-neutral-800: #262626;
  --color-neutral-900: #171717;

  /* Override base colors for report white background */
  --color-base-100: #ffffff;
  --color-base-200: #f8fafc;
  --color-base-300: #e2e8f0;
  --color-base-content: #1e293b;
}

.rpt {
  background: #fff;
  width: 210mm;
  min-height: 297mm;
  margin: 0 auto 8mm;
  padding: 10mm 11mm;
  box-shadow: 0 4px 18px rgba(0,0,0,.18);
  position: relative;
  box-sizing: border-box;

  /* Tailwind v4 color variable overrides — prevent dark-theme bleed into PDF */
  --color-slate-50: #f8fafc;
  --color-slate-100: #f1f5f9;
  --color-slate-200: #e2e8f0;
  --color-slate-300: #cbd5e1;
  --color-slate-400: #94a3b8;
  --color-slate-500: #64748b;
  --color-slate-600: #475569;
  --color-slate-700: #334155;
  --color-slate-800: #1e293b;
  --color-slate-900: #0f172a;

  --color-gray-50: #f9fafb;
  --color-gray-100: #f3f4f6;
  --color-gray-200: #e5e7eb;
  --color-gray-300: #d1d5db;
  --color-gray-400: #9ca3af;
  --color-gray-500: #6b7280;
  --color-gray-600: #4b5563;
  --color-gray-700: #374151;
  --color-gray-800: #1f2937;
  --color-gray-900: #111827;

  --color-zinc-50: #fafafa;
  --color-zinc-100: #f4f4f5;
  --color-zinc-200: #e4e4e7;
  --color-zinc-300: #d4d4d8;
  --color-zinc-400: #a1a1aa;
  --color-zinc-500: #71717a;
  --color-zinc-600: #52525b;
  --color-zinc-700: #3f3f46;
  --color-zinc-800: #27272a;
  --color-zinc-900: #18181b;

  --color-neutral-50: #fafafa;
  --color-neutral-100: #f5f5f5;
  --color-neutral-200: #e5e5e5;
  --color-neutral-300: #d4d4d4;
  --color-neutral-400: #a3a3a3;
  --color-neutral-500: #737373;
  --color-neutral-600: #525252;
  --color-neutral-700: #404040;
  --color-neutral-800: #262626;
  --color-neutral-900: #171717;

  --color-base-100: #ffffff;
  --color-base-200: #f8fafc;
  --color-base-300: #e2e8f0;
  --color-base-content: #1e293b;
  color: #1e293b;
}

/* Report internals */
.rpt-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 3px solid #FF6B35;
  padding-bottom: 6px;
  margin-bottom: 8px;
}
.rpt-logos-left img { max-height: 80px; object-fit: contain; }
.rpt-title { text-align: right; }
.rpt-title h1 {
  font-size: 14px;
  font-weight: 800;
  color: #0B2545;
  margin: 0;
  line-height: 1.2;
}
.rpt-title .s1 {
  font-size: 9px;
  color: #68758A;
  font-weight: 500;
  line-height: 1.35;
}

.rpt-banner { background: linear-gradient(120deg, #0B2545, #123163); color: #fff; border-radius: 10px; padding: 8px 14px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }

.biocard { display: flex; gap: 8px; margin-bottom: 6px; align-items: center; background: #FAFBFE; border-radius: 8px; padding: 6px 8px; border: 1px solid #E4E9F0; }
.biophoto { width: 58px; height: 72px; border-radius: 6px; object-fit: cover; background: #EDF1F6; border: 2px solid #E4E9F0; flex-shrink: 0; }
.biogrid { flex: 1; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 2px 8px; font-size: 9px; }
.biogrid b { display: block; color: #68758A; font-weight: 600; font-size: 7px; text-transform: uppercase; }
.biogrid span { font-size: 9.5px; font-weight: 700; color: #0B2545; overflow-wrap: break-word; word-break: break-word; }

.rpt-sec-title { color: #0B2545; font-weight: 800; font-size: 9px; text-transform: uppercase; letter-spacing: .4px; margin: 6px 0 4px; }
.rpttable { width: 100%; border-collapse: collapse; font-size: 8px; color: #1e293b; }
.rpttable th { background: #0B2545; color: #fff; padding: 4px 4px; font-size: 7.5px; text-transform: uppercase; border-right: 1px solid #1E3A8A; }
.rpttable th:last-child { border-right: none; }
.rpttable td { padding: 3px 4px; border-bottom: 1px solid #EEF1F5; text-align: center; border-right: 1px solid #EEF1F5; color: #1e293b; height: 20px; }
.rpttable td:last-child { border-right: none; }
.rpttable td:first-child { text-align: left; font-weight: 600; }
.session-col-divider { border-right: 2px solid #CBD5E1 !important; }

.rpt-charts { margin-top: 6px; }
.rpt-chart-box { background: #FAFBFE; border-radius: 8px; padding: 6px 8px; margin-bottom: 4px; border: 1px solid #E4E9F0; }
.rpt-chart-box h4 { font-size: 8px; color: #0B2545; margin-bottom: 4px; font-weight: 800; text-transform: uppercase; }

.signbox { text-align: center; font-size: 9px; width: 180px; color: #0B2545; }

.legend { font-size: 7px; color: #68758A; margin: 2px 0; }
.legend b { font-size: 7px; color: #0B2545; }
</style>
