<script setup>
import { ref } from 'vue';
import { Send, Sparkles } from 'lucide-vue-next';
import Card from '../Components/Card.vue';
import PrimaryButton from '../Components/PrimaryButton.vue';
import { useShared } from '../composables/usePage';
import { useTelegram } from '../composables/useTelegram';
import { post } from '../lib/api';
import { money } from '../lib/format';

defineProps({ bonuses: { type: Array, default: () => [] } });

const { settings } = useShared();
const { openLink, haptic } = useTelegram();
const amount = ref(null);
const loading = ref(false);
const error = ref('');
const invoice = ref(null);

async function create() {
    loading.value = true; error.value = '';
    try {
        invoice.value = await post('/app/topup', { amount: Number(amount.value) });
        haptic('light');
    } catch (e) {
        error.value = e.data?.message || 'Ошибка';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div data-page-root class="space-y-4 pt-2">
        <h1 class="font-display text-xl font-bold tracking-tight">Пополнение</h1>

        <Transition name="swap" mode="out-in">
            <div v-if="!invoice" key="form" class="space-y-4">
                <Card>
                    <label class="mb-2 block text-sm text-muted">Сумма пополнения, $</label>
                    <input
                        v-model="amount"
                        type="number"
                        inputmode="decimal"
                        :placeholder="`от ${money(settings.min_deposit)}`"
                        class="w-full rounded-xl border border-border bg-elevated px-4 py-3 font-display text-2xl outline-none focus:border-accent"
                    />
                </Card>

                <Card v-if="bonuses.length">
                    <p class="mb-2 flex items-center gap-2 text-sm text-muted"><Sparkles class="h-4 w-4 text-accent" /> Бонусы за объём</p>
                    <div class="space-y-1.5">
                        <div v-for="b in bonuses" :key="b.threshold" class="flex justify-between text-sm">
                            <span class="text-muted">от {{ money(b.threshold) }}$</span>
                            <span class="font-display font-semibold text-success">+{{ money(b.bonus) }}$</span>
                        </div>
                    </div>
                </Card>

                <Transition name="msg">
                    <p v-if="error" class="rounded-xl bg-danger/10 px-4 py-3 text-sm text-danger">{{ error }}</p>
                </Transition>
                <PrimaryButton :loading="loading" @click="create">Получить счёт</PrimaryButton>
            </div>

            <div v-else key="invoice" class="space-y-4">
                <Card class="space-y-3 text-center">
                    <p class="text-sm text-muted">Сумма к оплате</p>
                    <p class="font-display text-3xl font-bold text-accent">{{ invoice.amount }}$</p>
                    <p class="text-sm text-muted">
                        Оплата проходит через менеджера. Нажмите кнопку, отправьте сообщение и переведите сумму — менеджер подтвердит, баланс зачислится автоматически.
                    </p>
                </Card>
                <PrimaryButton @click="openLink(invoice.manager_url)">
                    <Send class="h-4 w-4" /> Оплатить у менеджера
                </PrimaryButton>
            </div>
        </Transition>
    </div>
</template>
