<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { CheckCircle2, XCircle, Phone, Globe, Mic, ExternalLink, ChevronRight } from 'lucide-vue-next';
import Card from '../Components/Card.vue';
import { useShared } from '../composables/usePage';
import { useTelegram } from '../composables/useTelegram';

const props = defineProps({ links: { type: Object, default: () => ({}) } });
const { settings } = useShared();
const { openLink } = useTelegram();

// FAQ — внутренняя страница, остальное (гайды/поддержка/канал) — внешние ссылки.
const externalLinks = computed(() => {
    const { faq, ...rest } = props.links || {};
    return rest;
});
const labels = { guide: 'Как пользоваться', improve: 'Как улучшить дозвон', support: 'Поддержка', channel: 'Канал' };

const results = [
    { icon: CheckCircle2, color: 'text-success', text: 'Активен — живой ответ' },
    { icon: XCircle, color: 'text-danger', text: 'Недоступен — нет связи / автоответчик' },
    { icon: Phone, color: 'text-accent', text: 'Реальный оператор с учётом MNP' },
    { icon: Globe, color: 'text-accent', text: 'Часовой пояс и статус абонента' },
    { icon: Mic, color: 'text-accent', text: 'Расшифровка разговора' },
];
</script>

<template>
    <div data-page-root class="space-y-4 pt-2">
        <h1 class="font-display text-xl font-bold tracking-tight">О сервисе</h1>

        <Card>
            <p class="text-sm text-muted">
                Радистка Cat помогает работать только по живым контактам — без слитого бюджета на «мёртвые» номера.
            </p>
        </Card>

        <Card class="space-y-3">
            <p class="text-sm font-medium">Что показывает проверка</p>
            <div v-for="r in results" :key="r.text" class="flex items-center gap-3 text-sm">
                <component :is="r.icon" class="h-5 w-5 shrink-0" :class="r.color" />
                <span>{{ r.text }}</span>
            </div>
        </Card>

        <Card class="space-y-2.5 text-sm">
            <div class="flex justify-between">
                <span class="text-muted">Форматы</span>
                <span>.xlsx, .txt</span>
            </div>
            <div class="flex justify-between">
                <span class="text-muted">Время работы</span>
                <span>{{ settings.wh_start }}:00–{{ settings.wh_end }}:00</span>
            </div>
            <p class="text-xs text-muted">Время указано по часовому поясу абонента.</p>
        </Card>

        <div class="space-y-2">
            <!-- FAQ — внутренняя страница приложения -->
            <Link
                href="/app/faq"
                class="flex w-full items-center justify-between rounded-2xl border border-border bg-surface px-4 py-3 text-sm transition-colors active:scale-[.99] cursor-pointer"
            >
                <span class="flex items-center gap-2"> Частые вопросы</span>
                <ChevronRight class="h-4 w-4 text-muted" />
            </Link>

            <!-- Внешние ресурсы -->
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
