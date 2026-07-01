<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted } from 'vue';
import gsap from 'gsap';
import { LayoutGrid, ScanLine, Wallet, User } from 'lucide-vue-next';
import { useTelegram } from '../composables/useTelegram';

const page = usePage();
const { haptic } = useTelegram();

const items = [
    { href: '/app', label: 'Главная', icon: LayoutGrid },
    { href: '/app/check', label: 'Проверка', icon: ScanLine },
    { href: '/app/topup', label: 'Пополнить', icon: Wallet },
    { href: '/app/profile', label: 'Профиль', icon: User },
];

const current = computed(() => page.url.split('?')[0]);
const isActive = (href) => (href === '/app' ? current.value === '/app' : current.value.startsWith(href));
const activeIndex = computed(() => {
    const i = items.findIndex((it) => isActive(it.href));
    return i === -1 ? 0 : i;
});

const pill = ref(null);
const reduceMotion = typeof window !== 'undefined'
    && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

function movePill(animate = true) {
    if (!pill.value) return;
    if (animate && !reduceMotion) {
        gsap.to(pill.value, { xPercent: activeIndex.value * 100, duration: 0.42, ease: 'power3.out' });
    } else {
        gsap.set(pill.value, { xPercent: activeIndex.value * 100 });
    }
}

onMounted(() => movePill(false));
watch(activeIndex, () => movePill(true));
</script>

<template>
    <nav class="fixed inset-x-0 bottom-0 z-30 pb-[env(safe-area-inset-bottom)]">
        <div
            class="relative mx-auto mb-3 flex w-[calc(100%-2rem)] max-w-[440px] gap-0 overflow-hidden rounded-2xl border border-border bg-elevated/90 p-1.5 shadow-[0_12px_40px_-16px_rgba(0,0,0,.5)] backdrop-blur-xl"
        >
            <!-- Бегущий активный фон -->
            <span
                ref="pill"
                class="pointer-events-none absolute inset-y-1.5 left-1.5 z-0 w-[calc((100%-0.75rem)/4)] overflow-hidden rounded-xl bg-gradient-to-b from-accent/22 to-accent/10 ring-1 ring-accent/30"
            >
                <span class="shine absolute inset-0"></span>
            </span>

            <Link
                v-for="item in items"
                :key="item.href"
                :href="item.href"
                @click="haptic('light')"
                class="relative z-10 flex flex-1 flex-col items-center gap-1 rounded-xl py-2 text-[11px] font-medium transition-colors duration-300 cursor-pointer"
                :class="isActive(item.href) ? 'text-accent' : 'text-muted hover:text-fg'"
            >
                <component
                    :is="item.icon"
                    class="h-5 w-5 transition-transform duration-300"
                    :class="isActive(item.href) ? 'scale-110 -translate-y-0.5' : ''"
                />
                {{ item.label }}
            </Link>
        </div>
    </nav>
</template>

<style scoped>
/* Бегущий блик по активному фону */
.shine {
    background: linear-gradient(110deg, transparent 30%, rgba(255, 255, 255, 0.18) 50%, transparent 70%);
    background-size: 220% 100%;
    animation: run 2.4s ease-in-out infinite;
}
@keyframes run {
    0% { background-position: 180% 0; }
    100% { background-position: -80% 0; }
}
@media (prefers-reduced-motion: reduce) {
    .shine { animation: none; }
}
</style>
