<template>
  <div :class="['flex items-center select-none font-sans', containerClasses]">
    <!-- Logo Icon -->
    <div
      v-if="variant === 'icon' || variant === 'full'"
      :class="['flex items-center justify-center shrink-0 rounded-xl bg-base-100 shadow-xs border border-base-300/40 p-1.5', iconContainerClasses]"
    >
      <svg
        viewBox="0 0 32 32"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        class="w-full h-full"
      >
        <g transform="skewX(-10) translate(2, 0)">
          <!-- Performance Bar Chart Blocks -->
          <rect x="5" y="18" width="4.5" height="8" rx="1.5" class="fill-primary/30" />
          <rect x="11.5" y="12" width="4.5" height="14" rx="1.5" class="fill-primary/65" />
          <rect x="18" y="5.5" width="4.5" height="20.5" rx="1.5" class="fill-primary" />
          
          <!-- Dynamic Score Swoosh (Athletic Wave / Growth Curve) -->
          <path
            d="M 3.5 16.5 C 7.5 24.5 15.5 26 26.5 7.5"
            stroke="url(#ps-logo-gradient)"
            stroke-width="3"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
          <path
            d="M 19.5 7.5 H 26.5 V 14.5"
            stroke="url(#ps-logo-gradient)"
            stroke-width="3"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </g>
        <defs>
          <linearGradient id="ps-logo-gradient" x1="0%" y1="100%" x2="100%" y2="0%">
            <stop offset="0%" stop-color="var(--color-primary)" />
            <stop offset="100%" stop-color="var(--color-secondary, var(--color-primary))" />
          </linearGradient>
        </defs>
      </svg>
    </div>

    <!-- Logo Text (Brand Wordmark) -->
    <div
      v-if="variant === 'full' || variant === 'text'"
      :class="['flex flex-col min-w-0', textContainerClasses, textClass]"
    >
      <span :class="['font-extrabold tracking-tight text-base-content leading-none', titleClasses]">
        Physical<span class="text-primary">Score</span>
      </span>
      <span
        v-if="showSubtitle"
        :class="['font-semibold text-base-content/50 tracking-wider uppercase leading-none mt-1', subtitleClasses]"
      >
        {{ subtitle }}
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  variant: {
    type: String,
    default: 'full',
    validator: (value) => ['icon', 'full', 'text'].includes(value),
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
  subtitle: {
    type: String,
    default: 'Evaluation Suite',
  },
  showSubtitle: {
    type: Boolean,
    default: true,
  },
  textClass: {
    type: String,
    default: '',
  },
});

const containerClasses = computed(() => {
  if (props.variant === 'icon') return '';
  return props.size === 'lg' ? 'flex-col text-center gap-4' : 'flex-row items-center gap-3';
});

const iconContainerClasses = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'w-9 h-9';
    case 'lg':
      return 'w-16 h-16 rounded-2xl p-2.5 shadow-md border-base-300';
    case 'md':
    default:
      return 'w-11 h-11 rounded-xl p-2';
  }
});

const textContainerClasses = computed(() => {
  return props.size === 'lg' ? 'space-y-1.5' : 'flex flex-col justify-center';
});

const titleClasses = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'text-sm';
    case 'lg':
      return 'text-2xl font-black';
    case 'md':
    default:
      return 'text-base';
  }
});

const subtitleClasses = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'text-[9px] mt-0.5';
    case 'lg':
      return 'text-xs';
    case 'md':
    default:
      return 'text-[10px]';
  }
});
</script>
