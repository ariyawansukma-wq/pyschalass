<template>
  <AppLayout>
    <div class="w-full space-y-6">
      <!-- Header Banner -->
      <div class="card bg-base-100 border border-base-200 text-base-content shadow-xs">
        <div class="card-body p-6 flex-row items-center justify-between flex-wrap gap-4">
          <div class="space-y-1">
            <div class="badge badge-primary badge-outline text-xs">
              <Link href="/sport-branches" class="hover:underline flex items-center gap-1">
                <v-icon name="hi-arrow-left" class="size-[1.2em]" />
                <span>Back to Sport Branches</span>
              </Link>
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight">{{ sportBranch.name }}</h1>
            <p class="text-xs opacity-75">{{ sportBranch.description || 'Registered Sport Branch details and physical testing indicators' }}</p>
          </div>
          <button @click="openCreateModal" class="btn btn-primary btn-sm gap-2 cursor-pointer shadow-xs">
            <v-icon name="hi-plus-circle" class="size-[1.2em]" />
            <span>Add Indicator</span>
          </button>
        </div>
      </div>

      <!-- Indicators Card & Table -->
      <div class="card bg-base-100 border border-base-200 shadow-xs">
        <div class="card-body p-5 space-y-4">
          <div class="flex items-center justify-between flex-wrap gap-4 pb-2">
            <h2 class="card-title text-base">Physical Test Indicators</h2>
          </div>

          <div class="tabs tabs-lift w-full">
            <!-- Active Tab -->
            <label class="tab cursor-pointer font-bold text-xs">
              <input type="radio" name="indicator_tabs" value="active" v-model="activeTab" />
              <v-icon name="hi-check-circle" class="size-4 me-2" />
              Active ({{ indicators.length }})
            </label>
            <div class="tab-content bg-base-100 border-base-300 p-6">
              <div class="overflow-x-auto border border-base-200 rounded-box">
                <DragDropProvider @dragEnd="onDragEnd">
                  <table class="table table-sm w-full text-xs">
                    <thead>
                      <tr class="bg-base-200 text-base-content font-bold">
                        <th class="w-10"></th>
                        <th class="w-16">No</th>
                        <th>Indicator Name</th>
                        <th>Biomotor Category</th>
                        <th class="text-center">Measurement Unit</th>
                        <th class="text-center">Scoring Direction</th>
                        <th class="text-center">Threshold (%)</th>
                        <th>Evaluation Recommendation</th>
                        <th class="text-right">Actions</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-base-200 font-medium">
                      <SortableIndicatorRow
                        v-for="(ind, index) in localIndicators"
                        :key="ind.id"
                        :ind="ind"
                        :index="index"
                        @edit="openEditModal"
                        @delete="deleteIndicator"
                      />
                      <tr v-if="!localIndicators || localIndicators.length === 0">
                        <td colspan="9" class="py-8 text-center text-base-content/50 font-medium">
                          No test indicators configured for this sport branch yet.
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </DragDropProvider>
              </div>
            </div>

            <!-- Archived Tab -->
            <label class="tab cursor-pointer font-bold text-xs">
              <input type="radio" name="indicator_tabs" value="archived" v-model="activeTab" />
              <v-icon name="hi-clock" class="size-4 me-2" />
              Archived ({{ archivedIndicators.length }})
            </label>
            <div class="tab-content bg-base-100 border-base-300 p-6">
              <div class="overflow-x-auto border border-base-200 rounded-box">
                <table class="table table-sm w-full text-xs">
                  <thead>
                    <tr class="bg-base-200 text-base-content font-bold">
                      <th class="w-16">No</th>
                      <th>Indicator Name</th>
                      <th>Biomotor Category</th>
                      <th class="text-center">Measurement Unit</th>
                      <th class="text-center">Scoring Direction</th>
                      <th class="text-center">Threshold (%)</th>
                      <th>Evaluation Recommendation</th>
                      <th class="text-right">Actions</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-base-200 font-medium">
                    <tr v-for="(ind, index) in archivedIndicators" :key="ind.id" class="hover opacity-75">
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
                      <td class="text-right">
                        <div class="flex items-center justify-end gap-1.5">
                          <button @click="restoreIndicator(ind)" class="btn btn-success btn-outline btn-xs gap-1 cursor-pointer">
                            <!-- Inline SVG Undo/Restore Icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                            </svg>
                            <span>Restore</span>
                          </button>
                          <button @click="forceDeleteIndicator(ind)" class="btn btn-error btn-outline btn-xs gap-1 cursor-pointer" title="Permanently Delete">
                            <v-icon name="hi-trash" class="size-[1.2em]" />
                          </button>
                        </div>
                      </td>
                    </tr>
                    <tr v-if="!archivedIndicators || archivedIndicators.length === 0">
                      <td colspan="8" class="py-8 text-center text-base-content/50 font-medium">
                        No archived test indicators found.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Create / Edit Indicator Modal -->
      <dialog :class="['modal', { 'modal-open': showModal }]">
        <div class="modal-box max-w-sm space-y-4">
          <div class="flex items-center justify-between pb-2 border-b border-base-200">
            <h3 class="font-extrabold text-base">
              {{ isEditing ? 'Edit Physical Indicator' : 'Create Physical Indicator' }}
            </h3>
            <button type="button" @click="showModal = false" class="btn btn-outline btn-square btn-xs">
              <v-icon name="hi-x" class="size-[1.2em]" />
            </button>
          </div>

          <form @submit.prevent="saveIndicator" class="space-y-4 text-xs">
            <div v-if="Object.keys(indForm.errors).length > 0" class="alert alert-error text-xs p-3">
              <div class="flex flex-col gap-1">
                <div v-for="(err, field) in indForm.errors" :key="field" class="flex items-center gap-2">
                  <v-icon name="hi-exclamation-circle" class="w-4 h-4 shrink-0" />
                  <span>{{ err }}</span>
                </div>
              </div>
            </div>
            <div class="form-control">
              <label class="label text-xs font-bold p-0 mb-1">Indicator Name <span class="text-error">*</span></label>
              <input type="text" v-model="indForm.name" required placeholder="e.g.: Bleep Test / 30m Sprint" class="input input-bordered input-sm w-full">
            </div>

            <div class="form-control">
              <label class="label text-xs font-bold p-0 mb-1">Biomotor Category</label>
              <input type="text" v-model="indForm.category" list="biomotor-categories" placeholder="e.g. Endurance, Strength, Speed" class="input input-bordered input-sm w-full text-xs">
              <datalist id="biomotor-categories">
                <option value="Endurance" />
                <option value="Strength" />
                <option value="Speed" />
                <option value="Flexibility" />
                <option value="Power" />
                <option value="Agility" />
                <option value="Coordination" />
                <option value="Balance" />
              </datalist>
            </div>

            <div class="form-control">
              <label class="label text-xs font-bold p-0 mb-1">Measurement Unit <span class="text-error">*</span></label>
              <input type="text" v-model="indForm.unit" required placeholder="e.g.: ml/kg/min, seconds, cm" class="input input-bordered input-sm w-full">
            </div>

            <div class="form-control">
              <label class="label text-xs font-bold p-0 mb-1">Scoring Direction <span class="text-error">*</span></label>
              <select v-model="indForm.scoring_direction" required class="select select-bordered select-sm w-full text-xs">
                <option value="HIGHER_IS_BETTER">Higher is Better (e.g. Endurance, Strength)</option>
                <option value="LOWER_IS_BETTER">Lower is Better (e.g. Sprint Time, Reaction Time)</option>
              </select>
            </div>

            <div class="form-control">
              <label class="label text-xs font-bold p-0 mb-1">Evaluation Threshold (%)</label>
              <input type="number" min="1" max="100" v-model="indForm.evaluation_threshold" placeholder="e.g. 50 (leave empty for no recommendation)" class="input input-bordered input-sm w-full text-xs">
            </div>

            <div class="form-control">
              <label class="label text-xs font-bold p-0 mb-1">
                Evaluation Recommendation
                <span v-if="indForm.evaluation_threshold" class="text-base-content/60">
                  (if performance &lt; {{ indForm.evaluation_threshold }}%)
                </span>
                <span v-else class="text-error/70 font-semibold">
                  (Disabled - threshold is empty)
                </span>
              </label>
              <textarea v-model="indForm.evaluation" rows="3" placeholder="e.g. Need cardiorespiratory endurance training (aerobic) 3-4x per week." class="textarea textarea-bordered w-full text-xs"></textarea>
            </div>

            <div class="modal-action">
              <button type="button" @click="showModal = false" class="btn btn-outline btn-xs">Cancel</button>
              <button type="submit" :disabled="indForm.processing" class="btn btn-primary btn-xs">
                Save Indicator
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
import { ref, inject, watch } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { DragDropProvider } from '@dnd-kit/vue';
import { isSortable } from '@dnd-kit/vue/sortable';
import SortableIndicatorRow from './SortableIndicatorRow.vue';

