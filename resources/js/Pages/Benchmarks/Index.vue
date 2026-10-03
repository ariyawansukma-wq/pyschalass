<template>
  <AppLayout>
    <div class="w-full space-y-6">
      <!-- Header Banner -->
      <div class="card bg-base-100 border border-base-200 text-base-content shadow-xs">
        <div class="card-body p-6 flex-row items-center justify-between flex-wrap gap-4">
          <div class="space-y-1">
            <div class="badge badge-primary badge-outline text-xs">
              <span>Standardization Engine</span>
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight">Benchmark Standards Matrix</h1>
            <p class="text-xs opacity-75">Configure target benchmark scores by sport branch, gender, and age group</p>
          </div>
          <div class="flex items-center gap-2 flex-wrap">
            <!-- <a :href="'/benchmarks/download-template' + (selectedCaborId ? '?sport_branch_id=' + selectedCaborId : '')" target="_blank" class="btn btn-outline btn-sm gap-2 cursor-pointer shadow-xs">
              <v-icon name="hi-document-text" class="size-[1.2em]" />
              <span>Download Template Excel</span>
            </a>
            <button @click="openImportModal" class="btn btn-secondary btn-sm gap-2 cursor-pointer shadow-xs">
              <v-icon name="hi-folder" class="size-[1.2em]" />
              <span>Import Excel</span>
            </button> -->
            <button @click="openCreateModal" class="btn btn-primary btn-sm gap-2 cursor-pointer shadow-xs">
              <v-icon name="hi-plus-circle" class="size-[1.2em]" />
              <span>Add Benchmark Set</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Filters & Sport Branch Selector -->
      <div class="card bg-base-100 border border-base-200 shadow-xs">
        <div class="card-body p-5 flex-row items-center justify-between gap-4 flex-wrap">
          <div class="w-full sm:w-80">
            <label class="label text-xs font-bold p-0 mb-1">Filter Sport Branch</label>
            <Select
              v-model="selectedCaborId"
              :options="caborOptions"
              :filter-by="() => true"
              @search="fetchCaborOptions"
              placeholder="Search or Select Sport Branch..."
              class="w-full text-xs"
              :teleport="true"
            />
          </div>

          <div class="text-xs text-base-content/70 font-semibold">
            Configured Standards: <span class="font-bold text-base-content">{{ activeBenchmarks.length }} Sets</span>
          </div>
        </div>
      </div>

      <!-- Benchmark Matrix Table -->
      <div v-if="selectedBranch" class="card bg-base-100 border border-base-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="table table-sm w-full text-xs">
            <thead>
              <tr class="bg-base-200 text-base-content font-bold">
                <th class="py-3.5 px-4">Label</th>
                <th class="py-3.5 px-4">Age Range</th>
                <th class="py-3.5 px-4 text-center">Gender</th>
                <th v-for="ind in currentIndicators" :key="ind.id" class="py-3.5 px-4 text-center min-w-[130px] border-l border-base-300">
                  <p class="font-bold text-base-content leading-tight">{{ ind.name }}</p>
                  <span v-if="ind.unit" class="text-[9.5px] text-base-content/60 font-semibold block mt-0.5">({{ ind.unit }})</span>
                </th>
                <th class="py-3.5 px-4 text-right border-l border-base-300">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-base-200 font-medium">
              <tr v-for="bm in activeBenchmarks" :key="bm.id" class="hover">
                <td class="py-3.5 px-4 font-bold text-base-content">
                  {{ bm.label || '-' }}
                </td>

                <td class="py-3.5 px-4 text-base-content">
                  {{ bm.age_min }} - {{ bm.age_max }} Years
                </td>

                <td class="py-3.5 px-4 text-center">
                  <span v-if="bm.gender === 'M'" class="badge badge-info badge-sm font-bold shadow-xs w-6 h-6 rounded-full inline-flex items-center justify-center text-[10px]">
                    M
                  </span>
                  <span v-else class="badge badge-secondary badge-sm font-bold shadow-xs w-6 h-6 rounded-full inline-flex items-center justify-center text-[10px]">
                    F
                  </span>
                </td>

                <td v-for="ind in currentIndicators" :key="ind.id" class="py-3.5 px-4 text-center border-l border-base-200 font-bold text-base-content">
                  {{ getBenchmarkValue(bm, ind.id) }}
                </td>

                <td class="py-3.5 px-4 text-right border-l border-base-200">
                  <div class="flex items-center justify-end gap-1.5">
                    <button @click="openEditModal(bm)" class="btn btn-info btn-outline btn-xs gap-1 cursor-pointer">
                      <v-icon name="hi-pencil" class="size-[1.2em]" />
                      <span>Edit</span>
                    </button>
                    <button @click="deleteBenchmark(bm)" class="btn btn-error btn-outline btn-xs gap-1 cursor-pointer">
                      <v-icon name="hi-trash" class="size-[1.2em]" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="activeBenchmarks.length === 0">
                <td :colspan="4 + currentIndicators.length" class="py-8 text-center text-base-content/50 font-medium">
                  No benchmark standards defined for this sport branch yet. Click "+ Add Benchmark Set" to add.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Create / Edit Modal -->
      <dialog :class="['modal', { 'modal-open': showModal }]">
        <div class="modal-box max-w-lg space-y-4">
          <div class="flex items-center justify-between pb-2 border-b border-base-200">
            <h3 class="font-extrabold text-base">
              {{ isEditing ? 'Edit Benchmark Standard' : 'Create Benchmark Standard' }}
            </h3>
            <button type="button" @click="showModal = false" class="btn btn-outline btn-square btn-xs">
              <v-icon name="hi-x" class="size-[1.2em]" />
            </button>
          </div>

          <form @submit.prevent="saveBenchmark" class="space-y-4 text-xs">
            <div v-if="Object.keys(bmForm.errors).length > 0" class="alert alert-error text-xs p-3">
              <div class="flex flex-col gap-1">
                <div v-for="(err, field) in bmForm.errors" :key="field" class="flex items-center gap-2">
                  <v-icon name="hi-exclamation-circle" class="w-4 h-4 shrink-0" />
                  <span>{{ err }}</span>
                </div>
              </div>
            </div>

            <div class="form-control">
              <label class="label text-xs font-bold p-0 mb-1">Label / Source <span class="text-error">*</span></label>
              <input type="text" v-model="bmForm.label" required placeholder="e.g. Standard Internal, Putra 10-12 Tahun" class="input input-bordered input-sm w-full">
            </div>

            <div class="grid grid-cols-3 gap-3">
              <div class="form-control">
                <label class="label text-xs font-bold p-0 mb-1">Min Age (Years)</label>
                <input type="number" v-model="bmForm.age_min" required min="1" max="100" class="input input-bordered input-sm w-full">
              </div>

              <div class="form-control">
                <label class="label text-xs font-bold p-0 mb-1">Max Age (Years)</label>
                <input type="number" v-model="bmForm.age_max" required min="1" max="100" class="input input-bordered input-sm w-full">
              </div>

              <div class="form-control">
                <label class="label text-xs font-bold p-0 mb-1">Gender</label>
                <select v-model="bmForm.gender" required class="select select-bordered select-sm w-full text-xs">
                  <option value="M">Male (M)</option>
                  <option value="F">Female (F)</option>
                </select>
              </div>
            </div>

            <div v-if="currentIndicators.length > 0" class="space-y-3 pt-2 border-t border-base-200">
              <h4 class="font-bold text-base-content text-xs">Target Standard Values for Each Indicator:</h4>
              <div class="grid grid-cols-2 gap-3">
                <div v-for="ind in currentIndicators" :key="ind.id" class="form-control">
                  <label class="label text-xs font-bold p-0 mb-1">
                    {{ ind.name }} <span v-if="ind.unit" class="text-base-content/60 font-normal">({{ ind.unit }})</span>
                  </label>
                  <input
                    type="number"
                    step="any"
                    v-model="bmForm.values[ind.id]"
                    placeholder="Standard score"
                    class="input input-bordered input-sm w-full"
                  />
                </div>
              </div>
            </div>

            <div class="modal-action">
              <button type="button" @click="showModal = false" class="btn btn-outline btn-xs">Cancel</button>
              <button type="submit" :disabled="bmForm.processing" class="btn btn-primary btn-xs">
                Save Benchmark Standard
              </button>
            </div>
          </form>
        </div>
        <form method="dialog" class="modal-backdrop" @click="showModal = false">
          <button>close</button>
        </form>
      </dialog>

      <!-- Import Excel Modal -->
      <dialog :class="['modal', { 'modal-open': showImportModal }]">
        <div class="modal-box max-w-md space-y-4">
          <div class="flex items-center justify-between pb-2 border-b border-base-200">
            <h3 class="font-extrabold text-base">Import Benchmark & Indicators</h3>
            <button type="button" @click="showImportModal = false" class="btn btn-outline btn-square btn-xs">
              <v-icon name="hi-x" class="size-[1.2em]" />
            </button>
          </div>

          <form @submit.prevent="submitImport" class="space-y-4 text-xs">
            <div v-if="Object.keys(importForm.errors).length > 0" class="alert alert-error text-xs p-3">
              <div class="flex flex-col gap-1">
                <div v-for="(err, field) in importForm.errors" :key="field" class="flex items-center gap-2">
                  <v-icon name="hi-exclamation-circle" class="w-4 h-4 shrink-0" />
                  <span>{{ err }}</span>
                </div>
              </div>
            </div>

            <div class="form-control">
              <label class="label text-xs font-bold p-0 mb-1">Target Sport Branch (Fallback)</label>
              <Select
                v-model="importForm.sport_branch_id"
                :options="importCaborOptions"
                :filter-by="() => true"
                @search="fetchImportCaborOptions"
                placeholder="-- Otomatis Deteksi dari Excel (Nama Cabor) --"
                class="w-full text-xs"
                :teleport="true"
              />
            </div>

            <div class="form-control">
              <label class="label text-xs font-bold p-0 mb-1">Excel File (.xlsx / .xls) <span class="text-error">*</span></label>
              <input type="file" @change="onFileChange" accept=".xlsx,.xls,.csv" required class="file-input file-input-bordered file-input-sm w-full">
            </div>

            <div class="p-3 bg-base-200 rounded-lg space-y-1 text-[11px] text-base-content/80">
              <p class="font-bold text-base-content">💡 Tip:</p>
              <p>Download file template Excel di atas, isi data indikator & benchmark, lalu upload file tersebut di sini.</p>
            </div>

            <div class="modal-action">
              <button type="button" @click="showImportModal = false" class="btn btn-outline btn-xs">Cancel</button>
              <button type="submit" :disabled="importForm.processing" class="btn btn-primary btn-xs gap-1">
                <span v-if="importForm.processing" class="loading loading-spinner loading-xs"></span>
                <span>Start Import</span>
              </button>
            </div>
          </form>
        </div>
        <form method="dialog" class="modal-backdrop" @click="showImportModal = false">
          <button>close</button>
        </form>
      </dialog>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, inject, onMounted, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { Select } from 'vue3-select-component';
