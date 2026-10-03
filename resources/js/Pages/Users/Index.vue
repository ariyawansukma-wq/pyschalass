<template>
  <AppLayout>
    <div class="w-full space-y-6">
      <!-- Header Banner -->
      <div class="card bg-base-100 border border-base-200 text-base-content shadow-xs">
        <div class="card-body p-6 flex-row items-center justify-between flex-wrap gap-4">
          <div class="space-y-1">
            <div class="badge badge-primary badge-outline text-xs">
              <span>Security & Access Control</span>
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight">Account Management</h1>
            <p class="text-xs opacity-75">Manage user accounts, roles (Admin / Officer), and system access credentials</p>
          </div>
          <button @click="openCreateModal" class="btn btn-primary btn-sm gap-2 cursor-pointer shadow-xs">
            <v-icon name="hi-plus-circle" class="size-[1.2em]" />
            <span>Add User Account</span>
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
              placeholder="Search name or username..."
              class="input input-bordered input-sm w-full pl-8"
            />
            <v-icon name="co-magnifying-glass" class="w-4 h-4 text-base-content/50 absolute left-2.5 top-2.5" />
          </div>
          <div class="text-xs text-base-content/70 font-semibold">
            Total Users: <span class="font-bold text-base-content">{{ users.total || users.data?.length || 0 }}</span>
          </div>
        </div>
      </div>

      <!-- User Accounts Table -->
      <div class="card bg-base-100 border border-base-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="table table-sm w-full text-xs">
            <thead>
              <tr class="bg-base-200 text-base-content font-bold">
                <th class="py-3.5 px-4">User Profile</th>
                <th class="py-3.5 px-4">Username</th>
                <th class="py-3.5 px-4">Email Address</th>
                <th class="py-3.5 px-4 text-center">Role</th>
                <th class="py-3.5 px-4 text-center">Max Devices</th>
                <th class="py-3.5 px-4 text-center">Status</th>
                <th class="py-3.5 px-4 text-center">Expires At</th>
                <th class="py-3.5 px-4 text-center">Created At</th>
                <th class="py-3.5 px-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-base-200 font-medium">
              <tr v-for="u in users.data" :key="u.id" class="hover">
                <td class="py-3.5 px-4">
                  <div class="flex items-center gap-3">
                    <div class="avatar placeholder">
                      <div class="w-9 h-9 rounded-full bg-primary/10 text-primary font-bold text-xs flex items-center justify-center">
                        <span>{{ u.name ? u.name.substring(0, 2).toUpperCase() : 'US' }}</span>
                      </div>
                    </div>
                    <div>
                      <p class="font-bold text-base-content text-sm">{{ u.name }}</p>
                    </div>
                  </div>
                </td>

                <td class="py-3.5 px-4 text-base-content/80 font-mono text-xs">
                  @{{ u.username }}
                </td>

                <td class="py-3.5 px-4 text-base-content/80 text-xs">
                  {{ u.email || '-' }}
                </td>

                <td class="py-3.5 px-4 text-center">
                  <span :class="['badge badge-sm font-bold',
                    u.role === 'admin'   ? 'badge-secondary badge-outline' :
                    u.role === 'kader'   ? 'badge-success badge-outline' :
                                          'badge-info badge-outline']">
                    {{ u.role === 'admin' ? 'Administrator' : u.role === 'kader' ? 'Kader' : 'Officer' }}
                  </span>
                  <div v-if="u.role === 'kader' && u.instansi" class="text-[10px] opacity-50 mt-0.5 truncate max-w-[100px]">{{ u.instansi }}</div>
                </td>

                <td class="py-3.5 px-4 text-center">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary border border-primary/20 shadow-2xs">
                    <v-icon name="hi-device-mobile" class="size-[1.15em] shrink-0 text-primary" />
                    <span>{{ u.max_devices || 1 }} device{{ (u.max_devices || 1) > 1 ? 's' : '' }}</span>
                  </span>
                </td>

                <td class="py-3.5 px-4 text-center">
                  <div class="flex flex-col items-center justify-center gap-1">
                    <div class="flex items-center gap-2">
                      <input
                        type="checkbox"
                        :checked="u.is_active && !isExpiredUser(u)"
                        :disabled="u.username === 'admin' || authUser.id === u.id"
                        @change="toggleUserStatus(u)"
                        class="toggle toggle-primary toggle-xs cursor-pointer"
                        :title="u.username === 'admin' || authUser.id === u.id ? 'Cannot change status of this account' : 'Toggle account active/inactive'"
                      />
                    </div>
                    <span v-if="!u.is_active" class="badge badge-error badge-outline text-[10px] font-bold">
                      Inactive
                    </span>
                    <span v-else-if="isExpiredUser(u)" class="badge badge-warning text-[10px] font-bold">
                      Expired: {{ formatDate(u.active_until) }}
                    </span>
                    <span v-else-if="u.active_until" class="badge badge-success badge-outline text-[10px] font-semibold">
                      Until {{ formatDate(u.active_until) }}
                    </span>
                    <span v-else class="badge badge-ghost text-[10px] opacity-75">
                      Permanent
                    </span>
                  </div>
                </td>

                <td class="py-3.5 px-4 text-center">
                  <div v-if="u.active_until" class="flex flex-col items-center gap-0.5">
                    <div :class="['flex items-center gap-1 text-xs font-semibold', isExpiredUser(u) ? 'text-error' : 'text-base-content/80']">
                      <v-icon name="hi-clock" class="size-[1em] shrink-0" />
                      <span>{{ formatDate(u.active_until) }}</span>
                    </div>
                    <span v-if="isExpiredUser(u)" class="text-[10px] text-error font-bold">Expired</span>
                  </div>
                  <span v-else class="text-base-content/40 text-xs">—</span>
                </td>

                <td class="py-3.5 px-4 text-center text-base-content/70 font-medium">
                  {{ formatDate(u.created_at) }}
                </td>

                <td class="py-3.5 px-4 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <button @click="sendResetEmail(u)" class="btn btn-primary btn-outline btn-xs gap-1 cursor-pointer" title="Send Password Reset Email">
                      <v-icon name="hi-lock-closed" class="size-[1.2em]" />
                      <span>Reset Email</span>
                    </button>
                    <button @click="openEditModal(u)" class="btn btn-info btn-outline btn-xs gap-1 cursor-pointer">
                      <v-icon name="bi-pencil-square" class="size-[1.2em]" />
                      <span>Edit</span>
                    </button>
                    <button v-if="u.username !== 'admin'" @click="deleteUser(u)" class="btn btn-error btn-outline btn-xs gap-1 cursor-pointer">
                      <v-icon name="hi-trash" class="size-[1.2em]" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!users.data || users.data.length === 0">
                <td colspan="9" class="py-8 text-center text-base-content/50 font-medium">No user accounts found.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <Pagination :links="users.links" :meta="users" />
      </div>

      <!-- Create / Edit User Modal -->
      <dialog :class="['modal', { 'modal-open': showModal }]">
        <div class="modal-box max-w-sm space-y-4">
          <div class="flex items-center justify-between pb-2 border-b border-base-200">
            <h3 class="font-extrabold text-base">
              {{ isEditing ? 'Edit User Account' : 'Create User Account' }}
            </h3>
            <button type="button" @click="showModal = false" class="btn btn-outline btn-square btn-xs">
              <v-icon name="hi-x" class="size-[1.2em]" />
            </button>
          </div>

          <form @submit.prevent="saveUser" class="space-y-4 text-xs">
            <div v-if="Object.keys(userForm.errors).length > 0" class="alert alert-error text-xs p-3">
              <div class="flex flex-col gap-1">
                <div v-for="(err, field) in userForm.errors" :key="field" class="flex items-center gap-2">
                  <v-icon name="hi-exclamation-circle" class="w-4 h-4 shrink-0" />
                  <span>{{ err }}</span>
                </div>
              </div>
            </div>
            <div class="form-control">
              <label class="label text-xs font-bold p-0 mb-1">Full Name <span class="text-error">*</span></label>
              <input type="text" v-model="userForm.name" required placeholder="User full name" class="input input-bordered input-sm w-full">
            </div>

            <div class="form-control">
              <label class="label text-xs font-bold p-0 mb-1">Username <span class="text-error">*</span></label>
              <input type="text" v-model="userForm.username" required placeholder="Username for login" class="input input-bordered input-sm w-full">
            </div>

            <div class="form-control">
              <label class="label text-xs font-bold p-0 mb-1">Email Address</label>
              <input type="email" v-model="userForm.email" placeholder="user@indexfitlab.com" class="input input-bordered input-sm w-full">
            </div>

            <div class="form-control">
              <label class="label text-xs font-bold p-0 mb-1">Role <span class="text-error">*</span></label>
              <select v-model="userForm.role" required class="select select-bordered select-sm w-full text-xs">
                <option value="officer">Officer</option>
                <option value="kader">Kader</option>
                <option value="admin">Administrator</option>
              </select>
            </div>

            <!-- Instansi & No HP — hanya tampil jika role kader -->
            <template v-if="userForm.role === 'kader'">
              <div class="form-control">
                <label class="label text-xs font-bold p-0 mb-1">Instansi / Lembaga</label>
                <input type="text" v-model="userForm.instansi" placeholder="Nama Posyandu / TK / Lembaga" class="input input-bordered input-sm w-full">
              </div>
              <div class="form-control">
                <label class="label text-xs font-bold p-0 mb-1">No. HP</label>
                <input type="text" v-model="userForm.no_hp" placeholder="08xx-xxxx-xxxx" class="input input-bordered input-sm w-full">
              </div>
            </template>

            <div class="form-control">
              <div class="flex items-center justify-between mb-1">
                <label class="label text-xs font-bold p-0">Maximum Allowed Devices <span class="text-error">*</span></label>
                <span class="text-base-content/50 text-[11px]">Active Sessions</span>
              </div>
              <div class="relative">
                <input
                  type="number"
                  v-model.number="userForm.max_devices"
                  min="1"
                  max="50"
                  required
                  placeholder="e.g. 1"
                  class="input input-bordered input-sm w-full pl-8 text-xs"
                />
                <v-icon name="hi-device-mobile" class="w-4 h-4 text-base-content/50 absolute left-2.5 top-2.5" />
              </div>
              <p class="text-[10px] text-base-content/60 mt-1">
                Maximum concurrent devices or browser sessions allowed to be logged in simultaneously.
              </p>
            </div>

            <div class="form-control space-y-1.5 p-2.5 bg-base-200/50 rounded-lg border border-base-200">
              <div class="flex items-center justify-between">
                <label class="label text-xs font-bold p-0">Account Validity Period</label>
                <label class="cursor-pointer flex items-center gap-1.5 text-[11px] text-base-content/80 font-medium">
                  <input 
                    type="checkbox" 
                    v-model="isPermanent" 
                    class="checkbox checkbox-primary checkbox-xs"
                    @change="onPermanentChange"
                  />
                  <span>No Expiration</span>
                </label>
              </div>

              <div v-if="!isPermanent" class="w-full space-y-1">
                <p class="text-[10px] text-base-content/60">Account will be automatically deactivated after this date:</p>
                <VueDatePicker
                  v-model="userForm.active_until"
                  placeholder="Select expiration date..."
                  model-type="yyyy-MM-dd"
                  format="dd MMM yyyy"
                  :teleport="true"
                  input-class="input input-bordered input-sm w-full text-xs"
                />
              </div>
              <div v-else class="flex items-center gap-1 text-[11px] text-success font-medium py-0.5">
                <v-icon name="hi-check-circle" class="size-[1.1em]" />
                <span>Account has no expiration date.</span>
              </div>
            </div>

            <div class="form-control space-y-1">
              <label class="label text-xs font-bold p-0">
                Password
                <span v-if="!isEditing" class="text-error">*</span>
                <span v-else class="opacity-60 font-normal"> (leave blank to keep current)</span>
              </label>
              <div class="relative w-full">
                <label class="input input-sm validator w-full pr-10">
                  <v-icon name="hi-key" class="w-4 h-4 opacity-50 shrink-0" />
                  <input 
                    :type="showPassword ? 'text' : 'password'" 
                    v-model="userForm.password" 
                    :required="!isEditing" 
                    placeholder="Enter password" 
                    minlength="8"
                    pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                    title="Must be more than 8 characters, including number, lowercase letter, uppercase letter"
                  >
                </label>
                <button 
                  type="button" 
                  @click="showPassword = !showPassword" 
                  class="absolute right-2.5 top-1/2 -translate-y-1/2 text-base-content/50 hover:text-base-content flex items-center justify-center cursor-pointer z-10"
                  title="Toggle Password Visibility"
                >
                  <v-icon :name="showPassword ? 'hi-eye-off' : 'hi-eye'" class="size-[1.2em]" />
                </button>
              </div>
              <p class="validator-hint hidden text-[10px] mt-0.5 leading-normal">
                Must be at least 8 characters, including
                <br />At least one number, one lowercase letter, and one uppercase letter
              </p>
            </div>

            <div class="modal-action">
              <button type="button" @click="showModal = false" class="btn btn-outline btn-xs">Cancel</button>
              <button type="submit" :disabled="userForm.processing" class="btn btn-primary btn-xs">
                Save Account
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
import { ref, inject, computed } from 'vue';
import { useForm, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const swal = inject('$swal');
const page = usePage();
const authUser = computed(() => page.props.auth?.user || {});

const props = defineProps({
  users: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
});

const searchQuery = ref(props.filters.search || '');
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const showPassword = ref(false);
const isPermanent = ref(true);

const userForm = useForm({
  name: '',
  username: '',
  email: '',
  role: 'officer',
  instansi: '',
  no_hp: '',
  max_devices: 1,
  password: '',
  active_until: null,
});

function isExpiredUser(u) {
  if (!u.active_until) return false;
  return new Date(u.active_until + 'T23:59:59') < new Date();
}

function onPermanentChange() {
  if (isPermanent.value) {
    userForm.active_until = null;
  }
}

let searchTimeout = null;
function onSearchInput() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    router.get('/users', { search: searchQuery.value }, { preserveState: true, replace: true });
  }, 300);
}

