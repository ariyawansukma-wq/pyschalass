<template>
  <AppLayout>
    <div class="w-full space-y-6 pb-8">
      <!-- Breadcrumb & Top Navigation -->
      <div class="flex items-center justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-2 text-xs font-semibold text-base-content/60">
          <Link href="/folders" class="hover:text-primary transition-colors inline-flex items-center gap-1">
            <v-icon name="hi-folder" class="w-4 h-4" />
            <span>Data Folders</span>
          </Link>
          <span class="text-base-content/30">/</span>
          <span class="text-base-content font-bold truncate max-w-xs sm:max-w-md">{{ folder.name }}</span>
        </div>

        <div class="flex items-center gap-2">
          <Link href="/folders" class="btn btn-ghost btn-xs gap-1">
            <v-icon name="hi-arrow-left" class="size-[1.2em]" />
            <span>All Folders</span>
          </Link>
        </div>
      </div>

      <!-- Header Banner -->
      <div class="card bg-base-100 border border-base-200 text-base-content shadow-xs">
        <div class="card-body p-6 flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
          <div class="space-y-2 max-w-3xl">
            <div class="flex items-center gap-2 flex-wrap">
              <div class="badge badge-primary badge-outline text-xs gap-1 font-bold">
                <v-icon name="hi-folder" class="w-3.5 h-3.5" />
                <span>Folder Analytics</span>
              </div>
              <span class="text-xs text-base-content/60 font-medium">Created: {{ formatDate(folder.created_at) }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-base-content">
              {{ folder.name }}
            </h1>
            <p class="text-xs sm:text-sm text-base-content/70 font-medium leading-relaxed">
              Performance statistics, sport branch benchmarks, and test session evaluation results isolated for this folder batch.
            </p>
          </div>

          <!-- Action Buttons Group -->
          <div class="flex items-center gap-2 flex-wrap shrink-0">
            <button
              type="button"
              @click="openPdfModal"
              class="btn btn-success btn-sm gap-1.5 font-bold shadow-xs hover:scale-[1.02] active:scale-[0.98] transition-transform cursor-pointer"
            >
              <v-icon name="hi-document-text" class="w-4 h-4" />
              <span>Export PDF Report</span>
            </button>

            <Link
              :href="'/athletes?folder_id=' + folder.id"
              class="btn btn-primary btn-sm gap-1.5 font-bold shadow-xs hover:scale-[1.02] active:scale-[0.98] transition-transform"
            >
              <v-icon name="hi-users" class="w-4 h-4" />
              <span>View Directory</span>
            </Link>
          </div>
        </div>
      </div>

      <!-- 6 Tactile KPI Stat Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <!-- Card 1: Total Athletes -->
        <div class="group card bg-base-100 border border-base-200/80 rounded-2xl shadow-xs p-4 hover:-translate-y-1 hover:shadow-md hover:border-emerald-500/40 transition-all duration-200 relative overflow-hidden">
          <div class="absolute top-0 left-0 right-0 h-1 bg-emerald-500"></div>
          <div class="flex items-center justify-between gap-2 mb-2">
            <span class="text-[11px] font-extrabold uppercase tracking-wider text-base-content/60">Folder Capacity</span>
            <div class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center group-hover:scale-110 transition-transform">
              <v-icon name="hi-users" class="w-5 h-5" />
            </div>
          </div>
          <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 tracking-tight">{{ kpi.total_athletes || 0 }}</div>
          <div class="text-[10px] text-base-content/50 font-semibold mt-1">Athletes in folder</div>
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
            <span class="text-[11px] font-extrabold uppercase tracking-wider text-base-content/60">Folder Avg</span>
            <div class="w-9 h-9 rounded-xl bg-violet-500/10 text-violet-600 dark:text-violet-400 flex items-center justify-center group-hover:scale-110 transition-transform">
              <v-icon name="hi-adjustments" class="w-5 h-5" />
            </div>
          </div>
          <div class="text-2xl font-black text-violet-600 dark:text-violet-400 tracking-tight">
            {{ kpi.average_score ? Number(kpi.average_score).toFixed(1) + '%' : '0%' }}
          </div>
          <div class="text-[10px] text-base-content/50 font-semibold mt-1">Mean batch score</div>
        </div>

        <!-- Card 5: Sport Branches -->
        <div class="group card bg-base-100 border border-base-200/80 rounded-2xl shadow-xs p-4 hover:-translate-y-1 hover:shadow-md hover:border-amber-500/40 transition-all duration-200 relative overflow-hidden">
          <div class="absolute top-0 left-0 right-0 h-1 bg-amber-500"></div>
          <div class="flex items-center justify-between gap-2 mb-2">
            <span class="text-[11px] font-extrabold uppercase tracking-wider text-base-content/60">Disciplines</span>
            <div class="w-9 h-9 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center group-hover:scale-110 transition-transform">
              <v-icon name="bi-trophy" class="w-5 h-5" />
            </div>
          </div>
          <div class="text-2xl font-black text-amber-600 dark:text-amber-400 tracking-tight">{{ kpi.total_sports || 0 }}</div>
          <div class="text-[10px] text-base-content/50 font-semibold mt-1">Active sport branches</div>
        </div>

        <!-- Card 6: Test Sessions -->
        <div class="group card bg-base-100 border border-base-200/80 rounded-2xl shadow-xs p-4 hover:-translate-y-1 hover:shadow-md hover:border-cyan-500/40 transition-all duration-200 relative overflow-hidden">
          <div class="absolute top-0 left-0 right-0 h-1 bg-cyan-500"></div>
          <div class="flex items-center justify-between gap-2 mb-2">
            <span class="text-[11px] font-extrabold uppercase tracking-wider text-base-content/60">Sessions</span>
            <div class="w-9 h-9 rounded-xl bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 flex items-center justify-center group-hover:scale-110 transition-transform">
              <v-icon name="hi-calendar" class="w-5 h-5" />
            </div>
          </div>
          <div class="text-2xl font-black text-cyan-600 dark:text-cyan-400 tracking-tight">{{ kpi.total_sessions || 0 }}</div>
          <div class="text-[10px] text-base-content/50 font-semibold mt-1">Recorded test events</div>
        </div>
      </div>

      <!-- Analytics Charts Section -->
      <div class="space-y-6">
        <!-- Chart 1: Total Athletes per Sport Branch -->
        <div class="card bg-base-100 border border-base-200/80 rounded-2xl shadow-xs overflow-hidden">
          <div class="px-5 py-4 border-b border-base-200/60 bg-base-200/30 flex items-center justify-between gap-2 flex-wrap">
            <div class="flex items-center gap-2">
              <div class="w-2.5 h-2.5 rounded-full bg-emerald-500"></div>
              <h3 class="font-bold text-sm text-base-content">Athletes per Sport Branch in {{ folder.name }}</h3>
            </div>
            <span class="text-[10px] font-bold text-base-content/50 uppercase tracking-wider">Capacity Distribution</span>
          </div>
          <div class="p-5">
            <div v-if="hasSportCountData" class="h-64 sm:h-72">
              <canvas ref="chartCountRef"></canvas>
            </div>
            <div v-else class="h-56 sm:h-64 flex flex-col items-center justify-center text-center p-6 border border-dashed border-base-content/15 rounded-xl bg-base-200/20">
              <div class="w-12 h-12 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-3">
                <v-icon name="hi-users" class="w-6 h-6" />
              </div>
              <p class="text-sm font-bold text-base-content/80">No Athletes Registered in this Folder</p>
              <p class="text-xs text-base-content/50 max-w-xs mt-1">Capacity distribution will display here once athletes are assigned to this folder.</p>
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
            <div v-else class="h-56 sm:h-64 flex flex-col items-center justify-center text-center p-6 border border-dashed border-base-content/15 rounded-xl bg-base-200/20">
              <div class="w-12 h-12 rounded-full bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center mb-3">
                <v-icon name="bi-trophy" class="w-6 h-6" />
              </div>
              <p class="text-sm font-bold text-base-content/80">No Sport Performance Data Available</p>
              <p class="text-xs text-base-content/50 max-w-xs mt-1">Performance scores will be calculated once physical test sessions are recorded in this folder.</p>
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
              <div v-else class="h-56 sm:h-64 flex flex-col items-center justify-center text-center p-6 border border-dashed border-base-content/15 rounded-xl bg-base-200/20">
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
                <h3 class="font-bold text-sm text-base-content">Average Performance by Age Group</h3>
              </div>
              <span class="text-[10px] font-bold text-base-content/50 uppercase tracking-wider">Age Categories</span>
            </div>
            <div class="p-5">
              <div v-if="hasAgeGroupData" class="h-64 sm:h-72">
                <canvas ref="chartAgeRef"></canvas>
              </div>
              <div v-else class="h-56 sm:h-64 flex flex-col items-center justify-center text-center p-6 border border-dashed border-base-content/15 rounded-xl bg-base-200/20">
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

      <!-- Sessions & Athlete Leaderboard Grid -->
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- Test Sessions in Folder (1 col) -->
        <div class="card bg-base-100 border border-base-200/80 rounded-2xl shadow-xs overflow-hidden flex flex-col">
          <div class="px-5 py-4 border-b border-base-200/60 bg-base-200/30 flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
              <v-icon name="hi-calendar" class="w-4 h-4 text-primary" />
              <h3 class="font-bold text-sm text-base-content">Folder Test Sessions</h3>
            </div>
            <span class="badge badge-sm badge-ghost font-bold">{{ sessions.length }} Sessions</span>
          </div>

          <div class="p-4 flex-1 overflow-y-auto max-h-96 space-y-3">
            <div
              v-for="session in sessions"
              :key="session.id"
              class="p-3 rounded-xl border border-base-200 bg-base-200/30 hover:bg-base-200/70 transition-colors flex items-center justify-between gap-3"
            >
              <div class="flex items-center gap-3 min-w-0">
                <div
                  class="w-3.5 h-3.5 rounded-full shrink-0 shadow-xs"
                  :style="{ backgroundColor: session.color || '#3B82F6' }"
                ></div>
                <div class="min-w-0">
                  <p class="text-xs font-bold text-base-content truncate">{{ session.name }}</p>
                  <p class="text-[10px] text-base-content/60 font-medium">
                    {{ session.date_time ? formatDate(session.date_time) : '-' }}
                    <span v-if="session.location">· {{ session.location }}</span>
                  </p>
                </div>
              </div>
              <div class="shrink-0 text-right">
                <span class="badge badge-sm badge-outline font-semibold text-[10px]">
                  {{ session.trials_count || 0 }} Trials
                </span>
              </div>
            </div>

            <div v-if="!sessions || sessions.length === 0" class="text-center py-8 text-base-content/50 text-xs font-medium">
              No test sessions registered in this folder yet.
            </div>
          </div>
        </div>

        <!-- Top Athletes Leaderboard (2 cols) -->
        <div class="xl:col-span-2 card bg-base-100 border border-base-200/80 rounded-2xl shadow-xs overflow-hidden flex flex-col">
          <div class="px-5 py-4 border-b border-base-200/60 bg-base-200/30 flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-2">
              <v-icon name="bi-trophy" class="w-4 h-4 text-amber-500" />
              <h3 class="font-bold text-sm text-base-content">Athlete Performance Rankings</h3>
            </div>
            <div class="flex items-center gap-2.5 flex-wrap">
              <!-- Search ranking input with Debounce -->
              <div class="relative">
                <input
                  type="text"
                  v-model="rankingSearch"
                  @input="onRankingSearchInput"
                  placeholder="Search athlete..."
                  class="input input-bordered input-xs w-36 sm:w-48 pl-7 text-xs"
                />
                <v-icon name="hi-search" class="w-3.5 h-3.5 text-base-content/50 absolute left-2 top-1.5" />
              </div>

              <!-- Per page select -->
              <select
                v-model="rankingPerPage"
                @change="onRankingPerPageChange"
                class="select select-bordered select-xs text-xs"
              >
                <option :value="5">5 / page</option>
                <option :value="10">10 / page</option>
                <option :value="25">25 / page</option>
                <option :value="50">50 / page</option>
                <option :value="100">100 / page</option>
              </select>

              <Link :href="'/athletes?folder_id=' + folder.id" class="btn btn-ghost btn-xs gap-1 text-primary">
                <span>Directory</span>
                <v-icon name="hi-arrow-right" class="size-[1.1em]" />
              </Link>
            </div>
          </div>

          <div class="overflow-x-auto flex-1">
            <table class="table table-sm w-full">
              <thead>
                <tr class="text-[11px] uppercase tracking-wider text-base-content/60 border-b border-base-200 bg-base-200/20">
                  <th class="w-12 text-center">Rank</th>
                  <th>Athlete</th>
                  <th>Sport Branch</th>
                  <th>Gender / Age</th>
                  <th>BMI</th>
                  <th class="text-right">Overall Score</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="item in (athleteScores.data || [])"
                  :key="item.athlete.id"
                  class="hover:bg-base-200/40 border-b border-base-200/50 transition-colors"
                >
                  <td class="text-center font-black text-xs">
                    <span
                      v-if="item.rank === 1"
                      class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-500/20 text-amber-600 dark:text-amber-400 font-extrabold text-xs"
                    >1</span>
                    <span
                      v-else-if="item.rank === 2"
                      class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-400/20 text-slate-600 dark:text-slate-300 font-extrabold text-xs"
                    >2</span>
                    <span
                      v-else-if="item.rank === 3"
                      class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-700/20 text-amber-700 dark:text-amber-500 font-extrabold text-xs"
                    >3</span>
                    <span v-else class="text-base-content/50 text-xs">#{{ item.rank }}</span>
                  </td>
                  <td>
                    <Link :href="'/athletes/' + item.athlete.id + '/edit'" class="hover:text-primary transition-colors block">
                      <div class="font-bold text-xs text-base-content leading-tight">{{ item.athlete.name }}</div>
                      <div class="text-[10px] text-base-content/50 font-mono">{{ item.athlete.athlete_number || '-' }}</div>
                    </Link>
                  </td>
                  <td>
                    <span class="badge badge-sm badge-ghost text-[10px] font-semibold">
                      {{ item.athlete.sport_branch?.name || '-' }}
                    </span>
                  </td>
                  <td>
                    <div class="text-xs font-semibold text-base-content">
                      <span :class="item.athlete.gender === 'M' ? 'text-sky-600 font-bold' : 'text-pink-600 font-bold'">
                        {{ item.athlete.gender === 'M' ? 'Male (L)' : 'Female (P)' }}
                      </span>
                    </div>
                    <div class="text-[10px] text-base-content/50">{{ item.age ? item.age + ' yo' : '-' }}</div>
                  </td>
                  <td>
                    <span
                      v-if="item.bmi_category"
                      class="badge badge-sm text-[9.5px] font-bold"
                      :class="{
                        'badge-info text-info-content': item.bmi_category === 'Underweight',
                        'badge-success text-success-content': item.bmi_category === 'Healthy Weight',
                        'badge-warning text-warning-content': item.bmi_category === 'At Risk of Overweight',
                        'badge-error text-error-content': item.bmi_category === 'Overweight'
                      }"
                    >
                      {{ item.bmi_category }}
                    </span>
                    <span v-else class="text-[10px] text-base-content/40">-</span>
                  </td>
                  <td class="text-right">
                    <div class="inline-flex flex-col items-end gap-1">
                      <span
                        class="font-black text-xs px-2 py-0.5 rounded-md"
                        :class="{
                          'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400': item.overall_score >= 80,
                          'bg-sky-500/15 text-sky-600 dark:text-sky-400': item.overall_score >= 65 && item.overall_score < 80,
                          'bg-amber-500/15 text-amber-600 dark:text-amber-400': item.overall_score >= 50 && item.overall_score < 65,
                          'bg-rose-500/15 text-rose-600 dark:text-rose-400': item.overall_score < 50 && item.overall_score !== null,
                          'text-base-content/40': item.overall_score === null
                        }"
                      >
                        {{ item.overall_score !== null ? Number(item.overall_score).toFixed(1) + '%' : '-' }}
                      </span>
                    </div>
                  </td>
                </tr>

                <tr v-if="!athleteScores.data || athleteScores.data.length === 0">
                  <td colspan="6" class="text-center py-8 text-base-content/50 text-xs font-medium">
                    No athlete scores recorded in this folder.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Server Pagination Footer -->
          <div class="px-5 py-3 border-t border-base-200/60 bg-base-200/20 flex items-center justify-between gap-2 flex-wrap text-xs">
            <span class="text-base-content/60 font-medium">
              Showing <span class="font-bold text-base-content">{{ athleteScores.from || 0 }}</span> to <span class="font-bold text-base-content">{{ athleteScores.to || 0 }}</span> of <span class="font-bold text-base-content">{{ athleteScores.total || 0 }}</span> athletes
            </span>

            <div v-if="athleteScores.last_page > 1" class="join">
              <button
                type="button"
                class="join-item btn btn-xs"
                :disabled="athleteScores.current_page <= 1"
                @click="goToPage(athleteScores.current_page - 1)"
              >
                <v-icon name="hi-chevron-left" class="size-[1.2em]" />
              </button>
              
              <button
                v-for="p in visiblePages"
                :key="p"
                type="button"
                class="join-item btn btn-xs"
                :class="{ 'btn-primary': p === athleteScores.current_page }"
                @click="goToPage(p)"
              >
                {{ p }}
              </button>

              <button
                type="button"
                class="join-item btn btn-xs"
                :disabled="athleteScores.current_page >= athleteScores.last_page"
                @click="goToPage(athleteScores.current_page + 1)"
              >
                <v-icon name="hi-chevron-right" class="size-[1.2em]" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Export PDF Selection Modal -->
    <dialog :class="['modal', { 'modal-open': showPdfModal }]">
      <div class="modal-box max-w-sm space-y-4 !overflow-visible">
        <div class="flex items-center justify-between pb-2 border-b border-base-200">
          <h3 class="font-extrabold text-base">Export Folder PDF Report</h3>
          <button type="button" @click="showPdfModal = false" class="btn btn-outline btn-square btn-xs">
            <v-icon name="hi-x" class="size-[1.2em]" />
          </button>
        </div>

        <div class="space-y-3">
          <div class="flex items-center gap-3 p-3 bg-base-200 rounded-lg">
            <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold shrink-0">
              <v-icon name="hi-folder" class="w-6 h-6" />
            </div>
            <div class="min-w-0">
              <p class="font-bold text-sm truncate">{{ folder.name }}</p>
              <p class="text-xs text-base-content/60">{{ kpi.total_athletes || 0 }} Athletes · {{ sessions.length }} Sessions</p>
            </div>
          </div>

          <div class="form-control">
            <label class="label text-xs font-bold p-0 mb-1">Select Institution / Letterhead <span class="text-error">*</span></label>
            <Select
              v-model="selectedInstitutionId"
              :options="institutionOptions"
              :filter-by="() => true"
              @search="fetchInstitutionOptions"
              placeholder="Search or Select Institution..."
              class="w-full text-xs"
              :teleport="false"
            />
          </div>
        </div>

        <div class="modal-action">
          <button type="button" @click="showPdfModal = false" class="btn btn-outline btn-xs">Cancel</button>
          <button
            type="button"
            @click="generateFolderPdf"
            :disabled="!selectedInstitutionId"
            class="btn btn-success btn-xs gap-1"
          >
            <v-icon name="hi-document-text" class="size-[1.2em]" />
            <span>Generate PDF</span>
          </button>
        </div>
      </div>
      <form method="dialog" class="modal-backdrop" @click="showPdfModal = false">
        <button>close</button>
      </form>
    </dialog>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { Select } from 'vue3-select-component';
