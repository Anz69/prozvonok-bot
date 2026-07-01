<script setup>
import { ref, computed } from 'vue';
import GeoSelect from '../Components/GeoSelect.vue';
import Card from '../Components/Card.vue';
import AnimatedMoney from '../Components/AnimatedMoney.vue';
import { useShared } from '../composables/usePage';

const { geos, user } = useShared();
const geo = ref(geos.value[0]?.code ?? 'RU');
const count = ref(1000);

const price = computed(() => geos.value.find((g) => g.code === geo.value)?.price ?? 0);
const discount = computed(() => user.value?.discount ?? 0);
const cost = computed(() => {
    const c = Math.max(0, Number(count.value) || 0);
    return (c / 1000) * price.value * (1 - discount.value / 100);
});
</script>

<template>
    <div data-page-root class="space-y-4 pt-2">
        <h1 class="font-display text-xl font-bold tracking-tight">Калькулятор</h1>

        <GeoSelect v-model="geo" :geos="geos" />

        <Card>
            <label class="mb-2 block text-sm text-muted">Количество номеров</label>
            <input
                v-model="count"
                type="number"
                inputmode="numeric"
                min="0"
                class="w-full rounded-xl border border-border bg-elevated px-4 py-3 font-display text-lg outline-none focus:border-accent"
            />
        </Card>

        <Card>
            <div class="flex items-end justify-between">
                <span class="text-sm text-muted">Стоимость</span>
                <span class="font-display text-3xl font-bold text-accent">
                    <AnimatedMoney :value="cost" />
                </span>
            </div>
            <Transition name="msg">
                <p v-if="discount" class="mt-2 text-xs text-muted">С учётом вашей скидки {{ discount }}%</p>
            </Transition>
            <p class="mt-1 text-xs text-muted">Тариф: {{ price }}$ / 1000 номеров</p>
        </Card>
    </div>
</template>
