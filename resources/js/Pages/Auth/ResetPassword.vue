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
          <h2 class="text-base font-extrabold text-base-content">Reset Password</h2>
          <p class="text-xs text-base-content/60 font-medium leading-relaxed">
            Please enter your email and establish your new secure account password.
          </p>
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

        <!-- Reset Password Form -->
        <form @submit.prevent="submit" class="space-y-4 pt-2">
          <!-- Hidden Token Input -->
          <input type="hidden" name="token" v-model="form.token" />

          <!-- Email input (usually readonly to prevent mismatch, but editable if needed) -->
          <div class="form-control w-full space-y-1">
            <label for="email" class="label text-xs font-bold p-0 text-base-content/85">Email Address</label>
            <input
              type="email"
              id="email"
              v-model="form.email"
              required
              class="input input-bordered input-md w-full text-xs focus:border-primary focus:outline-none"
            />
          </div>

          <!-- New Password -->
          <div class="form-control w-full space-y-1">
            <label for="password" class="label text-xs font-bold p-0 text-base-content/85">New Password</label>
            <div class="relative w-full">
              <label class="input validator w-full pr-10">
                <v-icon name="hi-key" class="w-4 h-4 opacity-50 shrink-0" />
                <input
                  :type="showPassword ? 'text' : 'password'"
                  id="password"
                  v-model="form.password"
                  required
                  placeholder="Min. 8 characters"
                  class="text-xs focus:outline-none"
                  minlength="8"
                  pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                  title="Must be more than 8 characters, including number, lowercase letter, uppercase letter"
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
            <p class="validator-hint hidden text-[10px] mt-0.5 leading-normal">
              Must be at least 8 characters, including
              <br />At least one number, one lowercase letter, and one uppercase letter
            </p>
          </div>

          <!-- Confirm Password -->
          <div class="form-control w-full space-y-1">
            <label for="password_confirmation" class="label text-xs font-bold p-0 text-base-content/85">Confirm Password</label>
            <div class="relative w-full">
              <label class="input validator w-full pr-10">
                <v-icon name="hi-key" class="w-4 h-4 opacity-50 shrink-0" />
                <input
                  :type="showConfirmPassword ? 'text' : 'password'"
                  id="password_confirmation"
                  v-model="form.password_confirmation"
                  required
                  placeholder="Confirm new password"
                  class="text-xs focus:outline-none"
                  minlength="8"
                  pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                  title="Must be more than 8 characters, including number, lowercase letter, uppercase letter"
                />
              </label>
              <button
                type="button"
                @click="showConfirmPassword = !showConfirmPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-base-content/50 hover:text-base-content flex items-center justify-center cursor-pointer z-10"
                title="Toggle Password Visibility"
              >
                <v-icon :name="showConfirmPassword ? 'hi-eye-off' : 'hi-eye'" class="size-[1.2em]" />
              </button>
            </div>
            <p class="validator-hint hidden text-[10px] mt-0.5 leading-normal">
              Must be at least 8 characters, including
              <br />At least one number, one lowercase letter, and one uppercase letter
            </p>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="btn btn-primary w-full shadow-md text-xs font-bold"
          >
            <span v-if="form.processing" class="loading loading-spinner loading-xs"></span>
            <span v-else>Update Password</span>
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
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import PhysicalScoreLogo from '@/Components/PhysicalScoreLogo.vue';

const props = defineProps({
  errors: { type: Object, default: () => ({}) },
  token: { type: String, required: true },
  email: { type: String, default: '' },
});

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);

function submit() {
  form.post('/reset-password', {
    onFinish: () => {
      form.reset('password', 'password_confirmation');
      showPassword.value = false;
      showConfirmPassword.value = false;
    },
  });
}
</script>
