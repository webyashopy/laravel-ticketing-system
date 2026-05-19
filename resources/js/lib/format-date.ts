/**
 * Sjednocené formátování dat pro balíček `@webyashopy/ticketing-system-ui`.
 *
 * host-agnostická utilita pro formátování dat.
 * Modul Tickets používá jen `formatDateTime`, balíček si nese vlastní kopii
 * bez závislosti na host aplikaci. Implementace bez externích knihoven
 * (`Intl.DateTimeFormat` + `Intl.RelativeTimeFormat`).
 *
 * Formáty:
 *  - `formatDate`      → krátký     DD. MM. YYYY      (např. „12. 05. 2026")
 *  - `formatDateTime`  → krátký+čas DD. MM. YYYY HH:mm
 *  - `formatDateLong`  → dlouhý     12. května 2026
 *  - `formatTime`      → pouze čas  HH:mm
 *  - `formatRelative`  → relativní  „před 3 dny", „za 2 hodiny"
 *
 * Všechny funkce přijímají `string | Date | number | null | undefined`
 * a vracejí `opts.fallback ?? '—'` pro null/invalid hodnoty.
 */

const CZ_LOCALE = 'cs-CZ';

export type DateInput = string | Date | number | null | undefined;

export type FormatOptions = {
    /** Co vrátit pro null/undefined/neplatnou hodnotu. Default `'—'`. */
    fallback?: string;
};

const DEFAULT_FALLBACK = '—';

/** Bezpečná konverze libovolného vstupu na Date nebo null. */
function toDate(value: DateInput): Date | null {
    if (value === null || value === undefined || value === '') {
        return null;
    }
    const d = value instanceof Date ? value : new Date(value);
    return Number.isNaN(d.getTime()) ? null : d;
}

/**
 * Krátký český formát: `DD. MM. YYYY` (např. „12. 05. 2026").
 */
export function formatDate(value: DateInput, opts: FormatOptions = {}): string {
    const d = toDate(value);
    if (!d) return opts.fallback ?? DEFAULT_FALLBACK;

    return new Intl.DateTimeFormat(CZ_LOCALE, {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    }).format(d);
}

/**
 * Datum + čas: `DD. MM. YYYY HH:mm` (např. „12. 05. 2026 14:30").
 */
export function formatDateTime(value: DateInput, opts: FormatOptions = {}): string {
    const d = toDate(value);
    if (!d) return opts.fallback ?? DEFAULT_FALLBACK;

    return new Intl.DateTimeFormat(CZ_LOCALE, {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(d);
}

/**
 * Dlouhý český formát: `12. května 2026`.
 * Vhodné pro nadpisy, tisk, tooltips.
 */
export function formatDateLong(value: DateInput, opts: FormatOptions = {}): string {
    const d = toDate(value);
    if (!d) return opts.fallback ?? DEFAULT_FALLBACK;

    return new Intl.DateTimeFormat(CZ_LOCALE, {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(d);
}

/**
 * Pouze čas: `HH:mm` (např. „14:30").
 */
export function formatTime(value: DateInput, opts: FormatOptions = {}): string {
    const d = toDate(value);
    if (!d) return opts.fallback ?? DEFAULT_FALLBACK;

    return new Intl.DateTimeFormat(CZ_LOCALE, {
        hour: '2-digit',
        minute: '2-digit',
    }).format(d);
}

/**
 * Relativní čas: „před 3 dny", „za 2 hodiny", „dnes".
 *
 * Vybere největší jednotku, ve které je rozdíl >= 1.
 * Vrací výsledek `Intl.RelativeTimeFormat` (locale „cs-CZ", numeric „auto").
 */
export function formatRelative(value: DateInput, opts: FormatOptions = {}): string {
    const d = toDate(value);
    if (!d) return opts.fallback ?? DEFAULT_FALLBACK;

    const diffSeconds = (d.getTime() - Date.now()) / 1000;
    const rtf = new Intl.RelativeTimeFormat(CZ_LOCALE, { numeric: 'auto' });

    const units: Array<[Intl.RelativeTimeFormatUnit, number]> = [
        ['year', 31_536_000],
        ['month', 2_592_000],
        ['week', 604_800],
        ['day', 86_400],
        ['hour', 3_600],
        ['minute', 60],
        ['second', 1],
    ];

    for (const [unit, secs] of units) {
        if (Math.abs(diffSeconds) >= secs || unit === 'second') {
            return rtf.format(Math.round(diffSeconds / secs), unit);
        }
    }

    return opts.fallback ?? DEFAULT_FALLBACK;
}
