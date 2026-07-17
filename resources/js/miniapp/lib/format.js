/** Деньги «по-человечески»: до 2 знаков (как в кошельке), без хвостовых нулей. 5.776 → «5,78». */
export function money(value) {
    const n = Number(value) || 0;
    let s = n.toLocaleString('ru-RU', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    return s.replace(/ /g, ' ');
}

/** Целое с разделителем тысяч. */
export function num(value) {
    return (Number(value) || 0).toLocaleString('ru-RU').replace(/ /g, ' ');
}
