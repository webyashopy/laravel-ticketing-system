/**
 * API klient balíčku `@webyashopy/ticketing-system-ui`.
 *
 * Vlastní implementace BEZ závislosti na host `@/lib/api`. Řeší:
 * - CSRF token primárně z `XSRF-TOKEN` cookie (Laravel ji rotuje s každou
 *   response — token nezestárne ani u dlouho otevřené stránky).
 * - Při HTTP 419 (CSRF mismatch) zavolá `GET /sanctum/csrf-cookie`
 *   a request 1× zopakuje.
 * - `credentials: include` — kvůli Sanctum SPA patternu.
 * - JSON hlavičky a jednotné error handling přes `ApiError`.
 *
 * Pozn.: balíček úmyslně neimplementuje meta-tag fallback ani redirect
 * na `/login` — to je věc host aplikace (ticketing modul je vždy uvnitř
 * autentizované plochy). Druhý 419 vyhodí `ApiError`, host si zvolí reakci.
 */

/**
 * Chyba z API — nese HTTP status a parsovaný JSON payload.
 * Zachovává `message` pro běžné catch-bloky čtoucí `error.message`.
 */
export class ApiError extends Error {
    public readonly status: number;
    public readonly payload: unknown;

    constructor(message: string, status: number, payload: unknown) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.payload = payload;
    }
}

/**
 * Přečte `XSRF-TOKEN` z cookie. Laravel ji aktualizuje v každé response;
 * middleware `ValidateCsrfToken` přijímá hodnotu v hlavičce `X-XSRF-TOKEN`
 * a sám si ji dekryptuje.
 */
export function getXsrfToken(): string {
    if (typeof document === 'undefined') return '';

    const match = document.cookie
        .split('; ')
        .find((row) => row.startsWith('XSRF-TOKEN='));

    if (!match) return '';

    return decodeURIComponent(match.split('=')[1] ?? '');
}

/** Složí CSRF hlavičky. Když cookie chybí, hlavičku prostě vynechá. */
export function csrfHeaders(): Record<string, string> {
    const xsrf = getXsrfToken();
    return xsrf ? { 'X-XSRF-TOKEN': xsrf } : {};
}

/**
 * Vynutí refresh `XSRF-TOKEN` cookie přes Sanctum endpoint.
 * Paralelní volání sdílí jeden in-flight promise (žádné zbytečné requesty).
 * Síťovou chybu tiše spolkneme — retry pak stejně selže na 419.
 */
let csrfRefreshInFlight: Promise<void> | null = null;

export function refreshCsrfCookie(): Promise<void> {
    if (csrfRefreshInFlight) return csrfRefreshInFlight;

    csrfRefreshInFlight = fetch('/sanctum/csrf-cookie', {
        method: 'GET',
        credentials: 'include',
        headers: { Accept: 'application/json' },
    })
        .then(() => undefined)
        .catch(() => undefined)
        .finally(() => {
            csrfRefreshInFlight = null;
        });

    return csrfRefreshInFlight;
}

/** Jedno kolo fetche s doplněnými CSRF hlavičkami. */
async function doFetch(
    url: string,
    init: RequestInit,
    extraHeaders: Record<string, string>,
): Promise<Response> {
    return fetch(url, {
        ...init,
        headers: {
            ...extraHeaders,
            ...csrfHeaders(),
            ...(init.headers as Record<string, string> | undefined),
        },
        credentials: 'include',
    });
}

// Metody, které CSRF token reálně vyžadují.
const MUTATING_METHODS = new Set(['POST', 'PUT', 'PATCH', 'DELETE']);

function isMutating(method: string | undefined): boolean {
    return MUTATING_METHODS.has((method ?? 'GET').toUpperCase());
}

/**
 * Extrahuje user-friendly chybovou zprávu z Laravel error response.
 *
 * Pořadí:
 * 1. `payload.errors[firstField][0]` (Laravel 422 validation) → první hláška.
 * 2. `payload.message` (Laravel default).
 * 3. Fallback `HTTP {status}`.
 */
