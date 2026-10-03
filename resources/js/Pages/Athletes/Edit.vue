<template>
  <AppLayout>
    <div class="w-full space-y-6">
      <!-- Header Banner -->
      <div class="card bg-base-100 border border-base-200 text-base-content shadow-xs">
        <div class="card-body p-6 flex-row items-center justify-between flex-wrap gap-4">
          <div class="space-y-1">
            <div class="badge badge-primary badge-outline text-xs">
              <span>Management Module</span>
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight">Edit Athlete - {{ athlete.name }}</h1>
            <p class="text-xs opacity-75">Update athlete profile details and physical assessment records</p>
          </div>
          <Link href="/athletes" class="btn btn-outline btn-primary btn-sm gap-2 shadow-xs">
            <v-icon name="hi-arrow-left" class="size-[1.2em]" />
            <span>Back to Directory</span>
          </Link>
        </div>
      </div>

      <form @submit.prevent="submitForm" class="space-y-6">
        <!-- Step 1: Sport & Data Folder -->
        <div class="card bg-base-100 border border-base-200 shadow-xs">
          <div class="card-body p-6 space-y-4">
            <div class="flex items-center gap-2.5 border-b border-base-200 pb-3">
              <span class="badge badge-primary font-extrabold p-2.5">1</span>
              <h3 class="card-title text-base font-extrabold">Sport Branch & Data Folder</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
              <div class="col-span-1 sm:col-span-2">
                <label class="label text-xs font-bold p-0 mb-1">Sport Branch <span class="text-error">*</span></label>
                <Select
                  v-model="form.sport_branch_id"
                  :options="sportBranchOptions"
                  :filter-by="() => true"
                  @search="fetchBranchOptions"
                  placeholder="Choose Sport Branch"
                  class="w-full text-xs"
                />
              </div>

              <div class="col-span-1 sm:col-span-2">
                <label class="label text-xs font-bold p-0 mb-1">Data Folder <span class="text-error">*</span></label>
                <Select
                  v-model="form.folder_id"
                  :options="folderOptions"
                  :filter-by="() => true"
                  @search="fetchFolderOptions"
                  placeholder="Select folder..."
                  class="w-full text-xs"
                />
                <p v-if="form.errors.folder_id" class="text-error text-xs mt-1">{{ form.errors.folder_id }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 2: Athlete Profile -->
        <div class="card bg-base-100 border border-base-200 shadow-xs">
          <div class="card-body p-6 space-y-4">
            <div class="flex items-center gap-2.5 border-b border-base-200 pb-3">
              <span class="badge badge-primary font-extrabold p-2.5">2</span>
              <h3 class="card-title text-base font-extrabold">Athlete Profile & Identity</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
              <div class="form-control">
                <label for="athlete_number" class="label text-xs font-bold p-0 mb-1">Athlete Number / ID</label>
                <input type="text" id="athlete_number" v-model="form.athlete_number" class="input input-bordered input-sm w-full">
              </div>

              <div class="form-control">
                <label for="name" class="label text-xs font-bold p-0 mb-1">Full Name <span class="text-error">*</span></label>
                <input type="text" id="name" v-model="form.name" required class="input input-bordered input-sm w-full">
              </div>

              <div class="form-control">
                <label for="date_of_birth" class="label text-xs font-bold p-0 mb-1">Date of Birth <span class="text-error">*</span></label>
                <VueDatePicker
                  v-model="form.date_of_birth"
                  placeholder="Choose Date of Birth"
                  input-class="input-sm"
                  model-type="yyyy-MM-dd"
                  format="dd MMM yyyy"
                  :enable-time-picker="false"
                  auto-apply
                  :teleport="true"
                />
              </div>

              <div class="form-control">
                <label for="event_number" class="label text-xs font-bold p-0 mb-1">Event Class / Category</label>
                <input type="text" id="event_number" v-model="form.event_number" placeholder="e.g.: 50m Freestyle / Weight Class 60kg" class="input input-bordered input-sm w-full">
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-center">
              <div>
                <label class="label text-xs font-bold p-0 mb-1">Gender <span class="text-error">*</span></label>
                <div class="flex items-center gap-6 pt-1">
                  <label class="label cursor-pointer gap-2 p-0">
                    <input type="radio" v-model="form.gender" value="M" required class="radio radio-primary radio-sm">
                    <span class="label-text text-xs font-bold">Male (M)</span>
                  </label>
                  <label class="label cursor-pointer gap-2 p-0">
                    <input type="radio" v-model="form.gender" value="F" required class="radio radio-primary radio-sm">
                    <span class="label-text text-xs font-bold">Female (F)</span>
                  </label>
                </div>
              </div>

              <div class="sm:col-span-2">
                <label for="photo" class="label text-xs font-bold p-0 mb-1">Update Profile Photo</label>
                <div class="flex items-center gap-3">
                  <div v-if="photoPreviewUrl" class="avatar">
                    <div class="w-12 h-12 rounded-full ring ring-primary ring-offset-base-100 ring-offset-2">
                      <img :src="photoPreviewUrl" class="object-cover" />
                    </div>
                  </div>
                  <input type="file" id="photo" @change="onPhotoChange" accept="image/*" class="file-input file-input-bordered file-input-sm flex-1 text-xs">
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 3: Test Sessions Matrix -->
        <div class="card bg-base-100 border border-base-200 shadow-xs">
          <div class="card-body p-6 space-y-4">
            <div class="flex items-center justify-between flex-wrap gap-2 border-b border-base-200 pb-3">
              <div class="flex items-center gap-2.5">
                <span class="badge badge-primary font-extrabold p-2.5">3</span>
                <div>
                  <h3 class="card-title text-base font-extrabold">Test Sessions</h3>
                  <p class="text-xs text-base-content/70 font-medium mt-0.5">Aligned comparison matrix for Height, Weight, BMI & Physical Indicators</p>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <button type="button" @click="addNewTestSession" class="btn btn-secondary btn-sm gap-1.5 cursor-pointer">
                  <v-icon name="hi-plus-circle" class="size-[1.2em]" />
                  <span>Add Test Session</span>
                </button>
              </div>
            </div>

            <div v-if="isLoadingSessions" class="p-6 space-y-4 border border-base-200 rounded-box bg-base-50/50">
              <div class="flex gap-4">
                <div class="skeleton h-10 w-44 shrink-0 rounded-lg"></div>
                <div class="skeleton h-10 w-44 shrink-0 rounded-lg"></div>
                <div class="skeleton h-10 w-44 shrink-0 rounded-lg"></div>
              </div>
              <div class="skeleton h-8 w-full rounded-md"></div>
              <div class="skeleton h-8 w-full rounded-md"></div>
              <div class="skeleton h-8 w-full rounded-md"></div>
              <div class="skeleton h-8 w-full rounded-md"></div>
            </div>

            <div v-else class="overflow-x-auto border border-base-200 rounded-box">
              <table class="table table-sm w-full text-xs">
                <thead>
                  <tr class="bg-base-200 text-base-content font-bold">
                    <th class="min-w-[160px] py-3 px-4">Parameter / Test</th>
                    <template v-for="sIdx in sessionCount" :key="sIdx">
                      <th class="min-w-[200px] py-3.5 px-4 text-center border-r border-base-300">
                        <div class="flex items-center justify-center gap-1.5">
                          <input type="color" v-model="form.sessions[sIdx].color" class="w-5 h-5 rounded cursor-pointer border-0 bg-transparent shrink-0" />
                          <textarea v-model="form.sessions[sIdx].name" v-auto-resize rows="1" class="text-center font-bold text-xs bg-transparent text-base-content border-b border-base-300 focus:outline-none px-1 py-0.5 resize-none flex-1 min-w-0 overflow-hidden leading-tight" placeholder="Session Name"></textarea>
                          <button type="button" @click="removeTestSession(sIdx)" class="btn btn-outline btn-error btn-square btn-xs" title="Delete Session">
                            <v-icon name="hi-trash" class="size-[1.2em]" />
                          </button>
                        </div>
                        <div class="mt-1.5 font-normal">
                          <VueDatePicker
                            v-model="form.sessions[sIdx].date"
                            placeholder="Choose Date"
                            input-class="input-xs"
                            model-type="yyyy-MM-dd"
                            format="dd MMM yyyy"
                            :enable-time-picker="false"
                            auto-apply
                            :teleport="true"
                          />
                        </div>
                      </th>
                      <th v-if="sIdx > 1" class="min-w-[120px] py-3.5 px-3 text-center bg-warning/10 text-warning-content border-r border-base-300 align-middle">
                        <div class="text-[11px] font-extrabold uppercase tracking-wider">PROGRESS</div>
                        <div class="text-[10px] font-semibold opacity-80 break-words whitespace-normal max-w-[150px] mx-auto">vs {{ form.sessions[sIdx - 1]?.name || ('Test ' + (sIdx - 1)) }}</div>
                      </th>
                    </template>
                  </tr>
                </thead>
                <tbody class="divide-y divide-base-200">
                  <tr>
                    <td class="py-3 px-4 font-bold text-base-content">Height (cm)</td>
                    <template v-for="sIdx in sessionCount" :key="sIdx">
                      <td class="py-3 px-4">
                        <input type="text" v-model="form.sessions[sIdx].height" placeholder="cm" class="input input-bordered input-xs text-center w-full">
                      </td>
                      <td v-if="sIdx > 1" class="py-3 px-3 text-center bg-warning/5 font-semibold text-xs border-r border-base-300 align-middle">
                        <div v-if="calcDiff(form.sessions[sIdx]?.height, form.sessions[sIdx - 1]?.height) !== null" class="inline-flex items-center justify-center gap-0.5" :class="getDeltaClass(calcDiff(form.sessions[sIdx]?.height, form.sessions[sIdx - 1]?.height))">
                          <v-icon :name="getDeltaIcon(calcDiff(form.sessions[sIdx]?.height, form.sessions[sIdx - 1]?.height))" class="w-3.5 h-3.5 shrink-0" />
                          <span class="font-extrabold text-[11px]">{{ getDeltaLabel(calcDiff(form.sessions[sIdx]?.height, form.sessions[sIdx - 1]?.height), ' cm') }}</span>
                        </div>
                        <span v-else class="text-base-content/40 font-normal">-</span>
                      </td>
                    </template>
                  </tr>
                  <tr>
                    <td class="py-3 px-4 font-bold text-base-content">Weight (kg)</td>
                    <template v-for="sIdx in sessionCount" :key="sIdx">
                      <td class="py-3 px-4">
                        <input type="text" v-model="form.sessions[sIdx].weight" placeholder="kg" class="input input-bordered input-xs text-center w-full">
                      </td>
                      <td v-if="sIdx > 1" class="py-3 px-3 text-center bg-warning/5 font-semibold text-xs border-r border-base-300 align-middle">
                        <div v-if="calcDiff(form.sessions[sIdx]?.weight, form.sessions[sIdx - 1]?.weight) !== null" class="inline-flex items-center justify-center gap-0.5" :class="getDeltaClass(calcDiff(form.sessions[sIdx]?.weight, form.sessions[sIdx - 1]?.weight))">
                          <v-icon :name="getDeltaIcon(calcDiff(form.sessions[sIdx]?.weight, form.sessions[sIdx - 1]?.weight))" class="w-3.5 h-3.5 shrink-0" />
                          <span class="font-extrabold text-[11px]">{{ getDeltaLabel(calcDiff(form.sessions[sIdx]?.weight, form.sessions[sIdx - 1]?.weight), ' kg') }}</span>
                        </div>
                        <span v-else class="text-base-content/40 font-normal">-</span>
                      </td>
                    </template>
                  </tr>
                  <tr>
                    <td class="py-3 px-4 font-bold text-base-content">BMI (Body Mass Index)</td>
                    <template v-for="sIdx in sessionCount" :key="sIdx">
                      <td class="py-3 px-4 text-center font-bold">
                        {{ calculateBMI(form.sessions[sIdx]?.height, form.sessions[sIdx]?.weight) || '-' }}
                      </td>
                      <td v-if="sIdx > 1" class="py-3 px-3 text-center bg-warning/5 font-semibold text-xs border-r border-base-300 align-middle">
                        <div v-if="calcDiff(calculateBMI(form.sessions[sIdx]?.height, form.sessions[sIdx]?.weight), calculateBMI(form.sessions[sIdx - 1]?.height, form.sessions[sIdx - 1]?.weight)) !== null" class="inline-flex items-center justify-center gap-0.5" :class="getDeltaClass(calcDiff(calculateBMI(form.sessions[sIdx]?.height, form.sessions[sIdx]?.weight), calculateBMI(form.sessions[sIdx - 1]?.height, form.sessions[sIdx - 1]?.weight)))">
                          <v-icon :name="getDeltaIcon(calcDiff(calculateBMI(form.sessions[sIdx]?.height, form.sessions[sIdx]?.weight), calculateBMI(form.sessions[sIdx - 1]?.height, form.sessions[sIdx - 1]?.weight)))" class="w-3.5 h-3.5 shrink-0" />
                          <span class="font-extrabold text-[11px]">{{ getDeltaLabel(calcDiff(calculateBMI(form.sessions[sIdx]?.height, form.sessions[sIdx]?.weight), calculateBMI(form.sessions[sIdx - 1]?.height, form.sessions[sIdx - 1]?.weight)), '') }}</span>
                        </div>
                        <span v-else class="text-base-content/40 font-normal">-</span>
                      </td>
                    </template>
                  </tr>

                  <!-- Indicators Rows -->
                  <tr v-for="ind in activeIndicators" :key="ind.id">
                    <td class="py-3 px-4 font-bold text-base-content">
                      {{ ind.name }}
                      <span v-if="ind.unit" class="badge badge-outline badge-xs ml-1">{{ ind.unit }}</span>
                    </td>
                    <template v-for="sIdx in sessionCount" :key="sIdx">
                      <td class="py-3 px-4">
                        <div class="space-y-1">
                          <div class="flex items-center justify-between text-[10px] text-base-content/70 font-semibold">
                            <span>BM: {{ getIndicatorPerf(sIdx, ind).benchVal ?? '-' }}</span>
                            <span class="font-bold" :style="{ color: getIndicatorPerf(sIdx, ind).color }">
                              {{ getIndicatorPerf(sIdx, ind).pct !== null ? getIndicatorPerf(sIdx, ind).pct + '%' : '-' }}
                            </span>
                          </div>
                          <input
                            type="text"
                            v-model="form.sessions[sIdx].indicators[ind.id]"
                            placeholder="Result"
                            class="input input-bordered input-xs text-center w-full"
                          />
                          <progress
                            class="progress h-1.5 w-full mt-1"
                            :class="getIndicatorProgressClass(getIndicatorPerf(sIdx, ind).pct)"
                            :value="getIndicatorPerf(sIdx, ind).pct || 0"
                            max="100"
                          ></progress>
                        </div>
                      </td>
                      <td v-if="sIdx > 1" class="py-3 px-3 text-center bg-warning/5 font-semibold text-xs border-r border-base-300 align-middle">
                        <div v-if="calcDiff(getIndicatorPerf(sIdx, ind).pct, getIndicatorPerf(sIdx - 1, ind).pct) !== null" class="inline-flex items-center justify-center gap-0.5" :class="getDeltaClass(calcDiff(getIndicatorPerf(sIdx, ind).pct, getIndicatorPerf(sIdx - 1, ind).pct))">
                          <v-icon :name="getDeltaIcon(calcDiff(getIndicatorPerf(sIdx, ind).pct, getIndicatorPerf(sIdx - 1, ind).pct))" class="w-3.5 h-3.5 shrink-0" />
                          <span class="font-extrabold text-[11px]">{{ getDeltaLabel(calcDiff(getIndicatorPerf(sIdx, ind).pct, getIndicatorPerf(sIdx - 1, ind).pct), '%') }}</span>
                        </div>
                        <span v-else class="text-base-content/40 font-normal">-</span>
                      </td>
                    </template>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Summary Cards -->
            <div class="flex items-center gap-4 flex-wrap pt-2">
              <div v-for="sIdx in sessionCount" :key="sIdx" class="p-3 bg-base-200/50 rounded-box border border-base-200 min-w-[130px]">
                <span class="text-[11px] text-base-content/70 block font-bold uppercase tracking-wider">Score: {{ form.sessions[sIdx]?.name || ('Test ' + sIdx) }}</span>
                <span class="text-lg font-extrabold mt-0.5 block" :style="{ color: getSessionOverall(sIdx).color }">
                  {{ getSessionOverall(sIdx).score !== null ? getSessionOverall(sIdx).score + '%' : '-' }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Submit Buttons -->
        <div class="card bg-base-100 border border-base-200 shadow-xs">
          <div class="card-body p-6 flex-row items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-2">
              <a :href="'/athletes/' + props.athlete.id + '/export-pdf?institution_id=' + (props.institutions?.[0]?.id || '')" target="_blank" class="btn btn-success btn-outline btn-sm gap-2">
                <v-icon name="hi-document-text" class="size-[1.2em]" />
                <span>PDF Report</span>
              </a>
              <a :href="'/athletes/' + props.athlete.id + '/export-excel'" class="btn btn-info btn-outline btn-sm gap-2">
                <v-icon name="hi-document-text" class="size-[1.2em]" />
                <span>Export Excel</span>
              </a>
            </div>
            <div class="flex items-center gap-2">
              <Link href="/athletes" class="btn btn-outline btn-sm">Cancel</Link>
              <button type="submit" :disabled="form.processing" class="btn btn-primary btn-sm gap-2 cursor-pointer">
                <span v-if="form.processing" class="loading loading-spinner loading-xs"></span>
                <template v-else>
                  <v-icon name="hi-check-circle" class="size-[1.2em]" />
                  <span>Update Athlete Data</span>
                </template>
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, inject, watch, onMounted } from 'vue';
import { useForm, Link, useHttp, router, usePage } from '@inertiajs/vue3';
import { Select } from 'vue3-select-component';
import 'vue3-select-component/styles';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useAbility } from '@/Composables/useAbility';

