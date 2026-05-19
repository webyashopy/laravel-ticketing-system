// Filtry nad listem ticketů (status / category / priority / search + superadmin all_orgs toggle).

import { Search } from 'lucide-react';

import type { TicketCategory, TicketPriority, TicketStatus, TicketsFilters as TicketsFiltersType } from '../types';
import {
    TICKET_CATEGORY_LABELS,
    TICKET_PRIORITY_LABELS,
    TICKET_STATUS_LABELS,
} from '../types';

interface TicketsFiltersProps {
    filters: TicketsFiltersType;
    onChange: (next: TicketsFiltersType) => void;
    canViewAllOrgs: boolean;
}

export function TicketsFilters({ filters, onChange, canViewAllOrgs }: TicketsFiltersProps) {
    return (
        <div className="card bg-base-100 shadow-sm">
            <div className="card-body p-4">
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
                    {/* Search */}
                    <div className="flex flex-col gap-1 lg:col-span-2">
                        <label className="label py-1 block">
                            <span className="font-medium text-xs">Hledat</span>
                        </label>
                        <div className="join w-full">
                            <span
                                className="join-item btn btn-square btn-sm bg-base-200 pointer-events-none"
                                aria-hidden="true"
                            >
                                <Search size={16} />
                            </span>
                            <input
                                type="text"
                                className="input input-bordered input-sm join-item w-full"
                                placeholder="Hledej v názvu nebo popisu…"
                                value={filters.search ?? ''}
                                onChange={(e) => onChange({ ...filters, search: e.target.value || undefined })}
                            />
                        </div>
                    </div>

                    {/* Status */}
                    <div className="flex flex-col gap-1">
                        <label className="label py-1 block">
                            <span className="font-medium text-xs">Stav</span>
                        </label>
                        <select
                            className="select select-bordered select-sm w-full"
                            value={filters.status ?? ''}
                            onChange={(e) =>
                                onChange({
                                    ...filters,
                                    status: (e.target.value || undefined) as TicketStatus | undefined,
                                })
                            }
                        >
                            <option value="">Vše</option>
                            {(Object.keys(TICKET_STATUS_LABELS) as TicketStatus[]).map((s) => (
                                <option key={s} value={s}>
                                    {TICKET_STATUS_LABELS[s]}
                                </option>
                            ))}
                        </select>
                    </div>

                    {/* Category */}
                    <div className="flex flex-col gap-1">
                        <label className="label py-1 block">
                            <span className="font-medium text-xs">Kategorie</span>
                        </label>
                        <select
                            className="select select-bordered select-sm w-full"
                            value={filters.category ?? ''}
                            onChange={(e) =>
                                onChange({
                                    ...filters,
                                    category: (e.target.value || undefined) as TicketCategory | undefined,
                                })
                            }
                        >
                            <option value="">Vše</option>
                            {(Object.keys(TICKET_CATEGORY_LABELS) as TicketCategory[]).map((c) => (
                                <option key={c} value={c}>
                                    {TICKET_CATEGORY_LABELS[c]}
                                </option>
                            ))}
                        </select>
                    </div>

                    {/* Priority */}
                    <div className="flex flex-col gap-1">
                        <label className="label py-1 block">
                            <span className="font-medium text-xs">Priorita</span>
                        </label>
                        <select
                            className="select select-bordered select-sm w-full"
                            value={filters.priority ?? ''}
                            onChange={(e) =>
                                onChange({
                                    ...filters,
                                    priority: (e.target.value || undefined) as TicketPriority | undefined,
                                })
                            }
                        >
                            <option value="">Vše</option>
                            {(Object.keys(TICKET_PRIORITY_LABELS) as TicketPriority[]).map((p) => (
                                <option key={p} value={p}>
                                    {TICKET_PRIORITY_LABELS[p]}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>

                {/* Superadmin toggle „Všechny organizace" */}
                {canViewAllOrgs && (
                    <div className="flex flex-col gap-1 mt-2">
                        <label className="label cursor-pointer justify-start gap-2">
                            <input
                                type="checkbox"
                                className="checkbox checkbox-sm checkbox-primary"
                                checked={Boolean(filters.all_orgs)}
                                onChange={(e) => onChange({ ...filters, all_orgs: e.target.checked || undefined })}
                            />
                            <span className="text-sm font-medium">Všechny organizace (superadmin)</span>
                        </label>
                    </div>
                )}
            </div>
        </div>
    );
}

export default TicketsFilters;
