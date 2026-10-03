<template>
  <AppLayout>
    <div class="space-y-6 w-full overflow-x-hidden">
 
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-base-200 pb-5">
        <div class="space-y-1">
          <div class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-primary">
            <span class="inline-block size-2 rounded-full bg-primary animate-pulse"></span>
            System Settings
          </div>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-base-content tracking-tight">Institution Profiles</h1>
          <p class="text-sm text-base-content/70 max-w-2xl">Configure organization headers, official logos, and report signature templates used in PDF exports.</p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
          <div class="stats bg-base-100 border border-base-200 shadow-xs">
            <div class="stat py-2 px-4 text-center">
              <div class="stat-value text-xl text-primary font-bold">{{ institutions.data?.length ?? 0 }}</div>
              <div class="stat-title text-[10px] font-bold uppercase tracking-wider text-base-content/60">Registered</div>
            </div>
          </div>
          <button @click="openCreateModal" class="btn btn-primary btn-sm sm:btn-md gap-2" id="btn-add-institution">
            <v-icon name="hi-plus" class="size-4" />
            Add Institution
          </button>
        </div>
      </div>

      <div class="space-y-4">

        <!-- Empty State -->
        <div v-if="!institutions.data || institutions.data.length === 0" class="card bg-base-100 border-2 border-dashed border-base-300 p-8 sm:p-12 text-center items-center">
          <div class="size-14 rounded-full bg-primary/10 text-primary flex items-center justify-center mb-4">
            <v-icon name="hi-office-building" class="size-8" />
          </div>
          <h3 class="text-lg font-bold text-base-content">No institutions yet</h3>
          <p class="text-sm text-base-content/70 max-w-sm mt-1">Add your first institution to start configuring headers and report templates.</p>
          <button @click="openCreateModal" class="btn btn-primary btn-sm gap-2 mt-5">
            <v-icon name="hi-plus" class="size-4" />
            Add Institution
          </button>
        </div>

        <!-- Institution Card -->
        <div v-for="inst in institutions.data" :key="inst.id" class="card bg-base-100 border border-base-200 shadow-sm hover:shadow-md transition-shadow duration-200 overflow-hidden">

          <!-- Card header -->
          <div class="flex flex-wrap items-center gap-3 p-4 sm:p-5 border-b border-base-200 bg-base-100">
            <div class="w-1 h-10 rounded-full bg-primary shrink-0"></div>
            <div class="avatar placeholder shrink-0">
              <div class="bg-primary/10 text-primary rounded-lg w-10 h-10 font-extrabold text-base flex items-center justify-center">
                <span>{{ inst.name?.charAt(0)?.toUpperCase() ?? 'I' }}</span>
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <h2 class="text-base font-bold text-base-content truncate">{{ inst.name }}</h2>
              <p class="text-xs text-base-content/60 flex items-center gap-1 mt-0.5 truncate">
                <v-icon name="hi-location-marker" class="size-3.5 shrink-0" />
                {{ inst.address || 'No address provided' }}
              </p>
            </div>
            <div class="flex items-center gap-2 shrink-0 ml-auto">
              <button @click="openEditModal(inst)" class="btn btn-outline btn-info btn-sm gap-1.5" :id="`btn-edit-${inst.id}`">
                <v-icon name="bi-pencil-square" class="size-3.5" />
                Edit
              </button>
              <button @click="deleteInstitution(inst)" class="btn btn-outline btn-error btn-sm btn-square" :id="`btn-delete-${inst.id}`">
                <v-icon name="hi-trash" class="size-4" />
              </button>
            </div>
          </div>

          <!-- Card body: 3-column info grid -->
          <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-base-200">

            <!-- Contact Info -->
            <div class="p-4 sm:p-5 space-y-2">
              <div class="text-[11px] font-bold uppercase tracking-wider text-base-content/60 flex items-center gap-1.5 mb-3">
                <v-icon name="hi-phone" class="size-3.5" />
                Contact
              </div>
              <div class="space-y-1.5 text-xs">
                <div class="flex items-baseline gap-2" v-if="inst.phone">
                  <span class="text-base-content/50 font-semibold min-w-[55px]">Phone</span>
                  <span class="text-base-content font-medium truncate">{{ inst.phone }}</span>
                </div>
                <div class="flex items-baseline gap-2" v-if="inst.email">
                  <span class="text-base-content/50 font-semibold min-w-[55px]">Email</span>
                  <span class="text-base-content font-medium truncate">{{ inst.email }}</span>
                </div>
                <div class="text-base-content/50 italic text-xs" v-if="!inst.phone && !inst.email">No contact info</div>
              </div>
            </div>

            <!-- Signatories -->
            <div class="p-4 sm:p-5 space-y-2">
              <div class="text-[11px] font-bold uppercase tracking-wider text-base-content/60 flex items-center gap-1.5 mb-3">
                <v-icon name="hi-identification" class="size-3.5" />
                Signature
              </div>
              <div class="space-y-1.5 text-xs">
                <div class="flex items-baseline gap-2" v-if="inst.signer_name">
                  <span class="text-base-content/50 font-semibold min-w-[70px]">Signer</span>
                  <span class="text-base-content font-bold truncate">{{ inst.signer_name }}</span>
                </div>
                <div class="flex items-baseline gap-2" v-if="inst.signer_title">
                  <span class="text-base-content/50 font-semibold min-w-[70px]">Title</span>
                  <span class="text-base-content font-medium truncate">{{ inst.signer_title }}</span>
                </div>
                <div class="flex items-baseline gap-2" v-if="inst.signature_city || inst.signature_date">
                  <span class="text-base-content/50 font-semibold min-w-[70px]">Place & Date</span>
                  <span class="text-base-content font-medium truncate">
                    {{ inst.signature_city || '—' }}<template v-if="inst.signature_date">, {{ formatDate(inst.signature_date) }}</template>
                  </span>
                </div>
                <div class="text-base-content/50 italic text-xs" v-if="!inst.signer_name && !inst.signer_title">No signatory configured</div>
              </div>
            </div>

            <!-- Logos -->
            <div class="p-4 sm:p-5 space-y-2">
              <div class="flex items-center justify-between text-[11px] font-bold uppercase tracking-wider text-base-content/60 mb-3">
                <span class="flex items-center gap-1.5">
                  <v-icon name="hi-photograph" class="size-3.5" />
                  Official Logos
                </span>
                <span class="badge badge-primary badge-sm font-bold">{{ inst.logos?.length ?? 0 }}</span>
              </div>
              <div class="flex flex-wrap gap-2 items-center">
                <!-- Existing logos -->
                <div v-for="logo in inst.logos" :key="logo.id" class="bg-base-200/60 border border-base-300 rounded-lg p-2 flex items-center gap-2.5 hover:border-primary/50 transition-colors">
                  <div class="flex items-center justify-center min-w-[48px] min-h-[32px]">
                    <img :src="'/storage/' + logo.logo_path" :style="{ height: logo.height_px + 'px' }" class="object-contain max-w-[100px] max-h-10" alt="Logo" />
                  </div>
                  <div class="flex flex-col gap-1 border-l border-base-300 pl-2">
                    <div class="flex items-center gap-1">
                      <input
                        type="number"
                        :value="logo.height_px"
                        @change="e => updateLogoHeight(inst.id, logo, e.target.value)"
                        min="20"
                        max="150"
                        class="input input-bordered input-xs w-12 text-center p-0 font-semibold"
                        :id="`logo-height-${logo.id}`"
                        title="Logo height in px"
                      />
                      <span class="text-[10px] font-semibold text-base-content/60 uppercase">px</span>
                    </div>
                    <button
                      @click="deleteLogo(inst.id, logo)"
                      class="btn btn-outline btn-error btn-square btn-xs"
                      title="Remove logo"
                      :id="`btn-del-logo-${logo.id}`"
                    >
                      <v-icon name="hi-x" class="size-3" />
                    </button>
                  </div>
                </div>

                <!-- Upload zone -->
                <label class="flex flex-col items-center justify-center w-20 h-16 border-2 border-dashed border-base-300 hover:border-primary hover:bg-primary/5 rounded-lg text-base-content/60 hover:text-primary cursor-pointer transition-colors text-[10px] font-bold uppercase tracking-wider" :for="`logo-upload-${inst.id}`">
                  <v-icon name="hi-upload" class="size-4 mb-0.5" />
                  <span>Add Logo</span>
                  <input :id="`logo-upload-${inst.id}`" type="file" @change="e => uploadLogo(inst.id, e.target.files[0])" accept="image/*" class="hidden" />
                </label>
              </div>
            </div>

          </div>
        </div>
      </div>

      <Pagination :links="institutions.links" :meta="institutions" class="rounded-box border border-base-200" />

      <dialog :class="['modal', { 'modal-open': showModal }]" @keydown.esc="showModal = false">
        <div class="modal-box max-w-lg p-0 overflow-hidden bg-base-100 border border-base-200 shadow-xl">
          <!-- Modal header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-base-200 bg-base-100">
            <div>
              <span class="text-[10px] font-bold uppercase tracking-wider text-primary block mb-0.5">{{ isEditing ? 'Update' : 'New' }}</span>
              <h3 class="text-base font-bold text-base-content">{{ isEditing ? 'Edit Institution Profile' : 'Add Institution' }}</h3>
            </div>
            <button type="button" @click="showModal = false" class="btn btn-sm btn-circle btn-outline" id="btn-modal-close">
              <v-icon name="hi-x" class="size-4" />
            </button>
          </div>

          <form @submit.prevent="saveInstitution" class="divide-y divide-base-200">
            <!-- Errors -->
            <div v-if="Object.keys(instForm.errors).length > 0" class="alert alert-error text-xs rounded-none border-x-0 border-t-0 p-4 flex items-start gap-2">
              <v-icon name="hi-exclamation-circle" class="size-4 shrink-0 mt-0.5" />
              <div>
                <div v-for="(err, field) in instForm.errors" :key="field">{{ err }}</div>
              </div>
            </div>

            <!-- Group: Identity -->
            <fieldset class="p-6 space-y-4">
              <legend class="text-[11px] font-bold uppercase tracking-wider text-base-content/60 mb-2">Identity</legend>
              <div class="form-control w-full space-y-1">
                <label class="label py-0" for="f-name">
                  <span class="label-text text-xs font-bold">Institution Name <span class="text-error">*</span></span>
                </label>
                <input id="f-name" type="text" v-model="instForm.name" required placeholder="e.g. KONI Provinsi / Dispora" class="input input-bordered input-sm w-full focus:input-primary" />
              </div>
              <div class="form-control w-full space-y-1">
                <label class="label py-0" for="f-address">
                  <span class="label-text text-xs font-bold">Address</span>
                </label>
                <textarea id="f-address" v-model="instForm.address" rows="2" placeholder="Full office address..." class="textarea textarea-bordered textarea-sm w-full focus:textarea-primary min-h-[70px]"></textarea>
              </div>
            </fieldset>

            <!-- Group: Contact -->
            <fieldset class="p-6 space-y-4">
              <legend class="text-[11px] font-bold uppercase tracking-wider text-base-content/60 mb-2">Contact</legend>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="form-control w-full space-y-1">
                  <label class="label py-0" for="f-phone">
                    <span class="label-text text-xs font-bold">Phone</span>
                  </label>
                  <input id="f-phone" type="text" v-model="instForm.phone" placeholder="(021) 123456" class="input input-bordered input-sm w-full focus:input-primary" />
                </div>
                <div class="form-control w-full space-y-1">
                  <label class="label py-0" for="f-email">
                    <span class="label-text text-xs font-bold">Email</span>
                  </label>
                  <input id="f-email" type="email" v-model="instForm.email" placeholder="info@institution.org" class="input input-bordered input-sm w-full focus:input-primary" />
                </div>
              </div>
            </fieldset>

            <!-- Group: Report Signature -->
            <fieldset class="p-6 space-y-4">
              <legend class="text-[11px] font-bold uppercase tracking-wider text-base-content/60 mb-2">Report Signature</legend>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="form-control w-full space-y-1">
                  <label class="label py-0" for="f-signer-title">
                    <span class="label-text text-xs font-bold">Title</span>
                  </label>
                  <input id="f-signer-title" type="text" v-model="instForm.signer_title" placeholder="e.g. Ketua Umum" class="input input-bordered input-sm w-full focus:input-primary" />
                </div>
                <div class="form-control w-full space-y-1">
                  <label class="label py-0" for="f-signer-name">
                    <span class="label-text text-xs font-bold">Signer Name</span>
                  </label>
                  <input id="f-signer-name" type="text" v-model="instForm.signer_name" placeholder="e.g. Dr. H. John Doe, M.Pd." class="input input-bordered input-sm w-full focus:input-primary" />
                </div>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="form-control w-full space-y-1">
                  <label class="label py-0" for="f-sig-city">
                    <span class="label-text text-xs font-bold">City</span>
                  </label>
                  <input id="f-sig-city" type="text" v-model="instForm.signature_city" placeholder="e.g. Jakarta" class="input input-bordered input-sm w-full focus:input-primary" />
                </div>
                <div class="form-control w-full space-y-1">
                  <label class="label py-0" for="f-sig-date">
                    <span class="label-text text-xs font-bold">Date</span>
                  </label>
                  <VueDatePicker id="f-sig-date" v-model="instForm.signature_date" placeholder="Select date" input-class="input input-bordered input-sm w-full focus:input-primary" />
                </div>
              </div>
            </fieldset>

            <!-- Group: Logos (create only) -->
            <fieldset v-if="!isEditing" class="p-6 space-y-4">
              <legend class="text-[11px] font-bold uppercase tracking-wider text-base-content/60 mb-2">Logos</legend>
              <div class="form-control w-full space-y-1">
                <label class="label py-0" for="f-logos">
                  <span class="label-text text-xs font-bold">Upload Files</span>
                </label>
                <input id="f-logos" type="file" @change="e => instForm.logos = Array.from(e.target.files)" accept="image/*" multiple class="file-input file-input-bordered file-input-sm w-full text-xs" />
                <p class="text-[11px] text-base-content/60 mt-1">JPG, PNG, SVG supported · Max 2 MB per file · Multiple allowed</p>
              </div>
            </fieldset>

            <!-- Actions -->
            <div class="p-4 px-6 bg-base-200/50 flex items-center justify-end gap-2 border-t border-base-200">
              <button type="button" @click="showModal = false" class="btn btn-outline btn-sm" id="btn-cancel">Cancel</button>
              <button type="submit" :disabled="instForm.processing" class="btn btn-primary btn-sm gap-2" id="btn-save">
                <span v-if="instForm.processing" class="loading loading-spinner loading-xs"></span>
                {{ isEditing ? 'Save Changes' : 'Create Institution' }}
              </button>
            </div>
          </form>
        </div>
        <form method="dialog" class="modal-backdrop" @click="showModal = false">
          <button>close</button>
        </form>
      </dialog>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, inject } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const swal = inject('$swal');