const swal = inject('$swal');
const http = useHttp({});
const page = usePage();
const { can } = useAbility();
const isOfficer = computed(() => !can('manage', 'all'));

const props = defineProps({
  athlete: { type: Object, required: true },
  sportBranches: { type: Array, default: () => [] },
  folders: { type: Array, default: () => [] },
  institutions: { type: Array, default: () => [] },
  existingSessions: { type: Array, default: () => [] },
  existingTrials: { type: Object, default: () => ({}) },
  existingAnthro: { type: Object, default: () => ({}) },
});

const sessionCount = ref(props.existingSessions.length || 1);
const isLoadingSessions = ref(false);
const selectedBranchData = ref(null);
const isLoadingBranch = ref(false);

const vAutoResize = {
  mounted(el) {
    el.style.height = 'auto';
    el.style.height = el.scrollHeight + 'px';
  },
  updated(el) {
    el.style.height = 'auto';
    el.style.height = el.scrollHeight + 'px';
  }
};
const SESSION_PALETTE = ['#FF6B35', '#06A77D', '#2563EB', '#7C3AED', '#DB2777', '#D97706', '#059669', '#4F46E5'];

// Folder switching
const urlParams = new URLSearchParams(window.location.search);
const activeFolderId = ref(urlParams.get('folder_id') ? Number(urlParams.get('folder_id')) : (props.athlete.folders?.[0]?.id || ''));

