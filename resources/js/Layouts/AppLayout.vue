<template>
  <div class="drawer lg:drawer-open min-h-screen bg-base-200 font-sans antialiased">
    <input id="my-drawer-4" type="checkbox" v-model="drawerOpen" class="drawer-toggle inline" />
    
    <div class="drawer-content flex flex-col min-h-screen">
      <!-- Navbar -->
      <nav class="navbar w-full bg-base-100 border-b border-base-300 px-4 shadow-xs">
        <label for="my-drawer-4" aria-label="toggle sidebar" class="btn btn-square btn-outline drawer-button cursor-pointer">
          <v-icon :name="drawerOpen ? 'oi-sidebar-expand' : 'oi-sidebar-collapse'" class="size-[1.2em]" />
        </label>
        <div class="flex-1 px-3">
          <span class="font-extrabold text-base text-base-content tracking-tight">PhysicalScore Assessment System</span>
        </div>
        <div class="flex-none flex items-center gap-3">
          <Link href="/password" class="avatar placeholder" title="Profil & Password">
            <div class="w-8 h-8 rounded-lg bg-primary text-primary-content font-bold text-xs flex items-center justify-center uppercase shadow-xs hover:opacity-80 transition-opacity cursor-pointer">
              <span>{{ userInitials }}</span>
            </div>
          </Link>
          <span class="text-xs font-bold text-base-content hidden sm:inline">{{ userName }}</span>
        </div>
      </nav>

      <!-- Page content here -->
      <main class="flex-1 w-full p-4 sm:p-6">
        <div v-if="$page.props.flash?.success" class="mb-4 alert alert-success text-xs p-3 shadow-xs">
          <div class="flex items-center gap-2 w-full">
            <v-icon name="hi-check-circle" class="w-4 h-4 shrink-0" />
            <span class="font-bold">{{ $page.props.flash.success }}</span>
          </div>
        </div>

        <div v-if="$page.props.flash?.error" class="mb-4 alert alert-error text-xs p-3 shadow-xs">
          <div class="flex items-center gap-2 w-full">
            <v-icon name="hi-exclamation-circle" class="w-4 h-4 shrink-0" />
            <span class="font-bold">{{ $page.props.flash.error }}</span>
          </div>
        </div>

        <!-- Global Validation Errors Alert -->
        <div v-if="Object.keys($page.props.errors || {}).length > 0" class="mb-4 alert alert-error text-xs p-3 shadow-xs">
          <div class="flex flex-col gap-1 w-full">
            <div v-for="(err, field) in $page.props.errors" :key="field" class="flex items-center gap-2">
              <v-icon name="hi-exclamation-circle" class="w-4 h-4 shrink-0" />
              <span class="font-bold">{{ err }}</span>
            </div>
          </div>
        </div>

        <slot />
      </main>
    </div>

    <!-- Drawer Side -->
    <div class="drawer-side is-drawer-close:overflow-visible z-40">
      <label for="my-drawer-4" aria-label="close sidebar" class="drawer-overlay"></label>
      <div class="flex min-h-full flex-col items-start bg-base-100 border-r border-base-300 is-drawer-close:w-14 is-drawer-open:w-64 transition-all duration-300 shadow-sm">
        
        <!-- Sidebar Brand Header -->
        <div class="p-4 w-full border-b border-base-300 flex items-center shrink-0">
          <PhysicalScoreLogo
            size="sm"
            subtitle="Evaluation Suite"
            text-class="is-drawer-close:hidden"
          />
        </div>

        <!-- Sidebar Menu Items -->
        <div class="w-full grow overflow-y-auto p-2">
          <ul class="menu w-full gap-1 p-0">
            <template v-for="item in navItems" :key="item.href">
              <li v-if="can('read', item.subject)">
                <Link
                  :href="item.href"
                  :class="[
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-xs font-semibold transition-all is-drawer-close:tooltip is-drawer-close:tooltip-right',
                    currentUrl.startsWith(item.check) ? 'bg-primary text-primary-content font-bold shadow-xs' : 'text-base-content/75 hover:bg-base-200 hover:text-base-content border border-transparent hover:border-base-300'
                  ]"
                  :data-tip="item.label"
                >
                  <v-icon :name="item.icon" class="w-4 h-4 shrink-0" />
                  <span class="is-drawer-close:hidden">{{ item.label }}</span>
                </Link>
              </li>
            </template>
          </ul>
        </div>

        <!-- Sidebar Footer User Profile & Logout -->
        <div class="p-3 w-full border-t border-base-300 mt-auto space-y-2 shrink-0 bg-base-100">
          <div class="flex items-center gap-2.5">
            <div class="avatar placeholder shrink-0">
              <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary font-bold text-xs flex items-center justify-center uppercase border border-primary/20">
                <span>{{ userInitials }}</span>
              </div>
            </div>
            <div class="flex-grow min-w-0 is-drawer-close:hidden">
              <p class="text-xs font-bold text-base-content truncate">{{ userName }}</p>
              <p class="text-[10px] text-base-content/60 font-medium">{{ userRole === 'admin' ? 'Administrator' : 'Officer' }}</p>
            </div>
          </div>
          <Link
            href="/logout"
            method="post"
            as="button"
            class="btn btn-error btn-outline btn-xs w-full cursor-pointer text-center"
          >
            <span class="is-drawer-close:hidden">Logout</span>
            <v-icon name="hi-logout" class="size-[1.2em] is-drawer-open:hidden" />
          </Link>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import PhysicalScoreLogo from '@/Components/PhysicalScoreLogo.vue';
