<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Радистка Cat</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        body { margin: 0; height: 100vh; display: grid; place-items: center;
            background: #0B0F14; color: #8A97A6; font-family: system-ui, sans-serif; }
        .spinner { width: 28px; height: 28px; border: 2.5px solid rgba(255,255,255,.15);
            border-top-color: #FF5C5C; border-radius: 50%; animation: spin .7s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div id="state"><div class="spinner"></div></div>
    <script>
        (function () {
            var tg = window.Telegram && window.Telegram.WebApp;
            try { tg && tg.ready(); tg && tg.expand(); } catch (e) {}
            var initData = tg ? tg.initData : '';
            fetch('/app/session', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'X-Telegram-Init-Data': initData,
                },
                body: '{}',
            }).then(function (r) {
                if (r.ok) { location.replace('/app'); }
                else { document.getElementById('state').textContent = 'Откройте приложение из Telegram.'; }
            }).catch(function () {
                document.getElementById('state').textContent = 'Ошибка авторизации.';
            });
        })();
    </script>
</body>
</html>