function computeSessions(existingSessions, existingAnthro, existingTrials) {
  const sessions = {};
  if (existingSessions && existingSessions.length > 0) {
    existingSessions.forEach((sess, idx) => {
      const sIdx = idx + 1;
      const anthro = existingAnthro?.[sess.id] || {};
      const trials = existingTrials?.[sess.id] || {};

      const indicatorsObj = {};
      Object.keys(trials).forEach(indId => {
        const trialList = trials[indId];
        if (Array.isArray(trialList) && trialList.length > 0) {
          indicatorsObj[indId] = trialList[0].value;
        }
      });

      sessions[sIdx] = {
        name: sess.name || `Test ${sIdx}`,
        date: sess.date_time ? sess.date_time.substring(0, 10) : new Date().toISOString().split('T')[0],
        color: sess.color || SESSION_PALETTE[(sIdx - 1) % SESSION_PALETTE.length],
        height: anthro.height || '',
        weight: anthro.weight || '',
        indicators: indicatorsObj,
      };
    });
  } else {
    sessions[1] = {
      name: 'Test 1',
      date: new Date().toISOString().split('T')[0],
      color: '#FF6B35',
      height: '',
      weight: '',
      indicators: {},
    };
  }
  return sessions;
}

const initialSessions = computeSessions(props.existingSessions, props.existingAnthro, props.existingTrials);

