<template>
  <ReportViewerLayout
    title="Final Assessment Report Viewer"
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
        
        <!-- COVER PAGE -->
        <div class="rpt flex flex-col justify-between items-center text-center" :style="{ width: paperStyles.w, minHeight: paperStyles.h, padding: '24mm 14mm' }">
          
          <!-- Top spacing to balance bottom -->
          <div></div>

          <!-- Center Content -->
          <div class="flex flex-col items-center space-y-6">
            <!-- Logos -->
            <div class="flex items-center justify-center gap-4">
              <img 
                v-for="logo in allLogos" 
                :key="logo.id || logo.logo_path" 
                :src="'/storage/' + logo.logo_path" 
                alt="Logo" 
                :style="{ height: logo.height_px + 'px', maxHeight: '100px', objectFit: 'contain' }"
              />
            </div>

            <!-- Cover Title -->
            <h1 class="text-3xl font-extrabold text-[#0B2545] uppercase tracking-tight max-w-xl leading-tight">
              {{ data.cover_title || 'Physical Test Final Report' }}
            </h1>
            
            <!-- Institution Info -->
            <div class="block w-full text-center" style="margin-top: 20px; margin-bottom: 20px;">
              <template v-if="data.institution && data.institution.header_lines">
                <div 
                  v-for="(line, lIdx) in data.institution.header_lines.split('\n')" 
                  :key="'header-line-' + lIdx"
                  class="text-lg font-bold text-[#0B2545] uppercase text-center block w-full"
                  style="line-height: 1.35; margin-bottom: 4px;"
                >
                  {{ line }}
                </div>
              </template>
              <div v-else class="text-lg font-bold text-[#0B2545] uppercase text-center block w-full" style="line-height: 1.35; margin-bottom: 4px;">
                {{ data.institution?.name || 'PhysicalScore' }}
              </div>
              <div v-if="data.institution && data.institution.address" class="text-[10px] text-slate-500 font-semibold uppercase text-center block w-full" style="line-height: 1.4; margin-top: 8px;">
                {{ data.institution.address }}
              </div>
            </div>

            <!-- Subtitle / Folder Name -->
            <div class="space-y-2 pt-2">
              <div class="text-sm font-extrabold text-[#FF6B35]">
                {{ data.cover_sub || (data.folder ? 'Activity: ' + data.folder.name : 'All Athlete Data') }}
              </div>
              <div v-if="data.folder?.description" class="text-xs text-slate-500 font-medium max-w-md mx-auto">
                {{ data.folder.description }}
              </div>
            </div>
          </div>
          
          <!-- Bottom Info (Footer) -->
          <div class="text-xs text-slate-500 font-medium leading-relaxed">
            PhysicalScore - Physical Performance Assessment System<br>
            {{ todayDate }}
          </div>
        </div>

        <!-- DAFTAR ISI -->
        <div class="rpt" :style="{ width: paperStyles.w, minHeight: paperStyles.h }">
          <h2 class="text-lg font-extrabold text-[#0B2545] mb-6">Table of Contents</h2>
          <div class="space-y-4 text-xs font-semibold text-slate-700 mt-4 max-w-xl mx-auto">
            
            <div class="flex items-center gap-2">
              <span>Table of Contents</span>
              <div class="flex-1 border-b border-dotted border-slate-300 relative top-1"></div>
              <span class="font-bold w-6 text-right">{{ tocPages.toc }}</span>
            </div>

            <div v-if="data.foreword" class="flex items-center gap-2">
              <span>Foreword</span>
              <div class="flex-1 border-b border-dotted border-slate-300 relative top-1"></div>
              <span class="font-bold w-6 text-right">{{ tocPages.foreword }}</span>
            </div>

            <div class="flex items-center gap-2">
              <span>Dashboard Summary</span>
              <div class="flex-1 border-b border-dotted border-slate-300 relative top-1"></div>
              <span class="font-bold w-6 text-right">{{ tocPages.dashboard }}</span>
            </div>

            <div class="flex items-center gap-2">
              <span>Overall Summary by Sport Branch</span>
              <div class="flex-1 border-b border-dotted border-slate-300 relative top-1"></div>
              <span class="font-bold w-6 text-right">{{ tocPages.overallBranch }}</span>
            </div>

            <div v-for="pb in data.per_branch" :key="pb.branch.id" class="flex items-center gap-2 pl-6">
              <span>Sport Branch: {{ pb.branch.name }}</span>
              <div class="flex-1 border-b border-dotted border-slate-300 relative top-1"></div>
              <span class="font-bold w-6 text-right">{{ tocPages.branches[pb.branch.id] }}</span>
            </div>

            <div class="flex items-center gap-2">
              <span>Overall Summary per Athlete</span>
              <div class="flex-1 border-b border-dotted border-slate-300 relative top-1"></div>
              <span class="font-bold w-6 text-right">{{ tocPages.overallAthlete }}</span>
            </div>

            <div class="flex items-center gap-2">
              <span>Conclusion</span>
              <div class="flex-1 border-b border-dotted border-slate-300 relative top-1"></div>
              <span class="font-bold w-6 text-right">{{ tocPages.conclusion }}</span>
            </div>
            
          </div>
        </div>

        <!-- KATA PENGANTAR (PAGINATED CHUNKS) -->
        <template v-if="data.foreword">
          <div 
            v-for="(chunk, idx) in chunkText(data.foreword)" 
            :key="'foreword-page-' + idx" 
            class="rpt" 
            :style="{ width: paperStyles.w, minHeight: paperStyles.h }"
          >
            <h2 class="text-lg font-extrabold text-[#0B2545] mb-3">
              Foreword <span v-if="chunkText(data.foreword).length > 1">(Page {{ idx + 1 }} of {{ chunkText(data.foreword).length }})</span>
            </h2>
            <p class="text-[13px] leading-relaxed text-slate-700 whitespace-pre-wrap mt-3 text-justify" style="overflow-wrap: anywhere; word-break: break-word;">
              {{ chunk }}
            </p>
          </div>
        </template>

        <!-- RINGKASAN DASHBOARD -->
        <div class="rpt" :style="{ width: paperStyles.w, minHeight: paperStyles.h }">
          <h2 class="text-lg font-extrabold text-[#0B2545] mb-3">Dashboard Summary</h2>
          <table class="rpttable w-full mt-3.5 border border-slate-100">
            <thead>
              <tr>
                <th class="text-left py-2 px-3 bg-[#0B2545] text-white">Metric</th>
                <th class="text-left py-2 px-3 bg-[#0B2545] text-white">Value</th>
              </tr>
            </thead>
            <tbody>
              <tr class="border-b border-slate-100">
                <td class="py-2.5 px-3">Total Athletes</td>
                <td class="py-2.5 px-3 font-bold text-[#0B2545]">{{ data.total_athletes }}</td>
              </tr>
              <tr class="border-b border-slate-100">
                <td class="py-2.5 px-3">Total Test Sessions</td>
                <td class="py-2.5 px-3 font-bold text-[#0B2545]">{{ data.total_sessions }}</td>
              </tr>
              <tr class="border-b border-slate-100">
                <td class="py-2.5 px-3">Number of Sport Branches</td>
                <td class="py-2.5 px-3 font-bold text-[#0B2545]">{{ data.per_branch?.length || 0 }}</td>
              </tr>
              <tr v-if="data.gender_stats" class="border-b border-slate-100">
                <td class="py-2.5 px-3">Male Athletes (L)</td>
                <td class="py-2.5 px-3 font-semibold text-slate-700">{{ data.gender_stats.male_count }} athletes</td>
              </tr>
              <tr v-if="data.gender_stats" class="border-b border-slate-100">
                <td class="py-2.5 px-3">Female Athletes (P)</td>
                <td class="py-2.5 px-3 font-semibold text-slate-700">{{ data.gender_stats.female_count }} athletes</td>
              </tr>
              <tr v-if="data.gender_stats && data.gender_stats.male_avg !== null" class="border-b border-slate-100">
                <td class="py-2.5 px-3">Male Avg. Performance</td>
                <td class="py-2.5 px-3">
                  <div class="flex items-center gap-2">
                    <div class="progress-outer w-20">
                      <div class="progress-inner" :style="{ width: data.gender_stats.male_avg + '%', backgroundColor: getProgressColor(data.gender_stats.male_avg) }"></div>
                    </div>
                    <span class="font-bold text-[#0B2545]">{{ data.gender_stats.male_avg }}%</span>
                  </div>
                </td>
              </tr>
              <tr v-if="data.gender_stats && data.gender_stats.female_avg !== null" class="border-b border-slate-100">
                <td class="py-2.5 px-3">Female Avg. Performance</td>
                <td class="py-2.5 px-3">
                  <div class="flex items-center gap-2">
                    <div class="progress-outer w-20">
                      <div class="progress-inner" :style="{ width: data.gender_stats.female_avg + '%', backgroundColor: getProgressColor(data.gender_stats.female_avg) }"></div>
                    </div>
                    <span class="font-bold text-[#0B2545]">{{ data.gender_stats.female_avg }}%</span>
                  </div>
                </td>
              </tr>
              <tr class="border-b border-slate-100">
                <td class="py-2.5 px-3">Overall Average Performance</td>
                <td class="py-2.5 px-3">
                  <div class="flex items-center gap-2">
                    <div v-if="data.average_score" class="progress-outer w-20">
                      <div class="progress-inner" :style="{ width: data.average_score + '%', backgroundColor: getProgressColor(data.average_score) }"></div>
                    </div>
                    <span class="font-extrabold text-[#0B2545]">{{ data.average_score ? data.average_score + '%' : '-' }}</span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Charts Section -->
          <div class="grid grid-cols-2 gap-4 mt-8 w-full" style="height: 185px;">
            <div class="relative w-full h-full">
              <canvas ref="dashboardChartCanvas"></canvas>
            </div>
            <div class="relative w-full h-full">
              <canvas ref="genderChartCanvas"></canvas>
            </div>
          </div>
        </div>

        <!-- REKAP KESELURUHAN PER CABANG OLAHRAGA -->
        <div class="rpt" :style="{ width: paperStyles.w, minHeight: paperStyles.h }">
          <h2 class="text-lg font-extrabold text-[#0B2545] mb-3">Overall Summary by Sport Branch</h2>
          <table class="rpttable w-full mt-3.5 border border-slate-100">
            <thead>
              <tr>
                <th class="text-left py-2 px-3">Sport Branch</th>
                <th class="text-center py-2 px-3">Number of Athletes</th>
                <th class="text-center py-2 px-3">Average Score</th>
                <th class="text-center py-2 px-3">Highest Score</th>
                <th class="text-left py-2 px-3">Best Athlete</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="pb in data.per_branch" :key="pb.branch.id" class="border-b border-slate-100">
                <td class="py-2.5 px-3 font-bold text-[#0B2545]">{{ pb.branch.name }}</td>
                <td class="py-2.5 px-3 text-center">{{ pb.athlete_count }}</td>
                <td class="py-2.5 px-3">
                  <div class="flex items-center gap-2 justify-center">
                    <div v-if="pb.avg_score" class="progress-outer w-12">
                      <div class="progress-inner" :style="{ width: pb.avg_score + '%', backgroundColor: getProgressColor(pb.avg_score) }"></div>
                    </div>
                    <span class="font-bold">{{ pb.avg_score ? pb.avg_score + '%' : '-' }}</span>
                  </div>
                </td>
                <td class="py-2.5 px-3">
                  <div class="flex items-center gap-2 justify-center">
                    <div v-if="pb.best_score" class="progress-outer w-12">
                      <div class="progress-inner" :style="{ width: pb.best_score + '%', backgroundColor: getProgressColor(pb.best_score) }"></div>
                    </div>
                    <span class="font-bold">{{ pb.best_score ? pb.best_score + '%' : '-' }}</span>
                  </div>
                </td>
                <td class="py-2.5 px-3">{{ pb.best_athlete?.name || '-' }}</td>
              </tr>
            </tbody>
          </table>

          <div class="flex flex-col gap-6 mt-8 w-full">
            <div class="relative w-full" style="height: 190px;">
              <canvas ref="branchBarChartCanvas"></canvas>
            </div>
            <div class="relative w-full" style="height: 190px;">
              <canvas ref="branchRadarChartCanvas"></canvas>
            </div>
          </div>
        </div>

        <div 
          v-for="(chunk, cIdx) in allAthleteChunks" 
          :key="'all-ath-' + cIdx" 
          class="rpt" 
          :style="{ width: paperStyles.w, minHeight: paperStyles.h }"
        >
          <h2 class="text-lg font-extrabold text-[#0B2545] mb-2">Overall Summary per Athlete (All Sport Branches)</h2>
          <p class="text-[11px] text-slate-500 font-medium mb-3">
            Page {{ cIdx + 1 }} of {{ allAthleteChunks.length }} | Total {{ data.total_athletes }} athletes, sorted by highest performance score.
          </p>
          <table class="rpttable w-full border border-slate-100">
            <thead>
              <tr>
                <th class="text-center py-1.5 px-2 w-10">No</th>
                <th class="text-left py-1.5 px-2">ID / Reg No.</th>
                <th class="text-left py-1.5 px-2">Athlete Name</th>
                <th class="text-left py-1.5 px-2">Sport Branch</th>
                <th class="text-center py-1.5 px-2">Gender</th>
                <th class="text-center py-1.5 px-2">Age</th>
                <th class="text-center py-1.5 px-2">Height / Weight</th>
                <th class="text-center py-1.5 px-2">BMI</th>
                <th class="text-center py-1.5 px-2">Performance Score</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(as, idx) in chunk" :key="as.athlete.id" class="border-b border-slate-100">
                <td class="py-1 px-2 text-center">{{ getChunkOffset(allAthleteChunks, cIdx) + idx + 1 }}</td>
                <td class="py-1 px-2 text-slate-500 font-semibold">{{ as.athlete.athlete_number || '-' }}</td>
                <td class="py-1 px-2 font-bold text-[#0B2545]">{{ as.athlete.name }}</td>
                <td class="py-1 px-2">{{ as.athlete.sport_branch?.name || as.athlete.sport_branch_name }}</td>
                <td class="py-1 px-2 text-center">{{ as.athlete.gender === 'M' ? 'M' : 'F' }}</td>
                <td class="py-1 px-2 text-center">{{ as.age !== null ? as.age + 'y' : '-' }}</td>
                <td class="py-1 px-2 text-center">{{ as.height || '-' }} cm / {{ as.weight || '-' }} kg</td>
                <td class="py-1 px-2 text-center">
                  <span class="font-bold">{{ as.bmi || '-' }}</span>
                  <span v-if="as.bmi_category" class="text-[7.5px] text-slate-500 block leading-none mt-0.5">({{ as.bmi_category }})</span>
                </td>
                <td class="py-1 px-2 text-center">
                  <div class="flex items-center gap-1.5 justify-center">
                    <div v-if="as.overall_score" class="progress-outer w-10">
                      <div class="progress-inner" :style="{ width: as.overall_score + '%', backgroundColor: getProgressColor(as.overall_score) }"></div>
                    </div>
                    <span class="font-extrabold text-[#0B2545]">{{ as.overall_score ? as.overall_score + '%' : '-' }}</span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- DETAIL PER SPORT BRANCH (PAGINATED) -->
        <template v-for="pb in data.per_branch" :key="pb.branch.id">
          <!-- Main Branch Summary Page -->
          <div class="rpt" :style="{ width: paperStyles.w, minHeight: paperStyles.h }">
            <h2 class="text-lg font-extrabold text-[#0B2545] mb-2">Sport Branch: {{ pb.branch.name }}</h2>
            <p class="text-xs text-slate-600 font-semibold mb-4">
              Athletes tested: <b class="text-[#0B2545]">{{ pb.athlete_count }}</b> |
              Overall avg. performance score: <b class="text-[#0B2545]">{{ pb.avg_score ? pb.avg_score + '%' : '-' }}</b>
            </p>

            <div class="rpt-sec-title">Average Performance per Test Indicator</div>
            <table class="rpttable w-full border border-slate-100 mb-4">
              <thead>
                <tr>
                  <th class="text-left py-2 px-3">Indicator</th>
                  <th class="text-left py-2 px-3">Unit</th>
                  <th class="text-center py-2 px-3">Athletes Measured</th>
                  <th class="text-center py-2 px-3">Average Performance</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="pi in pb.per_indicator" :key="pi.indicator.id" class="border-b border-slate-100">
                  <td class="py-2 px-3 font-bold text-slate-700">{{ pi.indicator.name }}</td>
                  <td class="py-2 px-3">{{ pi.indicator.unit || '-' }}</td>
                  <td class="py-2 px-3 text-center">{{ pi.count }}</td>
                  <td class="py-2 px-3 text-center">
                    <div class="flex items-center gap-2 justify-center">
                      <div v-if="pi.avg_score" class="progress-outer w-12">
                        <div class="progress-inner" :style="{ width: pi.avg_score + '%', backgroundColor: getProgressColor(pi.avg_score) }"></div>
                      </div>
                      <span class="font-extrabold text-slate-700">{{ pi.avg_score ? pi.avg_score + '%' : '-' }}</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>

            <template v-if="pb.athletes.length <= 10">
              <div class="rpt-sec-title">Athlete List</div>
              <table class="rpttable w-full border border-slate-100">
                <thead>
                  <tr>
                    <th class="text-center py-2 px-2 w-10">No</th>
                    <th class="text-left py-2 px-2">ID / Reg No.</th>
                    <th class="text-left py-2 px-2">Athlete Name</th>
                    <th class="text-center py-2 px-2">Gender</th>
                    <th class="text-center py-2 px-2">Age</th>
                    <th class="text-center py-2 px-2">Height / Weight</th>
                    <th class="text-center py-2 px-2">BMI</th>
                    <th class="text-center py-2 px-2">Performance Score</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(as, idx) in pb.athletes" :key="as.athlete.id" class="border-b border-slate-100">
                    <td class="py-2 px-2 text-center">{{ idx + 1 }}</td>
                    <td class="py-2 px-2 text-slate-500 font-semibold">{{ as.athlete.athlete_number || '-' }}</td>
                    <td class="py-2 px-2 font-bold text-[#0B2545]">{{ as.athlete.name }}</td>
                    <td class="py-2 px-2 text-center">{{ as.athlete.gender === 'M' ? 'M' : 'F' }}</td>
                    <td class="py-2 px-2 text-center">{{ as.age !== null ? as.age + 'y' : '-' }}</td>
                    <td class="py-2 px-2 text-center">{{ as.height || '-' }} cm / {{ as.weight || '-' }} kg</td>
                    <td class="py-2 px-2 text-center">
                      <span class="font-bold">{{ as.bmi || '-' }}</span>
                      <span v-if="as.bmi_category" class="text-[7.5px] text-slate-500 block leading-none mt-0.5">({{ as.bmi_category }})</span>
                    </td>
                    <td class="py-2.5 px-2 text-center">
                      <div class="flex items-center gap-1.5 justify-center">
                        <div v-if="as.overall_score" class="progress-outer w-10">
                          <div class="progress-inner" :style="{ width: as.overall_score + '%', backgroundColor: getProgressColor(as.overall_score) }"></div>
                        </div>
                        <span class="font-extrabold text-[#0B2545]">{{ as.overall_score ? as.overall_score + '%' : '-' }}</span>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </template>
          </div>

          <template v-if="pb.athletes.length > 10">
            <div 
              v-for="(chunk, cIdx) in getBranchAthleteChunks(pb.athletes)" 
              :key="'branch-ath-' + pb.branch.id + '-' + cIdx" 
              class="rpt" 
              :style="{ width: paperStyles.w, minHeight: paperStyles.h }"
            >
              <h2 class="text-lg font-extrabold text-[#0B2545] mb-2">Sport Branch Athlete List: {{ pb.branch.name }}</h2>
              <p class="text-xs text-slate-600 font-semibold mb-4">
                Page {{ cIdx + 1 }} of {{ getBranchAthleteChunks(pb.athletes).length }} | Total {{ pb.athletes.length }} athletes
              </p>

              <table class="rpttable w-full border border-slate-100">
                <thead>
                  <tr>
                    <th class="text-center py-1.5 px-2 w-10">No</th>
                    <th class="text-left py-1.5 px-2">ID / Reg No.</th>
                    <th class="text-left py-1.5 px-2">Athlete Name</th>
                    <th class="text-center py-1.5 px-2">Gender</th>
                    <th class="text-center py-1.5 px-2">Age</th>
                    <th class="text-center py-1.5 px-2">Height / Weight</th>
                    <th class="text-center py-1.5 px-2">BMI</th>
                    <th class="text-center py-1.5 px-2">Performance Score</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(as, idx) in chunk" :key="as.athlete.id" class="border-b border-slate-100">
                    <td class="py-1 px-2 text-center">{{ getChunkOffset(getBranchAthleteChunks(pb.athletes), cIdx) + idx + 1 }}</td>
                    <td class="py-1 px-2 text-slate-500 font-semibold">{{ as.athlete.athlete_number || '-' }}</td>
                    <td class="py-1 px-2 font-bold text-[#0B2545]">{{ as.athlete.name }}</td>
                    <td class="py-1 px-2 text-center">{{ as.athlete.gender === 'M' ? 'M' : 'F' }}</td>
                    <td class="py-1 px-2 text-center">{{ as.age !== null ? as.age + 'y' : '-' }}</td>
                    <td class="py-1 px-2 text-center">{{ as.height || '-' }} cm / {{ as.weight || '-' }} kg</td>
                    <td class="py-1 px-2 text-center">
                      <span class="font-bold">{{ as.bmi || '-' }}</span>
                      <span v-if="as.bmi_category" class="text-[7.5px] text-slate-500 block leading-none mt-0.5">({{ as.bmi_category }})</span>
                    </td>
                    <td class="py-1 px-2 text-center">
                      <div class="flex items-center gap-1.5 justify-center">
                        <div v-if="as.overall_score" class="progress-outer w-10">
                          <div class="progress-inner" :style="{ width: as.overall_score + '%', backgroundColor: getProgressColor(as.overall_score) }"></div>
                        </div>
                        <span class="font-extrabold text-[#0B2545]">{{ as.overall_score ? as.overall_score + '%' : '-' }}</span>
                      </div>
                    </td>
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

        <!-- CONCLUSION (PAGINATED CHUNKS) -->
        <template v-if="data.conclusion || data.average_score">
          <div 
            v-for="(chunk, idx) in (chunkText(data.conclusion || '').length ? chunkText(data.conclusion || '') : [''])" 
            :key="'conclusion-page-' + idx" 
            class="rpt" 
            :style="{ width: paperStyles.w, minHeight: paperStyles.h }"
          >
            <h2 class="text-lg font-extrabold text-[#0B2545] mb-3">
              Conclusion <span v-if="chunkText(data.conclusion || '').length > 1">(Page {{ idx + 1 }} of {{ chunkText(data.conclusion || '').length }})</span>
            </h2>
            
            <p v-if="idx === 0" class="text-[13px] leading-relaxed text-slate-700 mt-2.5 text-justify mb-3">
              From a total of {{ data.total_athletes }} athletes tested across {{ data.per_branch?.length || 0 }} sport branches, the overall average performance score is {{ data.average_score ? data.average_score + '%' : '-' }}.
            </p>

            <p class="text-[13px] leading-relaxed text-slate-700 whitespace-pre-wrap text-justify" style="overflow-wrap: anywhere; word-break: break-word;">
              {{ chunk }}
            </p>

            <!-- Signature Block only on the last page -->
            <div v-if="idx === (chunkText(data.conclusion || '').length ? chunkText(data.conclusion || '').length - 1 : 0) && data.institution && (signerName || signerTitle)" class="signblock" style="page-break-inside: avoid; margin-top: 25px;">
              <div class="signbox">
                <div>
                  {{ data.institution.signature_city ? data.institution.signature_city + ', ' : (data.institution.address ? data.institution.address + ', ' : '') }}{{ signatureDate }}
                </div>
                <div>Verified by,</div>
                <div style="font-weight: 600;">{{ signerTitle }}</div>
                <div style="height: 40px;"></div>
                <b style="display: inline-block; padding: 0 4px;">{{ signerName }}</b>
              </div>
            </div>
          </div>
        </template>

      </div>
    </div>
  </ReportViewerLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import Chart from 'chart.js/auto';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import ReportViewerLayout from '@/Components/ReportViewerLayout.vue';
