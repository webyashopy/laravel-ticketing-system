---
name: buguj
description: Stáhne otevřené tickety/bugy z bug-trackeru přes git a zobrazí je. Použij když uživatel napíše "/buguj", "buguj", "stáhni tickety", "co je k opravě" nebo chce vidět konkrétní ticket.
---

# Skill: buguj

Stáhne tickety z bug-trackeru přes git a vypíše je. Zdroj: git repo, do kterého
aplikace exportuje tickety příkazem `tickets:sync-export` (balíček
`webyashopy/laravel-ticketing-system`).

> Toto je **šablona**. Zkopíruj soubor do `~/.claude/skills/buguj/SKILL.md`
> a doplň placeholdery `<APP>`, `<EXPORT_REPO_URL>`, `<CESTA_K_PROJEKTU>`
> (viz sekce Placeholdery na konci).

## Argumenty

- `/buguj` (bez argumentu) — vypiš všechny otevřené tickety
- `/buguj <id>` — zobraz konkrétní ticket podle číselného ID (např. `/buguj 1`)
- `/buguj closed` — vypiš zavřené tickety

## Postup

1. **Sync repa.** Cache je v `~/.claude/cache/<APP>-tickets/`.
   - Pokud neexistuje, naklonuj:
     ```sh
     git clone <EXPORT_REPO_URL> ~/.claude/cache/<APP>-tickets
     ```
   - Pokud existuje:
     ```sh
     git -C ~/.claude/cache/<APP>-tickets pull --ff-only --quiet
     ```
   - Pokud `git pull` selže, použij
     `git -C ~/.claude/cache/<APP>-tickets fetch && git -C ~/.claude/cache/<APP>-tickets reset --hard origin/main`
     — repo je read-only mirror, lokální změny se nehodí.

2. **List (bez argumentu nebo `closed`).** Soubory jsou pojmenované `<id>-<slug>.md`.
   ```sh
   ls ~/.claude/cache/<APP>-tickets/open/
   ```
   Pro každý ticket přečti prvních ~10 řádků (heading + metadata) a vypiš tabulku:
   ```
   #ID  Priorita   Kategorie   Titulek                          Stáří
   1    Střední    Jiné        Příklad ticketu                  2h
   ```
   Stáří odhadni z metadata `**Vytvořeno:**` v souboru.

3. **Detail (`/buguj <id>`).** Najdi soubor
   `~/.claude/cache/<APP>-tickets/{open,closed}/<id>-*.md` a vypiš celý obsah
   pomocí Read tool. Pak nabídni uživateli:
   - "Chceš tento bug opravit? Pokud ano, přepnu se do `<CESTA_K_PROJEKTU>` a
     spustím `/validate` — vstupní krok bugfix workflow (analýza bugu a jeho
     příčiny)."
   - Pokud ticket obsahuje screenshoty, signed URL je platný 24 h (dle
     `signed_url_ttl_hours`) od posledního exportu — pro zobrazení použij
     WebFetch (nebo na něj uživatele odkaž).

4. **Pokud git/SSH selže** (Permission denied, Connection refused) — ověř přístup
   k `<EXPORT_REPO_URL>` (SSH klíč, oprávnění). Nepokoušej se o opakovaný clone
   — nahlas chybu uživateli.

## Poznámky

- Soubory v repu jsou auto-generované (`tickets:sync-export` je přepisuje),
  nikdy je needituj v cache adresáři.
- Slug v názvu souboru nemusí přesně odpovídat aktuálnímu titulku ticketu (po
  změně titulku se soubor při příštím syncu přejmenuje).
- Prázdný list = žádné otevřené tickety nebo poslední sync ještě neproběhl.

## Placeholdery

| Placeholder | Hodnota |
|-------------|---------|
| `<APP>` | Krátký název aplikace — název podadresáře v cache (např. `myapp`). |
| `<EXPORT_REPO_URL>` | Git URL repozitáře s exportovanými tickety (např. `ssh://git@server/cesta/tickets`). |
| `<CESTA_K_PROJEKTU>` | Cesta k lokálnímu projektu host aplikace, kde se bug opravuje. |
