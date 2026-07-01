function csrf() {
    return document.querySelector('meta[name=csrf-token]')?.content ?? '';
}

/** POST JSON или FormData с CSRF и сессионной кукой. Бросает Error с .data при !ok. */
export async function post(url, body = {}, isForm = false) {
    const headers = { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' };
    let payload;
    if (isForm) {
        payload = body; // FormData
    } else {
        headers['Content-Type'] = 'application/json';
        payload = JSON.stringify(body);
    }

    const res = await fetch(url, { method: 'POST', headers, body: payload, credentials: 'same-origin' });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        throw Object.assign(new Error(data.message || 'Ошибка запроса'), { status: res.status, data });
    }
    return data;
}
