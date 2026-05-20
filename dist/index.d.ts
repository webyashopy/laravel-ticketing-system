import { ButtonHTMLAttributes } from 'react';
import { ForwardRefExoticComponent } from 'react';
import { InputHTMLAttributes } from 'react';
import { JSX } from 'react/jsx-runtime';
import { ReactNode } from 'react';
import { RefAttributes } from 'react';
import { SelectHTMLAttributes } from 'react';
import { TextareaHTMLAttributes } from 'react';

/** Zkratky pro běžné HTTP metody. */
export declare const api: {
    get: <T>(url: string, options?: ApiOptions) => Promise<T>;
    post: <T>(url: string, data?: unknown, options?: ApiOptions) => Promise<T>;
    put: <T>(url: string, data?: unknown, options?: ApiOptions) => Promise<T>;
    patch: <T>(url: string, data?: unknown, options?: ApiOptions) => Promise<T>;
    delete: <T>(url: string, options?: ApiOptions) => Promise<T>;
    upload: typeof apiUpload;
};

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
export declare class ApiError extends Error {
    readonly status: number;
    readonly payload: unknown;
    constructor(message: string, status: number, payload: unknown);
}

/**
 * Základní fetch wrapper s JSON tělem.
 *
 * Při 419 na mutující metodě obnoví CSRF cookie a request 1× zopakuje.
 * Retry je bezpečný i pro POST — middleware request odmítne PŘED vstupem
 * do controlleru, takže DB zápis při prvním pokusu neproběhl.
 */
export declare function apiFetch<T>(url: string, options?: RequestInit): Promise<T>;

/**
 * Options pro `api.*` zkratky. Záměrně úzké — odhalujeme jen `signal`
 * pro `AbortController`, ne celý `RequestInit`.
 */
export declare interface ApiOptions {
    signal?: AbortSignal;
}

/**
 * Upload s FormData (pro přílohy / screenshoty).
 *
 * `Content-Type` se NEnastavuje — prohlížeč ho doplní i s `boundary`.
 * FormData instance se dá pro retry použít znovu (fetch ji čte synchronně).
 */
export declare function apiUpload<T>(url: string, formData: FormData): Promise<T>;

export declare const Button: ForwardRefExoticComponent<ButtonProps & RefAttributes<HTMLButtonElement>>;

export declare interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: ButtonVariant;
    size?: ButtonSize;
    fullWidth?: boolean;
    rounded?: boolean;
    loading?: boolean;
    leftSection?: ReactNode;
    rightSection?: ReactNode;
}

export declare type ButtonSize = 'default' | 'sm' | 'xs' | 'lg' | 'icon';

/**
 * DaisyUI v5 button primitiv balíčku.
 *
 * Vlastní kopie — NEimportuje z host `@/components/ui/*` a záměrně
 * NEpoužívá `class-variance-authority` (žádná extra závislost). Varianty
 * řeší prosté lookup mapy. 100% DaisyUI sémantické třídy.
 */
export declare type ButtonVariant = 'default' | 'secondary' | 'destructive' | 'outline' | 'ghost' | 'link' | 'subtle';

export declare const Checkbox: ForwardRefExoticComponent<CheckboxProps & RefAttributes<HTMLInputElement>>;

/**
 * DaisyUI v5 checkbox primitiv balíčku.
 *
 * Vlastní kopie — NEimportuje z host `@/components/ui/*`. 100% DaisyUI
 * sémantické třídy. Label je svázaný s inputem přes `htmlFor`.
 */
export declare interface CheckboxProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> {
    label?: string;
    description?: string;
    error?: string;
    indeterminate?: boolean;
}

/**
 * Minimální slučovač CSS tříd.
 *
 * Balíček záměrně NEpoužívá `clsx` ani `tailwind-merge` z host aplikace —
 * pro spojení DaisyUI tříd ve ui-primitivech stačí filtrovat falsy hodnoty
 * a poskládat řetězec. Žádná deduplikace Tailwind tříd není potřeba:
 * primitivy negenerují kolidující utility a host je nepřebíjí.
 */
export declare type ClassValue = string | number | bigint | null | false | undefined | ClassValue[];

export declare function cn(...inputs: ClassValue[]): string;

/** Složí CSRF hlavičky. Když cookie chybí, hlavičku prostě vynechá. */
export declare function csrfHeaders(): Record<string, string>;

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
export declare type DateInput = string | Date | number | null | undefined;