import { 
  useReportPdf, 
  getScoreColor as getProgressColor, 
  formatReportDate, 
  getPaperDimensions,
  calculatePageCapacity,
  getBalancedChunks,
  getChunkOffset
} from '@/Composables/useReportPdf';
import { setPdfViewerZoom } from '@/Utils/pdfViewer';
import AthleteReportPage from '@/Pages/Reports/AthleteReportPage.vue';

const dashboardChartCanvas = ref(null);
let dashboardChart = null;

const genderChartCanvas = ref(null);
let genderChart = null;

const branchBarChartCanvas = ref(null);
const branchRadarChartCanvas = ref(null);
let branchBarChart = null;
let branchRadarChart = null;

const props = defineProps({
  data: { type: Object, required: true },
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

const allAthleteChunks = computed(() => {
  const dim = getPaperDimensions(paperSize.value, paperOrientation.value);
  const capacity = calculatePageCapacity(dim.h, 24, 5.5, 28);
  return getBalancedChunks(props.data.athlete_scores || [], capacity, Math.min(6, Math.floor(capacity * 0.2)));
});

function getBranchAthleteChunks(athletes) {
  const dim = getPaperDimensions(paperSize.value, paperOrientation.value);
  const capacity = calculatePageCapacity(dim.h, 26, 5.5, 28);
  return getBalancedChunks(athletes || [], capacity, Math.min(4, Math.floor(capacity * 0.2)));
}

watch([paperSize, paperOrientation], () => {
  generatePdf();
});

const maxCharsPerPage = computed(() => {
  const dim = getPaperDimensions(paperSize.value, paperOrientation.value);
  const printableWidthPx = (dim.w - 28) * 3.78;
  const printableHeightPx = (dim.h - 28) * 3.78;
  const overheadPx = 100;
  const contentHeightPx = Math.max(200, printableHeightPx - overheadPx);
  
  const fontSizePx = 13;
  const lineHeightPx = fontSizePx * 1.625;
  const charWidthPx = fontSizePx * 0.55;
  
  const linesPerPage = contentHeightPx / lineHeightPx;
  const charsPerLine = printableWidthPx / charWidthPx;
  
  return Math.floor(linesPerPage * charsPerLine * 0.8);
});

function chunkText(text, limit = maxCharsPerPage.value) {
  if (!text) return [];
  const dim = getPaperDimensions(paperSize.value, paperOrientation.value);
  const printableWidthPx = (dim.w - 28) * 3.78;
  const charsPerLine = Math.floor(printableWidthPx / (13 * 0.55)) || 80;

  const paragraphs = text.split('\n');
  const chunks = [];
  let currentChunk = [];
  let currentCost = 0;

  for (const para of paragraphs) {
    const wrappedLinesCount = Math.ceil(para.length / charsPerLine) || 1;
    const paraCost = wrappedLinesCount * charsPerLine;

    if (paraCost > limit) {
      if (currentChunk.length > 0) {
        chunks.push(currentChunk.join('\n'));
        currentChunk = [];
        currentCost = 0;
      }
      let remaining = para;
      while (remaining.length > 0) {
        const piece = remaining.substring(0, limit);
        chunks.push(piece);
        remaining = remaining.substring(limit);
      }
    } else if (currentCost + paraCost > limit) {
      chunks.push(currentChunk.join('\n'));
      currentChunk = [para];
      currentCost = paraCost;
    } else {
      currentChunk.push(para);
      currentCost += paraCost;
    }
  }

  if (currentChunk.length > 0) {
    chunks.push(currentChunk.join('\n'));
  }

  return chunks;
}

const pdfFilename = computed(() => {
  const folderName = (props.data.folder?.name || 'Report').replace(/[^\w\s-]/g, '').trim().replace(/\s+/g, '_');
  return `Final_${folderName}.pdf`;
});

const tocPages = computed(() => {
  let currentPage = 1; // Cover Page is 1
  
  const pages = {
    cover: 1,
  };

  pages.toc = currentPage + 1;
  currentPage += 1;

  if (props.data.foreword) {
    pages.foreword = currentPage + 1;
    const forewordPages = chunkText(props.data.foreword).length;
    currentPage += forewordPages;
  } else {
    pages.foreword = null;
  }

  pages.dashboard = currentPage + 1;
  currentPage += 1;

  pages.overallBranch = currentPage + 1;
  currentPage += 1;

  pages.branches = {};
  if (props.data.per_branch) {
    props.data.per_branch.forEach(pb => {
      pages.branches[pb.branch.id] = currentPage + 1;
      currentPage += 1;
      if (pb.athletes && pb.athletes.length > 10) {
        currentPage += getGreedyChunks(pb.athletes, 24).length;
      }
      if (pb.athletes) {
        currentPage += pb.athletes.length;
      }
    });
  }

  pages.overallAthlete = currentPage + 1;
  if (props.data.athlete_scores) {
    currentPage += getGreedyChunks(props.data.athlete_scores, 24).length;
  } else {
    currentPage += 1;
  }

  pages.conclusion = currentPage + 1;
  const conclusionPages = chunkText(props.data.conclusion || '').length || 1;
  currentPage += conclusionPages;

  pages.individualReports = null;

  return pages;
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
const signatureDate = computed(() => props.data.institution?.signature_date_formatted || '');
const todayDate = computed(() => formatReportDate());

function wrapLabel(label, maxLen = 12) {
  if (Array.isArray(label)) return label;
  if (!label || label.length <= maxLen) return label;
  const words = label.trim().split(/\s+/);
  if (words.length <= 1) return label;

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
  if (dashboardChartCanvas.value && props.data.per_branch) {
    const labels = props.data.per_branch.map(pb => wrapLabel(pb.branch.name));
    const datasetData = props.data.per_branch.map(pb => pb.avg_score || 0);

    dashboardChart = new Chart(dashboardChartCanvas.value, {
      type: 'bar',
      plugins: [ChartDataLabels],
      data: {
        labels: labels,
        datasets: [{
          label: 'Average Performance (%)',
          data: datasetData,
          backgroundColor: '#FF6B35', // Accent color
          borderRadius: 4,
          maxBarThickness: 32,
        }]
      },
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
            formatter: (v) => v !== null && v !== undefined ? Number(v).toFixed(1) + '%' : '',
            font: { weight: 'bold', size: 7 },
            color: '#FF6B35'
          },
          legend: { display: false },
          title: { 
            display: true, 
            text: 'Average Performance per Sport Branch', 
            font: { size: 9, weight: 'bold', family: 'sans-serif' },
            padding: { bottom: 10 }
          }
        },
        scales: {
          y: { 
            min: 0, 
            max: 100, 
            ticks: { font: { size: 7 } },
            grid: { color: '#E2E8F0' }
          },
          x: { 
            ticks: { font: { size: 7 }, autoSkip: false, maxRotation: 45, minRotation: 0 },
            grid: { display: false }
          }
        }
      }
    });
  }

  if (genderChartCanvas.value && props.data.gender_stats) {
    const stats = props.data.gender_stats;
    const genderLabels = ['Male (L)', 'Female (P)'];
    const genderData = [stats.male_avg || 0, stats.female_avg || 0];

    genderChart = new Chart(genderChartCanvas.value, {
      type: 'doughnut',
      plugins: [ChartDataLabels],
      data: {
        labels: genderLabels,
        datasets: [{
          data: genderData,
          backgroundColor: ['#0284c7', '#ec4899'],
          borderWidth: 1.5,
          borderColor: '#ffffff',
        }]
      },
      options: {
        devicePixelRatio: 3,
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        plugins: {
          datalabels: {
            formatter: (v, ctx) => {
              const label = ctx.chart.data.labels[ctx.dataIndex];
              const score = v !== null && v !== undefined ? Number(v).toFixed(1) + '%' : '';
              return `${label}\n${score}`;
            },
            textAlign: 'center',
            font: { weight: 'bold', size: 7.5 },
            color: '#ffffff'
          },
          legend: { display: false },
          title: {
            display: true,
            text: 'Average Performance by Gender',
            font: { size: 9, weight: 'bold', family: 'sans-serif' },
            padding: { bottom: 10 }
          }
        }
      }
    });
  }

  if (branchBarChartCanvas.value && props.data.per_branch) {
    const labels = props.data.per_branch.map(pb => pb.branch.name);
    const avgScores = props.data.per_branch.map(pb => pb.avg_score || 0);
    const bestScores = props.data.per_branch.map(pb => pb.best_score || 0);

    branchBarChart = new Chart(branchBarChartCanvas.value, {
      type: 'bar',
      plugins: [ChartDataLabels],
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Average Score (%)',
            data: avgScores,
            backgroundColor: '#FF6B35', // Accent color
            borderRadius: 4,
            maxBarThickness: 16,
          },
          {
            label: 'Highest Score (%)',
            data: bestScores,
            backgroundColor: '#06A77D', // Success color
            borderRadius: 4,
            maxBarThickness: 16,
          }
        ]
      },
      options: {
        devicePixelRatio: 3,
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        layout: { padding: { top: 12 } },
        plugins: {
          datalabels: {
            anchor: 'end',
            align: 'top',
            formatter: (v) => v !== null && v !== undefined ? Number(v).toFixed(1) + '%' : '',
            font: { weight: 'bold', size: 6.5 },
            color: '#1e293b'
          },
          legend: { display: true, position: 'bottom', labels: { boxWidth: 6, font: { size: 6.5 } } },
          title: { 
            display: true, 
            text: 'Average vs. Highest Score', 
            font: { size: 9, weight: 'bold', family: 'sans-serif' },
            padding: { bottom: 5 }
          }
        },
        scales: {
          y: { 
            min: 0, 
            max: 100, 
            ticks: { font: { size: 6.5 } },
            grid: { color: '#E2E8F0' }
          },
          x: { 
            ticks: { font: { size: 6.5 }, autoSkip: false, maxRotation: 45, minRotation: 0 },
            grid: { display: false }
          }
        }
      }
    });
  }

  if (branchRadarChartCanvas.value && props.data.per_branch) {
    const labels = props.data.per_branch.map(pb => pb.branch.name);
    const avgScores = props.data.per_branch.map(pb => pb.avg_score || 0);

    branchRadarChart = new Chart(branchRadarChartCanvas.value, {
      type: 'radar',
      data: {
        labels: labels,
        datasets: [{
          label: 'Average Score (%)',
          data: avgScores,
          borderColor: '#FF6B35',
          backgroundColor: 'rgba(255, 107, 53, 0.15)',
          borderWidth: 1.5,
          pointBackgroundColor: '#FF6B35',
          pointRadius: 2,
        }]
      },
      options: {
        devicePixelRatio: 3,
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        plugins: {
          legend: { display: false },
          title: { 
            display: true, 
            text: 'Performance Comparison Map', 
            font: { size: 9, weight: 'bold', family: 'sans-serif' },
            padding: { bottom: 5 }
          }
        },
        scales: {
          r: {
            min: 0,
            max: 100,
            ticks: { display: false },
            pointLabels: { font: { size: 6.5, weight: 'bold' } },
            grid: { color: '#CBD5E1' },
            angleLines: { color: '#CBD5E1' }
          }
        }
      }
    });
  }

  await generatePdf();
  syncPdfToExportCenter(
    props.data.folder?.name ? `Final Report - ${props.data.folder.name}` : (props.data.cover_title || 'Physical Test Final Report'),
    pdfFilename.value,
    props.data.folder?.id || null
  );
});

function downloadReport() {
  downloadPdf(pdfFilename.value);
}
</script>

<style scoped>
body {
  overflow-y: hidden;
}
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

.progress-outer {
  height: 8px;
  background-color: #f1f5f9;
  border-radius: 9999px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
  display: inline-block;
  vertical-align: middle;
  flex-shrink: 0;
}
.progress-inner {
  height: 100%;
  border-radius: 9999px;
  transition: all 0.3s ease;
}
</style>
