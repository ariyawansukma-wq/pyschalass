<template>
  <AppLayout>
    <div class="max-w-md mx-auto space-y-6">
      <!-- Header Banner -->
      <div class="card bg-neutral text-neutral-content shadow-sm">
        <div class="card-body p-6 space-y-1">
          <h1 class="text-xl font-extrabold tracking-tight flex items-center gap-2">
            <v-icon name="hi-cog" class="w-6 h-6 text-primary" />
            <span>Pengaturan Akun</span>
          </h1>
          <p class="text-xs opacity-75">Perbarui profil dan password akun Anda</p>
        </div>
      </div>

      <!-- ── Profil (nama + instansi + no_hp) — tampil untuk semua, wajib untuk kader ── -->
      <div class="card bg-base-100 border border-base-200 shadow-xs">
        <div class="card-body p-6 space-y-4">
          <h2 class="text-sm font-bold flex items-center gap-2">
            <v-icon name="hi-user" class="w-4 h-4 text-primary" />
            Informasi Profil
          </h2>

          <div v-if="profileStatus" class="alert alert-success text-xs p-3">
            <v-icon name="hi-check-circle" class="w-4 h-4 shrink-0" />
            <span>{{ profileStatus }}</span>
          </div>

          <form @submit.prevent="submitProfile" class="space-y-3">
            <div class="form-control space-y-1">
              <label class="label text-xs font-bold p-0">Nama Lengkap</label>
              <input v-model="profileForm.name" type="text" required placeholder="Nama lengkap"
                class="input input-bordered input-sm w-full" />
            </div>

            <div v-if="props.authUser?.role === 'kader'" class="space-y-3">
              <div class="form-control space-y-1">
                <label class="label text-xs font-bold p-0">Instansi / Lembaga</label>
                <input v-model="profileForm.instansi" type="text" placeholder="Nama Posyandu / TK / Lembaga"
                  class="input input-bordered input-sm w-full" />
              </div>
              <div class="form-control space-y-1">
                <label class="label text-xs font-bold p-0">No. HP</label>
                <input v-model="profileForm.no_hp" type="text" placeholder="08xx-xxxx-xxxx"
                  class="input input-bordered input-sm w-full" />
              </div>
            </div>

            <!-- Info akun (readonly) -->
            <div class="bg-base-200/50 rounded-lg p-3 space-y-1.5 text-xs">
              <div class="flex justify-between">
                <span class="opacity-50">Username</span>
                <span class="font-mono font-semibold">{{ props.authUser?.username }}</span>
              </div>
              <div class="flex justify-between">
                <span class="opacity-50">Email</span>
                <span class="font-semibold">{{ props.authUser?.email || '—' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="opacity-50">Role</span>
                <span class="font-semibold capitalize">{{ props.authUser?.role }}</span>
              </div>
            </div>

            <div class="flex justify-end">
              <button type="submit" :disabled="profileForm.processing" class="btn btn-primary btn-sm gap-2">
                <span v-if="profileForm.processing" class="loading loading-spinner loading-xs"></span>
                <template v-else>
                  <v-icon name="hi-check-circle" class="size-[1.2em]" />
                  Simpan Profil
                </template>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- ── Ganti Password ── -->
      <div class="card bg-base-100 border border-base-200 shadow-xs">
        <div class="card-body p-6 space-y-4">
          <h2 class="text-sm font-bold flex items-center gap-2">
            <v-icon name="hi-lock-closed" class="w-4 h-4 text-primary" />
            Ganti Password
          </h2>

          <!-- Success Alert -->
          <div v-if="status" class="alert alert-success text-xs p-3">
            <v-icon name="hi-check-circle" class="w-4 h-4 shrink-0" />
            <span>{{ status }}</span>
          </div>

          <!-- Errors Alert -->
          <div v-if="Object.keys(errors).length > 0" class="alert alert-error text-xs p-3">
            <div class="flex flex-col gap-1">
              <div v-for="(err, field) in errors" :key="field" class="flex items-center gap-2">
                <v-icon name="hi-exclamation-circle" class="w-4 h-4 shrink-0" />
                <span>{{ err }}</span>
              </div>
            </div>
          </div>

          <form @submit.prevent="submit" class="space-y-4">
            <div class="form-control w-full space-y-1">
              <label for="current_password" class="label text-xs font-bold p-0">Password Saat Ini</label>
              <div class="relative w-full">
                <label class="input validator w-full pr-10">
                  <v-icon name="hi-key" class="w-4 h-4 opacity-50 shrink-0" />
                  <input :type="showCurrentPassword ? 'text' : 'password'" id="current_password"
                    v-model="form.current_password" required placeholder="Masukkan password saat ini" class="text-xs" />
                </label>
                <button type="button" @click="showCurrentPassword = !showCurrentPassword"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-base-content/50 hover:text-base-content flex items-center justify-center cursor-pointer z-10">
                  <v-icon :name="showCurrentPassword ? 'hi-eye-off' : 'hi-eye'" class="size-[1.2em]" />
                </button>
              </div>
            </div>

            <div class="form-control w-full space-y-1">
              <label for="password" class="label text-xs font-bold p-0">Password Baru</label>
              <div class="relative w-full">
                <label class="input validator w-full pr-10">
                  <v-icon name="hi-key" class="w-4 h-4 opacity-50 shrink-0" />
                  <input :type="showNewPassword ? 'text' : 'password'" id="password"
                    v-model="form.password" required placeholder="Min 8 karakter, huruf besar, angka"
                    class="text-xs" minlength="8"
                    pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                    title="Must be more than 8 characters, including number, lowercase letter, uppercase letter" />
                </label>
                <button type="button" @click="showNewPassword = !showNewPassword"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-base-content/50 hover:text-base-content flex items-center justify-center cursor-pointer z-10">
                  <v-icon :name="showNewPassword ? 'hi-eye-off' : 'hi-eye'" class="size-[1.2em]" />
                </button>
              </div>
            </div>

            <div class="form-control w-full space-y-1">
              <label for="password_confirmation" class="label text-xs font-bold p-0">Konfirmasi Password Baru</label>
              <div class="relative w-full">
                <label class="input validator w-full pr-10">
                  <v-icon name="hi-key" class="w-4 h-4 opacity-50 shrink-0" />
                  <input :type="showConfirmPassword ? 'text' : 'password'" id="password_confirmation"
                    v-model="form.password_confirmation" required placeholder="Ulangi password baru"
                    class="text-xs" minlength="8"
                    pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" />
                </label>
                <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-base-content/50 hover:text-base-content flex items-center justify-center cursor-pointer z-10">
                  <v-icon :name="showConfirmPassword ? 'hi-eye-off' : 'hi-eye'" class="size-[1.2em]" />
                </button>
              </div>
            </div>

            <div class="flex justify-end pt-2">
              <button type="submit" :disabled="form.processing" class="btn btn-primary btn-sm gap-2">
                <span v-if="form.processing" class="loading loading-spinner loading-xs"></span>
                <template v-else>
                  <v-icon name="hi-check-circle" class="size-[1.2em]" />
                  <span>Simpan Password</span>
                </template>
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  errors:   { type: Object, default: () => ({}) },
  status:   { type: String, default: '' },
  authUser: { type: Object, default: () => ({}) },
});

// ── Profile form ──────────────────────────────────────────────────────────
const profileForm = useForm({
  name:     props.authUser?.name     ?? '',
  instansi: props.authUser?.instansi ?? '',
  no_hp:    props.authUser?.no_hp    ?? '',
});

const profileStatus = ref('');

function submitProfile() {
  profileForm.put(route('profile.update'), {
    onSuccess: () => {
      profileStatus.value = 'Profil berhasil diperbarui.';
      setTimeout(() => { profileStatus.value = ''; }, 3000);
    },
  });
}

// ── Password form ─────────────────────────────────────────────────────────
const form = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const showCurrentPassword = ref(false);
const showNewPassword      = ref(false);
const showConfirmPassword  = ref(false);

function submit() {
  form.put('/password', {
    onSuccess: () => {
      form.reset();
      showCurrentPassword.value = false;
      showNewPassword.value     = false;
      showConfirmPassword.value = false;
    },
  });
}
</script>
