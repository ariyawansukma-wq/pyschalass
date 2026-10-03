<template>
  <ReportViewerLayout
    :title="`Folder Assessment Report Viewer - ${data.folder?.name || 'Data Folder'}`"
    :pdf-url="pdfUrl"
    :pdf-filename="pdfFilename"
    :current-page="currentPage"
    :total-pages="totalPages"
    :progress-percent="progressPercent"
    v-model:paper-size="paperSize"
    v-model:paper-orientation="paperOrientation"
    @download="downloadReport"
    @ready="handleViewerReady"
  >
    <div style="position: absolute; left: -9999px; top: -9999px; overflow: hidden;" :style="{ width: paperStyles.w }">
      <div :class="['report-wrap', { 'capturing-pdf': capturing }]" :style="{ width: paperStyles.w, maxWidth: paperStyles.w, margin: 0 }" id="reportContent">
        
        <!-- MAIN REPORT PAGE -->
        <div class="rpt" :style="{ width: paperStyles.w, minHeight: paperStyles.h }">
          <div class="rpt-head w-full">
            <div class="rpt-logos flex items-center gap-2">
              <img 
                v-for="logo in allLogos" 
                :key="logo.id || logo.logo_path" 
                :src="'/storage/' + logo.logo_path" 
                alt="Logo" 
                :style="{ height: (logo.height_px || 52) + 'px', maxHeight: '80px', objectFit: 'contain' }"
              />
            </div>
            <div class="rpt-title flex-1 text-right pl-3">
              <template v-if="data.institution && data.institution.header_lines">
                <h1 
                  v-for="(line, lIdx) in data.institution.header_lines.split('\n')" 
                  :key="'header-line-' + lIdx"
                  class="text-sm font-bold text-[#0B2545] uppercase leading-tight"
                >
                  {{ line }}
                </h1>
              </template>
              <h1 v-else class="text-sm font-bold text-[#0B2545] uppercase">
                {{ data.institution?.name || 'PhysicalScore' }}
              </h1>
              <div v-if="data.institution && data.institution.address" class="s1 text-[9px] text-slate-700 font-medium">
                {{ data.institution.address }}
              </div>
              <div class="s1 text-[9px] text-slate-600 font-medium">
                PhysicalScore - Physical Performance Assessment System
              </div>
            </div>
          </div>

          <h2 class="text-lg font-bold text-[#0B2545] mb-1">
            Folder Report - {{ data.folder?.name || 'Data Folder' }}
          </h2>
          <p class="text-[11px] text-slate-500 font-medium mb-4">
            Generated on {{ todayDate }}
          </p>

          <!-- RINGKASAN DASHBOARD -->
          <div class="rpt-sec-title">Dashboard Summary</div>
          <table class="rpttable w-full table-fixed mt-1.5 mb-4 border border-slate-100">
            <thead>
              <tr>
                <th class="text-left py-2 px-3 bg-[#0B2545] text-white">Metric</th>
                <th class="text-left py-2 px-3 bg-[#0B2545] text-white">Value</th>
              </tr>
            </thead>
            <tbody>
              <tr class="border-b border-slate-100">
                <td class="py-2 px-3">Total Athletes</td>
                <td class="py-2 px-3 font-bold text-[#0B2545]">{{ data.total_athletes }}</td>
              </tr>
              <tr class="border-b border-slate-100">
                <td class="py-2 px-3">Total Test Sessions</td>
                <td class="py-2 px-3 font-bold text-[#0B2545]">{{ data.total_sessions }}</td>
              </tr>
              <tr class="border-b border-slate-100">
                <td class="py-2 px-3">Total Sport Branches</td>
                <td class="py-2 px-3 font-bold text-[#0B2545]">{{ data.per_branch?.length || 0 }}</td>
              </tr>
              <tr class="border-b border-slate-100">
                <td class="py-2 px-3">Overall Avg. Performance</td>
                <td class="py-2 px-3 font-bold text-[#0B2545]">{{ data.average_score ? data.average_score + '%' : '-' }}</td>
              </tr>
            </tbody>
          </table>

          <!-- REKAP KESELURUHAN PER CABANG OLAHRAGA -->
          <div class="rpt-sec-title">Summary by Sport Branch</div>
          <table class="rpttable w-full mt-1.5 mb-4 border border-slate-100">
            <thead>
              <tr>
                <th class="text-left py-2 px-3">Sport Branch</th>
                <th class="text-center py-2 px-3">Total Athletes</th>
                <th class="text-center py-2 px-3">Avg. Score</th>
                <th class="text-center py-2 px-3">Best Score</th>
                <th class="text-left py-2 px-3">Best Athlete</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="pb in data.per_branch" :key="pb.branch.id" class="border-b border-slate-100">
                <td class="py-2 px-3 font-bold text-[#0B2545]">{{ pb.branch.name }}</td>
                <td class="py-2 px-3 text-center">{{ pb.athlete_count }}</td>
                <td class="py-2 px-3 text-center">
                  <div class="flex items-center justify-center gap-1.5">
                    <div v-if="pb.avg_score !== null" class="w-8 h-2 bg-slate-100 rounded-full overflow-hidden border border-slate-200/60 shrink-0">
                      <div class="h-full rounded-full transition-all" :style="{ width: Math.min(Math.max(pb.avg_score, 0), 100) + '%', backgroundColor: getScoreColor(pb.avg_score) }"></div>
                    </div>
                    <span :style="{ color: getScoreColor(pb.avg_score) }" class="font-extrabold">{{ pb.avg_score ? pb.avg_score + '%' : '-' }}</span>
                  </div>
                </td>
                <td class="py-2 px-3 text-center">
                  <div class="flex items-center justify-center gap-1.5">
                    <div v-if="pb.best_score !== null" class="w-8 h-2 bg-slate-100 rounded-full overflow-hidden border border-slate-200/60 shrink-0">
                      <div class="h-full rounded-full transition-all" :style="{ width: Math.min(Math.max(pb.best_score, 0), 100) + '%', backgroundColor: getScoreColor(pb.best_score) }"></div>
                    </div>
                    <span :style="{ color: getScoreColor(pb.best_score) }" class="font-extrabold">{{ pb.best_score ? pb.best_score + '%' : '-' }}</span>
                  </div>
                </td>
                <td class="py-2 px-3">{{ pb.best_athlete?.name || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div 
          v-if="showOverallSummaryPerAthlete"
          v-for="(chunk, cIdx) in athleteScoreChunks" 
          :key="'all-ath-' + cIdx" 
          class="rpt" 
          :style="{ width: paperStyles.w, minHeight: paperStyles.h }"
        >
          <div class="rpt-sec-title">Overall Summary per Athlete (All Sport Branches)</div>
          <p class="text-[11px] text-slate-500 font-medium mt-1 mb-2.5">
            Page {{ cIdx + 1 }} of {{ athleteScoreChunks.length }} | Total {{ data.total_athletes }} athletes, sorted by highest performance score.
          </p>
          <table class="rpttable w-full border border-slate-100">
            <thead>
              <tr>
                <th class="text-center py-2 px-3 w-12">No</th>
                <th class="text-left py-2 px-3">Athlete Name</th>
                <th class="text-left py-2 px-3">Sport Branch</th>
                <th class="text-left py-2 px-3">Gender</th>
                <th class="text-center py-2 px-3">Performance Score</th>
                <th class="text-left py-2 px-3">BMI Category</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(as, idx) in chunk" :key="as.athlete.id" class="border-b border-slate-100">
                <td class="py-2 px-3 text-center">{{ getChunkOffset(athleteScoreChunks, cIdx) + idx + 1 }}</td>
                <td class="py-2 px-3 font-bold text-[#0B2545]">{{ as.athlete.name }}</td>
                <td class="py-2 px-3">{{ as.athlete.sport_branch?.name || as.athlete.sport_branch_name }}</td>
                <td class="py-2 px-3">{{ as.athlete.gender === 'M' ? 'Male' : 'Female' }}</td>
                <td class="py-2 px-3 text-center">
                  <div class="flex items-center justify-center gap-1.5">
                    <div v-if="as.overall_score !== null" class="w-8 h-2 bg-slate-100 rounded-full overflow-hidden border border-slate-200/60 shrink-0">
                      <div class="h-full rounded-full transition-all" :style="{ width: Math.min(Math.max(as.overall_score, 0), 100) + '%', backgroundColor: getScoreColor(as.overall_score) }"></div>
                    </div>
                    <span :style="{ color: getScoreColor(as.overall_score) }" class="font-extrabold">{{ as.overall_score ? as.overall_score + '%' : '-' }}</span>
                  </div>
                </td>
                <td class="py-2 px-3">{{ as.bmi_category || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- DETAIL PER CABOR (PAGINATED) -->
        <template v-for="pb in data.per_branch" :key="pb.branch.id">
          <!-- Main Branch Summary Page -->
          <div class="rpt" :style="{ width: paperStyles.w, minHeight: paperStyles.h }">
            <h2 class="text-lg font-extrabold text-[#0B2545] mb-2">Sport Branch Summary: {{ pb.branch.name }}</h2>
            <p class="text-xs text-slate-600 font-semibold mb-4">
              Athletes tested: <b class="text-[#0B2545]">{{ pb.athlete_count }}</b> |
              Overall avg. performance score: <b class="text-[#0B2545]">{{ pb.avg_score ? pb.avg_score + '%' : '-' }}</b>
            </p>

            <div class="rpt-sec-title">Avg. Performance per Test Indicator</div>
            <table class="rpttable w-full border border-slate-100 mb-4">
              <thead>
                <tr>
                  <th class="text-left py-2 px-3">Indicator</th>
                  <th class="text-left py-2 px-3">Unit</th>
                  <th class="text-center py-2 px-3">Athletes Measured</th>
                  <th class="text-center py-2 px-3">Avg. Performance</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="pi in pb.per_indicator" :key="pi.indicator.id" class="border-b border-slate-100">
                  <td class="py-2 px-3 font-bold text-slate-700">{{ pi.indicator.name }}</td>
                  <td class="py-2 px-3">{{ pi.indicator.unit || '-' }}</td>
                  <td class="py-2 px-3 text-center">{{ pi.count }}</td>
                  <td class="py-2 px-3 text-center font-extrabold">{{ pi.avg_score ? pi.avg_score + '%' : '-' }}</td>
                </tr>
              </tbody>
            </table>

            <!-- Athlete List on the same page ONLY if athlete count is <= 10 -->
            <template v-if="pb.athletes.length <= 10">
              <div class="rpt-sec-title">Athlete List</div>
              <table class="rpttable w-full border border-slate-100">
                <thead>
                  <tr>
                    <th class="text-left py-2 px-3">Name</th>
                    <th class="text-left py-2 px-3">Gender</th>
                    <th class="text-center py-2 px-3">Performance Score</th>
                    <th class="text-left py-2 px-3">BMI Category</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="as in pb.athletes" :key="as.athlete.id" class="border-b border-slate-100">
                    <td class="py-2 px-3 font-bold text-[#0B2545]">{{ as.athlete.name }}</td>
                    <td class="py-2 px-3">{{ as.athlete.gender === 'M' ? 'Male' : 'Female' }}</td>
                    <td class="py-2 px-3 text-center">
                      <div class="flex items-center justify-center gap-1.5">
                        <div v-if="as.overall_score !== null" class="w-8 h-2 bg-slate-100 rounded-full overflow-hidden border border-slate-200/60 shrink-0">
                          <div class="h-full rounded-full transition-all" :style="{ width: Math.min(Math.max(as.overall_score, 0), 100) + '%', backgroundColor: getScoreColor(as.overall_score) }"></div>
                        </div>
                        <span :style="{ color: getScoreColor(as.overall_score) }" class="font-extrabold">{{ as.overall_score ? as.overall_score + '%' : '-' }}</span>
                      </div>
                    </td>
                    <td class="py-2 px-3">{{ as.bmi_category || '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </template>
          </div>

          <!-- Athlete List on separate pages if athlete count > 10 -->
          <template v-if="pb.athletes.length > 10">
            <div 
              v-for="(chunk, cIdx) in getBranchAthleteChunks(pb.athletes)" 
              :key="'branch-ath-' + pb.branch.id + '-' + cIdx" 
              class="rpt" 
              :style="{ width: paperStyles.w, minHeight: paperStyles.h }"
            >
              <h2 class="text-lg font-extrabold text-[#0B2545] mb-2">Sport Branch Athlete List: {{ pb.branch.name }}</h2>
              <p class="text-xs text-slate-600 font-semibold mb-4">
                Page {{ cIdx + 1 }} of {{ getBranchAthleteChunks(pb.athletes).length }} | Total {{ pb.athlete_count }} athletes
              </p>
              
              <table class="rpttable w-full border border-slate-100">
                <thead>
                  <tr>
                    <th class="text-center py-2 px-2 w-10">No</th>
                    <th class="text-left py-2 px-3">Name</th>
                    <th class="text-left py-2 px-3">Gender</th>
                    <th class="text-center py-2 px-3">Performance Score</th>
                    <th class="text-left py-2 px-3">BMI Category</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(as, idx) in chunk" :key="as.athlete.id" class="border-b border-slate-100">
                    <td class="py-2 px-2 text-center">{{ getChunkOffset(getBranchAthleteChunks(pb.athletes), cIdx) + idx + 1 }}</td>
                    <td class="py-2 px-3 font-bold text-[#0B2545]">{{ as.athlete.name }}</td>
                    <td class="py-2 px-3">{{ as.athlete.gender === 'M' ? 'Male' : 'Female' }}</td>
                    <td class="py-2 px-3 text-center">
                      <div class="flex items-center justify-center gap-1.5">
                        <div v-if="as.overall_score !== null" class="w-8 h-2 bg-slate-100 rounded-full overflow-hidden border border-slate-200/60 shrink-0">
                          <div class="h-full rounded-full transition-all" :style="{ width: Math.min(Math.max(as.overall_score, 0), 100) + '%', backgroundColor: getScoreColor(as.overall_score) }"></div>
                        </div>
                        <span :style="{ color: getScoreColor(as.overall_score) }" class="font-extrabold">{{ as.overall_score ? as.overall_score + '%' : '-' }}</span>
                      </div>
                    </td>
                    <td class="py-2 px-3">{{ as.bmi_category || '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </template>

          <!-- INDIVIDUAL ATHLETE REPORT PAGES FOR THIS SPORT BRANCH -->
          <AthleteReportPage
            v-for="as in pb.athletes"
            :key="'ind-' + as.athlete.id"
            :data="data.individual_reports[as.athlete.id]"
            :institution="data.institution"
            :paper-styles="paperStyles"
          />
        </template>

      </div>
    </div>
  </ReportViewerLayout>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue';
import ReportViewerLayout from '@/Components/ReportViewerLayout.vue';
import { 
  useReportPdf, 
  getScoreColor, 
  formatReportDate, 
  getPaperDimensions, 
  calculatePageCapacity, 
  getBalancedChunks, 
  getChunkOffset 
} from '@/Composables/useReportPdf';
import { setPdfViewerZoom } from '@/Utils/pdfViewer';
import AthleteReportPage from '@/Pages/Reports/AthleteReportPage.vue';

const props = defineProps({
  data: { type: Object, required: true },
  showOverallSummaryPerAthlete: {
      type: Boolean,
      default: true
  }
});

const {
  paperSize,
  paperOrientation,
  capturing,
  pdfUrl,
  progressPercent,
  currentPage,
  totalPages,
  paperStyles,
  generatePdf,
  syncPdfToExportCenter,
  downloadPdf,
} = useReportPdf('.rpt');

const athleteScoreChunks = computed(() => {
  const dim = getPaperDimensions(paperSize.value, paperOrientation.value);
  const capacity = calculatePageCapacity(dim.h, 22, 5.5, 28);
  return getBalancedChunks(props.data.athlete_scores || [], capacity, Math.min(6, Math.floor(capacity * 0.2)));
});

function getBranchAthleteChunks(athletes) {
  const dim = getPaperDimensions(paperSize.value, paperOrientation.value);
  const capacity = calculatePageCapacity(dim.h, 26, 5.5, 28);
  return getBalancedChunks(athletes || [], capacity, Math.min(4, Math.floor(capacity * 0.2)));
}

const pdfFilename = computed(() => {
  const folderName = (props.data.folder?.name || 'Report').replace(/[^\w\s-]/g, '').trim().replace(/\s+/g, '_');
  return `Folder_${folderName}.pdf`;
});

function handleViewerReady(registry) {
  setPdfViewerZoom(registry, null, 0.95, 250);
}

const allLogos = computed(() => {
  const logos = [];
  const inst = props.data.institution;
  if (inst && inst.logos) {
    inst.logos.forEach(l => logos.push(l));
  }
  return logos;
});

const signerName = computed(() => (props.data.institution?.signer_name || '').trim());
const signerTitle = computed(() => (props.data.institution?.signer_title || '').trim());
const todayDate = computed(() => formatReportDate());

watch([paperSize, paperOrientation], () => {
  generatePdf();
});

onMounted(async () => {
  await generatePdf();
  syncPdfToExportCenter(
    props.data.folder?.name ? `Folder Report - ${props.data.folder.name}` : (props.data.cover_title || 'Physical Test Report'),
    pdfFilename.value,
    props.data.folder?.id || null
  );
});

function downloadReport() {
  downloadPdf(pdfFilename.value);
}
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

  /* Gray, zinc, neutral — restore light-mode values to prevent dark-theme bleed into PDF */
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
.capturing-pdf .rpt {
  margin: 0 !important;
  box-shadow: none !important;
  border: none !important;
}
.rpt {
  background: #fff;
  width: 210mm;
  min-height: 297mm;
  margin: 0 auto 8mm;
  padding: 14mm;
  box-shadow: 0 4px 18px rgba(0,0,0,.18);
  position: relative;
  box-sizing: border-box;
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
.rpt-logos img { max-height: 80px; object-fit: contain; }
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

.rpt-sec-title { background: #F0F4F9; color: #0B2545; font-weight: 800; font-size: 10px; text-transform: uppercase; letter-spacing: .4px; padding: 4px 9px; border-radius: 7px; margin: 8px 0 5px; }
.rpttable { width: 100%; border-collapse: collapse; font-size: 9px; color: #1e293b; }
.rpttable th { background: #0B2545; color: #fff; padding: 4px 6px; font-size: 8.5px; text-transform: uppercase; text-align: left; }
.rpttable td { padding: 4px 6px; border-bottom: 1px solid #EEF1F5; color: #1e293b; }

.signblock { display: flex; justify-content: flex-end; margin-top: 15px; }
.signbox { text-align: center; font-size: 10px; width: 190px; color: #0B2545; }
.signbox .sp { height: 45px; }
</style>
