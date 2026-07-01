<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { Cat } from 'lucide-vue-next';
import ThemeToggle from '../Components/ThemeToggle.vue';
import BottomNav from '../Components/BottomNav.vue';
import AppLoader from '../Components/AppLoader.vue';

// Короткий лоадер только при первом входе; переходы между страницами — лёгкая CSS-анимация.
const booting = ref(true);
onMounted(() => setTimeout(() => (booting.value = false), 500));

// Ключ страницы — для пер-страничной анимации перехода.
const page = usePage();
const pageKey = computed(() => page.component);
</script>

<template>
    <div class="flex min-h-dvh flex-col bg-bg text-fg">
        <header class="sticky top-0 z-20 bg-bg/80 backdrop-blur">
            <div class="mx-auto flex h-14 w-full max-w-[440px] items-center justify-between px-4">
                <Link href="/app" class="flex items-center gap-2 cursor-pointer">
                    <Cat class="h-5 w-5 text-accent" />
                    <span class="font-display text-[15px] font-semibold tracking-tight">Радистка&nbsp;Cat</span>
                </Link>
                <ThemeToggle />
            </div>
        </header>

        <main class="relative mx-auto w-full max-w-[440px] flex-1 pb-28">
            <Transition name="page">
                <div :key="pageKey" class="page-pane px-4 pt-1">
                    <slot />
                </div>
            </Transition>
        </main>

        <BottomNav />

        <Transition name="loader">
            <AppLoader v-if="booting" />
        </Transition>
    </div>
</template>

<style scoped>

/* Кроссфейд между страницами: уходящая и входящая накладываются (нет «пустого кадра»,
   из-за которого Lottie на миг пропадали). GPU-only: opacity + transform. */
.page-enter-active,
.page-leave-active {
    transition: opacity 0.22s ease, transform 0.22s ease;
    will-change: opacity, transform;
}
/* Уходящую вынимаем из потока и накладываем поверх — обе видны одновременно. */
.page-leave-active {
    position: absolute;
    inset: 0;
}
.page-enter-from {
    opacity: 0;
    transform: translate3d(0, 8px, 0);
}
.page-leave-to {
    opacity: 0;
    transform: translate3d(0, -4px, 0);
}

.loader-leave-active {
    transition: opacity 0.3s ease;
}
.loader-leave-to {
    opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
    .page-enter-active,
    .page-leave-active {
        transition: none;
    }
    .page-enter-from,
    .page-leave-to {
        opacity: 1;
        transform: none;
    }
}
</style>