const form = useForm({
  sport_branch_id: props.athlete.sport_branch_id || '',
  folder_id: activeFolderId.value,
  original_folder_id: activeFolderId.value,
  athlete_number: props.athlete.athlete_number || '',
  name: props.athlete.name || '',
  date_of_birth: props.athlete.date_of_birth ? props.athlete.date_of_birth.substring(0, 10) : '',
  event_number: props.athlete.event_number || '',
  gender: props.athlete.gender || 'M',
  photo: null,
  sessions: initialSessions,
});

const initialBranch = props.sportBranches.find(b => Number(b.id) === Number(props.athlete.sport_branch_id || ''));
const sportBranchOptions = ref(initialBranch ? [{ label: initialBranch.name, value: Number(initialBranch.id) }] : []);

const initialFolder = props.folders.find(f => Number(f.id) === Number(activeFolderId.value));
const folderOptions = ref(initialFolder ? [{ label: initialFolder.name, value: Number(initialFolder.id) }] : []);

function mergeInitialOptions(fetchedOptions, selectedValue, allItems, labelKey = 'name') {
  const exists = fetchedOptions.some(o => Number(o.value) === Number(selectedValue));
  if (!exists && selectedValue) {
    const found = allItems.find(item => Number(item.id) === Number(selectedValue));
    if (found) {
      fetchedOptions.push({ label: found[labelKey], value: Number(found.id) });
    }
  }
  return fetchedOptions;
}

