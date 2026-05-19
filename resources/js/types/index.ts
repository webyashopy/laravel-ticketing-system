/**
 * Barel typů balíčku `@webyashopy/ticketing-system-ui`.
 *
 * Komponenty modulu importují typy odtud (`../types`) místo host `@/types/*`.
 */
export type {
    Ticket,
    TicketAttachment,
    TicketAuditEvent,
    TicketCategory,
    TicketComment,
    TicketCreateFormData,
    TicketPriority,
    TicketStatus,
    TicketsFilters,
    TicketsListProps,
    TicketsPaginatedResponse,
    OrganizationShort,
    UserShort,
} from './ticket';

export {
    TICKET_CATEGORY_BADGE_CLASS,
    TICKET_CATEGORY_LABELS,
    TICKET_PRIORITY_BADGE_CLASS,
    TICKET_PRIORITY_LABELS,
    TICKET_STATUS_BADGE_CLASS,
    TICKET_STATUS_LABELS,
} from './ticket';
