<template>
  <div class="min-h-screen flex items-center justify-center bg-base-200 p-4">
    <div class="card bg-base-100 w-full max-w-sm shadow-xl border border-base-300">
      <div class="card-body p-8 space-y-4">
        <!-- Logo and Brand -->
        <PhysicalScoreLogo
          size="lg"
          subtitle="Physical Performance Assessment System"
        />

        <!-- Errors Alert -->
        <div v-if="Object.keys(errors).length > 0" class="alert alert-error text-xs p-3">
          <div class="flex flex-col gap-1">
            <div v-for="(err, field) in errors" :key="field" class="flex items-center gap-1.5">
              <v-icon name="hi-exclamation-circle" class="w-4 h-4 shrink-0" />
              <span>{{ err }}</span>
            </div>
          </div>
        </div>

        <!-- Login Form -->
        <form @submit.prevent="submit" class="space-y-4">
          <div class="form-control w-full space-y-1">
            <label for="username" class="label text-xs font-bold p-0">Username / Email</label>
            <label class="input validator w-full">
              <v-icon name="hi-user" class="w-4 h-4 opacity-50 shrink-0" />
              <input
                type="text"
                id="username"
                v-model="form.username"
                required
                placeholder="Username atau email"
                class="text-xs"
              />
            </label>
          </div>

          <div class="form-control w-full space-y-1">
            <label for="password" class="label text-xs font-bold p-0">Password</label>
            <div class="relative w-full">
              <label class="input validator w-full pr-10">
                <v-icon name="hi-key" class="w-4 h-4 opacity-50 shrink-0" />
                <input
                  :type="showPassword ? 'text' : 'password'"
                  id="password"
                  v-model="form.password"
                  required
                  placeholder="Enter password"
                  class="text-xs"
                />
              </label>
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-base-content/50 hover:text-base-content flex items-center justify-center cursor-pointer z-10"
                title="Toggle Password Visibility"
              >
                <v-icon :name="showPassword ? 'hi-eye-off' : 'hi-eye'" class="size-[1.2em]" />
              </button>
            </div>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="btn btn-primary w-full shadow-md text-xs font-bold"
          >
            <span v-if="form.processing" class="loading loading-spinner loading-xs"></span>
            <span v-else>Sign In</span>
          </button>
        </form>

        <!-- Forgot Password -->
        <div class="text-center pt-2">
          <Link href="/forgot-password" class="text-xs text-base-content/60 hover:text-base-content font-bold transition-colors cursor-pointer">
            Forgot Password?
          </Link>
        </div>

        <!-- Register kader -->
        <div class="text-center border-t border-base-200 pt-3">
          <span class="text-xs text-base-content/50">Kader baru? </span>
          <Link href="/register" class="text-xs font-bold text-success hover:underline transition-colors cursor-pointer">
            Daftar di sini
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

const props = defineProps({
  errors: { type: Object, default: () => ({}) },
});

const form = useForm({
  username: '',
  password: '',
});

const showPassword = ref(false);

function submit() {
  form.post('/login', {
    onFinish: () => {
      form.reset('password');
      showPassword.value = false;
    },
  });
}
</script>