const props = defineProps({
  institutions: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
});

const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const instForm = useForm({
  name: '',
  address: '',
  phone: '',
  email: '',
  logos: [],
  signer_name: '',
  signer_title: '',
  signature_city: '',
  signature_date: '',
});

function openCreateModal() {
  isEditing.value = false;
  editingId.value = null;
  instForm.reset();
  instForm.signer_name = '';
  instForm.signer_title = '';
  instForm.signature_city = '';
  instForm.signature_date = '';
  showModal.value = true;
}

function openEditModal(inst) {
  isEditing.value = true;
  editingId.value = inst.id;
  instForm.name = inst.name;
  instForm.address = inst.address || '';
  instForm.phone = inst.phone || '';
  instForm.email = inst.email || '';
  instForm.signer_name = inst.signer_name || '';
  instForm.signer_title = inst.signer_title || '';
  instForm.signature_city = inst.signature_city || '';
  instForm.signature_date = inst.signature_date ? inst.signature_date.substring(0, 10) : '';
  instForm.logos = [];
  showModal.value = true;
}

function saveInstitution() {
  if (isEditing.value) {
    instForm.put('/institutions/' + editingId.value, {
      onSuccess: () => showModal.value = false,
    });
  } else {
    instForm.post('/institutions', {
      onSuccess: () => showModal.value = false,
    });
  }
}

