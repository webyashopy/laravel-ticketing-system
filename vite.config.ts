import { resolve } from 'node:path';

import react from '@vitejs/plugin-react';
import { defineConfig } from 'vite';
import dts from 'vite-plugin-dts';

/**
 * Vite konfigurace v library mode.
 *
 * Balíček `@webyashopy/ticketing-system-ui` se nebuilduje jako samostatná aplikace,
 * ale jako knihovna spotřebovaná Vite buildem host aplikace. Proto:
 * - `build.lib` — entry point `resources/js/index.ts`, ES výstup do `dist/`.
 * - `external` — react, react-dom, @inertiajs/react ani ostatní knihovny
 *   (lucide-react, sonner, date-fns, html2canvas-pro) NEbuildujeme dovnitř.
 *   Jsou to peerDependencies; host aplikace dodá svou jednu instanci
 *   (jinak by hrozily dvě instance Reactu = rozbité hooky a duplicitní kód).
 * - `vite-plugin-dts` — generuje `.d.ts` typy z TS zdrojů.
 */
export default defineConfig({
    plugins: [
        react(),
        dts({
            // Typy bere z tsconfig.json; do dist/ jde jen index.d.ts strom.
            include: ['resources/js'],
            rollupTypes: true,
        }),
    ],
    build: {
        lib: {
            entry: resolve(__dirname, 'resources/js/index.ts'),
            name: 'WebyashopyTicketingSystemUi',
            formats: ['es'],
            fileName: () => 'index.js',
        },
        rollupOptions: {
            // peerDependencies — host je dodá ze svého node_modules.
            // Regex pokrývá i podexporty (např. `date-fns/locale`).
            external: [
                'react',
                'react-dom',
                'react/jsx-runtime',
                '@inertiajs/react',
                'lucide-react',
                'sonner',
                'html2canvas-pro',
                /^date-fns(\/.*)?$/,
            ],
            output: {
                // Stabilní názvy assetů (host aplikace si je servíruje sama).
                assetFileNames: 'assets/[name][extname]',
            },
        },
        // Sourcemapy kvůli ladění uvnitř host aplikace.
        sourcemap: true,
        // Nečistíme dist mezi buildy zbytečně — necháme default (čistí se).
        emptyOutDir: true,
    },
});
