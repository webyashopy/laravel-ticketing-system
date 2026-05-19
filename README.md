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

```bash
composer require webyashopy/laravel-ticketing-system
npm install github:webyashopy/laravel-ticketing-system
```

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
