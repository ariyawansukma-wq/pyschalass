<template>
  <AppLayout>
    <div class="w-full space-y-6">
      <!-- Header Banner -->
      <div class="card bg-base-100 border border-base-200 text-base-content shadow-xs">
        <div class="card-body p-6 flex-row items-center justify-between flex-wrap gap-4">
          <div class="space-y-1">
            <div class="badge badge-primary badge-outline text-xs">
              <span>Folder Management</span>
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight">Data Folders</h1>
            <p class="text-xs opacity-75">Group athlete physical test batches into data folders</p>
          </div>
          <button v-if="can('manage', 'all')" @click="showModal = true" class="btn btn-primary btn-sm gap-2 cursor-pointer shadow-xs">
            <v-icon name="bi-folder-plus" class="size-[1.2em]" />
            <span>Create Data Folder</span>
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
              placeholder="Search folder name..."
              class="input input-bordered input-sm w-full pl-8"
            />
            <v-icon name="co-magnifying-glass" class="w-4 h-4 text-base-content/50 absolute left-2.5 top-2.5" />
          </div>
          <div class="text-xs text-base-content/70 font-semibold">
            Total Folders: <span class="font-bold text-base-content">{{ folders.total || folders.data?.length || 0 }}</span>
          </div>
        </div>
      </div>

      <!-- Folders Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <div v-for="folder in folders.data" :key="folder.id" class="card bg-base-100 border border-base-200 shadow-xs hover:shadow-md hover:border-primary/40 transition-all">
          <div class="card-body p-5 space-y-4">
            <div class="flex items-start justify-between gap-3">
              <Link :href="'/folders/' + folder.id" class="flex items-center gap-3 group/link min-w-0">
                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold shrink-0 group-hover/link:scale-105 transition-transform">
                  <v-icon name="hi-folder" class="w-6 h-6" />
                </div>
                <div class="min-w-0">
                  <h3 class="font-extrabold text-base-content leading-snug truncate group-hover/link:text-primary transition-colors">{{ folder.name }}</h3>
                  <p class="text-[11px] text-base-content/60 font-medium">Created: {{ formatDate(folder.created_at) }}</p>
                </div>
              </Link>
              <button v-if="can('manage', 'all')" @click="deleteFolder(folder)" class="btn btn-outline btn-error btn-square btn-xs shrink-0">
                <v-icon name="hi-trash" class="size-[1.2em]" />
              </button>
            </div>

            <div class="pt-2 border-t border-base-200 flex items-center justify-between text-xs flex-wrap gap-2">
              <span class="text-base-content/70 font-semibold">
                <span class="font-bold text-base-content">{{ folder.athletes_count || 0 }}</span> Athletes
              </span>

              <div class="flex items-center gap-1.5 flex-wrap">
                <Link :href="'/folders/' + folder.id" class="btn btn-primary btn-outline btn-xs gap-1">
                  <v-icon name="hi-chart-pie" class="size-[1.2em]" />
                  <span>Dashboard</span>
                </Link>
                <button type="button" @click="openPdfModal(folder)" class="btn btn-success btn-outline btn-xs gap-1 cursor-pointer">
                  <v-icon name="hi-document-text" class="size-[1.2em]" />
                  <span>PDF</span>
                </button>
                <Link :href="'/athletes?folder_id=' + folder.id" class="btn btn-outline btn-xs gap-1">
                  <v-icon name="hi-users" class="size-[1.2em]" />
                  <span>Athletes</span>
                </Link>
              </div>
            </div>
          </div>
        </div>

        <div v-if="!folders.data || folders.data.length === 0" class="col-span-full card bg-base-100 border border-base-200 p-12 text-center text-base-content/50 font-medium">
          No data folders found matching search query.
        </div>
      </div>

      <!-- Pagination -->
      <Pagination :links="folders.links" :meta="folders" class="rounded-box border border-base-200" />

      <!-- Create Folder Modal -->
      <dialog :class="['modal', { 'modal-open': showModal }]">
        <div class="modal-box max-w-xs space-y-4">
          <div class="flex items-center justify-between pb-2 border-b border-base-200">
            <h3 class="font-extrabold text-base">Create Data Folder</h3>
            <button type="button" @click="showModal = false" class="btn btn-outline btn-square btn-xs">
              <v-icon name="hi-x" class="size-[1.2em]" />
            </button>
          </div>

          <form @submit.prevent="saveFolder" class="space-y-4">
            <div v-if="Object.keys(folderForm.errors).length > 0" class="alert alert-error text-xs p-3">
              <div class="flex flex-col gap-1">
                <div v-for="(err, field) in folderForm.errors" :key="field" class="flex items-center gap-2">
                  <v-icon name="hi-exclamation-circle" class="w-4 h-4 shrink-0" />
                  <span>{{ err }}</span>
                </div>
              </div>
            </div>
            <div class="form-control">
              <label class="label text-xs font-bold p-0 mb-1">Folder Name <span class="text-error">*</span></label>
              <input type="text" v-model="folderForm.name" required placeholder="e.g.: Phase I Physical Test 2026" class="input input-bordered input-sm w-full">
            </div>

            <div class="modal-action">
              <button type="button" @click="showModal = false" class="btn btn-outline btn-xs">Cancel</button>
              <button type="submit" :disabled="folderForm.processing" class="btn btn-primary btn-xs">
                Save Folder
              </button>
            </div>
          </form>
        </div>
        <form method="dialog" class="modal-backdrop" @click="showModal = false">
          <button>close</button>
        </form>
      </dialog>

      <!-- Export PDF Selection Modal -->
      <dialog :class="['modal', { 'modal-open': showPdfModal }]">
        <div class="modal-box max-w-sm space-y-4 !overflow-visible">
          <div class="flex items-center justify-between pb-2 border-b border-base-200">
            <h3 class="font-extrabold text-base">Export Folder PDF Report</h3>
            <button type="button" @click="showPdfModal = false" class="btn btn-outline btn-square btn-xs">
              <v-icon name="hi-x" class="size-[1.2em]" />
            </button>
          </div>

          <div v-if="selectedFolderForPdf" class="space-y-3">
            <div class="flex items-center gap-3 p-3 bg-base-200 rounded-lg">
              <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold shrink-0">
                <v-icon name="hi-folder" class="w-6 h-6" />
              </div>
              <div class="min-w-0">
                <p class="font-bold text-sm truncate">{{ selectedFolderForPdf.name }}</p>
                <p class="text-xs text-base-content/60">{{ selectedFolderForPdf.athletes_count || 0 }} Athletes</p>
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
                :teleport="true"
              />
            </div>
          </div>

          <div class="modal-action">
            <button type="button" @click="showPdfModal = false" class="btn btn-outline btn-xs">Cancel</button>
            <button
              type="button"
              @click="generateFolderPdf"
              :disabled="!selectedFolderForPdf || !selectedInstitutionId"
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
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, inject, onMounted } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import { Select } from 'vue3-select-component';
import 'vue3-select-component/styles';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { useAbility } from '@/Composables/useAbility';
import { useSelect2Options } from '@/Composables/useSelect2Options';

