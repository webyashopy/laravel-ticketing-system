# webyashopy/laravel-ticketing-system

Znovupoužitelný **bug-tracker / ticketing systém** pro Laravel aplikace
s React/Inertia frontendem.

> **Status:** Funkční — backend, frontend i testy hotové
> (`composer test` 133 zelených, `npm run build` zelený).

## Co balíček obsahuje

- Composer balíček `webyashopy/laravel-ticketing-system` — backend (modely,
  controllery, services, migrace, notifikace, console commandy).
- npm balíček `@webyashopy/ticketing-system-ui` — React/Inertia frontend
  (ve stejném repu, `package.json` v kořeni).

## Vlastnosti (cílový stav)

- Lifecycle ticketů Open → Closed, lineární komentáře, append-only audit log
- Přílohy se signed URL, screenshot picker
- E-mailové + database notifikace účastníkům konverzace
- Markdown export ticketu (integrace s Claude Code)
- Cron pro upozornění na zastaralé tickety
- Multi-tenancy přes kontrakty (volitelná, single-tenant default)

## Instalace

```bash
composer require webyashopy/laravel-ticketing-system
npm install github:webyashopy/laravel-ticketing-system
```

Podrobnosti doplní dokumentace po dokončení extrakce.

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