async function uploadLogo(instId, file) {
  if (!file) return;
  const formData = new FormData();
  formData.append('logos', file);
  try {
    await router.post(`/institutions/${instId}/logos`, formData, {
      preserveState: true,
      preserveScroll: true,
    });
  } catch (e) {
    console.error('Failed to upload logo', e);
  }
}

async function updateLogoHeight(instId, logo, newHeight) {
  const height = parseInt(newHeight);
  if (!height || height < 20) return;
  try {
    await router.patch(`/institutions/${instId}/logos/${logo.id}`, { height_px: height }, {
      preserveState: true,
      preserveScroll: true,
    });
  } catch (e) {
    console.error('Failed to update logo height', e);
  }
}

async function deleteLogo(instId, logo) {
  const result = await swal.fire({
    icon: 'warning',
    title: 'Remove Logo',
    text: 'Remove this logo from the institution header?',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Remove',
    cancelButtonText: 'Cancel',
  });
  if (result.isConfirmed) {
    router.delete(`/institutions/${instId}/logos/${logo.id}`, {
      preserveState: true,
      preserveScroll: true,
    });
  }
}

async function deleteInstitution(inst) {
  const result = await swal.fire({
    icon: 'warning',
    title: 'Delete Institution',
    text: `Delete "${inst.name}"? This action cannot be undone.`,
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Delete',
    cancelButtonText: 'Cancel',
  });
  if (result.isConfirmed) {
    router.delete('/institutions/' + inst.id);
  }
}

function formatDate(dateStr) {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
}
</script>

