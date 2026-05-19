// Jeden řádek v tabulce ticketů.

import { router } from '@inertiajs/react';

import { formatDateTime } from '../lib/format-date';
import type { Ticket } from '../types';
import {
    TICKET_CATEGORY_BADGE_CLASS,
    TICKET_CATEGORY_LABELS,
    TICKET_PRIORITY_BADGE_CLASS,
    TICKET_PRIORITY_LABELS,
    TICKET_STATUS_BADGE_CLASS,
    TICKET_STATUS_LABELS,
} from '../types';

interface TicketRowProps {
    ticket: Ticket;
}

// Sjednoceno přes balíčkový lib/format-date.
const formatDate = (iso: string) => formatDateTime(iso);

export function TicketRow({ ticket }: TicketRowProps) {
    const handleClick = () => {
        router.visit(`/tickets/${ticket.uuid}`);
    };

    return (
        <tr className="hover:bg-base-200 cursor-pointer" onClick={handleClick}>
            <td>
                <span className={`badge ${TICKET_STATUS_BADGE_CLASS[ticket.status]} badge-sm`}>
                    {TICKET_STATUS_LABELS[ticket.status]}
                </span>
            </td>
            <td>
                <span className={`badge ${TICKET_CATEGORY_BADGE_CLASS[ticket.category]} badge-sm`}>
                    {TICKET_CATEGORY_LABELS[ticket.category]}
                </span>
            </td>
            <td>
                <span className={`badge ${TICKET_PRIORITY_BADGE_CLASS[ticket.priority]} badge-sm`}>
                    {TICKET_PRIORITY_LABELS[ticket.priority]}
                </span>
            </td>
            <td className="font-medium">{ticket.title}</td>
            <td className="text-sm text-base-content/70">{ticket.creator?.name ?? '—'}</td>
            <td className="text-sm text-base-content/70">{formatDate(ticket.created_at)}</td>
            <td className="text-sm text-base-content/70">
                {ticket.attachments?.length > 0 ? `${ticket.attachments.length}× příloha` : '—'}
            </td>
        </tr>
    );
}

export default TicketRow;
