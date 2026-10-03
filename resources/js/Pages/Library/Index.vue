<template>
  <AppLayout>
    <div class="w-full space-y-6">

      <!-- Header -->
      <div class="card bg-base-100 border border-base-200 shadow-xs">
        <div class="card-body p-6 flex-row items-center justify-between flex-wrap gap-4">
          <div class="space-y-1">
            <div class="badge badge-info badge-outline text-xs">Pustaka Ilmiah</div>
            <h1 class="text-2xl font-extrabold tracking-tight">Perpustakaan</h1>
            <p class="text-xs opacity-75">Jurnal, Buku, HAKI, dan Modul dari Tim ISKAD-V</p>
          </div>
        </div>
      </div>

      <!-- Form tambah karya (admin only) -->
      <div v-if="can('manage', 'Library')" class="card bg-base-100 border border-base-200 shadow-xs">
        <div class="card-body p-5">
          <h2 class="text-sm font-bold mb-4 flex items-center gap-2">
            <v-icon name="hi-plus-circle" class="w-4 h-4 text-primary" />
            Tambah Karya Ilmiah
          </h2>
          <form @submit.prevent="submitForm" class="space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div class="sm:col-span-2">
                <input
                  v-model="form.judul"
                  type="text"
                  placeholder="Judul Jurnal / Buku *"
                  required
                  class="input input-bordered input-sm w-full"
                />
              </div>

              <div>
                <select v-model="form.jenis" required class="select select-bordered select-sm w-full">
                  <option value="">Pilih Jenis *</option>
                  <option v-for="j in jenisOptions" :key="j" :value="j">{{ j }}</option>
                </select>
              </div>

              <div>
                <input
                  v-model.number="form.tahun"
                  type="number"
                  placeholder="Tahun Terbit *"
                  min="1900"
                  :max="currentYear"
                  required
                  class="input input-bordered input-sm w-full"
                />
              </div>

              <div class="sm:col-span-2">
                <input
                  v-model="form.link_eksternal"
                  type="url"
                  placeholder="Link Eksternal (opsional)"
                  class="input input-bordered input-sm w-full"
                />
              </div>

              <div class="sm:col-span-2">
                <textarea
                  v-model="form.deskripsi"
                  placeholder="Deskripsi singkat (opsional)"
                  rows="2"
                  class="textarea textarea-bordered textarea-sm w-full"
                ></textarea>
              </div>

              <div class="sm:col-span-2">
                <label class="text-xs font-semibold opacity-60 block mb-1">Upload PDF (opsional, maks 10 MB)</label>
                <input
                  @change="onFileChange"
                  type="file"
                  accept="application/pdf"
                  class="file-input file-input-bordered file-input-sm w-full"
                />
              </div>
            </div>

            <div class="flex items-center gap-3 pt-1">
              <button type="submit" :disabled="isSubmitting" class="btn btn-primary btn-sm gap-2">
                <span v-if="isSubmitting" class="loading loading-spinner loading-xs"></span>
                <v-icon v-else name="hi-plus-circle" class="size-[1.2em]" />
                Simpan
              </button>
              <p v-if="submitError" class="text-xs text-error">{{ submitError }}</p>
            </div>
          </form>
        </div>
      </div>

      <!-- Filter -->
      <div class="card bg-base-100 border border-base-200 shadow-xs">
        <div class="card-body p-4">
          <div class="flex flex-wrap gap-3">
            <div class="relative flex-1 min-w-[200px]">
              <input
                v-model="searchQuery"
                @input="onFilterChange"
                type="text"
                placeholder="Cari judul atau deskripsi..."
                class="input input-bordered input-sm w-full pl-8"
              />
              <v-icon name="hi-search" class="w-4 h-4 text-base-content/40 absolute left-2.5 top-2.5" />
            </div>
            <select
              v-model="filterJenis"
              @change="onFilterChange"
              class="select select-bordered select-sm"
            >
              <option value="">Semua Jenis</option>
              <option v-for="j in jenisOptions" :key="j" :value="j">{{ j }}</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Grid karya -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

        <!-- Empty state -->
        <div v-if="!karyas.data || karyas.data.length === 0"
             class="col-span-full card bg-base-100 border border-dashed border-base-300 shadow-xs">
          <div class="card-body py-14 items-center text-center gap-3">
            <v-icon name="hi-document-text" class="w-10 h-10 opacity-20" />
            <p class="font-semibold opacity-60">
              {{ searchQuery || filterJenis ? 'Tidak ada hasil yang cocok' : 'Belum ada karya ilmiah' }}
            </p>
          </div>
        </div>

        <!-- Cards -->
        <div
          v-for="karya in karyas.data"
          :key="karya.id"
          class="card bg-base-100 border border-base-200 shadow-xs hover:shadow-md transition-shadow"
        >
          <div class="card-body p-4">
            <!-- Badge jenis + tahun -->
            <div class="flex items-center gap-2 mb-2">
              <span class="badge badge-info badge-sm font-bold text-xs uppercase">{{ karya.jenis }}</span>
              <span class="text-xs opacity-50">{{ karya.tahun }}</span>
            </div>

            <!-- Judul -->
            <h3 class="font-bold text-sm leading-snug mb-2 line-clamp-2">{{ karya.judul }}</h3>

            <!-- Deskripsi -->
            <p v-if="karya.deskripsi" class="text-xs opacity-60 mb-3 line-clamp-2">{{ karya.deskripsi }}</p>

            <!-- Aksi -->
            <div class="flex gap-2 mt-auto pt-3 border-t border-base-200/60">
              <a
                v-if="karya.file_path"
                :href="fileUrl(karya.file_path)"
                target="_blank"
                class="btn btn-xs btn-info btn-outline flex-1 gap-1"
              >
                📄 Buka PDF
              </a>
              <a
                v-if="karya.link_eksternal"
                :href="karya.link_eksternal"
                target="_blank"
                class="btn btn-xs btn-ghost flex-1 gap-1"
              >
                🌐 Link
              </a>
              <button
                v-if="can('manage', 'Library')"
                @click="confirmDelete(karya)"
                class="btn btn-xs btn-ghost text-error"
                title="Hapus"
              >
                <v-icon name="hi-trash" class="size-[1em]" />
              </button>
            </div>
          </div>
        </div>

      </div>

      <!-- Pagination -->
      <Pagination
        v-if="karyas.links && karyas.links.length > 3"
        :links="karyas.links"
        :meta="karyas"
      />

    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { useAbility } from '@/Composables/useAbility';

