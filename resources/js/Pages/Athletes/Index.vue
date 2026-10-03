<template>
  <AppLayout>
    <div class="w-full space-y-6">
      <!-- Header Banner -->
      <div class="card bg-base-100 border border-base-200 text-base-content shadow-xs">
        <div class="card-body p-6 flex-row items-center justify-between flex-wrap gap-4">
          <div class="space-y-1">
            <div class="badge badge-primary badge-outline text-xs">
              <span>Directory Module</span>
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight">Athlete Directory</h1>
            <p class="text-xs opacity-75">Manage registered athletes, physical evaluation history, and individual reports</p>
          </div>
          <div class="flex items-center gap-2 flex-wrap">
            <button v-if="can('delete', 'Athlete')" @click="deleteAllAthletes" class="btn btn-error btn-outline btn-sm gap-2 shadow-xs cursor-pointer">
              <v-icon name="hi-trash" class="size-[1.2em]" />
              <span>Delete All Data</span>
            </button>
            <button @click="exportExcel" class="btn btn-success btn-outline btn-sm gap-2 shadow-xs cursor-pointer">
              <v-icon name="hi-download" class="size-[1.2em]" />
              <span>Export Excel</span>
            </button>
            <button @click="exportPdf" class="btn btn-primary btn-outline btn-sm gap-2 shadow-xs cursor-pointer">
              <v-icon name="hi-printer" class="size-[1.2em]" />
              <span>Export PDF</span>
            </button>
            <Link href="/athletes/create" class="btn btn-primary btn-sm gap-2 shadow-xs">
              <v-icon name="hi-plus-circle" class="size-[1.2em]" />
              <span>Add New Athlete</span>
            </Link>
          </div>
        </div>
      </div>

      <!-- Filters & Search Bar -->
      <div class="card bg-base-100 border border-base-200 shadow-xs">
        <div class="card-body p-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="lg:col-span-2 relative">
              <input
                type="text"
                v-model="searchQuery"
                @input="onFilterChange"
                placeholder="Search athlete by name or ID number..."
                class="input input-bordered input-sm w-full pl-8"
              />
              <v-icon name="hi-search" class="w-4 h-4 text-base-content/50 absolute left-2.5 top-2.5" />
            </div>

            <div>
              <Select
                v-model="filterBranch"
                :options="branchOptions"
                :filter-by="() => true"
                @search="fetchBranchOptions"
                placeholder="All Sport Branches"
                @change="onFilterChange"
                class="w-full text-xs"
              />
            </div>

            <div>
              <Select
                v-model="filterFolder"
                :options="folderOptions"
                :filter-by="() => true"
                @search="fetchFolderOptions"
                placeholder="All Data Folders"
                @change="onFilterChange"
                class="w-full text-xs"
              />
            </div>

            <div>
              <Select
                v-model="filterGender"
                :options="genderOptions"
                placeholder="All Genders"
                @change="onFilterChange"
                class="w-full text-xs"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Athletes Data Table -->
      <div class="card bg-base-100 border border-base-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="table table-sm w-full">
            <thead>
              <tr class="bg-base-200 text-base-content font-bold">
                <th class="w-12 text-center py-3 px-4">No</th>
                <th class="py-3 px-4">Athlete Info</th>
                <th class="py-3 px-4">Sport Branch</th>
                <th class="py-3 px-4">Data Folder</th>
                <th class="py-3 px-4 text-center">Gender & Age</th>
                <th class="py-3 px-4 text-center">Latest BMI</th>
                <th class="py-3 px-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="font-medium">
              <tr v-for="(athlete, idx) in athletes.data" :key="athlete.id" class="hover">
                <td class="text-center py-3 px-4 font-bold text-base-content/60">
                  {{ (athletes.current_page ? (athletes.current_page - 1) * athletes.per_page : 0) + idx + 1 }}
                </td>
                <td class="py-3 px-4">
                  <div class="flex items-center gap-3">
                    <div class="avatar placeholder">
                      <div class="w-9 h-9 rounded-full bg-primary/10 text-primary font-bold text-xs flex items-center justify-center">
                        <img v-if="athlete.photo_path" :src="'/storage/' + athlete.photo_path" class="object-cover" />
                        <span v-else>{{ athlete.name ? athlete.name.substring(0, 2).toUpperCase() : 'AT' }}</span>
                      </div>
                    </div>
                    <div>
                      <p class="font-bold text-base-content leading-snug">{{ athlete.name }}</p>
                      <div class="flex items-center gap-1.5 mt-0.5">
                        <span class="text-[10px] text-base-content/60 font-semibold">{{ athlete.athlete_number }}</span>
                        <span v-if="athlete.event_number" class="badge badge-info badge-outline badge-xs font-bold">
                          {{ athlete.event_number }}
                        </span>
                      </div>
                    </div>
                  </div>
                </td>

                <td class="py-3 px-4 text-base-content font-semibold">
                  {{ athlete.sport_branch ? athlete.sport_branch.name : '-' }}
                </td>

                <td class="py-3 px-4">
                  <div class="flex flex-wrap gap-1">
                    <span
                      v-for="folder in (athlete.folders || [])"
                      :key="folder.id"
                      class="badge badge-outline badge-xs"
                    >
                      {{ folder.name }}
                    </span>
                    <span v-if="!athlete.folders || athlete.folders.length === 0" class="text-base-content/40">-</span>
                  </div>
                </td>

                <td class="py-3 px-4 text-center">
                  <span v-if="athlete.gender === 'M'" class="badge badge-info badge-sm font-bold gap-1 shadow-xs">
                    <span class="w-1.5 h-1.5 rounded-full bg-info-content"></span>
                    Male (M)
                  </span>
                  <span v-else class="badge badge-secondary badge-sm font-bold gap-1 shadow-xs">
                    <span class="w-1.5 h-1.5 rounded-full bg-secondary-content"></span>
                    Female (F)
                  </span>
                </td>

                <td class="py-3 px-4 text-center font-bold">
                  {{ athlete.latest_anthropometry ? athlete.latest_anthropometry.bmi : (athlete.bmi ? athlete.bmi : '-') }}
                </td>

                <td class="py-3 px-4 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <button @click="openReportModal(athlete)" class="btn btn-success btn-outline btn-xs gap-1 cursor-pointer">
                      <v-icon name="hi-document-text" class="size-[1.2em]" />
                      <span>Report</span>
                    </button>
                    <Link :href="'/athletes/' + athlete.id + '/edit'" class="btn btn-info btn-outline btn-xs gap-1">
                      <v-icon name="hi-pencil" class="size-[1.2em]" />
                      <span>Edit</span>
                    </Link>
                    <button v-if="can('delete', 'Athlete')" @click="deleteAthlete(athlete)" class="btn btn-error btn-outline btn-xs gap-1 cursor-pointer">
                      <v-icon name="hi-trash" class="size-[1.2em]" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!athletes.data || athletes.data.length === 0">
                <td colspan="7" class="py-8 text-center text-base-content/50 font-medium">
                  No athletes found matching the search criteria.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <Pagination :links="athletes.links" :meta="athletes" />
      </div>

      <!-- Report Folder Selection Modal -->
      <dialog :class="['modal', { 'modal-open': showReportModal }]">
        <div class="modal-box max-w-sm space-y-4 !overflow-visible">
          <div class="flex items-center justify-between pb-2 border-b border-base-200">
            <h3 class="font-extrabold text-base">Export Report</h3>
            <button type="button" @click="showReportModal = false" class="btn btn-outline btn-square btn-xs">
              <v-icon name="hi-x" class="size-[1.2em]" />
            </button>
          </div>

          <div v-if="reportAthlete" class="space-y-3">
            <div class="flex items-center gap-3 p-3 bg-base-200 rounded-lg">
              <div class="avatar placeholder">
                <div class="w-10 h-10 rounded-full bg-primary/10 text-primary font-bold text-xs">
                  {{ reportAthlete.name?.substring(0, 2).toUpperCase() }}
                </div>
              </div>
              <div>
                <p class="font-bold text-sm">{{ reportAthlete.name }}</p>
                <p class="text-xs text-base-content/60">{{ reportAthlete.athlete_number }}</p>
              </div>
            </div>

            <div class="form-control">
              <label class="label text-xs font-bold p-0 mb-1">Select Folder <span class="text-error">*</span></label>
              <Select
                v-model="reportFolderId"
                :options="reportFolderOptions"
                :filter-by="() => true"
                @search="fetchReportFolderOptions"
                placeholder="Search or Select Folder..."
                class="w-full text-xs"
                :teleport="true"
              />
            </div>

            <div class="form-control">
              <label class="label text-xs font-bold p-0 mb-1">Select Institution / Letterhead <span class="text-error">*</span></label>
              <Select
                v-model="reportInstitutionId"
                :options="reportInstitutionOptions"
                :filter-by="() => true"
                @search="fetchReportInstitutionOptions"
                placeholder="Search or Select Institution..."
                class="w-full text-xs"
                :teleport="false"
              />
            </div>
          </div>

          <div class="modal-action">
            <button type="button" @click="showReportModal = false" class="btn btn-outline btn-xs">Cancel</button>
            <button
              type="button"
              @click="generateReport"
              :disabled="!reportFolderId || !reportInstitutionId"
              class="btn btn-success btn-xs gap-1"
            >
              <v-icon name="hi-document-text" class="size-[1.2em]" />
              <span>Generate Report</span>
            </button>
          </div>
        </div>
        <form method="dialog" class="modal-backdrop" @click="showReportModal = false">
          <button>close</button>
        </form>
      </dialog>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, inject, onMounted, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Select } from 'vue3-select-component';
