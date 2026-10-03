<template>
  <div class="fixed inset-0 w-full h-full flex flex-col bg-slate-900 font-sans antialiased overflow-hidden">
    <!-- Header / Custom Toolbar -->
    <div class="navbar bg-base-100 text-base-content border-b border-base-200 px-4 py-2 z-50 shadow-xs flex-wrap gap-2 shrink-0 justify-between">
      <div class="flex-1">
        <b class="text-sm font-semibold">{{ title }}</b>
      </div>
      <div class="flex-none flex items-center gap-3">
        <select 
          v-if="showPaperControls"
          :value="paperSize"
          @change="$emit('update:paperSize', $event.target.value)"
          class="select select-bordered select-xs bg-base-100 text-base-content font-bold cursor-pointer"
        >
          <option value="a4">Paper: A4 (210 x 297 mm)</option>
          <option value="letter">Paper: Letter (216 x 279 mm)</option>
          <option value="legal">Paper: Legal (216 x 356 mm)</option>
          <option value="folio">Paper: F4 / Folio (215 x 330 mm)</option>
        </select>
        <select 
          v-if="showPaperControls"
          :value="paperOrientation"
          @change="$emit('update:paperOrientation', $event.target.value)"
          class="select select-bordered select-xs bg-base-100 text-base-content font-bold cursor-pointer"
        >
          <option value="portrait">Orientation: Portrait</option>
          <option value="landscape">Orientation: Landscape</option>
        </select>
        <button 
          v-if="showDownload" 
          :disabled="!pdfUrl"
          @click="$emit('download')" 
          class="btn btn-success btn-sm text-white flex items-center gap-1.5 shadow-md cursor-pointer font-bold disabled:opacity-60"
        >
          <span v-if="!pdfUrl" class="loading loading-spinner loading-xs"></span>
          <v-icon v-else name="hi-download" class="size-[1.2em]" />
          <span>{{ pdfUrl ? 'Download PDF' : 'Preparing PDF...' }}</span>
        </button>
        <button @click="closeWindow" class="btn btn-outline btn-sm">
          Close Viewer
        </button>
      </div>
    </div>

    <!-- Viewer Body -->
    <div class="flex-1 w-full min-h-0 relative overflow-hidden">
      <PDFViewer 
        v-if="pdfUrl" 
        @ready="$emit('ready', $event)" 
        :config="viewerConfig" 
        class="w-full h-full border-none" 
      />
      <div v-else class="absolute inset-0 flex flex-col items-center justify-center space-y-5 px-6" style="background-color: #1e293b; color: #fff;">
        <div class="max-w-md w-full text-center space-y-4">
          <span class="loading loading-spinner loading-lg text-primary"></span>
          <div class="space-y-2">
            <p class="text-sm font-extrabold tracking-wider uppercase text-primary">PREPARING PDF REPORT...</p>
            <p class="text-xs font-semibold" style="color: #cbd5e1;">Rendering page {{ currentPage }} of {{ totalPages }} ({{ progressPercent }}%)</p>
          </div>
          <progress class="progress progress-primary w-full shadow-md" style="background-color: rgba(255,255,255,0.15);" :value="progressPercent" max="100"></progress>
        </div>
      </div>
    </div>

    <!-- Slot for hidden HTML element used for capturing PDF -->
    <slot />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { PDFViewer } from '@embedpdf/vue-pdf-viewer';
import { onMounted, onUnmounted } from 'vue';

const props = defineProps({
  title: { type: String, default: 'PDF Report Viewer' },
  pdfUrl: { type: String, default: null },
  pdfFilename: { type: String, default: '' },
  currentPage: { type: Number, default: 0 },
  totalPages: { type: Number, default: 0 },
  progressPercent: { type: Number, default: 0 },
  paperSize: { type: String, default: 'a4' },
  paperOrientation: { type: String, default: 'portrait' },
  showPaperControls: { type: Boolean, default: true },
  showDownload: { type: Boolean, default: true },
});

defineEmits(['update:paperSize', 'update:paperOrientation', 'download', 'ready']);

const viewerConfig = computed(() => {
  const cfg = { src: props.pdfUrl };
  if (props.pdfFilename) {
    cfg.export = { defaultFileName: props.pdfFilename };
  }
  return cfg;
});

function closeWindow() {
  window.close();
}

onMounted(() => {
  document.documentElement.style.setProperty('overflow', 'hidden', 'important');
  document.body.style.setProperty('overflow', 'hidden', 'important');
  document.body.style.setProperty('height', '100%', 'important');
});

onUnmounted(() => {
  document.documentElement.style.removeProperty('overflow');
  document.body.style.removeProperty('overflow');
  document.body.style.removeProperty('height');
});
</script>