<script setup>
import { PhoneCall, PhoneOff, Hash } from 'lucide-vue-next';
import Card from '../Components/Card.vue';
import Money from '../Components/Money.vue';
import { useShared } from '../composables/usePage';
import { num } from '../lib/format';

const { user } = useShared();
</script>

<template>
    <div data-page-root class="space-y-4 pt-2">
        <div data-stagger class="space-y-4">
            <div>
                <h1 class="font-display text-xl font-bold tracking-tight">{{ user?.name }}</h1>
                <p class="text-sm text-muted">ID {{ user?.id }} · с нами с {{ user?.reg_date }}</p>
            </div>

            <div class="grid grid-cols-3 gap-2">
                <Card class="text-center">
                    <Hash class="mx-auto h-4 w-4 text-muted" />
                    <div class="mt-1 font-display text-lg font-bold tabular">{{ num(user?.numbers_checked) }}</div>
                    <div class="text-[11px] text-muted">проверено</div>
                </Card>
                <Card class="text-center">
                    <PhoneCall class="mx-auto h-4 w-4 text-success" />
                    <div class="mt-1 font-display text-lg font-bold tabular">{{ num(user?.numbers_answered) }}</div>
                    <div class="text-[11px] text-muted">активные</div>
                </Card>
                <Card class="text-center">
                    <PhoneOff class="mx-auto h-4 w-4 text-danger" />
                    <div class="mt-1 font-display text-lg font-bold tabular">{{ num(user?.numbers_failed) }}</div>
                    <div class="text-[11px] text-muted">недоступны</div>
                </Card>
            </div>

            <Card class="space-y-2.5">
                <div class="flex justify-between text-sm"><span class="text-muted">Баланс</span><span class="font-display font-semibold"><Money :value="user?.deposit" /></span></div>
                <div class="flex justify-between text-sm"><span class="text-muted">Реферальный</span><span class="font-display font-semibold"><Money :value="user?.referral_balance" /></span></div>
                <div class="flex justify-between text-sm"><span class="text-muted">Всего пополнено</span><span class="font-display font-semibold"><Money :value="user?.total_deposited" /></span></div>
                <div class="flex justify-between text-sm"><span class="text-muted">% с рефералов</span><span class="font-display font-semibold">{{ user?.referral_percent }}%</span></div>
                <div class="flex justify-between text-sm"><span class="text-muted">Скидка на проверку</span><span class="font-display font-semibold">{{ user?.discount }}%</span></div>
                <div class="flex justify-between text-sm"><span class="text-muted">Премиум</span><span class="font-display font-semibold">{{ user?.premium ? (user.premium === 'premium_plus' ? 'Премиум+' : 'Премиум') : '—' }}</span></div>
            </Card>
        </div>
    </div>
</template>
