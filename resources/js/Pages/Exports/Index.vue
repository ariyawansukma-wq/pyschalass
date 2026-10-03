<template>
  <AppLayout>
    <div class="w-full space-y-6 pb-6">
      <!-- Header Banner -->
      <div class="card bg-base-100 border border-base-200 text-base-content shadow-xs">
        <div class="card-body p-6 flex-row items-center justify-between flex-wrap gap-4">
          <div class="space-y-1">
            <div class="badge badge-primary badge-outline text-xs">
              <span>Archive & Download Management</span>
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight">Export Center</h1>
            <p class="text-xs opacity-75">Centralized download repository and background generation history for PDF & Excel files</p>
          </div>

          <!-- Quota Indicator Widget -->
          <div class="flex items-center gap-3 bg-base-200/60 border border-base-200 rounded-xl p-3 text-xs font-semibold shrink-0">
            <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-bold">
              <v-icon name="hi-download" class="w-5 h-5" />
            </div>
            <div>
              <span class="text-[10px] uppercase tracking-wider text-base-content/60 block font-bold">Stored Archive Quota</span>
              <span class="text-sm font-black text-base-content">
                <span class="text-primary">{{ stats.total_files || 0 }}</span> / {{ stats.max_quota || 50 }} Files
                <span class="text-[11px] font-normal text-base-content/60">({{ formatBytes(stats.total_bytes) }})</span>
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Search & Filters -->
      <div class="card bg-base-100 border border-base-200 shadow-xs">
        <div class="card-body p-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="lg:col-span-2 relative">
              <input
                type="text"
                v-model="searchQuery"
                @input="onFilterChange"
                placeholder="Search by report title or file name..."
                class="input input-bordered input-sm w-full pl-8"
              />
              <v-icon name="hi-search" class="w-4 h-4 text-base-content/50 absolute left-2.5 top-2.5" />
            </div>

            <div>
              <select
                v-model="filterType"
                @change="onFilterChange"
                class="select select-bordered select-sm w-full text-xs"
              >
                <option value="">All File Formats</option>
                <option value="pdf">PDF Documents</option>
                <option value="excel">Excel Spreadsheets</option>
              </select>
            </div>

            <div>
              <select
                v-model="filterFolder"
                @change="onFilterChange"
                class="select select-bordered select-sm w-full text-xs"
              >
                <option value="">All Data Folders</option>
                <option v-for="folder in folders" :key="folder.id" :value="folder.id">
                  {{ folder.name }}
                </option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Generated Reports Table -->
      <div class="card bg-base-100 border border-base-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="table table-sm w-full text-xs">
            <thead>
              <tr class="bg-base-200 text-base-content font-bold">
                <th class="w-12 text-center py-3 px-4">No</th>
                <th class="w-24 text-center py-3 px-4">Format</th>
                <th class="py-3 px-4">Report Details</th>
                <th class="py-3 px-4">Source Folder</th>
                <th class="py-3 px-4 text-center">File Size</th>
                <th class="py-3 px-4">Generated At</th>
                <th class="py-3 px-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-base-200 font-medium">
              <tr v-for="(item, idx) in reports.data" :key="item.id" class="hover transition-colors">
                <td class="text-center py-3 px-4 font-bold text-base-content/60">
                  {{ (reports.current_page ? (reports.current_page - 1) * reports.per_page : 0) + idx + 1 }}
                </td>

                <td class="text-center py-3 px-4">
                  <span
                    v-if="item.file_type === 'pdf'"
                    class="badge badge-error badge-outline badge-sm font-black tracking-wider text-[10px] gap-1"
                  >
                    <v-icon name="hi-document-text" class="w-3 h-3" />
                    PDF
                  </span>
                  <span
                    v-else
                    class="badge badge-success badge-outline badge-sm font-black tracking-wider text-[10px] gap-1"
                  >
                    <v-icon name="hi-download" class="w-3 h-3" />
                    EXCEL
                  </span>
                </td>

                <td class="py-3 px-4">
                  <div class="font-extrabold text-sm text-base-content leading-snug">{{ item.title }}</div>
                  <div class="text-[11px] text-base-content/50 font-mono mt-0.5 truncate max-w-xs sm:max-w-md">
                    {{ item.file_name }}
                  </div>
                </td>

                <td class="py-3 px-4">
                  <span v-if="item.folder" class="badge badge-neutral badge-outline badge-sm font-semibold">
                    {{ item.folder.name }}
                  </span>
                  <span v-else class="text-base-content/40 text-xs">Directory / General</span>
                </td>

                <td class="text-center py-3 px-4 font-bold text-base-content/80 font-mono">
                  {{ item.formatted_size || '-' }}
                </td>

                <td class="py-3 px-4">
                  <div class="text-xs font-semibold text-base-content">{{ formatDateTime(item.created_at) }}</div>
                  <div class="text-[10px] text-base-content/50">{{ item.user ? item.user.name : 'System' }}</div>
                </td>

                <td class="py-3 px-4 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <button
                      @click="downloadReportFile(item)"
                      class="btn btn-primary btn-sm btn-outline gap-1.5 cursor-pointer font-bold"
                      title="Download file"
                    >
                      <v-icon name="hi-download" class="size-[1.1em]" />
                      <span>Download</span>
                    </button>
                    <button
                      @click="deleteReport(item)"
                      class="btn btn-error btn-sm btn-outline btn-square cursor-pointer"
                      title="Delete archive file"
                    >
                      <v-icon name="hi-trash" class="size-[1.1em]" />
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="!reports.data || reports.data.length === 0">
                <td colspan="7" class="py-12 text-center text-base-content/50">
                  <div class="w-12 h-12 rounded-full bg-base-200 flex items-center justify-center mx-auto mb-3 text-base-content/40">
                    <v-icon name="hi-download" class="w-6 h-6" />
                  </div>
                  <p class="font-bold text-sm text-base-content/80">No Generated Reports in Archive</p>
                  <p class="text-xs text-base-content/50 max-w-sm mx-auto mt-1">
                    When you export PDF or Excel reports from Folders or the Athlete Directory, they will automatically be saved and accessible here.
                  </p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <Pagination :links="reports.links" :meta="reports" />
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, inject } from 'vue';
import { router } from '@inertiajs/vue3';
import { saveAs } from 'file-saver';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const swal = inject('$swal');

