<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { Check, Sparkles, BadgePercent, Wallet, Zap, Crown, Gem } from 'lucide-vue-next';
import Card from '../Components/Card.vue';
import Money from '../Components/Money.vue';
import PrimaryButton from '../Components/PrimaryButton.vue';
import { useShared } from '../composables/usePage';
import { useTelegram } from '../composables/useTelegram';
import { post } from '../lib/api';
import { money, num } from '../lib/format';

const props = defineProps({
    tiers: { type: Array, default: () => [] },
    days: { type: Number, default: 30 },
    trialDeposit: { type: Number, default: 1000 },
    paybackNumbers: { type: Number, default: 0 },
});

const { user } = useShared();
const { haptic } = useTelegram();

const isActive = computed(() => !!user.value?.premium);
const currentTier = computed(() => user.value?.premium);
const maxDiscount = computed(() => Math.max(0, ...props.tiers.map((t) => t.discount)));

const loadingTier = ref('');
const error = ref('');
const success = ref('');

const benefits = computed(() => [
    { icon: BadgePercent, text: `Скидка до ${maxDiscount.value}% на каждую проверку` },
    { icon: Wallet, text: 'Доступен вывод реферального баланса' },
    { icon: Zap, text: 'Приоритетная обработка ваших баз' },
    { icon: Sparkles, text: props.paybackNumbers ? `Окупается на объёме (~${num(props.paybackNumbers)} номеров)` : 'Окупается уже на средних объёмах' },
]);

async function activate(tier) {
    loadingTier.value = tier;
    error.value = '';
    success.value = '';
    try {
        const res = await post('/app/premium/activate', { tier });
        success.value = `Подписка активна до ${res.until}`;
        haptic('medium');
        router.reload({ preserveScroll: true });
    } catch (e) {
        error.value = e.data?.message || 'Не удалось активировать';
        haptic('light');
        if (e.data?.need_topup) setTimeout(() => router.visit('/app/topup'), 1300);
    } finally {
        loadingTier.value = '';
    }
}

async function trial() {
    loadingTier.value = 'trial';
    error.value = '';
    success.value = '';
    try {
        const res = await post('/app/premium/trial', {});
        success.value = res.message || 'Заявка принята';
        haptic('medium');
    } catch (e) {
        error.value = e.data?.message || 'Не удалось отправить заявку';
        haptic('light');
    } finally {
        loadingTier.value = '';
    }
}
</script>

<template>
    <div data-page-root class="space-y-4 pt-2">
        <div class="flex items-center gap-2">
            <Gem class="h-5 w-5 text-accent" />
            <h1 class="font-display text-xl font-bold tracking-tight">Премиум</h1>
        </div>

        <!-- Статус -->
        <Card :class="isActive ? 'ring-1 ring-accent/40' : ''">
            <template v-if="isActive">
                <p class="flex items-center gap-2 text-sm text-muted">
                    <Gem class="h-4 w-4 shrink-0 text-accent" />
                    {{ currentTier === 'premium_plus' ? 'Премиум+' : 'Премиум' }} активен
                </p>
                <p class="mt-2 font-display text-lg font-bold">до {{ user?.premium_until }}</p>
                <p class="mt-0.5 text-sm text-muted">Скидка на проверки: {{ user?.discount }}%</p>
            </template>
            <template v-else>
                <p class="text-sm text-muted">Подписка для тех, кто проверяет базы регулярно — постоянная скидка и вывод рефералки.</p>
            </template>
        </Card>

        <!-- Преимущества -->
        <Card class="space-y-3">
            <p class="text-sm font-medium">Что даёт премиум</p>
            <div v-for="b in benefits" :key="b.text" class="flex items-center gap-3 text-sm">
                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-accent/12 text-accent">
                    <component :is="b.icon" class="h-4 w-4" />
                </span>
                <span>{{ b.text }}</span>
            </div>
        </Card>

        <!-- Тарифы -->
        <div class="grid grid-cols-2 gap-3">
            <Card
                v-for="t in tiers"
                :key="t.key"
                class="flex flex-col gap-2"
                :class="currentTier === t.key ? 'ring-1 ring-accent/40' : ''"
            >
                <div class="flex items-center justify-between">
                    <span class="font-display font-semibold">{{ t.name }}</span>
                    <component :is="t.key === 'premium_plus' ? Gem : Crown" class="h-5 w-5 text-accent" />
                </div>
                <div class="font-display text-2xl font-bold"><Money :value="t.price" /></div>
                <div class="text-[11px] text-muted">за {{ days }} дн. · скидка {{ t.discount }}%</div>
                <PrimaryButton
                    class="mt-1"
                    :loading="loadingTier === t.key"
                    @click="activate(t.key)"
                >
                    {{ currentTier === t.key ? 'Продлить' : 'Подключить' }}
                </PrimaryButton>
            </Card>
        </div>

        <!-- Пробный -->
        <Card class="space-y-2 text-center">
            <p class="text-sm text-muted">Пробный премиум при депозите от {{ money(trialDeposit) }}$</p>
            <PrimaryButton variant="ghost" :loading="loadingTier === 'trial'" @click="trial">
                <Sparkles class="h-4 w-4 text-accent" /> Запросить пробный
            </PrimaryButton>
        </Card>

        <!-- Сообщения -->
        <Transition name="msg">
            <p v-if="error" class="rounded-xl bg-danger/10 px-4 py-3 text-sm text-danger">{{ error }}</p>
        </Transition>
        <Transition name="msg">
            <p v-if="success" class="flex items-center gap-2 rounded-xl bg-success/10 px-4 py-3 text-sm text-success">
                <Check class="h-4 w-4" /> {{ success }}
            </p>
        </Transition>
    </div>
</template>