async function fetchBranchOptions(q = '') {
  try {
    const res = await http.get(`/api/select2/sport-branches?q=${encodeURIComponent(q)}`);
    let opts = res.results.map(r => ({ label: r.text, value: Number(r.id) }));
    if (!q && form.sport_branch_id) {
      opts = mergeInitialOptions(opts, form.sport_branch_id, props.sportBranches);
    }
    sportBranchOptions.value = opts;
  } catch (err) {
    console.error(err);
  }
}

async function fetchFolderOptions(q = '') {
  try {
    const res = await http.get(`/api/select2/folders?q=${encodeURIComponent(q)}`);
    let opts = res.results.map(r => ({ label: r.text, value: Number(r.id) }));
    if (!q && form.folder_id) {
      opts = mergeInitialOptions(opts, form.folder_id, props.folders);
    }
    folderOptions.value = opts;
  } catch (err) {
    console.error(err);
  }
}

onMounted(() => {
  fetchBranchOptions();
  fetchFolderOptions();
});

watch(() => form.folder_id, (newFolderId) => {
  if (newFolderId && Number(newFolderId) !== Number(activeFolderId.value)) {
    activeFolderId.value = Number(newFolderId);

    const url = new URL(window.location.href);
    url.searchParams.set('folder_id', newFolderId);
    window.history.replaceState({}, '', url.toString());

    isLoadingSessions.value = true;

    router.reload({
      only: ['existingSessions', 'existingTrials', 'existingAnthro'],
      data: { folder_id: newFolderId },
      onSuccess: (page) => {
        form.sessions = computeSessions(
          page.props.existingSessions,
          page.props.existingAnthro,
          page.props.existingTrials
        );
        sessionCount.value = page.props.existingSessions?.length || 1;
      },
      onFinish: () => {
        isLoadingSessions.value = false;
      }
    });
  }
});