import 'vue3-select-component/styles';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useSelect2Options } from '@/Composables/useSelect2Options';

const swal = inject('$swal');

const props = defineProps({
  sportBranches: { type: Array, default: () => [] },
  benchmarks: { type: Array, default: () => [] },
  indicators: { type: Array, default: () => [] },
  selectedBranchId: { type: [Number, String], default: '' },
});

const selectedCaborId = ref(props.selectedBranchId || props.sportBranches[0]?.id || '');
const showModal = ref(false);
const showImportModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const { options: caborOptions, fetchOptions: fetchCaborOptions } = useSelect2Options('sport-branches');
const { options: importCaborOptionsRaw, fetchOptions: fetchImportCaborOptionsRaw } = useSelect2Options('sport-branches');

const importCaborOptions = computed(() => {
  return [
    { label: '-- Otomatis Deteksi dari Excel (Nama Cabor) --', value: '' },
    ...importCaborOptionsRaw.value
  ];
});

function fetchImportCaborOptions(q = '') {
  fetchImportCaborOptionsRaw(q);
}

watch(selectedCaborId, (newId) => {
  if (newId) {
    onBranchChange();
  }
});

onMounted(() => {
  if (props.sportBranches && props.sportBranches.length > 0) {
    caborOptions.value = props.sportBranches.map(b => ({
      label: `${b.name} (${b.indicators_count || b.indicators?.length || 0} Indicators)`,
      value: b.id,
    }));
    importCaborOptionsRaw.value = props.sportBranches.map(b => ({
      label: b.name,
      value: b.id,
    }));
  } else {
    fetchCaborOptions();
    fetchImportCaborOptions();
  }
});

