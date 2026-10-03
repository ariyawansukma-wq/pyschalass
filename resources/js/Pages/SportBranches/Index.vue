<template>
  <AppLayout>
    <div class="w-full space-y-6">
      <!-- Header Banner -->
      <div class="card bg-base-100 border border-base-200 text-base-content shadow-xs">
        <div class="card-body p-6 flex-row items-center justify-between flex-wrap gap-4">
          <div class="space-y-1">
            <div class="badge badge-primary badge-outline text-xs">
              <span>Branch Management</span>
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight">Sport Branches & Test Indicators</h1>
            <p class="text-xs opacity-75">Configure sport branches and their physical evaluation metrics</p>
          </div>
          <button @click="openCreateModal" class="btn btn-primary btn-sm gap-2 cursor-pointer shadow-xs">
            <v-icon name="hi-plus-circle" class="size-[1.2em]" />
            <span>Add Sport Branch</span>
          </button>
        </div>
      </div>

      <!-- Search & Filters -->
      <div class="card bg-base-100 border border-base-200 shadow-xs">
        <div class="card-body p-4 flex-row items-center justify-between gap-4 flex-wrap">
          <div class="relative w-full sm:w-80">
            <input
              type="text"
              v-model="searchQuery"
              @input="onSearchInput"
              placeholder="Search sport branch..."
              class="input input-bordered input-sm w-full pl-8"
            />
            <v-icon name="hi-search" class="w-4 h-4 text-base-content/50 absolute left-2.5 top-2.5" />
          </div>
          <div class="text-xs text-base-content/70 font-semibold">
            Total Branches: <span class="font-bold text-base-content">{{ sportBranches.total || sportBranches.data?.length || 0 }}</span>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="card bg-base-100 border border-base-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="table table-sm w-full text-xs">
            <thead>
              <tr class="bg-base-200 text-base-content font-bold">
                <th class="w-16">No</th>
                <th>Sport Branch Name</th>
                <th>Description</th>
                <th class="text-center">Athletes</th>
                <th class="text-center">Indicators</th>
                <th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-base-200 font-medium">
              <tr v-for="(cabor, idx) in sportBranches.data" :key="cabor.id" class="hover">
                <td class="font-bold text-base-content/60">
                  {{ (sportBranches.current_page ? (sportBranches.current_page - 1) * sportBranches.per_page : 0) + idx + 1 }}
                </td>
                <td class="font-bold text-base-content text-sm">{{ cabor.name }}</td>
                <td class="text-base-content/70 max-w-xs truncate">{{ cabor.description || '-' }}</td>
                <td class="text-center">
                  <span class="badge badge-info badge-outline badge-sm font-bold">
                    {{ cabor.athletes_count || 0 }} Athletes
                  </span>
                </td>
                <td class="text-center">
                  <span class="badge badge-success badge-outline badge-sm font-bold">
                    {{ cabor.indicators_count || cabor.indicators?.length || 0 }} Indicators
                  </span>
                </td>
                <td class="text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <Link :href="'/sport-branches/' + cabor.id" class="btn btn-outline btn-xs gap-1">
                      <v-icon name="hi-adjustments" class="size-[1.2em]" />
                      <span>Indicators</span>
                    </Link>
                    <button @click="openExportPdfModal(cabor)" class="btn btn-success btn-outline btn-xs gap-1 cursor-pointer" title="Download PDF Report">
                      <v-icon name="hi-document-text" class="size-[1.2em]" />
                      <span>PDF</span>
                    </button>
                    <a :href="'/athletes/export/excel?sport_branch_id=' + cabor.id" class="btn btn-info btn-outline btn-xs gap-1 cursor-pointer" title="Download Excel Report">
                      <v-icon name="hi-download" class="size-[1.2em]" />
                      <span>Excel</span>
                    </a>
                    <button @click="openEditModal(cabor)" class="btn btn-info btn-outline btn-xs gap-1 cursor-pointer">
                      <v-icon name="hi-pencil" class="size-[1.2em]" />
                      <span>Edit</span>
                    </button>
                    <button @click="duplicateCabor(cabor)" class="btn btn-warning btn-outline btn-xs gap-1 cursor-pointer">
                      <v-icon name="hi-document-duplicate" class="size-[1.2em]" />
                      <span>Duplicate</span>
                    </button>
                    <button @click="deleteCabor(cabor)" class="btn btn-error btn-outline btn-xs gap-1 cursor-pointer">
                      <v-icon name="hi-trash" class="size-[1.2em]" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!sportBranches.data || sportBranches.data.length === 0">
                <td colspan="6" class="py-8 text-center text-base-content/50 font-medium">No sport branches found.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <Pagination :links="sportBranches.links" :meta="sportBranches" />
      </div>

      <!-- Create / Edit Modal -->
      <dialog :class="['modal', { 'modal-open': showModal }]">
        <div class="modal-box max-w-sm space-y-4">
          <div class="flex items-center justify-between pb-2 border-b border-base-200">
            <h3 class="font-extrabold text-base">
              {{ isEditing ? 'Edit Sport Branch' : 'Create Sport Branch' }}
            </h3>
            <button type="button" @click="showModal = false" class="btn btn-outline btn-square btn-xs">
              <v-icon name="hi-x" class="size-[1.2em]" />
            </button>
          </div>

          <form @submit.prevent="saveCabor" class="space-y-4">
            <div v-if="Object.keys(caborForm.errors).length > 0" class="alert alert-error text-xs p-3">
              <div class="flex flex-col gap-1">
                <div v-for="(err, field) in caborForm.errors" :key="field" class="flex items-center gap-2">
                  <v-icon name="hi-exclamation-circle" class="w-4 h-4 shrink-0" />
                  <span>{{ err }}</span>
                </div>
              </div>
            </div>
            <div class="form-control">
              <label class="label text-xs font-bold p-0 mb-1">Branch Name <span class="text-error">*</span></label>
              <input type="text" v-model="caborForm.name" required placeholder="e.g.: Athletics / Football" class="input input-bordered input-sm w-full">
            </div>

            <div class="form-control">
              <label class="label text-xs font-bold p-0 mb-1">Description</label>
              <textarea v-model="caborForm.description" rows="3" placeholder="Brief description of the sport branch..." class="textarea textarea-bordered w-full text-xs"></textarea>
            </div>

            <div class="modal-action">
              <button type="button" @click="showModal = false" class="btn btn-outline btn-xs">Cancel</button>
              <button type="submit" :disabled="caborForm.processing" class="btn btn-primary btn-xs">
                Save Branch
              </button>
            </div>
          </form>
        </div>
        <form method="dialog" class="modal-backdrop" @click="showModal = false">
          <button>close</button>
        </form>
      </dialog>

      <!-- Export PDF Institution Modal -->
      <dialog :class="['modal', { 'modal-open': showExportPdfModal }]">
        <div class="modal-box max-w-sm space-y-4 !overflow-visible">
          <div class="flex items-center justify-between pb-2 border-b border-base-200">
            <h3 class="font-extrabold text-base">Export PDF Report</h3>
            <button type="button" @click="showExportPdfModal = false" class="btn btn-outline btn-square btn-xs">
              <v-icon name="hi-x" class="size-[1.2em]" />
            </button>
          </div>

          <div v-if="exportCabor" class="space-y-3">
            <div class="flex items-center gap-3 p-3 bg-base-200 rounded-lg">
              <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold text-sm shrink-0">
                <v-icon name="bi-trophy" class="w-5 h-5" />
              </div>
              <div class="min-w-0">
                <p class="font-bold text-sm truncate">{{ exportCabor.name }}</p>
                <p class="text-xs text-base-content/60">{{ exportCabor.athletes_count || 0 }} Athletes · {{ exportCabor.indicators_count || exportCabor.indicators?.length || 0 }} Indicators</p>
              </div>
            </div>

            <div class="form-control">
              <label class="label text-xs font-bold p-0 mb-1">Select Institution / Letterhead <span class="text-error">*</span></label>
              <Select
                v-model="exportInstitutionId"
                :options="institutionOptions"
                :filter-by="() => true"
                placeholder="Search or Select Institution..."
                class="w-full text-xs"
                :teleport="false"
              />
            </div>
          </div>

          <div class="modal-action">
            <button type="button" @click="showExportPdfModal = false" class="btn btn-outline btn-xs">Cancel</button>
            <button
              type="button"
              @click="generatePdf"
              :disabled="!exportInstitutionId || !exportCabor"
              class="btn btn-success btn-xs gap-1"
            >
              <v-icon name="hi-document-text" class="size-[1.2em]" />
              <span>Generate PDF</span>
            </button>
          </div>
        </div>
        <form method="dialog" class="modal-backdrop" @click="showExportPdfModal = false">
          <button>close</button>
        </form>
      </dialog>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, inject } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import { Select } from 'vue3-select-component';