/**
 * Obecná komponenta pro upload dokumentů s drag & drop podporou.
 * Funguje proti libovolnému endpointu, který přijímá multipart/form-data
 * s polem `file` (a volitelně `title`, `description`, `run_ocr`).
 *
 * UX rozhodnutí:
 * - Single mode (1 soubor) → shared inputy nahoře, auto-upload při dropu
 * - Multi mode (2+ souborů) → shared inputy se SKRYJÍ, každý soubor má
 *   vlastní per-file form (Název + Popis), upload se spouští kliknutím
 *   na tlačítko „Nahrát"
 */
export declare function DocumentDropZone({ endpoint, mode, onFilesChange, onUploaded, onUploadSuccess, allowedMimes, maxSize, maxFiles, showOcrCheckbox, showMetadataInputs, className, initialFiles, acceptedTypesLabel, }: DocumentDropZoneProps): JSX.Element;

export declare interface DocumentDropZoneProps {
    /**
     * Plný API endpoint pro upload (např. /api/cases/{uuid}/documents).
     * V `mode='staged'` je nepovinný — upload provádí rodičovský formulář.
     */
    endpoint?: string;
    /**
     * Režim uploadu.
     * - `immediate` (default) — soubory se uploadují okamžitě po dropu na `endpoint`.
     * - `staged` — žádný upload, jen se drží v lokálním stavu a předávají rodiči
     *   přes `onFilesChange`. Použití pro modaly, kde se submituje jako celý form.
     */
    mode?: 'immediate' | 'staged';
    /** Callback v `staged` režimu — vrací aktuální seznam staged File objektů. */
    onFilesChange?: (files: File[]) => void;
    /** Callback po úspěšném uploadu (přijde po každém souboru) — jen `immediate` mode */
    onUploaded?: () => void;
    /**
     * Callback po úspěšném uploadu, který dostane parsovanou
     * JSON odpověď z `endpoint`. Volaný kromě `onUploaded` — pokud chceš
     * jen prostou notifikaci, použij `onUploaded`. Pokud potřebuješ z odpovědi
     * vyčíst např. `document_uuid` pro polling, použij `onUploadSuccess`.
     */
    onUploadSuccess?: (response: unknown, file: File) => void;
    /** Povolené MIME typy - default: PDF, JPG, PNG, GIF, WEBP, DOC, DOCX, XLS, XLSX, TXT, RTF */
    allowedMimes?: string[];
    /** Maximální velikost souboru v bytech - default 20 MB */
    maxSize?: number;
    /** Maximální počet souborů — pokud je nastaven, další se odmítnou s toastem. */
    maxFiles?: number;
    /** Zobrazit checkbox "Spustit OCR" - default true */
    showOcrCheckbox?: boolean;
    /** Zobrazit pole pro Název + Popis - default true */
    showMetadataInputs?: boolean;
    /** Doplňková CSS třída na vnější element */
    className?: string;
    /**
     * Custom popisek povolených typů pro footer pod
     * dropzonou (např. „PDF, JPG, PNG"). Pokud není zadáno, použije se
     * generický fallback „PDF, obrázky, Word, Excel, TXT".
     * Doporučeno: vždy předat, pokud `allowedMimes` zužuješ pro daný kontext —
     * jinak text v UI lže.
     */
    acceptedTypesLabel?: string;
    /**
     * Počáteční staged soubory — použito např. pro screenshot
     * předvyplnění z TicketCreateModal. Aplikuje se jen v `staged` režimu
     * a jen při mountu (parent musí změnit `key` pro remount, pokud chce
     * resetovat).
     */
    initialFiles?: File[];
}

/**
 * Extrahuje user-friendly chybovou zprávu z Laravel error response.
 *
 * Pořadí:
 * 1. `payload.errors[firstField][0]` (Laravel 422 validation) → první hláška.
 * 2. `payload.message` (Laravel default).
 * 3. Fallback `HTTP {status}`.
 */
export declare function extractErrorMessage(payload: unknown, status: number): string;

/**
 * Krátký český formát: `DD. MM. YYYY` (např. „12. 05. 2026").
 */
export declare function formatDate(value: DateInput, opts?: FormatOptions): string;

/**
 * Dlouhý český formát: `12. května 2026`.
 * Vhodné pro nadpisy, tisk, tooltips.
 */
export declare function formatDateLong(value: DateInput, opts?: FormatOptions): string;

/**
 * Datum + čas: `DD. MM. YYYY HH:mm` (např. „12. 05. 2026 14:30").
 */
export declare function formatDateTime(value: DateInput, opts?: FormatOptions): string;

export declare type FormatOptions = {
    /** Co vrátit pro null/undefined/neplatnou hodnotu. Default `'—'`. */
    fallback?: string;
};

