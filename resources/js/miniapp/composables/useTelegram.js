export function initTelegram() {
    const tg = window.Telegram?.WebApp;
    if (!tg) return;
    try {
        tg.ready();
        tg.expand();
        tg.disableVerticalSwipes?.();
    } catch (e) {
        // no-op
    }
}

export function useTelegram() {
    const tg = window.Telegram?.WebApp;

    const haptic = (type = 'light') => {
        try {
            tg?.HapticFeedback?.impactOccurred?.(type);
        } catch (e) {
            // no-op
        }
    };

    const openLink = (url) => {
        if (tg?.openTelegramLink && url.startsWith('https://t.me/')) {
            tg.openTelegramLink(url);
        } else if (tg?.openLink) {
            tg.openLink(url);
        } else {
            window.open(url, '_blank');
        }
    };

    const close = () => {
        try {
            tg?.close();
        } catch (e) {
            // no-op
        }
    };

    return { tg, isTelegram: !!tg, haptic, openLink, close };
}
