<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { CheckCircle2, Phone, Globe, ExternalLink, ChevronRight, FileText, Clock } from 'lucide-vue-next';
import Card from '../Components/Card.vue';
import Flag from '../Components/Flag.vue';
import { useShared } from '../composables/usePage';
import { useTelegram } from '../composables/useTelegram';
import { money, num } from '../lib/format';

const props = defineProps({ links: { type: Object, default: () => ({}) } });
const { settings, geos } = useShared();
const { openLink } = useTelegram();

// FAQ — внутренняя страница; остальное (гайды/поддержка/канал) — внешние ссылки.
const externalLinks = computed(() => {
    const { faq, ...rest } = props.links || {};
    return rest;
});
const labels = { guide: 'Как пользоваться', improve: 'Как улучшить дозвон', support: 'Поддержка', channel: 'Канал' };

const checks = [
    { icon: CheckCircle2, text: 'Доступность номера' },
    { icon: Phone, text: 'Текущий оператор (с учётом переноса, MNP)' },
    { icon: Globe, text: 'Часовой пояс абонента' },
];
</script>

<template>
    <div data-page-root class="space-y-4 pt-2">
        <h1 class="font-display text-xl font-bold tracking-tight">О сервисе</h1>

        <Card>
            <p class="text-sm text-muted">
                Подготовьте базу к обзвону заранее: сервис проверит каждый номер и вернёт отчёт с актуальной информацией по контактам — работайте только по живым.
            </p>
        </Card>

        <Card class="space-y-3">
            <p class="text-sm font-medium">Что проверяется</p>
            <div v-for="c in checks" :key="c.text" class="flex items-center gap-3 text-sm">
                <component :is="c.icon" class="h-5 w-5 shrink-0 text-accent" />
                <span>{{ c.text }}</span>
            </div>
        </Card>

        <Card class="space-y-2.5">
            <p class="text-sm font-medium">География и тарифы</p>
            <div v-for="g in geos" :key="g.code" class="flex items-center justify-between text-sm">
                <span class="flex items-center gap-2">
                    <Flag :code="g.code" :fallback="g.flag" class="h-5 w-5" />
                    {{ g.name }}
                </span>
                <span class="font-display font-semibold tabular">{{ money(g.price) }}$ / 1000</span>
            </div>
        </Card>

        <Card class="space-y-2.5 text-sm">
            <div class="flex items-center gap-3">
                <FileText class="h-5 w-5 shrink-0 text-muted" />
                <span>Форматы .xlsx, .txt · до {{ num(settings.max_numbers) }} номеров</span>
            </div>
            <div class="flex items-center gap-3">
                <Clock class="h-5 w-5 shrink-0 text-muted" />
                <span>Обработка {{ settings.wh_start }}:00–{{ settings.wh_end }}:00 по часовому поясу абонента</span>
            </div>
            <p class="text-xs text-muted">Файл вне рабочего времени встанет в очередь и уйдёт в работу после начала окна.</p>
        </Card>

        <div class="space-y-2">
            <Link
                href="/app/faq"
                class="flex w-full items-center justify-between rounded-2xl border border-border bg-surface px-4 py-3 text-sm transition-colors active:scale-[.99] cursor-pointer"
            >
                <span>Частые вопросы</span>
                <ChevronRight class="h-4 w-4 text-muted" />
            </Link>

            <button
                v-for="(url, key) in externalLinks"
                v-show="url"
                :key="key"
                @click="openLink(url)"
                class="flex w-full items-center justify-between rounded-2xl border border-border bg-surface px-4 py-3 text-sm transition-colors active:scale-[.99] cursor-pointer"
            >
                <span>{{ labels[key] || key }}</span>
                <ExternalLink class="h-4 w-4 text-muted" />
            </button>
        </div>
    </div>
</template>
