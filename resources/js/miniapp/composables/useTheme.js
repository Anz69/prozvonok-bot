import { ref } from 'vue';

const theme = ref('dark');
const STORAGE = 'miniapp-theme';

function apply(value) {
    theme.value = value;
    document.documentElement.classList.toggle('dark', value === 'dark');

    const tg = window.Telegram?.WebApp;
    const bg = value === 'dark' ? '#0B0F14' : '#F6F7F9';
    try {
        tg?.setBackgroundColor?.(bg);
        tg?.setHeaderColor?.(bg);
    } catch (e) {
        // вне Telegram — игнорируем
    }
}

export function initTheme() {
    const tg = window.Telegram?.WebApp;
    const saved = localStorage.getItem(STORAGE);
    const fromSystem = window.matchMedia?.('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    apply(saved || tg?.colorScheme || fromSystem);

    // если пользователь не выбрал вручную — следуем за темой Telegram
    tg?.onEvent?.('themeChanged', () => {
        if (!localStorage.getItem(STORAGE)) {
            apply(tg.colorScheme || 'dark');
        }
    });
}

export function useTheme() {
    const toggle = () => {
        const next = theme.value === 'dark' ? 'light' : 'dark';
        localStorage.setItem(STORAGE, next);
        apply(next);
    };

    return { theme, toggle };
}