const selectedBranch = computed(() => {
  return props.sportBranches.find(b => b.id == selectedCaborId.value) || null;
});

const currentIndicators = computed(() => {
  if (selectedBranch.value && selectedBranch.value.indicators && selectedBranch.value.indicators.length > 0) {
    return selectedBranch.value.indicators;
  }
  return props.indicators || [];
});

const activeBenchmarks = computed(() => {
  if (!selectedCaborId.value) return [];
  return props.benchmarks.filter(bm => bm.sport_branch_id == selectedCaborId.value);
});

const bmForm = useForm({
  sport_branch_id: '',
  label: '',
  age_min: 15,
  age_max: 20,
  gender: 'M',
  values: {},
});

const importForm = useForm({
  sport_branch_id: selectedCaborId.value,
  file: null,
});

function openImportModal() {
  importForm.reset();
  importForm.sport_branch_id = selectedCaborId.value;
  showImportModal.value = true;
}

function onFileChange(e) {
  if (e.target.files && e.target.files[0]) {
    importForm.file = e.target.files[0];
  }
}

function submitImport() {
  importForm.post('/benchmarks/import', {
    onSuccess: () => {
      showImportModal.value = false;
      if (swal) {
        swal.fire('Berhasil!', 'Data benchmark dan indikator berhasil di-import dari Excel.', 'success');
      }
    },
  });
}