import 'vue3-select-component/styles';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { useSelect2Options } from '@/Composables/useSelect2Options';
import { useAbility } from '@/Composables/useAbility';

const swal = inject('$swal');
const page = usePage();
const authUser = computed(() => page.props.auth?.user || {});
const { can } = useAbility();

const props = defineProps({
  athletes: { type: Object, required: true },
  sportBranches: { type: Array, default: () => [] },
  folders: { type: Array, default: () => [] },
  institutions: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
});

const defaultInstitutionId = computed(() => props.institutions[0]?.id || '');

// Report Modal
const showReportModal = ref(false);
const reportAthlete = ref(null);
const reportFolderId = ref('');
const reportInstitutionId = ref('');

const reportFolderOptions = ref([]);

const reportInstitutionOptions = computed(() => {
  return props.institutions.map(inst => ({
    label: inst.name,
    value: Number(inst.id),
  }));
});

const { fetchOptions: fetchFolders } = useSelect2Options('folders');
const { fetchOptions: fetchBranches } = useSelect2Options('sport-branches');

function mergeInitialOptions(fetchedOptions, selectedValue, allItems, labelKey = 'name') {
  const exists = fetchedOptions.some(o => o.value == selectedValue);
  if (!exists && selectedValue) {
    const found = allItems.find(item => item.id == selectedValue);
    if (found) {
      fetchedOptions.push({ label: found[labelKey], value: found.id });
    }
  }
  return fetchedOptions;
}

