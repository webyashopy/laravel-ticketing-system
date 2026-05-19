// Stránka /tickets — list ticketů s filtry.
// čistá exportovatelná komponenta `TicketsIndexPage` BEZ host
// `AppLayout`. Layout (sidebar, navbar, breadcrumbs) aplikuje host aplikace
// kolem této komponenty ve svém wrapperu.

import { Head, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';

import { TicketCreateModal } from '../components/TicketCreateModal';
import { TicketsFilters } from '../components/TicketsFilters';
import { TicketsList } from '../components/TicketsList';
import type { TicketsFilters as TicketsFiltersType, TicketsListProps } from '../types';

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
export function TicketsIndexPage({ tickets, filters: initialFilters, can }: TicketsListProps) {
    const [filters, setFilters] = useState<TicketsFiltersType>(initialFilters);
    const [createOpen, setCreateOpen] = useState(false);
    const debounceRef = useRef<number | null>(null);

    // Aplikuj filtry — debounce search aby se nevolal Inertia request při každém keypressu
    const applyFilters = useCallback((next: TicketsFiltersType) => {
        const params = new URLSearchParams();
        if (next.search) params.append('search', next.search);
        if (next.status) params.append('status', next.status);
        if (next.category) params.append('category', next.category);
        if (next.priority) params.append('priority', next.priority);
        if (next.all_orgs) params.append('all_orgs', '1');

        router.get(`/tickets?${params.toString()}`, {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, []);

    // Reaktivní filtry — debounce 300ms (čeká až user dopíše)
    useEffect(() => {
        if (debounceRef.current) {
            window.clearTimeout(debounceRef.current);
        }
        debounceRef.current = window.setTimeout(() => {
            applyFilters(filters);
        }, 300);

        return () => {
            if (debounceRef.current) {
                window.clearTimeout(debounceRef.current);
            }
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [filters]);

    return (
        <>
            <Head title="Tickety" />

            <div className="space-y-4 p-4 md:p-6">
                <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-bold">Tickety</h1>
                        <p className="text-sm text-base-content/60">
                            Interní bug-tracker — hlášení chyb a požadavků na vylepšení.
                        </p>
                    </div>
                    <button
                        type="button"
                        className="btn btn-primary"
                        onClick={() => setCreateOpen(true)}
                    >
                        <Plus size={18} />
                        Nový ticket
                    </button>
                </div>

                <TicketsFilters
                    filters={filters}
                    onChange={setFilters}
                    canViewAllOrgs={can?.viewAllOrgs ?? false}
                />

                <div className="card bg-base-100 shadow-sm">
                    <div className="card-body p-0">
                        <TicketsList tickets={tickets.data} />
                    </div>
                </div>

                {/* Stránkování */}
                {tickets.last_page > 1 && (
                    <div className="flex justify-center mt-4">
                        <div className="join">
                            {Array.from({ length: tickets.last_page }, (_, i) => i + 1).map((page) => {
                                const params = new URLSearchParams();
                                if (filters.search) params.append('search', filters.search);
                                if (filters.status) params.append('status', filters.status);
                                if (filters.category) params.append('category', filters.category);
                                if (filters.priority) params.append('priority', filters.priority);
                                if (filters.all_orgs) params.append('all_orgs', '1');
                                params.append('page', String(page));
                                return (
                                    <button
                                        key={page}
                                        type="button"
                                        className={`join-item btn btn-sm ${page === tickets.current_page ? 'btn-active' : ''}`}
                                        onClick={() =>
                                            router.get(`/tickets?${params.toString()}`, {}, {
                                                preserveState: true,
                                                preserveScroll: true,
                                            })
                                        }
                                    >
                                        {page}
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                )}
            </div>

            <TicketCreateModal
                open={createOpen}
                onClose={() => setCreateOpen(false)}
                onCreated={() => router.reload({ only: ['tickets', 'ticketsOpenCount'] })}
            />
        </>
    );
}

export default TicketsIndexPage;
