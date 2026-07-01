import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/** Шорткаты к расшаренным пропсам Inertia. */
export function useShared() {
    const page = usePage();
    return {
        user: computed(() => page.props.user),
        geos: computed(() => page.props.geos ?? []),
        settings: computed(() => page.props.settings ?? {}),
        flash: computed(() => page.props.flash ?? {}),
    };
}