const swal = inject('$swal');

const props = defineProps({
  sportBranch: { type: Object, required: true },
  indicators: { type: Array, required: true },
  archivedIndicators: { type: Array, required: true },
});

const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const activeTab = ref('active');

const localIndicators = ref([...props.indicators]);

watch(() => props.indicators, (newVal) => {
  localIndicators.value = [...newVal];
});

const indForm = useForm({
  sport_branch_id: props.sportBranch.id,
  name: '',
  unit: '',
  category: '',
  scoring_direction: 'HIGHER_IS_BETTER',
  evaluation: '',
  evaluation_threshold: null,
});

function openCreateModal() {
  isEditing.value = false;
  editingId.value = null;
  indForm.reset();
  indForm.sport_branch_id = props.sportBranch.id;
  indForm.category = '';
  indForm.evaluation_threshold = null;
  showModal.value = true;
}

function openEditModal(ind) {
  isEditing.value = true;
  editingId.value = ind.id;
  indForm.sport_branch_id = props.sportBranch.id;
  indForm.name = ind.name;
  indForm.unit = ind.unit;
  indForm.category = ind.category || '';
  indForm.scoring_direction = ind.scoring_direction;
  indForm.evaluation = ind.evaluation || '';
  indForm.evaluation_threshold = ind.evaluation_threshold !== null && ind.evaluation_threshold !== undefined ? ind.evaluation_threshold : null;
  showModal.value = true;
}

