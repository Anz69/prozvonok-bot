<script setup>
import { onMounted, onBeforeUnmount, ref, watch } from 'vue';
import lottie from 'lottie-web/build/player/lottie_light';

const props = defineProps({
    src: { type: String, required: true },          // путь к .json в /public
    loop: { type: [Boolean, Number], default: true },
    autoplay: { type: Boolean, default: true },
    speed: { type: Number, default: 1 },
    playOnHover: { type: Boolean, default: false },  // играть только при наведении/тапе
});

const emit = defineEmits(['loopComplete', 'complete']);

const root = ref(null);
let anim = null;
let alive = true;

const reduceMotion = typeof window !== 'undefined'
    && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

// Кэш распарсенного JSON по пути: при повторном монтировании (навигация между
// страницами) анимация строится мгновенно из памяти, без сетевого запроса и пустого кадра.
const dataCache = new Map();
function loadData(src) {
    if (!dataCache.has(src)) {
        dataCache.set(src, fetch(src).then((r) => r.json()).catch(() => null));
    }
    return dataCache.get(src);
}

async function build() {
    if (!root.value) return;
    const data = await loadData(props.src);
    if (!alive || !root.value) return; // размонтировали, пока грузили

    anim = lottie.loadAnimation({
        container: root.value,
        renderer: 'svg',
        loop: props.loop,
        autoplay: props.autoplay && !props.playOnHover && !reduceMotion,
        // animationData — мгновенно из кэша; path — фолбэк, если fetch не удался
        ...(data ? { animationData: data } : { path: props.src }),
        rendererSettings: { progressiveLoad: false, hideOnTransparent: true },
    });
    anim.setSpeed(props.speed);
    anim.addEventListener('loopComplete', () => emit('loopComplete'));
    anim.addEventListener('complete', () => emit('complete'));
    // prefers-reduced-motion: показываем статичный последний кадр и сразу «завершаем» цикл
    if (reduceMotion && anim) {
        anim.addEventListener('DOMLoaded', () => {
            anim.goToAndStop(anim.totalFrames - 1, true);
            emit('loopComplete');
            emit('complete');
        });
    }
}

function play() { if (props.playOnHover && anim && !reduceMotion) { anim.goToAndPlay(0, true); } }

onMounted(build);
onBeforeUnmount(() => { alive = false; anim?.destroy(); anim = null; });
watch(() => props.src, () => { anim?.destroy(); build(); });

defineExpose({ play });
</script>

<template>
    <div
        ref="root"
        class="lottie pointer-events-none select-none"
        @mouseenter="play"
        @touchstart.passive="play"
        aria-hidden="true"
    />
</template>

<style scoped>
.lottie { display: inline-block; line-height: 0; }
.lottie :deep(svg) { display: block; }
</style>