const { can } = useAbility();

const props = defineProps({
  karyas:  { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
});

// ── Filter ────────────────────────────────────────────────────────────────
const searchQuery = ref(props.filters.search  ?? '');
const filterJenis  = ref(props.filters.jenis  ?? '');
let filterTimeout  = null;

function onFilterChange() {
  clearTimeout(filterTimeout);
  filterTimeout = setTimeout(() => {
    router.get(route('library.index'), {
      search:   searchQuery.value  || undefined,
      jenis:    filterJenis.value  || undefined,
    }, { preserveState: true, replace: true });
  }, 350);
}

// ── Form tambah ───────────────────────────────────────────────────────────
const jenisOptions  = ['Jurnal', 'Buku', 'HAKI', 'Modul', 'Lainnya'];
const currentYear   = new Date().getFullYear();
const isSubmitting  = ref(false);
const submitError   = ref('');

const form = ref({
  judul: '', jenis: '', tahun: '', deskripsi: '', link_eksternal: '',
});
let fileInput = null;

function onFileChange(e) {
  fileInput = e.target.files[0] ?? null;
}

async function submitForm() {
  isSubmitting.value = true;
  submitError.value  = '';

  const fd = new FormData();
  Object.entries(form.value).forEach(([k, v]) => { if (v) fd.append(k, v); });
  if (fileInput) fd.append('file_dokumen', fileInput);

  try {
    await axios.post(route('library.store'), fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    form.value    = { judul: '', jenis: '', tahun: '', deskripsi: '', link_eksternal: '' };
    fileInput     = null;
    router.reload({ preserveScroll: true });
  } catch (err) {
    const errors = err?.response?.data?.errors;
    if (errors) {
      submitError.value = Object.values(errors).flat().join(' ');
    } else {
      submitError.value = err?.response?.data?.message ?? 'Gagal menyimpan.';
    }
  } finally {
    isSubmitting.value = false;
  }
}

// ── Delete ────────────────────────────────────────────────────────────────
async function confirmDelete(karya) {
  const swal = (await import('sweetalert2')).default;
  const result = await swal.fire({
    icon: 'warning',
    title: 'Hapus Karya Ilmiah',
    html: `Hapus <b>${karya.judul}</b>? File PDF juga akan dihapus.`,
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor:  '#64748b',
    confirmButtonText: 'Ya, hapus',
    cancelButtonText:  'Batal',
  });
  if (result.isConfirmed) {
    router.delete(route('library.destroy', karya.id));
  }
}

// ── Helpers ───────────────────────────────────────────────────────────────
function fileUrl(path) {
  // path disimpan relatif dari storage/app/public/
  return `/storage/${path}`;
}
</script>
