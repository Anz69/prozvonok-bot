<script setup>
import { ref, onMounted } from 'vue';
import gsap from 'gsap';
import { Gem } from 'lucide-vue-next';
import { money } from '../lib/format';

const props = defineProps({
    deposit: { type: Number, default: 0 },
    refBalance: { type: Number, default: 0 },
    premium: { type: String, default: null },
    premiumUntil: { type: String, default: null },
});

const shown = ref('0');

onMounted(() => {
    const reduce = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    if (reduce) {
        shown.value = money(props.deposit);
        return;
    }
    const o = { v: 0 };
    gsap.to(o, {
        v: props.deposit,
        duration: 0.9,
        ease: 'power2.out',
        onUpdate: () => (shown.value = money(o.v)),
    });
});
</script>

<template>
    <div class="rounded-2xl border border-border bg-elevated p-5">
        <div class="flex items-center justify-between">
            <span class="text-sm text-muted">Баланс</span>
            <span
                v-if="premium"
                class="inline-flex items-center gap-1 rounded-full bg-accent/12 px-2.5 py-1 text-[11px] font-medium text-accent"
            >
                <Gem class="h-3.5 w-3.5" />
                {{ premium === 'premium_plus' ? 'Премиум+' : 'Премиум' }}
            </span>
        </div>

        <div class="mt-1 font-display text-[40px] font-bold leading-none tabular">
            {{ shown }}<span class="text-2xl text-muted">$</span>
        </div>

        <div class="mt-4 flex items-center gap-2 text-sm">
            <span class="text-muted">Реферальный:</span>
            <span class="font-display font-semibold tabular">{{ money(refBalance) }}$</span>
        </div>
    </div>
</template>