async function fetchBranchOptions(q = '') {
  const results = await fetchBranches(q);
  let opts = [...results];
  if (!q) {
    opts = mergeInitialOptions(opts, filterBranch.value, props.sportBranches);
  }
  branchOptions.value = [
    { label: 'All Sport Branches', value: '' },
    ...opts
  ];
}

async function fetchFolderOptions(q = '') {
  const results = await fetchFolders(q);
  let opts = [...results];
  if (!q) {
    opts = mergeInitialOptions(opts, filterFolder.value, props.folders);
  }
  folderOptions.value = [
    { label: 'All Data Folders', value: '' },
    ...opts
  ];
}

async function fetchReportFolderOptions(q = '') {
  if (!reportAthlete.value) return;
  const results = await fetchFolders(q, { athlete_id: reportAthlete.value.id });
  let opts = [...results];
  const defaultVal = reportFolderId.value;
  if (!q && defaultVal) {
    const found = (reportAthlete.value.folders || []).find(f => f.id == defaultVal);
    if (found && !opts.some(o => Number(o.value) === Number(defaultVal))) {
      opts.push({ label: found.name, value: Number(found.id) });
    }
  }
  reportFolderOptions.value = opts;
}

onMounted(() => {
  fetchBranchOptions();
  fetchFolderOptions();
});