function saveIndicator() {
  if (isEditing.value) {
    indForm.put('/indicators/' + editingId.value, {
      onSuccess: () => {
        showModal.value = false;
      },
    });
  } else {
    indForm.post('/indicators', {
      onSuccess: () => {
        showModal.value = false;
      },
    });
  }
}

async function deleteIndicator(ind) {
  const result = await swal.fire({
    icon: 'warning',
    title: 'Archive Indicator',
    text: `Are you sure you want to archive indicator "${ind.name}"? You can restore it later from the Archived tab.`,
    showCancelButton: true,
    confirmButtonColor: '#e11d48',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Yes, archive',
    cancelButtonText: 'Cancel',
  });

  if (result.isConfirmed) {
    router.delete('/indicators/' + ind.id, {
      preserveScroll: true
    });
  }
}

async function restoreIndicator(ind) {
  const result = await swal.fire({
    icon: 'question',
    title: 'Restore Indicator',
    text: `Are you sure you want to restore indicator "${ind.name}"?`,
    showCancelButton: true,
    confirmButtonColor: '#22c55e',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Yes, restore',
    cancelButtonText: 'Cancel',
  });

  if (result.isConfirmed) {
    router.post(`/indicators/${ind.id}/restore`, {}, {
      preserveScroll: true
    });
  }
}

async function forceDeleteIndicator(ind) {
  const result = await swal.fire({
    icon: 'warning',
    title: 'Permanently Delete Indicator',
    text: `Are you sure you want to permanently delete indicator "${ind.name}"? This action CANNOT be undone and will delete all related scores!`,
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Yes, permanently delete',
    cancelButtonText: 'Cancel',
  });

  if (result.isConfirmed) {
    router.delete(`/indicators/${ind.id}/force-delete`, {
      preserveScroll: true
    });
  }
}

function onDragEnd(event) {
  if (event.canceled) return;
  const { source } = event.operation;
  if (isSortable(source)) {
    const { initialIndex, index } = source;
    if (initialIndex !== index) {
      const newItems = [...localIndicators.value];
      const [removed] = newItems.splice(initialIndex, 1);
      newItems.splice(index, 0, removed);
      localIndicators.value = newItems;

      const ids = newItems.map(ind => ind.id);
      router.put(`/sport-branches/${props.sportBranch.id}/reorder-indicators`, {
        ids: ids,
      }, {
        preserveScroll: true,
      });
    }
  }
}
</script>
