/**
 * Minimální slučovač CSS tříd.
 *
 * Balíček záměrně NEpoužívá `clsx` ani `tailwind-merge` z host aplikace —
 * pro spojení DaisyUI tříd ve ui-primitivech stačí filtrovat falsy hodnoty
 * a poskládat řetězec. Žádná deduplikace Tailwind tříd není potřeba:
 * primitivy negenerují kolidující utility a host je nepřebíjí.
 */
export type ClassValue =
    | string
    | number
    | bigint
    | null
    | false
    | undefined
    | ClassValue[];

export function cn(...inputs: ClassValue[]): string {
    const out: string[] = [];

    const walk = (value: ClassValue): void => {
        if (!value) return;
        if (Array.isArray(value)) {
            value.forEach(walk);
            return;
        }
        out.push(String(value));
    };

    inputs.forEach(walk);

    return out.join(' ');
}
