# Integrace s Claude Code

Balíček `webyashopy/laravel-ticketing-system` umí tickety **exportovat jako
Markdown** a synchronizovat do git repozitáře. Vývojář pak ve
[Claude Code](https://claude.com/claude-code) otevřené tickety vidí a opravuje
agentním workflow — aniž by musel chodit do webového UI.

## Jak to funguje

```
┌──────────────┐  tickets:sync-export   ┌─────────────┐  git push   ┌──────────┐
│  Aplikace    │ ─────────────────────► │  git repo   │ ──────────► │  remote  │
│  (tickety)   │   (Markdown export)    │ open/ ...   │             │          │
└──────────────┘                        │ closed/ ... │             └────┬─────┘
                                         └─────────────┘                  │
                                                            git clone/pull │
                                                                           ▼
                            ┌──────────────────────────────────────────────────┐
                            │  Claude Code  +  skill `buguj`                    │
                            │  /buguj <id>  →  /validate  →  oprava ticketu     │
                            └──────────────────────────────────────────────────┘
```

1. **Export** — příkaz `tickets:sync-export` zapíše otevřené i zavřené tickety
   jako Markdown soubory (`open/<id>-<slug>.md`, `closed/...`) do git repozitáře.
2. **Sync** — repozitář se commitne a pushne (typicky cron každých pár minut).
3. **Čtení** — skill `buguj` v Claude Code repozitář naklonuje/pullne a tickety
   zobrazí jako tabulku, případně rozbalí detail jednoho ticketu.
4. **Oprava** — od ticketu spustíš `/validate` (vstupní krok bugfix workflow).

## Nastavení na straně balíčku

V `config/tickets.php`:

| Klíč | Env | Význam |
|------|-----|--------|
| `features.sync_export` | `TICKETS_FEATURE_SYNC_EXPORT` | Zapnutí exportu (default `true`). |
| `sync_export_path` | `TICKETS_SYNC_EXPORT_PATH` | Cesta ke git working-copy, do které se tickety exportují. **Bez hodnoty / neexistující cesty se export tiše přeskočí.** |
| `signed_url_ttl_hours` | `TICKETS_SIGNED_TTL` | Platnost signed URL u screenshotů (default `24` h). |

Naplánuj `tickets:sync-export` ve scheduleru host aplikace:

```php
// routes/console.php (Laravel 11+) nebo app/Console/Kernel.php
Schedule::command('tickets:sync-export')->everyFiveMinutes();
```

Cílový repozitář je potřeba ještě **pushnout na remote** (git hook v cílové
cestě, navazující scheduled task nebo cron) — odtud ho čte skill `buguj`.

## Skill `buguj`

Skill pro Claude Code, který tickety zobrazuje, je v repu jako šablona:
**[`skills/buguj/SKILL.md`](skills/buguj/SKILL.md)**.

Zkopíruj ho do `~/.claude/skills/buguj/SKILL.md` a doplň placeholdery
(`<APP>`, `<EXPORT_REPO_URL>`, `<CESTA_K_PROJEKTU>` — popsané přímo v souboru
skillu). Skill žije v lokální konfiguraci vývojáře — verze v repu je jen
sanitizovaná šablona ke zkopírování (bez konkrétních serverů a cest).

## Workflow opravy ticketu

```
ticket → /buguj <id> → /validate → /plan → /dev-back | /dev-front
                                                    → /test → /review → /docs → /deploy
```

`/validate` je **vstupní krok** — analýza bugu a jeho příčiny. Teprve poté
`/plan` navrhuje konkrétní opravu. Plný workflow viz `bugfix.flow.md` v host
aplikaci (`.claude/workflows/`).