/**
 * Relativní čas: „před 3 dny", „za 2 hodiny", „dnes".
 *
 * Vybere největší jednotku, ve které je rozdíl >= 1.
 * Vrací výsledek `Intl.RelativeTimeFormat` (locale „cs-CZ", numeric „auto").
 */
export declare function formatRelative(value: DateInput, opts?: FormatOptions): string;

/**
 * Pouze čas: `HH:mm` (např. „14:30").
 */
export declare function formatTime(value: DateInput, opts?: FormatOptions): string;

/**
 * Přečte `XSRF-TOKEN` z cookie. Laravel ji aktualizuje v každé response;
 * middleware `ValidateCsrfToken` přijímá hodnotu v hlavičce `X-XSRF-TOKEN`
 * a sám si ji dekryptuje.
 */
export declare function getXsrfToken(): string;

export declare const Input: ForwardRefExoticComponent<InputProps & RefAttributes<HTMLInputElement>>;

/**
 * DaisyUI v5 input primitiv balíčku.
 *
 * Vlastní kopie — NEimportuje z host `@/components/ui/*`. Používá výhradně
 * DaisyUI sémantické třídy (`input`, `input-error`, `text-error`, …),
 * takže barvy čte runtime z CSS proměnných host aplikace (BrandingProvider).
 */
export declare interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
    label?: string;
    description?: string;
    error?: string;
    withAsterisk?: boolean;
    leftSection?: ReactNode;
    rightSection?: ReactNode;
}

/** Pomůcka — zda jde o `AbortError` (request byl zrušen). */
export declare function isAbortError(err: unknown): boolean;

export declare interface OrganizationShort {
    id: number;
    name: string;
}

export declare function refreshCsrfCookie(): Promise<void>;

export declare function ScreenshotPicker({ onCapture, onCancel, maxSize }: ScreenshotPickerProps): JSX.Element;

declare interface ScreenshotPickerProps {
    onCapture: (file: File) => void;
    onCancel: () => void;
    /** Maximální velikost výsledného PNG v bytech (default neomezeno). */
    maxSize?: number;
}

export declare const Select: ForwardRefExoticComponent<SelectProps & RefAttributes<HTMLSelectElement>>;

/**
 * DaisyUI v5 select primitiv balíčku.
 *
 * Vlastní kopie — NEimportuje z host `@/components/ui/*`. 100% DaisyUI
 * sémantické třídy.
 */
export declare interface SelectOption {
    value: string;
    label: string;
    disabled?: boolean;
}

export declare interface SelectProps extends Omit<SelectHTMLAttributes<HTMLSelectElement>, 'data'> {
    label?: string;
    description?: string;
    error?: string;
    withAsterisk?: boolean;
    data: (string | SelectOption)[];
    placeholder?: string;
}

export declare const Textarea: ForwardRefExoticComponent<TextareaProps & RefAttributes<HTMLTextAreaElement>>;

/**
 * DaisyUI v5 textarea primitiv balíčku.
 *
 * Vlastní kopie — NEimportuje z host `@/components/ui/*`. 100% DaisyUI
 * sémantické třídy.
 */
export declare interface TextareaProps extends TextareaHTMLAttributes<HTMLTextAreaElement> {
    label?: string;
    description?: string;
    error?: string;
    withAsterisk?: boolean;
    minRows?: number;
    autosize?: boolean;
}

export declare const TextInput: ForwardRefExoticComponent<InputProps & RefAttributes<HTMLInputElement>>;

export declare interface Ticket {
    uuid: string;
    title: string;
    description: string;
    category: TicketCategory;
    priority: TicketPriority;
    status: TicketStatus;
    page_url: string | null;
    viewport: string | null;
    user_agent: string | null;
    organization_id: number;
    user_id: number;
    creator: UserShort;
    organization: OrganizationShort;
    attachments: TicketAttachment[];
    comments?: TicketComment[];
    audit_log?: TicketAuditEvent[];
    can?: {
        update: boolean;
    };
    closed_at: string | null;
    closed_by_user_id: number | null;
    created_at: string;
    updated_at: string;
}

export declare const TICKET_CATEGORY_BADGE_CLASS: Record<TicketCategory, string>;

export declare const TICKET_CATEGORY_LABELS: Record<TicketCategory, string>;

export declare const TICKET_PRIORITY_BADGE_CLASS: Record<TicketPriority, string>;

export declare const TICKET_PRIORITY_LABELS: Record<TicketPriority, string>;

export declare const TICKET_STATUS_BADGE_CLASS: Record<TicketStatus, string>;

export declare const TICKET_STATUS_LABELS: Record<TicketStatus, string>;

