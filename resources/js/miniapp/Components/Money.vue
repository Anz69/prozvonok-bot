<script setup>
import { computed } from 'vue';
import { money } from '../lib/format';

const props = defineProps({ value: { type: [Number, String], default: 0 }, suffix: { type: String, default: '$' } });
const text = computed(() => money(props.value) + props.suffix);
</script>

<template>
    <span class="tabular font-display">{{ text }}</span>
</template>
