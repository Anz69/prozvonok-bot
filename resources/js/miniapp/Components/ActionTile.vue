<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: { type: String, required: true },
    label: { type: String, required: true },
    icon: { type: [Object, Function], required: true },
});
</script>

<template>
    <Link
        :href="href"
        class="flex flex-col items-start gap-3 rounded-2xl border border-border bg-surface p-4 transition-colors duration-200 hover:border-accent/40 active:scale-[.98] cursor-pointer"
    >
        <span class="grid h-11 w-11 place-items-center rounded-xl bg-accent/12 text-accent">
            <component :is="icon" class="h-5 w-5" />
        </span>
        <span class="text-sm font-medium text-fg">{{ label }}</span>
    </Link>
</template>
