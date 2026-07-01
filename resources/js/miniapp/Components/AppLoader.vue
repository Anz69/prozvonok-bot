<script setup>
// Минимальный лоадер: тонкое кольцо-спиннер. Без Lottie.
</script>

<template>
    <div class="app-loader fixed inset-0 z-[60] grid place-items-center bg-bg">
        <span class="spinner"></span>
    </div>
</template>

<style scoped>
.app-loader { animation: fade-in 0.2s ease-out; }
.spinner {
    width: 28px;
    height: 28px;
    border: 2.5px solid var(--c-border);
    border-top-color: var(--c-accent);
    border-radius: 9999px;
    animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
@keyframes fade-in { from { opacity: 0; } to { opacity: 1; } }
@media (prefers-reduced-motion: reduce) {
    .spinner { animation-duration: 1.4s; }
    .app-loader { animation: none; }
}
</style>