const { can } = useAbility();

const swal = inject('$swal');

const props = defineProps({
  folders: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  institutions: { type: Array, default: () => [] },
});

const searchQuery = ref(props.filters.search || '');
const showModal = ref(false);
const showPdfModal = ref(false);
const selectedFolderForPdf = ref(null);
const selectedInstitutionId = ref(props.institutions?.[0]?.id || '');

const { options: institutionOptions, fetchOptions: fetchInstitutionOptions } = useSelect2Options('institutions');

onMounted(() => {
  if (props.institutions && props.institutions.length > 0) {
    institutionOptions.value = props.institutions.map(inst => ({
      label: inst.name,
      value: Number(inst.id),
    }));
  } else {
    fetchInstitutionOptions();
  }
});

function openPdfModal(folder) {
  selectedFolderForPdf.value = folder;
  if (!selectedInstitutionId.value && props.institutions?.[0]?.id) {
    selectedInstitutionId.value = props.institutions[0].id;
  }
  showPdfModal.value = true;
}

function generateFolderPdf() {
  if (!selectedFolderForPdf.value || !selectedInstitutionId.value) return;
  const url = `/folders/${selectedFolderForPdf.value.id}/export-pdf?institution_id=${selectedInstitutionId.value}`;
  window.open(url, '_blank');
  showPdfModal.value = false;
}

const folderForm = useForm({
  name: '',
});

let searchTimeout = null;
function onSearchInput() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    router.get('/folders', { search: searchQuery.value }, { preserveState: true, replace: true });
  }, 300);
}

function formatDate(dStr) {
  if (!dStr) return '-';
  const d = new Date(dStr);
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

function saveFolder() {
  folderForm.post('/folders', {
    onSuccess: () => {
      showModal.value = false;
      folderForm.reset();
    },
  });
}

async function deleteFolder(folder) {
  const result = await swal.fire({
    icon: 'warning',
    title: 'Delete Data Folder',
    text: `Are you sure you want to delete data folder "${folder.name}"?`,
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Yes, delete',
    cancelButtonText: 'Cancel',
  });
  if (result.isConfirmed) {
    router.delete('/folders/' + folder.id);
  }
}
</script>
