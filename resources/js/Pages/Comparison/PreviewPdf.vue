<template>
  <div class="w-screen h-screen flex flex-col bg-slate-900 font-sans antialiased overflow-hidden">
    <!-- Header / Custom Toolbar -->
    <div class="navbar bg-base-100 text-base-content border-b border-base-200 px-4 py-2 z-50 shadow-xs flex-wrap gap-2 shrink-0 justify-between">
      <div class="flex-1">
        <b class="text-sm font-semibold">Performance Comparison Report Viewer</b>
      </div>
      <div class="flex-none">
        <button @click="closeWindow" class="btn btn-outline btn-xs">
          Close Viewer
        </button>
      </div>
    </div>

    <!-- Viewer Body -->
    <div class="flex-1 w-full h-full relative">
      <PDFViewer v-if="pdfUrl" :config="{ src: pdfUrl }" class="w-full h-full border-none" />
      <div v-else class="absolute inset-0 flex flex-col items-center justify-center text-white space-y-4 bg-slate-800">
        <span class="loading loading-spinner loading-lg text-primary"></span>
        <p class="text-xs font-bold tracking-wider">PREPARING PDF REPORT...</p>
      </div>
    </div>

    <!-- Hidden Element for capturing HTML to PDF -->
    <div style="position: absolute; left: -9999px; top: -9999px; overflow: hidden; width: 210mm;">
      <div :class="['report-wrap', { 'capturing-pdf': capturing }]" style="width: 210mm; max-width: 210mm; margin: 0;" id="reportContent">
        <div class="rpt-page">
          <!-- KOP SURAT -->
          <div class="rpt-head">
            <div class="rpt-logos flex items-center gap-2">
              <img 
                v-for="logo in allLogos" 
                :key="logo.id || logo.logo_path" 
                :src="'/storage/' + logo.logo_path" 
                alt="Logo" 
                :style="{ height: logo.height_px + 'px', maxHeight: '80px', objectFit: 'contain' }"
              />
            </div>
            <div class="rpt-title flex-1 text-right pl-3">
              <h1 v-if="institution && institution.header_lines" class="text-sm font-bold text-[#0B2545] uppercase leading-tight whitespace-pre-line">
                {{ institution.header_lines }}
              </h1>
              <h1 v-else class="text-sm font-bold text-[#0B2545] uppercase">
                {{ institution?.name || 'PhysicalScore' }}
              </h1>
              <div v-if="institution && institution.address" class="s1 text-[9px] text-slate-700 font-medium">
                {{ institution.address }}
              </div>
              <div class="s1 text-[9px] text-slate-600 font-medium">
                Physical Performance Assessment & Comparison Report
              </div>
            </div>
          </div>

          <!-- Banner -->
          <div class="rpt-banner flex justify-between items-center bg-gradient-to-r from-[#0B2545] via-[#123163] to-[#081a33] text-white rounded-xl p-3 mb-2.5">
            <div>
              <h2 class="text-white text-sm font-bold margin-0">Cross-Athlete & Cross-Folder Performance Matrix</h2>
              <div class="text-[9.5px] opacity-90 mt-0.5 font-medium">
                Comparing {{ comparedAthletes.length }} Athletes across {{ columns.length }} Test Sessions
              </div>
            </div>
            <div class="text-[10px] font-bold bg-white/15 px-2.5 py-1 rounded-md">
              Date: {{ todayDate }}
            </div>
          </div>

          <div class="rpt-sec-title">Performance Score Matrix (%)</div>

          <!-- Table Matrix -->
          <table class="rpttable w-full border border-slate-100">
            <thead>
              <tr>
                <th class="text-left py-2 px-3">Indicator</th>
                <th v-for="col in columns" :key="col.key" class="text-center py-2 px-3 border-l border-slate-700/30">
                  <div class="font-bold text-white leading-tight">{{ col.athlete?.name }}</div>
                  <div class="text-[7.5px] opacity-80 font-normal mt-0.5">{{ col.session ? col.session.name : '-' }}</div>
                  <div class="text-[7px] opacity-70 font-normal">{{ col.session && col.session.folder ? col.session.folder.name : '' }}</div>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="ind in indicators" :key="ind.id" class="border-b border-slate-100">
                <td class="py-2 px-3 font-bold text-[#0B2545]">
                  {{ ind.name }} <span v-if="ind.unit" class="text-[9.5px] font-normal text-slate-500">({{ ind.unit }})</span>
                </td>
                <td v-for="col in columns" :key="col.key" class="py-2 px-3 text-center border-l border-slate-100 leading-snug">
                  <template v-if="comparisonMatrix[ind.id] && comparisonMatrix[ind.id][col.key] !== null">
                    <div class="flex items-center justify-center gap-1.5">
                      <div class="w-8 h-2 bg-slate-100 rounded-full overflow-hidden border border-slate-200/60 shrink-0">
                        <div 
                          class="h-full rounded-full transition-all" 
                          :style="{ width: Math.min(Math.max(comparisonMatrix[ind.id][col.key], 0), 100) + '%', backgroundColor: getScoreColor(comparisonMatrix[ind.id][col.key]) }"
                        ></div>
                      </div>
                      <span class="font-extrabold text-slate-800">
                        {{ Number(comparisonMatrix[ind.id][col.key]).toFixed(1) }}%
                      </span>
                    </div>
                    <span v-if="rawMatrix[ind.id] && rawMatrix[ind.id][col.key] !== null" class="text-[7.5px] text-slate-400 font-semibold block mt-0.5">
                      ({{ rawMatrix[ind.id][col.key] }})
                    </span>
                  </template>
                  <span v-else class="text-slate-300">-</span>
                </td>
              </tr>

              <tr class="bg-[#F0F4F9] font-bold border-t border-slate-300">
                <td class="py-2.5 px-3">OVERALL SCORE</td>
                <td v-for="col in columns" :key="col.key" class="py-2.5 px-3 text-center border-l border-slate-200 text-sm font-bold">
                  <div v-if="overallPerColumn[col.key] !== null" class="flex items-center justify-center gap-1.5">
                    <div class="w-10 h-2 bg-slate-100 rounded-full overflow-hidden border border-slate-200/60 shrink-0">
                      <div 
                        class="h-full rounded-full transition-all" 
                        :style="{ width: Math.min(Math.max(overallPerColumn[col.key], 0), 100) + '%', backgroundColor: getScoreColor(overallPerColumn[col.key]) }"
                      ></div>
                    </div>
                    <span :style="{ color: getScoreColor(overallPerColumn[col.key]) }">
                      {{ Number(overallPerColumn[col.key]).toFixed(1) }}%
                    </span>
                  </div>
                  <span v-else>-</span>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Charts (if enough indicators) -->
          <div v-if="indicators && indicators.length >= 1" class="rpt-charts">
            <div class="flex items-start justify-start gap-2.5">
              <!-- Radar Chart Box -->
              <div v-if="indicators.length >= 1" class="rpt-chart-box w-[240px] shrink-0">
                <h4 class="text-center font-bold">Radar Performance Comparison</h4>
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

          <!-- Signature Box -->
          <div v-if="institution && (signerName || signerTitle)" class="signblock" style="page-break-inside: avoid;">
            <div class="signbox">
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
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { PDFViewer } from '@embedpdf/vue-pdf-viewer';
import { snapdom } from '@zumer/snapdom';
import { jsPDF } from 'jspdf';
import Chart from 'chart.js/auto';
import ChartDataLabels from 'chartjs-plugin-datalabels';

