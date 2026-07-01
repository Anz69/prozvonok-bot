<script setup>
import { ref } from 'vue';
import { Copy, Check, Users, Wallet } from 'lucide-vue-next';
import Card from '../Components/Card.vue';
import Money from '../Components/Money.vue';
import { useShared } from '../composables/usePage';
import { useTelegram } from '../composables/useTelegram';

const { user } = useShared();
const { haptic } = useTelegram();
const copied = ref(false);

async function copy() {
    try {
        await navigator.clipboard.writeText(user.value?.referral_link ?? '');
        copied.value = true;
        haptic('light');
        setTimeout(() => (copied.value = false), 1600);
    } catch (e) { /* no-op */ }
}
</script>

<template>
    <div data-page-root class="space-y-4 pt-2">
        <h1 class="font-display text-xl font-bold tracking-tight">Партнёрам</h1>

        <div class="grid grid-cols-2 gap-3">
            <Card class="text-center">
                <Users class="mx-auto h-5 w-5 text-accent" />
                <div class="mt-1 font-display text-2xl font-bold tabular">{{ user?.referrals ?? 0 }}</div>
                <div class="text-xs text-muted">приглашено</div>
            </Card>
            <Card class="text-center">
                <Wallet class="mx-auto h-5 w-5 text-accent" />
                <div class="mt-1 font-display text-2xl font-bold"><Money :value="user?.referral_balance" /></div>
                <div class="text-xs text-muted">баланс</div>
            </Card>
        </div>

        <Card>
            <p class="mb-2 text-sm text-muted">Ваша ссылка</p>
            <div class="flex items-center gap-2">
                <code class="flex-1 truncate rounded-xl bg-elevated px-3 py-2.5 text-sm">{{ user?.referral_link }}</code>
                <button
                    @click="copy"
                    class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-accent text-accent-fg transition-transform active:scale-95 cursor-pointer"
                    aria-label="Скопировать"
                >
                    <Transition name="pop" mode="out-in">
                        <Check v-if="copied" key="ok" class="h-5 w-5" />
                        <Copy v-else key="copy" class="h-5 w-5" />
                    </Transition>
                </button>
            </div>
        </Card>

        <Card class="space-y-3 text-sm">
            <div class="flex justify-between gap-4">
                <span class="text-muted">С пополнений рефералов</span>
                <span class="shrink-0 font-medium">{{ user?.referral_percent }}%</span>
            </div>
            <div class="flex justify-between gap-4">
                <span class="text-muted">Бонус за реферала от 100$</span>
                <span class="shrink-0 font-medium">+10$</span>
            </div>
            <div class="flex justify-between gap-4">
                <span class="text-muted">Вывод реф. баланса</span>
                <span class="shrink-0 font-medium">{{ user?.can_withdraw ? 'доступен' : 'от 10 реф.' }}</span>
            </div>
        </Card>
    </div>
</template>