watch(() => form.sport_branch_id, async (newId) => {
  if (!newId) {
    selectedBranchData.value = null;
    return;
  }

  isLoadingBranch.value = true;
  try {
    const response = await http.get(`/api/sport-branches/${newId}/details`);
    selectedBranchData.value = response.data || response; 
  } catch (err) {
    console.error("Gagal mengambil detail cabang olahraga:", err);
    selectedBranchData.value = null;
  } finally {
    isLoadingBranch.value = false;
  }
}, { immediate: true });

const selectedBranch = computed(() => {
  return selectedBranchData.value || null;
});

const activeIndicators = computed(() => {
  if (!selectedBranch.value || !selectedBranch.value.indicators) return [];
  return [...selectedBranch.value.indicators]; 
});

function calculateAgeInYears(dobStr, sessionDateStr) {
  if (!dobStr) return null;
  const cleanDob = typeof dobStr === 'string' ? dobStr.substring(0, 10) : dobStr;
  const cleanSDate = typeof sessionDateStr === 'string' ? sessionDateStr.substring(0, 10) : sessionDateStr;
  const dob = new Date(cleanDob + 'T00:00:00');
  const sDate = cleanSDate ? new Date(cleanSDate + 'T00:00:00') : new Date();
  if (isNaN(dob.getTime()) || isNaN(sDate.getTime())) return null;
  let age = sDate.getFullYear() - dob.getFullYear();
  const m = sDate.getMonth() - dob.getMonth();
  if (m < 0 || (m === 0 && sDate.getDate() < dob.getDate())) {
    age--;
  }
  return age;
}

