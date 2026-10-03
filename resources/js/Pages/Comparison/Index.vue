<template>
  <AppLayout>
    <div class="w-full space-y-6">
      <!-- Header Banner -->
      <div class="card bg-base-100 border border-base-200 text-base-content shadow-xs">
        <div class="card-body p-6 flex-row items-center justify-between flex-wrap gap-4">
          <div class="space-y-1">
            <div class="badge badge-primary badge-outline text-xs">
              <span>Comparison Engine</span>
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight">Performance Comparison Tool</h1>
            <p class="text-xs opacity-75">Compare physical evaluation metrics and session progress across multiple athletes</p>
          </div>

          <div v-if="comparedAthletes.length > 0" class="flex items-center gap-2">
            <a :href="'/comparison?athlete_ids[]=' + selectedAthleteIds.join('&athlete_ids[]=') + '&preview_pdf=1&basis=' + props.basis" target="_blank" class="btn btn-success btn-sm gap-1.5 shadow-xs">
              <v-icon name="hi-document-text" class="size-[1.2em]" />
              <span>Export / Print PDF</span>
            </a>
            <a :href="'/comparison?athlete_ids[]=' + selectedAthleteIds.join('&athlete_ids[]=') + '&export_excel=1&basis=' + props.basis" class="btn btn-info btn-sm gap-1.5 shadow-xs">
              <v-icon name="hi-document-text" class="size-[1.2em]" />
              <span>Export Excel</span>
            </a>
            <Link href="/comparison" class="btn btn-outline btn-sm">
              Reset
            </Link>
          </div>
        </div>
      </div>

      <!-- Athlete Select Form -->
      <div class="card bg-base-100 border border-base-200 shadow-xs">
        <div class="card-body p-6 space-y-4">
          <label class="label text-xs font-bold p-0">
            Select Athletes to Compare
          </label>
          <div class="grid grid-cols-1 sm:grid-cols-6 gap-4 items-end">
            <div class="sm:col-span-2">
              <label class="label text-[11px] font-bold p-0 mb-1 opacity-70">Athlete A</label>
              <Select
                v-model="athleteA"
                :options="athleteAOptions"
                :filter-by="() => true"
                @search="fetchAthleteAOptions"
                placeholder="Search Athlete A..."
                class="w-full text-xs"
              />
            </div>
            <div class="sm:col-span-2">
              <label class="label text-[11px] font-bold p-0 mb-1 opacity-70">Athlete B</label>
              <Select
                v-model="athleteB"
                :options="athleteBOptions"
                :filter-by="() => true"
                @search="fetchAthleteBOptions"
                placeholder="Search Athlete B..."
                class="w-full text-xs"
              />
            </div>
            <div class="sm:col-span-1">
              <label class="label text-[11px] font-bold p-0 mb-1 opacity-70">Comparison Basis</label>
              <select v-model="basis" class="select select-bordered select-sm w-full text-xs">
                <option value="best">Best</option>
                <option value="latest">Latest</option>
              </select>
            </div>
            <div class="sm:col-span-1">
              <button @click="submitComparison" class="btn btn-primary btn-sm w-full gap-2 cursor-pointer shadow-xs">
                <v-icon name="hi-adjustments" class="size-[1.2em]" />
                <span>Compare</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Comparison Results Table -->
      <div v-if="comparedAthletes.length > 0" class="card bg-base-100 border border-base-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="table table-sm w-full text-xs">
            <thead>
              <tr class="bg-base-200 text-base-content font-bold">
                <th class="py-3.5 px-4 min-w-[180px]">Indicator / Parameter</th>
                <th v-for="col in columns" :key="col.key" class="py-3.5 px-4 text-center min-w-[160px] border-l border-base-300">
                  <p class="font-bold text-base-content leading-tight">{{ col.athlete.name }}</p>
                  <p class="text-[9.5px] text-base-content/60 font-normal mt-0.5">
                    {{ col.session ? (col.session.name + ' (' + (col.session.folder ? col.session.folder.name : 'No Folder') + ')') : 'No Session' }}
                  </p>
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-base-200 font-medium">
              <!-- Indicators Scores -->
              <tr v-for="ind in indicators" :key="ind.id" class="hover">
                <td class="py-3.5 px-4 font-bold text-base-content">
                  {{ ind.name }}
                  <span v-if="ind.unit" class="text-[10px] text-base-content/60 font-semibold block">({{ ind.unit }})</span>
                </td>
                <td v-for="col in columns" :key="col.key" class="py-3.5 px-4 text-center border-l border-base-200">
                  <div v-if="rawMatrix[ind.id] && rawMatrix[ind.id][col.key] !== null">
                    <span class="font-bold text-base-content text-sm block">{{ rawMatrix[ind.id][col.key] }}</span>
                    <span class="text-[10px] font-bold block mt-0.5" :style="{ color: getScoreColor(comparisonMatrix[ind.id]?.[col.key]) }">
                      Score: {{ comparisonMatrix[ind.id]?.[col.key] }}%
                    </span>
                  </div>
                  <span v-else class="text-base-content/40">-</span>
                </td>
              </tr>

              <!-- Overall Session Summary -->
              <tr class="bg-base-200/60 font-bold border-t-2 border-base-300">
                <td class="py-4 px-4 text-base-content font-extrabold text-[11px]">Overall Session Score</td>
                <td v-for="col in columns" :key="col.key" class="py-4 px-4 text-center border-l border-base-200 text-base">
                  <span v-if="overallPerColumn[col.key] !== null" :style="{ color: getScoreColor(overallPerColumn[col.key]) }">
                    {{ overallPerColumn[col.key] }}%
                  </span>
                  <span v-else class="text-base-content/40 text-xs">-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-else class="card bg-base-100 border border-base-200 shadow-xs text-center p-12 space-y-2">
        <v-icon name="hi-adjustments" class="w-12 h-12 text-base-content/30 mx-auto" />
        <h3 class="card-title text-base justify-center">No Athletes Selected for Comparison</h3>
        <p class="text-xs text-base-content/70 max-w-sm mx-auto">Select Athlete A and Athlete B above and click "Compare" to generate performance metrics comparison.</p>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { Select } from 'vue3-select-component';