export declare interface TicketAttachment {
    uuid: string;
    filename: string;
    mime_type: string;
    size_bytes: number;
    signed_url: string;
}

export declare interface TicketAuditEvent {
    field: 'title' | 'description' | 'category' | 'priority' | 'status' | 'attachment_added' | 'attachment_removed' | 'comment_added' | 'comment_deleted';
    old_value: string | null;
    new_value: string | null;
    user: UserShort | null;
    created_at: string;
}

export declare function TicketAuditTimeline({ events }: TicketAuditTimelineProps): JSX.Element | null;

declare interface TicketAuditTimelineProps {
    events: TicketAuditEvent[];
}

export declare type TicketCategory = 'bug' | 'feature' | 'question' | 'other';

export declare interface TicketComment {
    uuid: string;
    body: string;
    body_html: string;
    created_at: string;
    updated_at: string;
    author: UserShort | null;
    can_edit: boolean;
    can_delete: boolean;
}

export declare function TicketCommentComposer({ ticketUuid }: TicketCommentComposerProps): JSX.Element;

declare interface TicketCommentComposerProps {
    ticketUuid: string;
}

export declare function TicketComments({ comments }: TicketCommentsProps): JSX.Element;

declare interface TicketCommentsProps {
    comments: TicketComment[];
}

export declare interface TicketCreateFormData {
    title: string;
    description: string;
    category: TicketCategory;
    priority: TicketPriority;
    page_url: string;
    viewport: string;
    user_agent: string;
    attachments: File[];
}

export declare function TicketCreateModal({ open, onClose, onCreated }: TicketCreateModalProps): JSX.Element | null;

declare interface TicketCreateModalProps {
    open: boolean;
    onClose: () => void;
    onCreated?: () => void;
}

export declare function TicketDetail({ ticket }: TicketDetailProps): JSX.Element;

/**
 * Čistá obsahová komponenta detailu ticketu.
 *
 * Pozn.: host appka komponentu obaluje vlastním layoutem — proto zde NENÍ
 * `AppLayout` ani breadcrumbs. `<Head>` a `<Link>` z Inertie ponechány
 * (peer-dependency, host-agnostické).
 */
export declare function TicketDetailPage({ ticket }: TicketDetailPageProps): JSX.Element;

declare interface TicketDetailPageProps {
    ticket: Ticket;
}

declare interface TicketDetailProps {
    ticket: Ticket;
}

export declare type TicketPriority = 'low' | 'medium' | 'high' | 'urgent';

export declare function TicketRow({ ticket }: TicketRowProps): JSX.Element;

declare interface TicketRowProps {
    ticket: Ticket;
}

export declare function TicketScreenshotLightbox({ attachment, onClose }: TicketScreenshotLightboxProps): JSX.Element | null;

declare interface TicketScreenshotLightboxProps {
    attachment: TicketAttachment | null;
    onClose: () => void;
}

export declare function TicketsFab(): JSX.Element | null;

export declare function TicketsFilters({ filters, onChange, canViewAllOrgs }: TicketsFiltersProps): JSX.Element;

declare interface TicketsFiltersProps {
    filters: TicketsFiltersType;
    onChange: (next: TicketsFiltersType) => void;
    canViewAllOrgs: boolean;
}

export declare interface TicketsFiltersType {
    status?: TicketStatus;
    category?: TicketCategory;
    priority?: TicketPriority;
    search?: string;
    all_orgs?: boolean;
}

/**
 * Čistá obsahová komponenta stránky se seznamem ticketů.
 *
 * Textové stavy:
 * - Empty: "Žádné tickety neodpovídají filtrům."
 * - Loading: standardní Inertia preserveState
 * - Success create: toast „Ticket byl úspěšně vytvořen"
 *
 * Pozn.: host appka komponentu obaluje vlastním layoutem — proto zde NENÍ
 * `AppLayout` ani breadcrumbs. `<Head>` z Inertie ponechán (peer-dependency,
 * host-agnostický), host si může title přepsat vlastním `<Head>`.
 */
export declare function TicketsIndexPage({ tickets, filters: initialFilters, can }: TicketsListProps): JSX.Element;

export declare function TicketsList({ tickets }: TicketsListProps_2): JSX.Element;

export declare interface TicketsListProps {
    tickets: TicketsPaginatedResponse;
    filters: TicketsFiltersType;
    can: {
        viewAllOrgs: boolean;
    };
}

declare interface TicketsListProps_2 {
    tickets: Ticket[];
}

export declare interface TicketsPaginatedResponse {
    data: Ticket[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
}

export declare type TicketStatus = 'open' | 'closed';

export declare interface UserShort {
    id: number;
    name: string;
    email: string;
}

export { }