import 'vue3-select-component/styles';
import Chart from 'chart.js/auto';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useSelect2Options } from '@/Composables/useSelect2Options';

const props = defineProps({
  folder: { type: Object, required: true },
  kpi: { type: Object, default: () => ({}) },
  performanceBySport: { type: Array, default: () => [] },
  performanceByGender: { type: Array, default: () => [] },
  athletesBySport: { type: Array, default: () => [] },
  performanceByAgeGroup: { type: Array, default: () => [] },
  sessions: { type: Array, default: () => [] },
  athleteScores: { type: Object, default: () => ({ data: [] }) },
  filters: { type: Object, default: () => ({}) },
  institutions: { type: Array, default: () => [] },
});

const showPdfModal = ref(false);
const selectedInstitutionId = ref(props.institutions?.[0]?.id || '');

const { options: institutionOptions, fetchOptions: fetchInstitutionOptions } = useSelect2Options('institutions');

function openPdfModal() {
  if (!selectedInstitutionId.value && props.institutions?.[0]?.id) {
    selectedInstitutionId.value = props.institutions[0].id;
  }
  showPdfModal.value = true;
}

function generateFolderPdf() {
  if (!selectedInstitutionId.value) return;
  const url = `/folders/${props.folder.id}/export-pdf?institution_id=${selectedInstitutionId.value}`;
  window.open(url, '_blank');
  showPdfModal.value = false;
}

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

