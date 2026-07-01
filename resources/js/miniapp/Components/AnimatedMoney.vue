<script setup>
import { ref, watch, onMounted } from 'vue';
import gsap from 'gsap';
import { money } from '../lib/format';

// Деньги с плавным «перекручиванием» числа при изменении значения.
const props = defineProps({
    value: { type: [Number, String], default: 0 },
    suffix: { type: String, default: '$' },
    duration: { type: Number, default: 0.45 },
});

const display = ref('0');
const tweened = { v: 0 };
const reduce = typeof window !== 'undefined'
    && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

function animateTo(target) {
    const t = Number(target) || 0;
    if (reduce) {
        tweened.v = t;
        display.value = money(t);
        return;
    }
    gsap.to(tweened, {
        v: t,
        duration: props.duration,
        ease: 'power2.out',
        overwrite: true, // новый ввод отменяет предыдущий твин
        onUpdate: () => (display.value = money(tweened.v)),
    });
}

onMounted(() => {
    tweened.v = Number(props.value) || 0;
    display.value = money(tweened.v);
});
watch(() => props.value, (nv) => animateTo(nv));
</script>

<template>
    <span class="tabular font-display">{{ display }}{{ suffix }}</span>
</template>
