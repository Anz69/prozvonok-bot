/** Деньги «по-человечески»: до 5 знаков, без хвостовых нулей, разделитель тысяч. */
export function money(value) {
    const n = Number(value) || 0;
    let s = n.toLocaleString('ru-RU', { minimumFractionDigits: 0, maximumFractionDigits: 5 });
    return s.replace(/ /g, ' ');
}

/** Целое с разделителем тысяч. */
export function num(value) {
    return (Number(value) || 0).toLocaleString('ru-RU').replace(/ /g, ' ');
}