import 'vue3-select-component/styles';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const swal = inject('$swal');

const props = defineProps({
  sportBranches: { type: Object, required: true },
  institutions: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
});

const searchQuery = ref(props.filters.search || '');
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

// Export PDF Modal
const showExportPdfModal = ref(false);
const exportCabor = ref(null);
const exportInstitutionId = ref('');
const defaultInstitutionId = computed(() => props.institutions[0]?.id || '');

const institutionOptions = computed(() => {
  return props.institutions.map(inst => ({
    label: inst.name,
    value: Number(inst.id),
  }));
});

function openExportPdfModal(cabor) {
  exportCabor.value = cabor;
  exportInstitutionId.value = defaultInstitutionId.value ? Number(defaultInstitutionId.value) : '';
  showExportPdfModal.value = true;
}

function generatePdf() {
  if (!exportCabor.value || !exportInstitutionId.value) return;
  const url = `/athletes/export/pdf?sport_branch_id=${exportCabor.value.id}&institution_id=${exportInstitutionId.value}`;
  window.open(url, '_blank');
  showExportPdfModal.value = false;
}

const caborForm = useForm({
  name: '',
  description: '',
});

let searchTimeout = null;
function onSearchInput() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    router.get('/sport-branches', { search: searchQuery.value }, { preserveState: true, replace: true });
  }, 300);
}

