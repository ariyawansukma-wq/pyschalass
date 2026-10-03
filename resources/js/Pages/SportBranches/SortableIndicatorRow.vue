<template>
  <tr 
    ref="element" 
    class="hover cursor-grab active:cursor-grabbing select-none"
    :class="{ 'opacity-50 bg-slate-50': isDragging }"
  >
    <!-- Drag Handle Indicator (Visual cue) -->
    <td class="text-center w-10 text-slate-400 hover:text-slate-600">
      <v-icon name="hi-menu" class="w-4 h-4 inline-block" />
    </td>

    <td class="font-semibold text-base-content/50">
      {{ index + 1 }}
    </td>
    <td class="font-bold text-base-content text-sm">{{ ind.name }}</td>
    <td class="text-base-content font-semibold">
      <span v-if="ind.category" class="badge badge-sm badge-neutral font-bold">{{ ind.category }}</span>
      <span v-else class="text-base-content/40 font-normal">-</span>
    </td>
    <td class="text-center font-semibold text-base-content/80">
      {{ ind.unit || '-' }}
    </td>
    <td class="text-center">
      <span
        :class="['badge badge-sm font-bold', ind.scoring_direction === 'HIGHER_IS_BETTER' ? 'badge-success badge-outline' : 'badge-info badge-outline']"
      >
        {{ ind.scoring_direction === 'HIGHER_IS_BETTER' ? 'Higher is Better' : 'Lower is Better' }}
      </span>
    </td>
    <td class="text-center font-semibold text-base-content/80">
      {{ ind.evaluation_threshold ? ind.evaluation_threshold + '%' : '-' }}
    </td>
    <td class="text-left text-base-content/70 max-w-[200px] truncate font-semibold" :title="ind.evaluation || ''">
      {{ ind.evaluation || '-' }}
    </td>
    <!-- Action buttons (ensure click works without dragging) -->
    <td class="text-right" @pointerdown.stop>
      <div class="flex items-center justify-end gap-1.5">
        <button @click="$emit('edit', ind)" class="btn btn-info btn-outline btn-xs gap-1 cursor-pointer">
          <v-icon name="hi-pencil" class="size-[1.2em]" />
          <span>Edit</span>
        </button>
        <button
          v-if="ind.can_be_deleted !== false"
          @click="$emit('delete', ind)"
          class="btn btn-error btn-outline btn-xs gap-1 cursor-pointer"
        >
          <v-icon name="hi-trash" class="size-[1.2em]" />
        </button>
        <span v-else class="btn btn-outline btn-xs btn-disabled" title="Used in active benchmarks">
          <v-icon name="hi-trash" class="size-[1.2em]" />
        </span>
      </div>
    </td>
  </tr>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useSortable } from '@dnd-kit/vue/sortable';

const props = defineProps({
  ind: { type: Object, required: true },
  index: { type: Number, required: true },
});

defineEmits(['edit', 'delete']);

const element = ref(null);

const {
  isDragging
} = useSortable({
  id: computed(() => props.ind.id),
  index: computed(() => props.index),
  element,
});
</script>
