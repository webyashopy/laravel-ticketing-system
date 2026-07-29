// Vrstvy (z-index) UI balíčku ticketů.
//
// Balíček se vkládá do cizí host aplikace, takže se nesmí spoléhat na to, jak
// vysoko sahají její vlastní overlaye. FAB („Nahlásit problém") musí být nad
// běžnými modaly hostu — DaisyUI dává `.modal` z-index 999, Tailwind utility
// končí na z-50 — jinak ho otevřený modal překryje a bug nejde nahlásit
// z obrazovky, na které se právě projevil.
//
// Hodnoty se aplikují inline (`style={{ zIndex: … }}`), NE Tailwind třídou:
//   1) inline style vyhraje nad libovolným CSS hostu (kromě !important),
//   2) nezávisí na tom, jestli si host zařadil zdrojáky balíčku do Tailwind
//      content/@source — arbitrary třída `z-[1100]` by se jinak nevygenerovala.
//
// Pořadí vrstev: FAB < vlastní modal < screenshot picker.
export const Z_LAYERS = {
    /** Plovoucí tlačítko pro nahlášení problému — nad modaly host aplikace. */
    fab: 1100,
    /** Modal pro vytvoření ticketu — nad FAB. */
    modal: 1200,
    /** Overlay screenshot pickeru — nad vším ostatním z balíčku. */
    screenshotOverlay: 1300,
    /** Spinner / chybový stav pickeru — nad jeho vlastním overlayem. */
    screenshotTop: 1310,
} as const;
