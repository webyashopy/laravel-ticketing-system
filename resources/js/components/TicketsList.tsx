// Tabulka ticketů.

import type { Ticket } from '../types';

import { TicketRow } from './TicketRow';

interface TicketsListProps {
    tickets: Ticket[];
}

export function TicketsList({ tickets }: TicketsListProps) {
    if (tickets.length === 0) {
        return (
            <div className="alert">
                <span>Žádné tickety neodpovídají filtrům.</span>
            </div>
        );
    }

    return (
        <div className="overflow-x-auto">
            <table className="table table-zebra">
                <thead>
                    <tr>
                        <th>Stav</th>
                        <th>Kategorie</th>
                        <th>Priorita</th>
                        <th>Název</th>
                        <th>Vytvořil</th>
                        <th>Vytvořeno</th>
                        <th>Přílohy</th>
                    </tr>
                </thead>
                <tbody>
                    {tickets.map((ticket) => (
                        <TicketRow key={ticket.uuid} ticket={ticket} />
                    ))}
                </tbody>
            </table>
        </div>
    );
}

export default TicketsList;