function isSameGender(g1, g2) {
  if (!g1 || !g2) return false;
  const n1 = (g1 === 'L' ? 'M' : g1 === 'P' ? 'F' : g1).toUpperCase();
  const n2 = (g2 === 'L' ? 'M' : g2 === 'P' ? 'F' : g2).toUpperCase();
  return n1 === n2;
}

function findMatchingBenchmarkSet(bms, gender, ageYears) {
  if (!bms || bms.length === 0) return null;
  let genderBms = bms.filter(b => isSameGender(b.gender, gender));
  if (genderBms.length === 0) genderBms = bms;

  let matched = genderBms.find(b => ageYears >= b.age_min && ageYears <= b.age_max);
  if (matched) return matched;

  const sortedDesc = [...genderBms].sort((a, b) => b.age_max - a.age_max);
  if (sortedDesc[0] && ageYears > sortedDesc[0].age_max) return sortedDesc[0];

  const sortedAsc = [...genderBms].sort((a, b) => a.age_min - b.age_min);
  if (sortedAsc[0] && ageYears < sortedAsc[0].age_min) return sortedAsc[0];

  return genderBms[0];
}

function getScoreColor(v) {
  if (v >= 85) return '#06A77D';
  if (v >= 70) return '#5B8DEF';
  if (v >= 55) return '#F4A100';
  return '#E63946';
}

function getIndicatorProgressClass(v) {
  if (v === null || v === undefined) return '';
  if (v >= 85) return 'progress-success';
  if (v >= 70) return 'progress-info';
  if (v >= 55) return 'progress-warning';
  return 'progress-error';
}

function parseLocaleFloat(val) {
  if (val === null || val === undefined) return NaN;
  const str = String(val).trim().replace(',', '.');
  return parseFloat(str);
}

function calcDiff(valA, valB) {
  const a = parseLocaleFloat(valA);
  const b = parseLocaleFloat(valB);
  if (isNaN(a) || isNaN(b)) return null;
  return a - b;
}

function getDeltaIcon(diff) {
  if (diff === null || diff === undefined || isNaN(diff)) return '';
  const roundDiff = Math.round(diff * 10) / 10;
  if (roundDiff === 0) return 'hi-minus';
  return roundDiff > 0 ? 'hi-arrow-up' : 'hi-arrow-down';
}

function getDeltaClass(diff) {
  if (diff === null || diff === undefined || isNaN(diff)) return 'text-base-content/40';
  const roundDiff = Math.round(diff * 10) / 10;
  if (roundDiff === 0) return 'text-base-content/60';
  return roundDiff > 0 ? 'text-success' : 'text-error';
}

function getDeltaLabel(diff, unit = '') {
  if (diff === null || diff === undefined || isNaN(diff)) return '-';
  const roundDiff = Math.round(diff * 10) / 10;
  if (roundDiff === 0) return 'No Change';
  const sign = roundDiff > 0 ? '+' : '';
  return `${sign}${roundDiff}${unit}`;
}

