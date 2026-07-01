<script setup>
import { ref } from 'vue';
import gsap from 'gsap';
import { ChevronDown, HelpCircle } from 'lucide-vue-next';
import { useTelegram } from '../composables/useTelegram';

defineProps({ items: { type: Array, default: () => [] } });

const { haptic } = useTelegram();
const open = ref(null);
const reduceMotion = typeof window !== 'undefined'
    && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

function toggle(i) {
    open.value = open.value === i ? null : i;
    haptic('light');
}

// Плавное раскрытие по высоте
function onEnter(el) {
    if (reduceMotion) return;
    gsap.fromTo(el, { height: 0, opacity: 0 }, { height: 'auto', opacity: 1, duration: 0.3, ease: 'power2.out' });
}
function onLeave(el, done) {
    if (reduceMotion) return done();
    gsap.to(el, { height: 0, opacity: 0, duration: 0.22, ease: 'power2.in', onComplete: done });
}
</script>

<template>
    <div data-page-root class="space-y-4 pt-2">
        <h1 class="font-display text-xl font-bold tracking-tight">Частые вопросы</h1>

        <div data-stagger class="space-y-2.5">
            <div
                v-for="(item, i) in items"
                :key="i"
                class="overflow-hidden rounded-2xl border border-border bg-surface"
            >
                <button
                    @click="toggle(i)"
                    class="flex w-full items-center gap-3 px-4 py-3.5 text-left cursor-pointer"
                >
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-accent/12 text-accent">
                        <HelpCircle class="h-4 w-4" />
                    </span>
                    <span class="flex-1 text-sm font-medium">{{ item.q }}</span>
                    <ChevronDown
                        class="h-5 w-5 shrink-0 text-muted transition-transform duration-300"
                        :class="open === i ? 'rotate-180 text-accent' : ''"
                    />
                </button>

                <Transition @enter="onEnter" @leave="onLeave" :css="false">
                    <div v-show="open === i" class="overflow-hidden">
                        <p class="whitespace-pre-line px-4 pb-4 text-sm leading-relaxed text-muted text-center">{{ item.a }}</p>
                    </div>
                </Transition>
            </div>
        </div>

        <p v-if="!items.length" class="rounded-2xl border border-border bg-surface px-4 py-8 text-center text-sm text-muted">
            Вопросы пока не добавлены.
        </p>
    </div>
</template>