function onBranchChange() {
  router.get('/benchmarks', { sport_branch_id: selectedCaborId.value }, { preserveState: true, replace: true });
}

function getBenchmarkValue(bm, indId) {
  if (!bm.values) return '-';
  const val = bm.values[indId] !== undefined ? bm.values[indId] : bm.values[String(indId)];
  return val !== undefined && val !== null && val !== '' ? val : '-';
}

function openCreateModal() {
  isEditing.value = false;
  editingId.value = null;
  bmForm.reset();
  bmForm.sport_branch_id = selectedCaborId.value;
  bmForm.label = '';
  bmForm.values = {};
  showModal.value = true;
}

function openEditModal(bm) {
  isEditing.value = true;
  editingId.value = bm.id;
  bmForm.sport_branch_id = bm.sport_branch_id;
  bmForm.label = bm.label || '';
  bmForm.age_min = bm.age_min;
  bmForm.age_max = bm.age_max;
  bmForm.gender = bm.gender || 'M';
  bmForm.values = { ...(bm.values || {}) };
  showModal.value = true;
}

function saveBenchmark() {
  bmForm.sport_branch_id = selectedCaborId.value;
  if (isEditing.value) {
    bmForm.put('/benchmarks/' + editingId.value, {
      onSuccess: () => showModal.value = false,
    });
  } else {
    bmForm.post('/benchmarks', {
      onSuccess: () => showModal.value = false,
    });
  }
}

async function deleteBenchmark(bm) {
  const result = await swal.fire({
    icon: 'warning',
    title: 'Delete Benchmark Set',
    text: `Are you sure you want to delete this benchmark set (${bm.age_min}-${bm.age_max} years)?`,
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Yes, delete',
    cancelButtonText: 'Cancel',
  });

  if (result.isConfirmed) {
    router.delete('/benchmarks/' + bm.id);
  }
}
</script>
