<template>
  <VueDatePicker
    v-model="internalValue"
    :model-type="modelType"
    :format="format"
    :time-config="timeConfig"
    :auto-apply="autoApply"
    :disabled="disabled"
    :placeholder="placeholder"
    :teleport="teleport"
    :min-date="minDate"
    :max-date="maxDate"
    :input-class-name="inputClass || 'input input-bordered w-full text-sm'"
  />
</template>

<script setup>
import { computed } from 'vue';
import { VueDatePicker } from '@vuepic/vue-datepicker';

const props = defineProps({
  modelValue:       { type: [String, Date, Object, Array], default: '' },
  placeholder:      { type: String,  default: 'Pick a date' },
  disabled:         { type: Boolean, default: false },
  inputClass:       { type: String,  default: '' },
  modelType:        { type: String,  default: 'yyyy-MM-dd' },
  format:           { type: String,  default: 'dd MMM yyyy' },
  // Time — passed via timeConfig (v14 API)
  enableTimePicker: { type: Boolean, default: false },
  enableSeconds:    { type: Boolean, default: false },
  is24:             { type: Boolean, default: true },
  // Date constraints
  minDate:          { type: [String, Date], default: null },
  maxDate:          { type: [String, Date], default: null },
  // Behaviour
  autoApply:        { type: Boolean, default: true },
  teleport:         { type: [Boolean, String], default: true },
});

const emit = defineEmits(['update:modelValue']);

// timeConfig controls all time-related behavior (official API per vue3datepicker.com/props/time-picker-configuration)
const timeConfig = computed(() => ({
  enableTimePicker: props.enableTimePicker,
  enableSeconds:    props.enableSeconds,
  is24:             props.is24,
}));

const internalValue = computed({
  get() {
    if (!props.modelValue) return null;
    return props.modelValue;
  },
  set(val) {
    emit('update:modelValue', val ?? '');
  },
});
</script>
