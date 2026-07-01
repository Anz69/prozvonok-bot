<script setup>
import { computed } from 'vue';
import Lottie from './Lottie.vue';

const props = defineProps({
    code: { type: String, default: '' },     // ГЕО-код: RU / KZ / BY
    fallback: { type: String, default: '' }, // эмодзи-флаг, если нет Lottie
});

// Для каких ГЕО есть анимированные флаги (public/lottie/flag-<code>.json)
const KNOWN = ['ru', 'kz', 'by'];
const lc = computed(() => (props.code || '').toLowerCase());
const hasLottie = computed(() => KNOWN.includes(lc.value));
</script>

<template>
    <Lottie
        v-if="hasLottie"
        :src="`/lottie/flag-${lc}.json`"
        class="inline-block aspect-square"
    />
    <span v-else>{{ fallback }}</span>
</template>
