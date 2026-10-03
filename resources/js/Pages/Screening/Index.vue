<template>
  <AppLayout>
    <div class="w-full space-y-6">

      <!-- Header -->
      <div class="card bg-base-100 border border-base-200 shadow-xs">
        <div class="card-body p-6 flex-row items-center justify-between flex-wrap gap-4">
          <div class="space-y-1">
            <div class="badge badge-success badge-outline text-xs">Skrining ISKAD-V</div>
            <h1 class="text-2xl font-extrabold tracking-tight">Riwayat Skrining</h1>
            <p class="text-xs opacity-75">Daftar anak yang telah dilakukan skrining keseimbangan</p>
          </div>
          <a :href="route('screening.create')" class="btn btn-success btn-sm gap-2 shadow-xs">
            <v-icon name="hi-plus-circle" class="size-[1.2em]" />
            Mulai Skrining Baru
          </a>
        </div>
      </div>

      <!-- Search -->
      <div class="card bg-base-100 border border-base-200 shadow-xs">
        <div class="card-body p-4">
          <div class="relative">
            <input
              v-model="searchQuery"
              @input="onSearch"
              type="text"
              placeholder="Cari nama anak..."
              class="input input-bordered input-sm w-full pl-8"
            />
            <v-icon name="hi-search" class="w-4 h-4 text-base-content/40 absolute left-2.5 top-2.5" />
          </div>
        </div>
      </div>

      <!-- Grid cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

        <!-- Empty state -->
        <div v-if="!screenings.data || screenings.data.length === 0"
             class="col-span-full card bg-base-100 border border-dashed border-base-300 shadow-xs">
          <div class="card-body py-14 items-center text-center gap-3">
            <v-icon name="hi-document-text" class="w-10 h-10 opacity-20" />
            <p class="font-semibold opacity-60">
              {{ searchQuery ? `Tidak ada anak dengan nama "${searchQuery}"` : 'Belum ada data skrining' }}
            </p>
            <a v-if="!searchQuery" :href="route('screening.create')" class="btn btn-success btn-sm mt-1 gap-2">
              <v-icon name="hi-plus-circle" class="size-[1.2em]" />
              Mulai Skrining Pertama
            </a>
          </div>
        </div>

        <!-- Cards -->
        <div
          v-for="s in screenings.data"
          :key="s.id"
          class="card bg-base-100 border border-base-200 shadow-xs hover:shadow-md transition-shadow"
        >
          <div class="card-body p-4">
            <!-- Header card -->
            <div class="flex justify-between items-start mb-2">
              <div>
                <h3 class="font-bold text-base">{{ s.nama_anak }}</h3>
                <p class="text-xs opacity-50 mt-0.5">
                  {{ s.umur_bulan }} Bulan &bull; {{ formatDate(s.created_at) }}
                </p>
              </div>
              <span class="badge badge-success badge-outline text-xs font-bold">
                Skor: {{ s.total_skor }}
              </span>
            </div>

            <!-- Divider + actions -->
            <div class="flex justify-between items-center pt-3 mt-1 border-t border-base-200/60">
              <span class="text-sm font-semibold" :class="kategoriColor(s.kategori)">
                {{ s.kategori }}
              </span>
              <div class="flex items-center gap-2">
                <Link
                  :href="route('screening.show', s.id)"
                  class="btn btn-xs btn-ghost gap-1 text-primary"
                >
                  <v-icon name="hi-eye" class="size-[1em]" />
                  Detail
                </Link>
                <button @click="confirmDelete(s)" class="btn btn-xs btn-ghost text-error">
                  <v-icon name="hi-trash" class="size-[1em]" />
                </button>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Pagination -->
      <Pagination
        v-if="screenings.links && screenings.links.length > 3"
        :links="screenings.links"
        :meta="screenings"
      />

    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
  screenings: { type: Object, required: true },
  filters:    { type: Object, default: () => ({}) },
});

const searchQuery = ref(props.filters.search ?? '');
let searchTimeout = null;

function onSearch() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    router.get(route('screening.index'), { search: searchQuery.value || undefined }, {
      preserveState: true, replace: true,
    });
  }, 400);
}

async function confirmDelete(screening) {
  const swal = (await import('sweetalert2')).default;
  const result = await swal.fire({
    icon: 'warning',
    title: 'Hapus Data Skrining',
    html: `Hapus data skrining <b>${screening.nama_anak}</b>?<br><small class="opacity-60">Tindakan ini tidak bisa dibatalkan.</small>`,
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor:  '#64748b',
    confirmButtonText:  'Ya, hapus',
    cancelButtonText:   'Batal',
  });
  if (result.isConfirmed) {
    router.delete(route('screening.destroy', screening.id));
  }
}

function formatDate(dt) {
  if (!dt) return '—';
  return new Date(dt).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
}

function kategoriColor(kategori) {
  if (kategori === 'NORMAL')        return 'text-success';
  if (kategori === 'RISIKO RINGAN') return 'text-warning';
  return 'text-error';
}
</script>