function formatDate(dStr) {
  if (!dStr) return '-';
  const d = new Date(dStr);
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

function openCreateModal() {
  isEditing.value = false;
  editingId.value = null;
  userForm.reset();
  userForm.max_devices = 1;
  isPermanent.value = true;
  showPassword.value = false;
  showModal.value = true;
}

function openEditModal(u) {
  isEditing.value = true;
  editingId.value = u.id;
  userForm.name = u.name;
  userForm.username = u.username;
  userForm.email = u.email || '';
  userForm.role = u.role || 'officer';
  userForm.instansi = u.instansi || '';
  userForm.no_hp = u.no_hp || '';
  userForm.max_devices = u.max_devices || 1;
  userForm.password = '';
  userForm.active_until = u.active_until || null;
  isPermanent.value = !u.active_until;
  showPassword.value = false;
  showModal.value = true;
}

function saveUser() {
  if (isEditing.value) {
    userForm.put('/users/' + editingId.value, {
      onSuccess: () => showModal.value = false,
    });
  } else {
    userForm.post('/users', {
      onSuccess: () => showModal.value = false,
    });
  }
}

function toggleUserStatus(u) {
  router.patch(`/users/${u.id}/toggle-status`, {}, {
    preserveScroll: true,
    onError: (errors) => {
      swal.fire({
        icon: 'error',
        title: 'Error',
        text: errors.error || 'Failed to update user status.',
      });
    }
  });
}

async function deleteUser(u) {
  const result = await swal.fire({
    icon: 'warning',
    title: 'Delete User Account',
    text: `Are you sure you want to delete user account "${u.name}"?`,
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Yes, delete',
    cancelButtonText: 'Cancel',
  });
  if (result.isConfirmed) {
    router.delete('/users/' + u.id);
  }
}

async function sendResetEmail(u) {
  if (!u.email) {
    const { value: email } = await swal.fire({
      icon: 'question',
      title: 'Set Email Address',
      text: `User "${u.name}" does not have an email address. Enter a valid email address to save and send the reset password link:`,
      input: 'email',
      inputPlaceholder: 'user@indexfitlab.com',
      showCancelButton: true,
      confirmButtonColor: '#2563eb',
      cancelButtonColor: '#64748b',
      confirmButtonText: 'Save & Send Link',
      cancelButtonText: 'Cancel',
      inputValidator: (value) => {
        if (!value) {
          return 'Email address is required!';
        }
      }
    });

    if (email) {
      router.post('/users/' + u.id + '/send-reset-link', { email });
    }
  } else {
    const result = await swal.fire({
      icon: 'question',
      title: 'Send Reset Email',
      text: `Send password reset link to "${u.name}" (${u.email})?`,
      showCancelButton: true,
      confirmButtonColor: '#2563eb',
      cancelButtonColor: '#64748b',
      confirmButtonText: 'Yes, send link',
      cancelButtonText: 'Cancel',
    });
    if (result.isConfirmed) {
      router.post('/users/' + u.id + '/send-reset-link');
    }
  }
}
</script>
