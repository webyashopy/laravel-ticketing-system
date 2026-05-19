// Floating Action Button pro rychlé vytvoření ticketu.
// Fixed bottom-4 right-4. Zavíratelný (X) — preference v sessionStorage.
// fix: persistence přepnuta z localStorage na sessionStorage,
// aby se FAB po reloadu / nové záložce zase zobrazil (přístup k ticketům
// je i ze sidebaru/menu, proto persistent skrytí není potřeba).
// UX tweak: btn-error místo btn-primary, X jako overlay
// badge v pravém horním rohu Bug ikony (místo separátního tlačítka vlevo),
// tooltip zkrácen na „Schovat".
// X badge je teď perfektní kruh přes explicit h-4 w-4 rounded-full
// (DaisyUI badge-circle badge-xs s child ikonou se rozměry nevyrovnaly → vznikla šiška).

import { Bug, X } from 'lucide-react';
import { useEffect, useState } from 'react';

import { TicketCreateModal } from './TicketCreateModal';

const FAB_HIDDEN_KEY = 'ticketsFabHidden';

export function TicketsFab() {
    const [hidden, setHidden] = useState<boolean>(() => {
        if (typeof window === 'undefined') return false;
        return sessionStorage.getItem(FAB_HIDDEN_KEY) === 'true';
    });
    const [modalOpen, setModalOpen] = useState(false);

    // Cleanup legacy localStorage klíče — uživatelé, kteří
    // měli FAB skrytý před touto migrací, by jinak museli ručně promazat
    // localStorage v devtools. Smažeme jednorázově při mountu.
    useEffect(() => {
        if (typeof window !== 'undefined') {
            localStorage.removeItem(FAB_HIDDEN_KEY);
        }
    }, []);

    // Persistujeme stav skrytí jen v rámci aktuální browser session
    useEffect(() => {
        sessionStorage.setItem(FAB_HIDDEN_KEY, String(hidden));
    }, [hidden]);

    if (hidden) {
        return modalOpen ? (
            <TicketCreateModal open={modalOpen} onClose={() => setModalOpen(false)} />
        ) : null;
    }

    return (
        <>
            <div className="fixed bottom-4 right-4 z-50">
                {/* Hlavní FAB tlačítko — otevírá modal. btn-error pro vizuální */}
                {/* odlišení od běžných primárních akcí (bug-tracker indikátor). */}
                <div className="tooltip tooltip-left" data-tip="Nahlásit problém">
                    <button
                        type="button"
                        onClick={() => setModalOpen(true)}
                        className="btn btn-circle btn-error shadow-lg transition-transform hover:scale-105 relative"
                        aria-label="Nahlásit problém"
                    >
                        <Bug size={20} />
                        {/* X jako overlay badge v rohu — kliknutí zavře FAB. */}
                        {/* stopPropagation zabrání bublání na parent button (modal). */}
                        <span
                            role="button"
                            tabIndex={0}
                            aria-label="Schovat"
                            title="Schovat"
                            onClick={(e) => {
                                e.stopPropagation();
                                setHidden(true);
                            }}
                            onKeyDown={(e) => {
                                if (e.key === 'Enter' || e.key === ' ') {
                                    e.stopPropagation();
                                    e.preventDefault();
                                    setHidden(true);
                                }
                            }}
                            className="absolute -top-1 -right-1 h-4 w-4 rounded-full cursor-pointer bg-base-100 border border-base-300 text-base-content hover:bg-base-200 flex items-center justify-center"
                        >
                            <X size={10} strokeWidth={2.5} />
                        </span>
                    </button>
                </div>
            </div>

            <TicketCreateModal open={modalOpen} onClose={() => setModalOpen(false)} />
        </>
    );
}

export default TicketsFab;
