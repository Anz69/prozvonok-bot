<script setup>
import { Link } from '@inertiajs/vue3';
import { ScanLine, Calculator, Users, Info, Wallet, HelpCircle, ChevronRight, Gem } from 'lucide-vue-next';
import BalanceCard from '../Components/BalanceCard.vue';
import ActionTile from '../Components/ActionTile.vue';
import Flag from '../Components/Flag.vue';
import { useShared } from '../composables/usePage';
import { num } from '../lib/format';

defineProps({ availability: { type: Array, default: () => [] } });
const { user } = useShared();
</script>

<template>
    <div data-page-root>
        <div data-stagger class="space-y-4 pt-2">
            <div>
                <p class="text-sm text-muted">Привет,</p>
                <h1 class="font-display text-2xl font-bold tracking-tight">
                    {{ user?.first_name || 'друг' }}
                </h1>
            </div>

            <BalanceCard
                :deposit="user?.deposit || 0"
                :ref-balance="user?.referral_balance || 0"
                :premium="user?.premium"
                :premium-until="user?.premium_until"
            />

            <div class="rounded-2xl border border-border bg-surface p-4">
                <p class="mb-3 text-sm text-muted">Доступно к проверке</p>
                <div class="grid grid-cols-3 gap-2">
                    <div v-for="a in availability" :key="a.code" class="rounded-xl bg-elevated/60 p-3 text-center">
                        <Flag :code="a.code" :fallback="a.flag" class="mx-auto h-7 w-7" />
                        <div class="mt-1 font-display text-base font-semibold tabular">{{ num(a.numbers) }}</div>
                        <div class="text-[11px] text-muted">номеров</div>
                    </div>
                </div>
            </div>

            <Link
                href="/app/premium"
                class="flex items-center gap-3 rounded-2xl border border-border bg-surface p-4 transition-colors active:scale-[.99] cursor-pointer"
            >
                <Gem class="size-6 shrink-0 text-accent" />
                <div class="min-w-0 flex-1">
                    <p class="font-display text-sm font-semibold">{{ user?.premium ? 'Премиум активен' : 'Премиум' }}</p>
                    <p class="truncate text-xs text-muted">
                        {{ user?.premium ? `до ${user?.premium_until} · скидка ${user?.discount}%` : 'Скидка на проверки и вывод рефералки' }}
                    </p>
                </div>
                <ChevronRight class="h-5 w-5 shrink-0 text-muted" />
            </Link>

            <div class="grid grid-cols-2 gap-3">
                <ActionTile href="/app/check" label="Проверить базу" :icon="ScanLine" />
                <ActionTile href="/app/topup" label="Пополнить" :icon="Wallet" />
                <ActionTile href="/app/calculator" label="Калькулятор" :icon="Calculator" />
                <ActionTile href="/app/referral" label="Партнёрам" :icon="Users" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <ActionTile href="/app/faq" label="Частые вопросы" :icon="HelpCircle" />
                <ActionTile href="/app/info" label="О сервисе" :icon="Info" />
            </div>
        </div>
    </div>
</template>
