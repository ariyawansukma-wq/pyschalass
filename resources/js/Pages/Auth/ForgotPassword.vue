<template>
  <div class="min-h-screen flex items-center justify-center bg-base-200 p-4 font-sans antialiased">
    <div class="card bg-base-100 w-full max-w-sm shadow-xl border border-base-300">
      <div class="card-body p-8 space-y-4">
        
        <!-- Logo and Brand -->
        <PhysicalScoreLogo
          size="lg"
          subtitle="Physical Performance Assessment System"
        />

        <div class="space-y-1 text-center pt-2">
          <h2 class="text-base font-extrabold text-base-content">Forgot Password?</h2>
          <p class="text-xs text-base-content/60 font-medium leading-relaxed">
            Enter your registered email address and we'll send you a secure link to reset your password.
          </p>
        </div>

        <!-- Success Alert -->
        <div v-if="status" class="alert alert-success text-xs p-3 shadow-xs">
          <div class="flex items-center gap-2 w-full">
            <v-icon name="hi-check-circle" class="w-4 h-4 shrink-0 text-success-content" />
            <span class="font-bold text-success-content">{{ status }}</span>
          </div>
        </div>

        <!-- Validation Errors Alert -->
        <div v-if="Object.keys(errors).length > 0" class="alert alert-error text-xs p-3 shadow-xs">
          <div class="flex flex-col gap-1 w-full">
            <div v-for="(err, field) in errors" :key="field" class="flex items-center gap-2">
              <v-icon name="hi-exclamation-circle" class="w-4 h-4 shrink-0 text-error-content" />
              <span class="font-bold text-error-content">{{ err }}</span>
            </div>
          </div>
        </div>

        <!-- Forgot Password Form -->
        <form @submit.prevent="submit" class="space-y-4 pt-2">
          <div class="form-control w-full space-y-1">
            <label for="email" class="label text-xs font-bold p-0 text-base-content/85">Email Address</label>
            <input
              type="email"
              id="email"
              v-model="form.email"
              required
              placeholder="name@indexfitlab.com"
              class="input input-bordered input-md w-full text-xs focus:border-primary focus:outline-none"
            />
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="btn btn-primary w-full shadow-md text-xs font-bold"
          >
            <span v-if="form.processing" class="loading loading-spinner loading-xs"></span>
            <span v-else>Send Reset Link</span>
          </button>
        </form>

        <!-- Back to Login Link -->
        <div class="text-center pt-2">
          <Link href="/login" class="inline-flex items-center gap-1.5 text-xs text-base-content/60 hover:text-base-content font-bold transition-colors cursor-pointer">
            <v-icon name="hi-arrow-left" class="w-3.5 h-3.5 shrink-0" />
            <span>Back to Sign In</span>
          </Link>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import PhysicalScoreLogo from '@/Components/PhysicalScoreLogo.vue';

const props = defineProps({
  errors: { type: Object, default: () => ({}) },
  status: { type: String, default: '' },
});

const form = useForm({
  email: '',
});

function submit() {
  form.post('/forgot-password', {
    onSuccess: () => form.reset(),
  });
}
</script>
