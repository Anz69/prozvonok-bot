<script setup>
import { Loader2 } from 'lucide-vue-next';

const props = defineProps({
    loading: Boolean,
    disabled: Boolean,
    variant: { type: String, default: 'accent' }, // accent | ghost
});
</script>

<template>
    <button
        type="button"
        :disabled="loading || disabled"
        class="group relative inline-flex w-full items-center justify-center gap-2 overflow-hidden rounded-2xl px-5 py-3.5 font-display text-[15px] font-semibold transition-all duration-200 active:scale-[.98] disabled:opacity-50 cursor-pointer"
        :class="variant === 'accent'
            ? 'bg-accent text-accent-fg shadow-[0_8px_28px_-12px_var(--c-accent)]'
            : 'border border-border bg-surface text-fg'"
    >
        <!-- Бегущий активный фон (только акцентная кнопка, не в loading) -->
        <span
            v-if="variant === 'accent' && !disabled"
            class="run-bg pointer-events-none absolute inset-0"
            :class="loading ? 'opacity-40' : ''"
        ></span>

        <Loader2 v-if="loading" class="relative h-4 w-4 animate-spin" />
        <span class="relative inline-flex items-center gap-2"><slot /></span>
    </button>
</template>

<style scoped>
.run-bg {
    background: linear-gradient(110deg, transparent 35%, rgba(255, 255, 255, 0.28) 50%, transparent 65%);
    background-size: 220% 100%;
    animation: run 2.6s ease-in-out infinite;
}
@keyframes run {
    0% { background-position: 180% 0; }
    100% { background-position: -80% 0; }
}
@media (prefers-reduced-motion: reduce) {
    .run-bg { animation: none; }
}
</style>
