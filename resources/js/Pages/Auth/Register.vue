<template>
  <div class="min-h-screen flex items-center justify-center bg-base-200 p-4">
    <div class="card bg-base-100 w-full max-w-md shadow-xl border border-base-300">
      <div class="card-body p-8 space-y-4">

        <!-- Logo & Brand -->
        <PhysicalScoreLogo size="lg" subtitle="Daftar Akun Kader ISKAD-V" />

        <!-- Error alert -->
        <div v-if="Object.keys(errors).length > 0" class="alert alert-error text-xs p-3">
          <div class="flex flex-col gap-1">
            <div v-for="(err, field) in errors" :key="field" class="flex items-center gap-1.5">
              <v-icon name="hi-exclamation-circle" class="w-4 h-4 shrink-0" />
              <span>{{ err }}</span>
            </div>
          </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="submit" class="space-y-3">

          <!-- Nama -->
          <div class="form-control space-y-1">
            <label class="label text-xs font-bold p-0">Nama Lengkap <span class="text-error">*</span></label>
            <input
              v-model="form.name"
              type="text"
              required
              placeholder="Nama lengkap Anda"
              class="input input-bordered input-sm w-full"
            />
          </div>

          <!-- Email -->
          <div class="form-control space-y-1">
            <label class="label text-xs font-bold p-0">Email <span class="text-error">*</span></label>
            <input
              v-model="form.email"
              type="email"
              required
              placeholder="email@domain.com"
              class="input input-bordered input-sm w-full"
            />
            <p class="text-[10px] opacity-50">Email digunakan untuk login dan reset password.</p>
          </div>

          <!-- Instansi -->
          <div class="form-control space-y-1">
            <label class="label text-xs font-bold p-0">Instansi / Lembaga <span class="text-error">*</span></label>
            <input
              v-model="form.instansi"
              type="text"
              required
              placeholder="Nama Posyandu / TK / Lembaga"
              class="input input-bordered input-sm w-full"
            />
          </div>

          <!-- No HP -->
          <div class="form-control space-y-1">
            <label class="label text-xs font-bold p-0">No. HP <span class="text-error">*</span></label>
            <input
              v-model="form.no_hp"
              type="text"
              required
              placeholder="08xx-xxxx-xxxx"
              class="input input-bordered input-sm w-full"
            />
          </div>

          <!-- Password -->
          <div class="form-control space-y-1">
            <label class="label text-xs font-bold p-0">Password <span class="text-error">*</span></label>
            <div class="relative w-full">
              <label class="input input-sm validator w-full pr-10">
                <v-icon name="hi-key" class="w-4 h-4 opacity-50 shrink-0" />
                <input
                  :type="showPass ? 'text' : 'password'"
                  v-model="form.password"
                  required
                  placeholder="Min 8 karakter, huruf besar, angka"
                  minlength="8"
                  class="text-xs"
                />
              </label>
              <button type="button" @click="showPass = !showPass"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-base-content/50 hover:text-base-content cursor-pointer z-10">
                <v-icon :name="showPass ? 'hi-eye-off' : 'hi-eye'" class="size-[1.2em]" />
              </button>
            </div>
          </div>

          <!-- Konfirmasi Password -->
          <div class="form-control space-y-1">
            <label class="label text-xs font-bold p-0">Konfirmasi Password <span class="text-error">*</span></label>
            <div class="relative w-full">
              <label class="input input-sm validator w-full pr-10">
                <v-icon name="hi-key" class="w-4 h-4 opacity-50 shrink-0" />
                <input
                  :type="showPass2 ? 'text' : 'password'"
                  v-model="form.password_confirmation"
                  required
                  placeholder="Ulangi password"
                  class="text-xs"
                />
              </label>
              <button type="button" @click="showPass2 = !showPass2"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-base-content/50 hover:text-base-content cursor-pointer z-10">
                <v-icon :name="showPass2 ? 'hi-eye-off' : 'hi-eye'" class="size-[1.2em]" />
              </button>
            </div>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="btn btn-success w-full text-xs font-bold shadow-md mt-1"
          >
            <span v-if="form.processing" class="loading loading-spinner loading-xs"></span>
            <span v-else>Daftar Sekarang</span>
          </button>
        </form>

        <!-- Link ke login -->
        <div class="text-center pt-1">
          <span class="text-xs text-base-content/60">Sudah punya akun? </span>
          <Link href="/login" class="text-xs font-bold text-primary hover:underline">
            Masuk di sini
          </Link>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import PhysicalScoreLogo from '@/Components/PhysicalScoreLogo.vue';

defineProps({
  errors: { type: Object, default: () => ({}) },
});

const form = useForm({
  name:                 '',
  email:                '',
  instansi:             '',
  no_hp:                '',
  password:             '',
  password_confirmation:'',
});

const showPass  = ref(false);
const showPass2 = ref(false);

function submit() {
  form.post('/register', {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
}
</script>