const props = defineProps({
  reports: { type: Object, required: true },
  folders: { type: Array, default: () => [] },
  stats: { type: Object, default: () => ({ total_files: 0, total_bytes: 0, max_quota: 50 }) },
  filters: { type: Object, default: () => ({}) },
});

const searchQuery = ref(props.filters.search || '');
const filterType = ref(props.filters.file_type || '');
const filterFolder = ref(props.filters.folder_id || '');

let filterTimeout = null;
function onFilterChange() {
  clearTimeout(filterTimeout);
  filterTimeout = setTimeout(() => {
    router.get('/exports', {
      search: searchQuery.value,
      file_type: filterType.value,
      folder_id: filterFolder.value,
    }, { preserveState: true, replace: true });
  }, 300);
}

function formatBytes(bytes) {
  if (!bytes) return '0 KB';
  if (bytes >= 1048576) {
    return (bytes / 1048576).toFixed(1) + ' MB';
  }
  return (bytes / 1024).toFixed(0) + ' KB';
}

function formatDateTime(dStr) {
  if (!dStr) return '-';
  const d = new Date(dStr);
  return d.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

function downloadReportFile(item) {
  saveAs('/exports/' + item.id + '/download', item.file_name);
}

async function deleteReport(item) {
  const result = await swal.fire({
    icon: 'warning',
    title: 'Delete Export Archive',
    text: `Are you sure you want to delete "${item.title}" (${item.file_name})? This file will be permanently removed from storage.`,
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Yes, delete',
    cancelButtonText: 'Cancel',
  });

  if (result.isConfirmed) {
    router.delete('/exports/' + item.id, {
      preserveScroll: true,
      onSuccess: () => {
        swal.fire({
          icon: 'success',
          title: 'Deleted!',
          text: 'The file has been deleted from the Export Center.',
          timer: 1500,
          showConfirmButton: false,
        });
      },
    });
  }
}
</script>
