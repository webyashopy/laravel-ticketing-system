/**
 * Veřejné API balíčku `@webyashopy/ticketing-system-ui`.
 *
 * Build entry pro Vite library mode (viz `vite.config.ts`).
 * Obsahuje UI primitivy, API klienta, stránky, komponenty a typy
 * modulu Tickets.
 */

// UI primitivy (DaisyUI v5).
export {
    Button,
    Checkbox,
    Input,
    TextInput,
    Select,
    Textarea,
} from './ui';
export type {
    ButtonProps,
    ButtonVariant,
    ButtonSize,
    CheckboxProps,
    InputProps,
    SelectProps,
    SelectOption,
    TextareaProps,
} from './ui';

// API klient (Sanctum cookie-first CSRF).
export {
    api,
    apiFetch,
    apiUpload,
    ApiError,
    csrfHeaders,
    getXsrfToken,
    refreshCsrfCookie,
    extractErrorMessage,
    isAbortError,
} from './lib/api';
export type { ApiOptions } from './lib/api';

// Utilita pro skládání tříd.
export { cn } from './lib/cn';
export type { ClassValue } from './lib/cn';

// Formátování dat (host-agnostická kopie).
export {
    formatDate,
    formatDateTime,
    formatDateLong,
    formatTime,
    formatRelative,
} from './lib/format-date';
export type { DateInput, FormatOptions } from './lib/format-date';

// ── Tickets modul ──────────────────────────────────────────

// Stránky — čisté exportovatelné komponenty BEZ host layoutu.
export { TicketsIndexPage, TicketDetailPage } from './pages';

// Komponenty modulu Tickets.
export {
    TicketsFab,
    TicketCreateModal,
    TicketsList,
    TicketRow,
    TicketDetail,
    TicketScreenshotLightbox,
    TicketsFilters,
    ScreenshotPicker,
    TicketComments,
    TicketCommentComposer,
    TicketAuditTimeline,
    DocumentDropZone,
} from './components';
export type { DocumentDropZoneProps } from './components';

// Typy a lokalizační/badge konstanty modulu Tickets.
export type {
    Ticket,
    TicketAttachment,
    TicketAuditEvent,
    TicketCategory,
    TicketComment,
    TicketCreateFormData,
    TicketPriority,
    TicketStatus,
    TicketsFilters as TicketsFiltersType,
    TicketsListProps,
    TicketsPaginatedResponse,
    OrganizationShort,
    UserShort,
} from './types';
export {
    TICKET_CATEGORY_BADGE_CLASS,
    TICKET_CATEGORY_LABELS,
    TICKET_PRIORITY_BADGE_CLASS,
    TICKET_PRIORITY_LABELS,
    TICKET_STATUS_BADGE_CLASS,
    TICKET_STATUS_LABELS,
} from './types';
