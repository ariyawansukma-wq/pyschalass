<template>
  <AppLayout>
    <div class="w-full space-y-6">
      <!-- Header Banner -->
      <div class="card bg-base-100 border border-base-200 text-base-content shadow-xs">
        <div class="card-body p-6 space-y-1">
          <div class="badge badge-primary badge-outline text-xs">
            <span>Reporting Module</span>
          </div>
          <h1 class="text-2xl font-extrabold tracking-tight">Generate Final Evaluation Report</h1>
          <p class="text-xs opacity-75">Configure document settings, cover page, and preview aggregated physical test results</p>
        </div>
      </div>

      <form action="/final-report/preview" method="POST" target="_blank" class="space-y-6">
        <input type="hidden" name="_token" :value="csrfToken" />

        <div class="card bg-base-100 border border-base-200 shadow-xs relative z-20">
          <div class="card-body p-6 space-y-4">
            <h3 class="card-title text-base border-b border-base-200 pb-3">1. Scope & Institution Header</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="form-control">
                <label class="label text-xs font-bold p-0 mb-1">Data Folder <span class="text-error">*</span></label>
                <Select
                  v-model="form.folder_id"
                  :options="folderOptions"
                  :filter-by="() => true"
                  @search="fetchFolderOptions"
                  placeholder="Search or Select Folder..."
                  class="w-full text-xs"
                  :teleport="true"
                />
                <input type="text" name="folder_id" :value="form.folder_id" required class="w-0 h-0 opacity-0 pointer-events-none absolute" />
              </div>

              <div class="form-control">
                <label class="label text-xs font-bold p-0 mb-1">Institution Setting <span class="text-error">*</span></label>
                <Select
                  v-model="form.institution_id"
                  :options="institutionOptions"
                  :filter-by="() => true"
                  @search="fetchInstitutionOptions"
                  placeholder="Search or Select Institution..."
                  class="w-full text-xs"
                  :teleport="true"
                />
                <input type="text" name="institution_id" :value="form.institution_id" required class="w-0 h-0 opacity-0 pointer-events-none absolute" />
              </div>
            </div>
          </div>
        </div>

        <div class="card bg-base-100 border border-base-200 shadow-xs relative z-10">
          <div class="card-body p-6 space-y-4">
            <h3 class="card-title text-base border-b border-base-200 pb-3">2. Document Cover Title & Remarks</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="form-control">
                <label class="label text-xs font-bold p-0 mb-1">Report Cover Title</label>
                <input type="text" name="cover_title" v-model="form.cover_title" placeholder="Physical Test Final Report" class="input input-bordered input-sm w-full">
              </div>

              <div class="form-control">
                <label class="label text-xs font-bold p-0 mb-1">Cover Subtitle</label>
                <input type="text" name="cover_sub" v-model="form.cover_sub" placeholder="e.g.: Batch Evaluation 2026" class="input input-bordered input-sm w-full">
              </div>
            </div>

            <div class="form-control">
              <label class="label text-xs font-bold p-0 mb-1">Foreword / Introduction Text</label>
              <textarea name="foreword" v-model="form.foreword" rows="3" placeholder="Enter introductory remarks for the report..." class="textarea textarea-bordered w-full text-xs"></textarea>
            </div>

            <div class="form-control">
              <label class="label text-xs font-bold p-0 mb-1">Conclusion / Summary Recommendations</label>
              <textarea name="conclusion" v-model="form.conclusion" rows="3" placeholder="Enter conclusion or coach recommendations..." class="textarea textarea-bordered w-full text-xs"></textarea>
            </div>
          </div>
        </div>

        <div class="card bg-base-100 border border-base-200 shadow-xs">
          <div class="card-body p-6 flex-row items-center justify-end gap-3">
            <button type="submit" class="btn btn-success btn-sm gap-2 text-white">
              <v-icon name="hi-document-text" class="size-[1.2em]" />
              <span>Generate & Preview Report</span>
            </button>
          </div>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Select } from 'vue3-select-component';
import 'vue3-select-component/styles';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useSelect2Options } from '@/Composables/useSelect2Options';

const props = defineProps({
  folders: { type: Array, default: () => [] },
  institutions: { type: Array, default: () => [] },
});

const page = usePage();
const csrfToken = computed(() => {
  return page.props.csrf_token || '';
});

const { options: folderOptions, fetchOptions: fetchFolderOptions } = useSelect2Options('folders');
const { options: institutionOptions, fetchOptions: fetchInstitutionOptions } = useSelect2Options('institutions');

onMounted(() => {
  if (props.folders && props.folders.length > 0) {
    folderOptions.value = props.folders.map(f => ({
      label: `${f.name} (${f.athletes_count} athletes)`,
      value: f.id,
    }));
  } else {
    fetchFolderOptions();
  }

  if (props.institutions && props.institutions.length > 0) {
    institutionOptions.value = props.institutions.map(inst => ({
      label: inst.name,
      value: inst.id,
    }));
  } else {
    fetchInstitutionOptions();
  }
});

const form = ref({
  folder_id: '',
  institution_id: props.institutions[0]?.id || '',
  cover_title: 'Physical Test Final Report',
  cover_sub: '',
  foreword: '',
  conclusion: '',
});
</script>