import 'vue3-select-component/styles';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useSelect2Options } from '@/Composables/useSelect2Options';

const props = defineProps({
  allAthletes: { type: Array, default: () => [] },
  selectedAthleteIds: { type: Array, default: () => [] },
  comparedAthletes: { type: Array, default: () => [] },
  indicators: { type: Array, default: () => [] },
  columns: { type: Array, default: () => [] },
  comparisonMatrix: { type: Object, default: () => ({}) },
  rawMatrix: { type: Object, default: () => ({}) },
  overallPerColumn: { type: Object, default: () => ({}) },
  bestPerIndicator: { type: Object, default: () => ({}) },
  basis: { type: String, default: 'best' },
});

const athleteA = ref(props.selectedAthleteIds[0] || null);
const athleteB = ref(props.selectedAthleteIds[1] || null);
const basis = ref(props.basis || 'best');

const athleteAOptions = ref([]);
const athleteBOptions = ref([]);

const { fetchOptions: fetchAthletes } = useSelect2Options('athletes');

function mergeInitialOptions(fetchedOptions, selectedValue) {
  if (!selectedValue) return fetchedOptions;
  const opts = [...fetchedOptions];
  const exists = opts.some(o => Number(o.value) === Number(selectedValue));
  if (!exists) {
    let found = props.comparedAthletes.find(item => Number(item.id) === Number(selectedValue) || Number(item.athlete?.id) === Number(selectedValue));
    if (!found && props.allAthletes) {
      found = props.allAthletes.find(item => Number(item.id) === Number(selectedValue));
    }
    if (found) {
      const athleteObj = found.athlete || found;
      const label = `${athleteObj.name} (${athleteObj.athlete_number || 'ID: ' + athleteObj.id})`;
      opts.push({ label, value: Number(athleteObj.id) });
    }
  }
  return opts;
}

async function fetchAthleteAOptions(q = '') {
  const results = await fetchAthletes(q);
  athleteAOptions.value = q ? results : mergeInitialOptions(results, athleteA.value);
}

async function fetchAthleteBOptions(q = '') {
  const results = await fetchAthletes(q);
  athleteBOptions.value = q ? results : mergeInitialOptions(results, athleteB.value);
}

onMounted(() => {
  fetchAthleteAOptions();
  fetchAthleteBOptions();
});

function getScoreColor(v) {
  if (v === null || v === undefined) return '#68758A';
  if (v >= 85) return '#06A77D';
  if (v >= 70) return '#5B8DEF';
  if (v >= 55) return '#F4A100';
  return '#E63946';
}

function submitComparison() {
  const ids = [];
  if (athleteA.value) ids.push(athleteA.value);
  if (athleteB.value) ids.push(athleteB.value);
  router.get('/comparison', { athlete_ids: ids, basis: basis.value }, { preserveState: true, replace: true });
}
</script>
