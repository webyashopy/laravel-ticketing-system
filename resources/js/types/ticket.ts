// Typy pro modul Tickets (interní bug-tracker).
// přesun do balíčku `@webyashopy/ticketing-system-ui`.

export type TicketStatus = 'open' | 'closed';
export type TicketCategory = 'bug' | 'feature' | 'question' | 'other';
export type TicketPriority = 'low' | 'medium' | 'high' | 'urgent';

export interface TicketAttachment {
    uuid: string;
    filename: string;
    mime_type: string;
    size_bytes: number;
    signed_url: string;
}

export interface UserShort {
    id: number;
    name: string;
    email: string;
}

export interface OrganizationShort {
    id: number;
    name: string;
}

// lineární komentář (Asana/GitHub styl)
export interface TicketComment {
    uuid: string;
    body: string; // raw Markdown (pro edit)
    body_html: string; // pre-renderovaný HTML (sanitized na BE)
    created_at: string;
    updated_at: string;
    author: UserShort | null;
    can_edit: boolean; // autor && do 5 min od created_at
    can_delete: boolean; // autor nebo superadmin
}

// audit log event v timeline
export interface TicketAuditEvent {
    field:
        | 'title'
        | 'description'
        | 'category'
        | 'priority'
        | 'status'
        | 'attachment_added'
        | 'attachment_removed'
        | 'comment_added'
        | 'comment_deleted';
    old_value: string | null;
    new_value: string | null;
    user: UserShort | null;
    created_at: string;
}

export interface Ticket {
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
    // volitelné props v detailu (show endpoint)
    comments?: TicketComment[];
    audit_log?: TicketAuditEvent[];
    can?: { update: boolean };
    closed_at: string | null;
    closed_by_user_id: number | null;
    created_at: string;
    updated_at: string;
}

export interface TicketsPaginatedResponse {
    data: Ticket[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
}

export interface TicketsFilters {
    status?: TicketStatus;
    category?: TicketCategory;
    priority?: TicketPriority;
    search?: string;
    all_orgs?: boolean;
}

export interface TicketsListProps {
    tickets: TicketsPaginatedResponse;
    filters: TicketsFilters;
    can: { viewAllOrgs: boolean };
}

export interface TicketCreateFormData {
    title: string;
    description: string;
    category: TicketCategory;
    priority: TicketPriority;
    page_url: string;
    viewport: string;
    user_agent: string;
    attachments: File[];
}

// Lokalizační mapy pro UI

export const TICKET_STATUS_LABELS: Record<TicketStatus, string> = {
    open: 'Otevřený',
    closed: 'Zavřený',
};

export const TICKET_CATEGORY_LABELS: Record<TicketCategory, string> = {
    bug: 'Chyba',
    feature: 'Návrh',
    question: 'Dotaz',
    other: 'Jiné',
};

export const TICKET_PRIORITY_LABELS: Record<TicketPriority, string> = {
    low: 'Nízká',
    medium: 'Střední',
    high: 'Vysoká',
    urgent: 'Urgentní',
};

// DaisyUI badge třídy pro stavy/priority/kategorie

export const TICKET_STATUS_BADGE_CLASS: Record<TicketStatus, string> = {
    open: 'badge-success',
    closed: 'badge-ghost',
};

export const TICKET_PRIORITY_BADGE_CLASS: Record<TicketPriority, string> = {
    low: 'badge-info',
    medium: 'badge-neutral',
    high: 'badge-warning',
    urgent: 'badge-error',
};

export const TICKET_CATEGORY_BADGE_CLASS: Record<TicketCategory, string> = {
    bug: 'badge-error',
    feature: 'badge-primary',
    question: 'badge-info',
    other: 'badge-ghost',
};