function openCreateModal() {
  isEditing.value = false;
  editingId.value = null;
  caborForm.reset();
  showModal.value = true;
}

function openEditModal(cabor) {
  isEditing.value = true;
  editingId.value = cabor.id;
  caborForm.name = cabor.name;
  caborForm.description = cabor.description || '';
  showModal.value = true;
}

function saveCabor() {
  if (isEditing.value) {
    caborForm.put('/sport-branches/' + editingId.value, {
      onSuccess: () => showModal.value = false,
    });
  } else {
    caborForm.post('/sport-branches', {
      onSuccess: () => showModal.value = false,
    });
  }
}

async function deleteCabor(cabor) {
  const result = await swal.fire({
    icon: 'warning',
    title: 'Delete Sport Branch',
    text: `Are you sure you want to delete sport branch "${cabor.name}"?`,
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Yes, delete',
    cancelButtonText: 'Cancel',
  });
  if (result.isConfirmed) {
    router.delete('/sport-branches/' + cabor.id);
  }
}

async function duplicateCabor(cabor) {
  const result = await swal.fire({
    icon: 'question',
    title: 'Duplicate Sport Branch',
    text: `Are you sure you want to duplicate "${cabor.name}" along with all of its physical test indicators?`,
    showCancelButton: true,
    confirmButtonColor: '#eab308',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Yes, duplicate',
    cancelButtonText: 'Cancel',
  });
  if (result.isConfirmed) {
    router.post(`/sport-branches/${cabor.id}/duplicate`);
  }
}
</script>