function openReportModal(athlete) {
  reportAthlete.value = athlete;
  reportFolderId.value = athlete.folders?.[0]?.id || '';
  reportInstitutionId.value = defaultInstitutionId.value;
  fetchReportFolderOptions();
  showReportModal.value = true;
}

function generateReport() {
  if (!reportFolderId.value || !reportAthlete.value || !reportInstitutionId.value) return;
  const url = `/athletes/${reportAthlete.value.id}/export-pdf?folder_id=${reportFolderId.value}&institution_id=${reportInstitutionId.value}`;
  window.open(url, '_blank');
  showReportModal.value = false;
}

const searchQuery = ref(props.filters.search || '');
const filterBranch = ref(props.filters.sport_branch_id || '');
const filterFolder = ref(props.filters.folder_id || '');
const filterGender = ref(props.filters.gender || '');

const initialBranch = props.sportBranches.find(b => b.id == filterBranch.value);
const branchOptions = ref([
  { label: 'All Sport Branches', value: '' },
  ...(initialBranch ? [{ label: initialBranch.name, value: initialBranch.id }] : [])
]);

const initialFolder = props.folders.find(f => f.id == filterFolder.value);
const folderOptions = ref([
  { label: 'All Data Folders', value: '' },
  ...(initialFolder ? [{ label: initialFolder.name, value: initialFolder.id }] : [])
]);

const genderOptions = [
  { label: 'All Genders', value: '' },
  { label: 'Male (M)', value: 'M' },
  { label: 'Female (F)', value: 'F' },
];

watch([filterBranch, filterFolder, filterGender], () => {
  onFilterChange();
});

let filterTimeout = null;
function onFilterChange() {
  clearTimeout(filterTimeout);
  filterTimeout = setTimeout(() => {
    router.get('/athletes', {
      search: searchQuery.value,
      sport_branch_id: filterBranch.value,
      folder_id: filterFolder.value,
      gender: filterGender.value,
    }, { preserveState: true, replace: true });
  }, 300);
}

async function deleteAthlete(athlete) {
  const result = await swal.fire({
    icon: 'warning',
    title: 'Delete Athlete',
    text: `Are you sure you want to delete athlete "${athlete.name}"?`,
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Yes, delete',
    cancelButtonText: 'Cancel',
  });
  if (result.isConfirmed) {
    router.delete('/athletes/' + athlete.id);
  }
}

async function deleteAllAthletes() {
  const result = await swal.fire({
    icon: 'warning',
    title: 'Delete All Athletes',
    text: 'Are you sure you want to delete ALL athletes and their associated test data? This action cannot be undone!',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Yes, delete all',
    cancelButtonText: 'Cancel',
  });
  if (result.isConfirmed) {
    router.delete('/athletes/delete-all', {
      onSuccess: () => {
        swal.fire('Deleted!', 'All athletes have been deleted.', 'success');
      }
    });
  }
}

function exportExcel() {
  const params = new URLSearchParams({
    search: searchQuery.value,
    sport_branch_id: filterBranch.value,
    folder_id: filterFolder.value,
    gender: filterGender.value,
  }).toString();
  window.open(`/athletes/export/excel?${params}`, '_blank');
}

function exportPdf() {
  const params = new URLSearchParams({
    search: searchQuery.value,
    sport_branch_id: filterBranch.value,
    folder_id: filterFolder.value,
    gender: filterGender.value,
  }).toString();
  window.open(`/athletes/export/pdf?${params}`, '_blank');
}
</script>
