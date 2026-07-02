<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { UploadCloud, FileCheck2, CheckCircle2 } from 'lucide-vue-next';
import GeoSelect from '../Components/GeoSelect.vue';
import Card from '../Components/Card.vue';
import Money from '../Components/Money.vue';
import PrimaryButton from '../Components/PrimaryButton.vue';
import { useShared } from '../composables/usePage';
import { useTelegram } from '../composables/useTelegram';
import { post } from '../lib/api';
import { num } from '../lib/format';

const { geos, settings } = useShared();
const { haptic } = useTelegram();

const geo = ref(geos.value[0]?.code ?? 'RU');
const file = ref(null);
const fileName = ref('');
const loading = ref(false);
const error = ref('');
const quote = ref(null);
const done = ref(null);

function pick(e) {
    file.value = e.target.files?.[0] ?? null;
    fileName.value = file.value?.name ?? '';
    quote.value = null;
    error.value = '';
}

async function getQuote() {
    if (!file.value) { error.value = 'Выберите файл .xlsx или .txt'; return; }
    loading.value = true; error.value = '';
    try {
        const fd = new FormData();
        fd.append('geo', geo.value);
        fd.append('file', file.value);
        quote.value = await post('/app/check/quote', fd, true);
        haptic('light');
    } catch (e) {
        error.value = e.data?.message || 'Ошибка расчёта';
    } finally {
        loading.value = false;
    }
}

async function confirm() {
    loading.value = true; error.value = '';
    try {
        const res = await post('/app/check/confirm', { token: quote.value.token, geo: quote.value.geo });
        done.value = res;
        haptic('medium');
    } catch (e) {
        error.value = e.data?.message || 'Ошибка подтверждения';
        if (e.data?.need_topup) setTimeout(() => router.visit('/app/topup'), 1200);
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div data-page-root class="space-y-4 pt-2">
        <h1 class="font-display text-xl font-bold tracking-tight">Проверка базы</h1>

        <Transition name="swap" mode="out-in">
            <div v-if="!done" key="form" class="space-y-4">
                <GeoSelect v-model="geo" :geos="geos" />

                <Card>
                    <label class="flex cursor-pointer flex-col items-center gap-2 py-4 text-center">
                        <span class="grid h-12 w-12 place-items-center rounded-xl bg-accent/12 text-accent">
                            <Transition name="pop" mode="out-in">
                                <FileCheck2 v-if="fileName" class="h-6 w-6" />
                                <UploadCloud v-else class="h-6 w-6" />
                            </Transition>
                        </span>
                        <span class="text-sm font-medium">{{ fileName || 'Загрузить файл' }}</span>
                        <span class="text-xs text-muted">.xlsx или .txt · до {{ num(settings.max_numbers) }} номеров</span>
                        <input type="file" accept=".xlsx,.txt" class="hidden" @change="pick" />
                    </label>
                </Card>

                <Transition name="msg">
                    <p v-if="error" class="rounded-xl bg-danger/10 px-4 py-3 text-sm text-danger">{{ error }}</p>
                </Transition>

                <Transition name="msg">
                    <Card v-if="quote" class="space-y-2">
                        <div class="flex justify-between text-sm"><span class="text-muted">Номеров (валидных)</span><span class="font-display font-semibold">{{ num(quote.valid) }} / {{ num(quote.total) }}</span></div>
                        <div v-if="quote.free" class="flex justify-between text-sm"><span class="text-muted">Бесплатно</span><span class="font-display font-semibold text-success">{{ num(quote.free) }}</span></div>
                        <div v-if="quote.discount" class="flex justify-between text-sm"><span class="text-muted">Скидка</span><span class="font-display font-semibold">{{ quote.discount }}%</span></div>
                        <div class="flex justify-between border-t border-border pt-2 text-base"><span class="text-muted">К списанию</span><span class="font-display text-xl font-bold text-accent"><Money :value="quote.cost" /></span></div>
                    </Card>
                </Transition>

                <Transition name="fade" mode="out-in">
                    <PrimaryButton v-if="!quote" key="calc" :loading="loading" @click="getQuote">Рассчитать</PrimaryButton>
                    <PrimaryButton v-else key="confirm" :loading="loading" @click="confirm">Подтвердить и запустить</PrimaryButton>
                </Transition>
            </div>

            <Card v-else key="done" class="text-center">
                <span class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-success/12 text-success">
                    <CheckCircle2 class="h-7 w-7" />
                </span>
                <h2 class="mt-3 font-display text-lg font-bold">Принято в работу</h2>
                <p class="mt-1 text-sm text-muted">
                    {{ num(done.valid) }} номеров · списано <Money :value="done.cost" />
                </p>
                <p v-if="done.eta" class="mt-2 text-sm font-medium">
                    Готовность — {{ done.eta }}
                </p>
                <p class="mt-1 text-xs text-muted">Результат придёт отдельным файлом в бота.</p>
            </Card>
        </Transition>
    </div>
</template>
