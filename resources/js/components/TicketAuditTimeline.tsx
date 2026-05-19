// Timeline změn ticketu (collapsed <details>).

import { formatDistanceToNow } from 'date-fns';
import { cs } from 'date-fns/locale';
import {
    FileText,
    History,
    Image as ImageIcon,
    MessageSquare,
    Trash2,
    type LucideIcon,
} from 'lucide-react';

import type { TicketAuditEvent } from '../types';

interface TicketAuditTimelineProps {
    events: TicketAuditEvent[];
}

// Lokalizační mapy pro pole + helper na ikonu
const FIELD_LABELS: Record<TicketAuditEvent['field'], string> = {
    title: 'název',
    description: 'popis',
    category: 'kategorie',
    priority: 'priorita',
    status: 'stav',
    attachment_added: 'přidána příloha',
    attachment_removed: 'smazána příloha',
    comment_added: 'přidal komentář',
    comment_deleted: 'smazal komentář',
};

function getIcon(field: TicketAuditEvent['field']): LucideIcon {
    if (field === 'comment_added' || field === 'comment_deleted') return MessageSquare;
    if (field === 'attachment_added') return ImageIcon;
    if (field === 'attachment_removed') return Trash2;
    return FileText;
}

// Formátuje hodnotu změny pro UI (truncate dlouhý text)
function formatValue(value: string | null): string {
    if (value === null) return '∅';
    return value.length > 50 ? value.substring(0, 50) + '…' : value;
}

function describeEvent(event: TicketAuditEvent): string {
    const label = FIELD_LABELS[event.field];
    if (event.field === 'comment_added' || event.field === 'comment_deleted') {
        return label;
    }
    if (event.field === 'attachment_added') {
        return `přidal přílohu „${formatValue(event.new_value)}"`;
    }
    if (event.field === 'attachment_removed') {
        return `smazal přílohu „${formatValue(event.old_value)}"`;
    }
    // Standardní pole — zobrazit změnu z X na Y
    const oldStr = formatValue(event.old_value);
    const newStr = formatValue(event.new_value);
    return `změnil ${label} z „${oldStr}" na „${newStr}"`;
}

function formatTime(iso: string): string {
    try {
        return formatDistanceToNow(new Date(iso), { addSuffix: true, locale: cs });
    } catch {
        return iso;
    }
}

export function TicketAuditTimeline({ events }: TicketAuditTimelineProps) {
    if (events.length === 0) {
        return null; // Žádný historie — neukazovat collapse
    }

    // Newest first (BE vrací oldest first dle orderBy v relation)
    const sorted = [...events].reverse();

    return (
        <details className="collapse collapse-arrow bg-base-100 border border-base-300">
            <summary className="collapse-title font-medium text-sm">
                <span className="flex items-center gap-2">
                    <History size={16} />
                    Historie změn ({events.length})
                </span>
            </summary>
            <div className="collapse-content">
                <ul className="space-y-2 text-sm">
                    {sorted.map((event, idx) => {
                        const Icon = getIcon(event.field);
                        return (
                            <li key={idx} className="flex items-start gap-2 text-base-content/70">
                                <Icon size={14} className="mt-0.5 flex-shrink-0" />
                                <div className="flex-1">
                                    <span className="font-medium">
                                        {event.user?.name ?? 'systém'}
                                    </span>{' '}
                                    {describeEvent(event)}
                                    <span className="text-xs text-base-content/40 ml-2">
                                        {formatTime(event.created_at)}
                                    </span>
                                </div>
                            </li>
                        );
                    })}
                </ul>
            </div>
        </details>
    );
}

export default TicketAuditTimeline;
