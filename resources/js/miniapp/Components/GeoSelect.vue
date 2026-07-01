<script setup>
import Flag from './Flag.vue';

const props = defineProps({
    geos: { type: Array, default: () => [] },
    modelValue: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <div class="grid grid-cols-3 gap-2">
        <button
            v-for="g in geos"
            :key="g.code"
            type="button"
            @click="emit('update:modelValue', g.code)"
            class="flex flex-col items-center gap-1 rounded-xl border py-3 text-sm font-medium transition-colors duration-200 active:scale-[.97] cursor-pointer"
            :class="modelValue === g.code
                ? 'border-accent bg-accent/12 text-fg'
                : 'border-border bg-surface text-muted'"
        >
            <Flag :code="g.code" :fallback="g.flag" class="h-7 w-7" />
            <span>{{ g.code }}</span>
            <span class="text-[11px] text-muted">{{ g.price }}$/1k</span>
        </button>
    </div>
</template>
