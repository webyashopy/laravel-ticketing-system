# webyashopy/laravel-ticketing-system

🌐 [English](README.md) | **Čeština**

Znovupoužitelný **bug-tracker / ticketing systém** pro Laravel aplikace
s React/Inertia frontendem.

> **Status:** Funkční — backend, frontend i testy hotové
> (`composer test` 133 zelených, `npm run build` zelený).

## Co balíček obsahuje

- Composer balíček `webyashopy/laravel-ticketing-system` — backend (modely,
  controllery, services, migrace, notifikace, console commandy).
- npm balíček `@webyashopy/ticketing-system-ui` — React/Inertia frontend
  (ve stejném repu, `package.json` v kořeni).

## Vlastnosti

- Lifecycle ticketů Open → Closed, lineární komentáře, append-only audit log
- Přílohy se signed URL, screenshot picker
- E-mailové + database notifikace účastníkům konverzace
- Markdown export ticketu (integrace s Claude Code)
- Cron pro upozornění na zastaralé tickety
- Multi-tenancy přes kontrakty (volitelná, single-tenant default)

## Instalace

### 1) Composer + npm balíček

```bash
composer require webyashopy/laravel-ticketing-system
npm install github:webyashopy/laravel-ticketing-system
```

### 2) Publish konfigurace a migrace

```bash
php artisan vendor:publish --provider="Webyashopy\Tickets\TicketsServiceProvider"
php artisan migrate
```

### 3) **Tailwind v4 + DaisyUI v5 setup (POVINNÉ)**

Balíček používá DaisyUI komponentní třídy (`.btn`, `.input`, `.select`, `.card`).
Tailwind CSS se vždy buildí v hostitelské aplikaci, takže DaisyUI plugin musíš
zaregistrovat ve **svém** `resources/css/app.css`:

```css
@import 'tailwindcss';

/* DaisyUI plugin — komponentní třídy z balíčku */
@plugin "daisyui" {
    themes: light --default;
    logs: false;
}

/* Source path pro JIT — aby Tailwind zachytil DaisyUI třídy z dist/ balíčku */
@source '../../node_modules/@webyashopy/ticketing-system-ui/dist/**/*.{js,mjs,cjs}';
```

> ⚠️ **Bez tohoto kroku** se komponenty zobrazí jako "duchové" (DOM správně,
> ale třídy `.btn`/`.card`/`.input` nemají žádné CSS pravidlo). Symptom:
> tlačítka bez pozadí, inputy bez borderu, karty bez stínu.

`daisyui` package se nainstaluje automaticky jako transitive npm dependency.

### 4) Sonner Toaster (notifikace)

Balíček volá `toast()` ze [`sonner`](https://sonner.emilkowal.ski/) — host
aplikace musí mít `<Toaster />` v root layoutu:

```tsx
import { Toaster } from 'sonner';

// ...uvnitř root layoutu
<Toaster position="top-right" richColors closeButton />
```

### 5) Inertia stránky a routy

Vytvoř ve své aplikaci dvě thin Inertia wrappery, které obalí komponenty
balíčku do svého `AppLayout`:

```tsx
// resources/js/pages/tickets/index.tsx
import { TicketsIndexPage, type TicketsListProps } from '@webyashopy/ticketing-system-ui';
import AppLayout from '@/layouts/app-layout';

export default function TicketsIndex(props: TicketsListProps) {
    return (
        <AppLayout>
            <TicketsIndexPage {...props} />
        </AppLayout>
    );
}
```

```tsx
// resources/js/pages/tickets/show.tsx
import { TicketDetailPage, type Ticket } from '@webyashopy/ticketing-system-ui';
import AppLayout from '@/layouts/app-layout';

export default function TicketShow({ ticket }: { ticket: Ticket }) {
    return (
        <AppLayout>
            <TicketDetailPage ticket={ticket} />
        </AppLayout>
    );
}
```

### 6) Globální FAB „Nahlásit bug" (volitelné)

```tsx
import { TicketsFab } from '@webyashopy/ticketing-system-ui';

// ...uvnitř root layoutu
<TicketsFab />
```

## Architektura

- **Skeleton:** Spatie Package Tools
- **PHP namespace:** `Webyashopy\Tickets\`
- **Multi-tenancy:** kontrakty `Webyashopy\Tickets\Contracts\TicketTenantResolver`
  + `TicketAuthorizer` — host aplikace si nabinduje vlastní implementace
- **Migrace:** sloupec `tenant_id` (nullable, bez FK) místo `organization_id`

## Integrace s Claude Code

Tickety lze exportovat jako Markdown a opravovat přímo z
[Claude Code](https://claude.com/claude-code):

- **[CLAUDE-CODE.md](CLAUDE-CODE.md)** — návod k integraci (export → git →
  skill `buguj` → `/validate`)
- **[skills/buguj/SKILL.md](skills/buguj/SKILL.md)** — skill `buguj` pro Claude
  Code (šablona ke zkopírování do `~/.claude/skills/`)

## Vývoj

```bash
composer install
composer test
```

## Licence

MIT — viz [LICENSE](LICENSE).
