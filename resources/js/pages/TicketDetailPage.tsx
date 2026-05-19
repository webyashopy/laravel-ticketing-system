// Detail ticketu /tickets/{uuid}.
// čistá exportovatelná komponenta `TicketDetailPage` BEZ host
// `AppLayout`. Layout (sidebar, navbar, breadcrumbs) aplikuje host aplikace
// kolem této komponenty ve svém wrapperu.

import { Head, Link } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';

import { TicketDetail } from '../components/TicketDetail';
import type { Ticket } from '../types';

interface TicketDetailPageProps {
    ticket: Ticket;
}

/**
 * Čistá obsahová komponenta detailu ticketu.
 *
 * Pozn.: host appka komponentu obaluje vlastním layoutem — proto zde NENÍ
 * `AppLayout` ani breadcrumbs. `<Head>` a `<Link>` z Inertie ponechány
 * (peer-dependency, host-agnostické).
 */
export function TicketDetailPage({ ticket }: TicketDetailPageProps) {
    return (
        <>
            <Head title={`Ticket: ${ticket.title}`} />

            <div className="space-y-4 p-4 md:p-6">
                <Link href="/tickets" className="btn btn-ghost btn-sm">
                    <ChevronLeft size={16} />
                    Zpět na seznam
                </Link>

                <TicketDetail ticket={ticket} />
            </div>
        </>
    );
}

export default TicketDetailPage;
