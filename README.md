# webyashopy/laravel-ticketing-system

🌐 **English** | [Čeština](README.cs.md)

A reusable **bug-tracker / ticketing system** for Laravel applications
with a React/Inertia frontend.

> **Status:** Working — backend, frontend and tests complete
> (`composer test` 133 passing, `npm run build` passing).

## What's in the package

- Composer package `webyashopy/laravel-ticketing-system` — backend (models,
  controllers, services, migrations, notifications, console commands).
- npm package `@webyashopy/ticketing-system-ui` — React/Inertia frontend
  (same repo, `package.json` at the root).

## Features

- Ticket lifecycle Open → Closed, linear comments, append-only audit log
- Attachments with signed URLs, screenshot picker
- Email + database notifications to conversation participants
- Markdown ticket export (Claude Code integration)
- Cron for stale-ticket reminders
- Multi-tenancy via contracts (optional, single-tenant by default)

## Installation

### 1) Composer + npm package

```bash
composer require webyashopy/laravel-ticketing-system
npm install github:webyashopy/laravel-ticketing-system
```

### 2) Publish config and migrations

```bash
php artisan vendor:publish --provider="Webyashopy\Tickets\TicketsServiceProvider"
php artisan migrate
```

### 3) **Tailwind v4 + DaisyUI v5 setup (REQUIRED)**

The package uses DaisyUI component classes (`.btn`, `.input`, `.select`, `.card`).
Tailwind CSS is always built in the host application, so you must register the
DaisyUI plugin in **your** `resources/css/app.css`:

```css
@import 'tailwindcss';

/* DaisyUI plugin — component classes from the package */
@plugin "daisyui" {
    themes: light --default;
    logs: false;
}

/* Source path for JIT — so Tailwind picks up DaisyUI classes from dist/ */
@source '../../node_modules/@webyashopy/ticketing-system-ui/dist/**/*.{js,mjs,cjs}';
```

> ⚠️ **Without this step** components render as "ghosts" (DOM is correct,
> but `.btn`/`.card`/`.input` classes have no CSS rule). Symptoms: buttons
> without background, inputs without border, cards without shadow.

The `daisyui` package is installed automatically as a transitive npm dependency.

### 4) Sonner Toaster (notifications)

The package calls `toast()` from [`sonner`](https://sonner.emilkowal.ski/) —
the host app must have `<Toaster />` in its root layout:

```tsx
import { Toaster } from 'sonner';

// ...inside the root layout
<Toaster position="top-right" richColors closeButton />
```

### 5) Inertia pages and routes

Create two thin Inertia wrappers in your app that wrap the package's components
in your own `AppLayout`:

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

### 6) Global "Report a bug" FAB (optional)

```tsx
import { TicketsFab } from '@webyashopy/ticketing-system-ui';

// ...inside the root layout
<TicketsFab />
```

The FAB renders at `z-index: 1100` — above DaisyUI modals (999), so a ticket
can be filed even from a screen with an open modal. If your app has overlays
above 1100, override the values in `resources/js/lib/z-layers.ts`.

After submitting the form the user **stays on the current page** — they only
get a toast linking to the new ticket.

### 7) `ShareTicketsBadge` middleware (recommended)

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \Webyashopy\Tickets\Http\Middleware\ShareTicketsBadge::class,
    ]);
})
```

Shares two Inertia props:

- `ticketsOpenCount` — open ticket count for the header badge (lazy, only
  evaluated on partial reloads that request it)
- `ticketsFlash.created` — data of the newly created ticket for the toast

The package works without the middleware; the toast just loses its link to the
new ticket and the badge has no count.

The toast payload travels through a short-lived per-user **cache**, not the
session flash — the middleware picks it up with an atomic `pull()`, and only
for a genuine Inertia GET. Session flash cannot do this reliably: after
`back()`, other requests (React Query refetches, polling) race the Inertia GET,
and the `database` session driver has no inter-request locking, so whichever
request arrives first eats the flash and the toast renders nothing. The app
therefore needs a working cache driver; TTL is set by
`tickets.created_flash_ttl_seconds` (default 30 s).

## Architecture

- **Skeleton:** Spatie Package Tools
- **PHP namespace:** `Webyashopy\Tickets\`
- **Multi-tenancy:** `Webyashopy\Tickets\Contracts\TicketTenantResolver`
  + `TicketAuthorizer` contracts — the host application binds its own
  implementations
- **Migrations:** `tenant_id` column (nullable, no FK) instead of
  `organization_id`

## Claude Code integration

Tickets can be exported as Markdown and fixed directly from
[Claude Code](https://claude.com/claude-code):

- **[CLAUDE-CODE.md](CLAUDE-CODE.md)** — integration guide (export → git →
  `buguj` skill → `/validate`)
- **[skills/buguj/SKILL.md](skills/buguj/SKILL.md)** — the `buguj` skill for
  Claude Code (template to copy into `~/.claude/skills/`)

## Development

```bash
composer install
composer test
```

## License

MIT — see [LICENSE](LICENSE).