export function extractErrorMessage(payload: unknown, status: number): string {
    if (payload && typeof payload === 'object') {
        const obj = payload as Record<string, unknown>;

        const errors = obj.errors;
        if (errors && typeof errors === 'object') {
            const errorsObj = errors as Record<string, unknown>;
            const firstField = Object.keys(errorsObj)[0];
            if (firstField) {
                const fieldErrors = errorsObj[firstField];
                if (Array.isArray(fieldErrors) && fieldErrors.length > 0) {
                    return String(fieldErrors[0]);
                }
            }
        }

        if (typeof obj.message === 'string' && obj.message) {
            return obj.message;
        }
    }

    return `HTTP ${status}`;
}

/** Pomůcka — zda jde o `AbortError` (request byl zrušen). */
export function isAbortError(err: unknown): boolean {
    return err instanceof DOMException && err.name === 'AbortError';
}

/**
 * Základní fetch wrapper s JSON tělem.
 *
 * Při 419 na mutující metodě obnoví CSRF cookie a request 1× zopakuje.
 * Retry je bezpečný i pro POST — middleware request odmítne PŘED vstupem
 * do controlleru, takže DB zápis při prvním pokusu neproběhl.
 */
export async function apiFetch<T>(
    url: string,
    options: RequestInit = {},
): Promise<T> {
    const defaultHeaders = {
        'Content-Type': 'application/json',
        Accept: 'application/json',
    };

    let response = await doFetch(url, options, defaultHeaders);

    if (response.status === 419 && isMutating(options.method)) {
        await refreshCsrfCookie();
        response = await doFetch(url, options, defaultHeaders);
    }

    if (!response.ok) {
        const error = await response.json().catch(() => ({}));
        throw new ApiError(
            extractErrorMessage(error, response.status),
            response.status,
            error,
        );
    }

    // 204 No Content → prázdný objekt.
    if (response.status === 204) {
        return {} as T;
    }

    return response.json();
}

/**
 * Upload s FormData (pro přílohy / screenshoty).
 *
 * `Content-Type` se NEnastavuje — prohlížeč ho doplní i s `boundary`.
 * FormData instance se dá pro retry použít znovu (fetch ji čte synchronně).
 */
export async function apiUpload<T>(url: string, formData: FormData): Promise<T> {
    const baseInit: RequestInit = {
        method: 'POST',
        body: formData,
    };
    const acceptHeader = { Accept: 'application/json' };

    let response = await doFetch(url, baseInit, acceptHeader);

    if (response.status === 419) {
        await refreshCsrfCookie();
        response = await doFetch(url, baseInit, acceptHeader);
    }

    if (!response.ok) {
        const error = await response.json().catch(() => ({}));
        throw new ApiError(
            extractErrorMessage(error, response.status),
            response.status,
            error,
        );
    }

    if (response.status === 204) {
        return {} as T;
    }

    return response.json();
}

/**
 * Options pro `api.*` zkratky. Záměrně úzké — odhalujeme jen `signal`
 * pro `AbortController`, ne celý `RequestInit`.
 */
export interface ApiOptions {
    signal?: AbortSignal;
}

/** Zkratky pro běžné HTTP metody. */
export const api = {
    get: <T>(url: string, options?: ApiOptions) =>
        apiFetch<T>(url, { signal: options?.signal }),

    post: <T>(url: string, data?: unknown, options?: ApiOptions) =>
        apiFetch<T>(url, {
            method: 'POST',
            body: data ? JSON.stringify(data) : undefined,
            signal: options?.signal,
        }),

    put: <T>(url: string, data?: unknown, options?: ApiOptions) =>
        apiFetch<T>(url, {
            method: 'PUT',
            body: data ? JSON.stringify(data) : undefined,
            signal: options?.signal,
        }),

    patch: <T>(url: string, data?: unknown, options?: ApiOptions) =>
        apiFetch<T>(url, {
            method: 'PATCH',
            body: data ? JSON.stringify(data) : undefined,
            signal: options?.signal,
        }),

    delete: <T>(url: string, options?: ApiOptions) =>
        apiFetch<T>(url, {
            method: 'DELETE',
            signal: options?.signal,
        }),

    upload: apiUpload,
};