import { useAbility } from '@/Composables/useAbility';

const { can } = useAbility();

const page = usePage();

const drawerOpen = ref(typeof window !== 'undefined' ? localStorage.getItem('sidebar_open') !== 'false' : true);

const currentUrl = computed(() => page.url || '');
const user = computed(() => page.props.auth?.user || {});
const userName = computed(() => user.value.name || 'User');
const userRole = computed(() => user.value.role || 'officer');
const userInitials = computed(() => userName.value.substring(0, 2).toUpperCase());

// Heartbeat keep-alive every 5 minutes while browser tab is active/visible
let heartbeatInterval = null;

function sendHeartbeat() {
  if (typeof document !== 'undefined' && document.visibilityState === 'visible' && user.value.id) {
    axios.post('/heartbeat').catch(() => {});
  }
}

function handleVisibilityChange() {
  if (document.visibilityState === 'visible') {
    sendHeartbeat();
  }
}

onMounted(() => {
  // Run heartbeat every 10 minutes
  heartbeatInterval = setInterval(sendHeartbeat, 10 * 60 * 1000);
  document.addEventListener('visibilitychange', handleVisibilityChange);
});

onUnmounted(() => {
  if (heartbeatInterval) clearInterval(heartbeatInterval);
  document.removeEventListener('visibilitychange', handleVisibilityChange);
});

const navItems = [
  { href: '/dashboard',           check: '/dashboard',           icon: 'hi-home',           label: 'Dashboard',             subject: 'Dashboard' },
  { href: '/athletes/create',     check: '/athletes/create',     icon: 'hi-pencil',         label: 'Add Athlete',           subject: 'Athlete' },
  { href: '/physical-assessment', check: '/physical-assessment', icon: 'bi-laptop',         label: 'Physical Assessment',   subject: 'CameraAssessment' },
  { href: '/camera-assessments',  check: '/camera-assessments',  icon: 'hi-clock',          label: 'Assessment History',    subject: 'CameraAssessment' },
  { href: '/athletes',            check: '/athletes',            icon: 'hi-users',          label: 'Athlete Directory',     subject: 'Athlete' },
  { href: '/comparison',          check: '/comparison',          icon: 'hi-adjustments',    label: 'Comparison Tool',       subject: 'Comparison' },
  { href: '/sport-branches',      check: '/sport-branches',      icon: 'bi-trophy',         label: 'Sport Branches',        subject: 'SportBranch' },
  { href: '/benchmarks',          check: '/benchmarks',          icon: 'hi-flag',           label: 'Benchmarks',            subject: 'Benchmark' },
  { href: '/folders',             check: '/folders',             icon: 'hi-folder',         label: 'Data Folders',          subject: 'Folder' },
  { href: '/final-report',        check: '/final-report',        icon: 'hi-document-text',  label: 'Final Report',          subject: 'Report' },
  { href: '/exports',             check: '/exports',             icon: 'hi-download',       label: 'Export Center',         subject: 'Export' },
  { href: '/screening/mulai',     check: '/screening',           icon: 'hi-device-mobile',  label: 'Skrining Anak',         subject: 'Screening' },
  { href: '/screening',           check: '/screening',           icon: 'hi-calendar',       label: 'Riwayat Skrining',      subject: 'Screening' },
  { href: '/perpustakaan',        check: '/perpustakaan',        icon: 'hi-document-duplicate', label: 'Perpustakaan',      subject: 'Library' },
  { href: '/institutions',        check: '/institutions',        icon: 'hi-cog',            label: 'Institution Settings',  subject: 'Institution' },
  { href: '/users',               check: '/users',               icon: 'hi-user-group',     label: 'Account Management',    subject: 'User' },
];
</script>
