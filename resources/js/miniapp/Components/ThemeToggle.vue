<script setup>
import { Sun, Moon } from 'lucide-vue-next';
import { useTheme } from '../composables/useTheme';

const { theme, toggle } = useTheme();
</script>

<template>
    <button
        type="button"
        @click="toggle"
        aria-label="Сменить тему"
        class="grid h-10 w-10 place-items-center rounded-full border border-border bg-surface text-muted transition-colors active:scale-95 cursor-pointer"
    >
        <Transition name="pop" mode="out-in">
            <Moon v-if="theme === 'dark'" key="moon" class="h-5 w-5" />
            <Sun v-else key="sun" class="h-5 w-5" />
        </Transition>
    </button>
</template>