const props = defineProps({
  selectedAthleteIds: { type: Array, default: () => [] },
  comparedAthletes: { type: Object, required: true },
  indicators: { type: Array, default: () => [] },
  columns: { type: Array, default: () => [] },
  comparisonMatrix: { type: Object, default: () => ({}) },
  rawMatrix: { type: Object, default: () => ({}) },
  overallPerColumn: { type: Object, default: () => ({}) },
  bestPerIndicator: { type: Object, default: () => ({}) },
  institution: { type: Object, default: () => null },
});

const generating = ref(true);
const capturing = ref(false);
const pdfUrl = ref(null);

const allLogos = computed(() => {
  const logos = [];
  if (props.institution) {
    if (props.institution.logos && props.institution.logos.length > 0) {
      props.institution.logos.forEach(l => logos.push(l));
    } else {
      if (props.institution.logo_path) {
        logos.push({ logo_path: props.institution.logo_path, height_px: 52 });
      }
    }
  }
  return logos;
});

const todayDate = computed(() => {
  const d = new Date();
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
});

const signerName = computed(() => {
  return (props.institution?.signer_name || '').trim();
});

const signerTitle = computed(() => {
  return (props.institution?.signer_title || '').trim();
});

const signatureDate = computed(() => {
  return props.institution?.signature_date_formatted || '';
});

function closeWindow() {
  window.close();
}

async function generatePdf() {
  generating.value = true;
  capturing.value = true;
  if (pdfUrl.value) {
    URL.revokeObjectURL(pdfUrl.value);
    pdfUrl.value = null;
  }
  
  // Wait a small bit for layout to settle
  await new Promise(resolve => setTimeout(resolve, 800));

  try {
    const el = document.getElementById('reportContent');
    const pages = el.querySelectorAll('.rpt-page');

    const pdf = new jsPDF({ unit: 'mm', format: 'a4', orientation: 'portrait' });

    for (let i = 0; i < pages.length; i++) {
      const pageEl = pages[i];
      const canvas = await snapdom.toCanvas(pageEl, {
        scale: 2,
        backgroundColor: '#ffffff',
      });
      if (i > 0) pdf.addPage();
      pdf.addImage(canvas.toDataURL('image/jpeg', 0.95), 'JPEG', 0, 0, 210, 297);
    }

    const pdfBlob = pdf.output('blob');
    pdfUrl.value = URL.createObjectURL(pdfBlob);
  } catch (e) {
    console.error('Error generating PDF:', e);
  } finally {
    capturing.value = false;
    generating.value = false;
  }
}