// Server-Side Rankings Search with Debounce & Pagination
const rankingSearch = ref(props.filters.search || '');
const rankingPerPage = ref(Number(props.filters.per_page) || props.athleteScores.per_page || 10);

let searchTimeout = null;
function onRankingSearchInput() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchRankings({ search: rankingSearch.value, page: 1 });
  }, 350);
}

function onRankingPerPageChange() {
  fetchRankings({ per_page: rankingPerPage.value, page: 1 });
}

function goToPage(page) {
  if (page < 1 || page > props.athleteScores.last_page || page === props.athleteScores.current_page) return;
  fetchRankings({ page });
}

function fetchRankings(overrides = {}) {
  const params = {
    search: rankingSearch.value || undefined,
    per_page: rankingPerPage.value || undefined,
    page: props.athleteScores.current_page || 1,
    ...overrides,
  };

  Object.keys(params).forEach(k => {
    if (params[k] === undefined || params[k] === '') delete params[k];
  });

  router.get(`/folders/${props.folder.id}`, params, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

const visiblePages = computed(() => {
  const current = props.athleteScores.current_page || 1;
  const total = props.athleteScores.last_page || 1;
  if (total <= 7) {
    return Array.from({ length: total }, (_, i) => i + 1);
  }
  const start = Math.max(1, Math.min(current - 2, total - 4));
  const end = Math.min(total, start + 4);
  const pages = [];
  for (let i = start; i <= end; i++) {
    pages.push(i);
  }
  return pages;
});

function formatDate(dStr) {
  if (!dStr) return '-';
  const d = new Date(dStr);
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

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
  if (props.institutions && props.institutions.length > 0) {
    institutionOptions.value = props.institutions.map(inst => ({
      label: inst.name,
      value: Number(inst.id),
    }));
  } else {
    fetchInstitutionOptions();
  }

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