function getIndicatorPerf(sIdx, ind) {
  const session = form.sessions[sIdx];
  if (!session || !selectedBranch.value) return { benchVal: null, rawVal: null, pct: null, color: '#68758A' };

  const bms = selectedBranch.value.benchmarks || [];
  const ageYears = calculateAgeInYears(form.date_of_birth, session.date) ?? 20;
  const benchmarkSet = findMatchingBenchmarkSet(bms, form.gender, ageYears);

  let benchVal = null;
  if (benchmarkSet && benchmarkSet.values) {
    let vals = benchmarkSet.values;
    if (typeof vals === 'string') {
      try { vals = JSON.parse(vals); } catch (e) {}
    }
    if (vals && typeof vals === 'object') {
      const rawBm = vals[ind.id] !== undefined ? vals[ind.id] : vals[String(ind.id)];
      if (rawBm !== undefined && rawBm !== '' && rawBm !== null) {
        benchVal = parseFloat(rawBm);
      }
    }
  }

  const rawVal = parseLocaleFloat(session.indicators?.[ind.id]);
  if (benchVal !== null && !isNaN(benchVal) && !isNaN(rawVal)) {
    const isLower = ind.scoring_direction === 'LOWER_IS_BETTER' || ind.scoring_direction === 'lower';
    let pct = 0;
    if (isLower) {
      if (rawVal <= 0) {
        pct = 100;
      } else {
        pct = (benchVal / rawVal) * 100;
      }
    } else {
      pct = (rawVal / benchVal) * 100;
    }
    pct = Math.max(0, Math.min(100, Math.round(pct * 100) / 100));
    return { benchVal, rawVal, pct, color: getScoreColor(pct) };
  }

  return { benchVal, rawVal: isNaN(rawVal) ? null : rawVal, pct: null, color: '#68758A' };
}

function calculateBMI(hStr, wStr) {
  const h = parseLocaleFloat(hStr);
  const w = parseLocaleFloat(wStr);
  if (!h || !w || h <= 0) return null;
  const hM = h / 100;
  return (w / (hM * hM)).toFixed(1);
}

function getSessionOverall(sIdx) {
  if (!activeIndicators.value.length) return { score: null, color: '#68758A' };

  let total = 0;
  let count = 0;

  activeIndicators.value.forEach(ind => {
    const perf = getIndicatorPerf(sIdx, ind);
    if (perf.pct !== null) {
      total += perf.pct;
      count++;
    }
  });

  if (count === 0) return { score: null, color: '#68758A' };
  const avg = Math.round((total / count) * 10) / 10;
  return { score: avg, color: getScoreColor(avg) };
}

function addNewTestSession() {
  sessionCount.value++;
  const sessionColor = SESSION_PALETTE[(sessionCount.value - 1) % SESSION_PALETTE.length];
  form.sessions[sessionCount.value] = {
    name: `Test ${sessionCount.value}`,
    date: new Date().toISOString().split('T')[0],
    color: sessionColor,
    height: '',
    weight: '',
    indicators: {},
  };
}

async function removeTestSession(sIdx) {
  const result = await swal.fire({
    icon: 'warning',
    title: 'Delete Session',
    text: `Are you sure you want to delete "${form.sessions[sIdx]?.name || 'this session'}"? All entered data for this session will be lost.`,
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Yes, delete',
    cancelButtonText: 'Cancel',
  });

  if (result.isConfirmed) {
    const newSessions = {};
    let newIdx = 1;
    for (let i = 1; i <= sessionCount.value; i++) {
      if (i !== sIdx) {
        newSessions[newIdx] = { ...form.sessions[i] };
        newIdx++;
      }
    }
    
    sessionCount.value = Math.max(1, newIdx - 1);
    
    if (newIdx === 1) {
      newSessions[1] = {
        name: 'Test 1',
        date: new Date().toISOString().split('T')[0],
        color: '#FF6B35',
        height: '',
        weight: '',
        indicators: {},
      };
      sessionCount.value = 1;
    }
    
    form.sessions = newSessions;
  }
}

const photoPreviewUrl = ref(props.athlete.photo_path ? '/storage/' + props.athlete.photo_path : null);

function onPhotoChange(e) {
  const file = e.target.files[0];
  form.photo = file;
  if (file) {
    photoPreviewUrl.value = URL.createObjectURL(file);
  } else {
    photoPreviewUrl.value = null;
  }
}

function submitForm() {
  form.put(`/athletes/${props.athlete.id}`);
}
</script>