const radarCanvas = ref(null);
const barCanvas = ref(null);
let radarChart = null;
let barChart = null;

function getScoreColor(v) {
  if (v === null || v === undefined) return '#68758A';
  if (v >= 85) return '#06A77D';
  if (v >= 70) return '#5B8DEF';
  if (v >= 55) return '#F4A100';
  return '#E63946';
}

function getColumnColor(colIndex) {
  const colors = ['#5B8DEF', '#06A77D', '#F4A100', '#E63946'];
  return colors[colIndex % colors.length];
}

onMounted(async () => {
  const labels = props.indicators.map(ind => ind.name);
  
  const radarDatasets = [];
  const barDatasets = [];

  props.columns.forEach((col, colIdx) => {
    const color = getColumnColor(colIdx);
    const dataPoints = [];

    props.indicators.forEach(ind => {
      const score = (props.comparisonMatrix[ind.id] && props.comparisonMatrix[ind.id][col.key] !== null)
        ? props.comparisonMatrix[ind.id][col.key]
        : 0;
      dataPoints.push(Math.round(score * 10) / 10);
    });

    radarDatasets.push({
      label: col.athlete?.name || 'Athlete ' + (colIdx + 1),
      data: dataPoints,
      borderColor: color,
      backgroundColor: color + '22',
      borderWidth: 2,
      pointBackgroundColor: color,
      pointBorderColor: '#ffffff',
      pointBorderWidth: 1.5,
      pointRadius: 3,
    });

    barDatasets.push({
      label: col.athlete?.name || 'Athlete ' + (colIdx + 1),
      data: dataPoints,
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
              font: { size: 8 }
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

  generatePdf();
});
</script>

<style scoped>
.report-wrap {
  margin: 70px auto 40px;

  /* Tailwind v4 color variables overrides to prevent html2canvas oklch crash */
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

  --color-red-50: #fef2f2;
  --color-red-100: #fee2e2;
  --color-red-200: #fecaca;
  --color-red-500: #ef4444;
  --color-red-600: #dc2626;

  --color-emerald-50: #ecfdf5;
  --color-emerald-200: #a7f3d0;
  --color-emerald-600: #059669;
  --color-emerald-700: #047857;

  --color-blue-50: #eff6ff;
  --color-blue-200: #bfdbfe;
  --color-blue-600: #2563eb;
  --color-blue-700: #1d4ed8;
}
.capturing-pdf .rpt-page {
  margin: 0 !important;
  box-shadow: none !important;
  border: none !important;
}
.rpt-page {
  background: #fff;
  width: 210mm;
  min-height: 297mm;
  margin: 0 auto 8mm;
  padding: 12px;
  box-shadow: 0 4px 18px rgba(0,0,0,.18);
  position: relative;
  box-sizing: border-box;
}

/* Report internals */
.rpt-head { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px double #000; padding-bottom: 8px; margin-bottom: 12px; }
.rpt-logos img { max-height: 80px; object-fit: contain; }
.rpt-title { text-align: right; }
.rpt-title h1 { font-size: 14px; font-weight: 900; color: #0B2545; margin: 0; line-height: 1.2; }
.rpt-title .s1 { font-size: 9px; color: #333; font-weight: 500; line-height: 1.3; }

.rpt-sec-title { background: #F0F4F9; color: #0B2545; font-weight: 800; font-size: 10px; text-transform: uppercase; letter-spacing: .4px; padding: 4px 9px; border-radius: 7px; margin: 10px 0 6px; }
.rpttable { width: 100%; border-collapse: collapse; font-size: 8.5px; }
.rpttable th { background: #0B2545; color: #fff; padding: 4px 6px; font-size: 8px; text-transform: uppercase; text-align: left; }
.rpttable td { padding: 4px 6px; border-bottom: 1px solid #EEF1F5; }

.signblock { display: flex; justify-content: flex-end; margin-top: 20px; }
.signbox { text-align: center; font-size: 9.5px; width: 190px; color: #0B2545; }
.signbox .sp { height: 45px; }

.rpt-charts { margin-top: 10px; width: 100%; }
.rpt-chart-box { background: #FAFBFE; border-radius: 8px; padding: 8px 10px; margin-bottom: 6px; border: 1px solid #E4E9F0; }
.rpt-chart-box h4 { font-size: 9px; color: #0B2545; margin-bottom: 6px; font-weight: 800; text-transform: uppercase; }
</style>
