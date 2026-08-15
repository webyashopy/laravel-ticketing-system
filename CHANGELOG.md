# Changelog

Všechny významné změny tohoto balíčku jsou dokumentovány zde.

Formát vychází z [Keep a Changelog](https://keepachangelog.com/cs/1.1.0/),
verzování dle [SemVer](https://semver.org/lang/cs/).

## [Nezveřejněno]

### Opraveno
- `ScreenshotPicker`: selhání capture (html2canvas / `canvas.toBlob`) se nově
  hlásí přes `window.reportError()` (fallback `console.error`), ne jen zobrazí
  uživateli. Host aplikace tak chybu zachytí globálním `error` listenerem a
  může ji poslat do vlastní telemetrie — dosud se přesný důvod selhání
  z produkce nedal zjistit (T4A ticket #8c196548).

### Změněno
- Signed-route stream přílohy (`tickets.attachment.show`) stojí nově MIMO
  auth route skupinu — platný podpis (HMAC z APP_KEY + TTL
  `tickets.signed_url_ttl_hours`) je autorizace sám o sobě. Dosud route
  dědila `['web', 'auth']` ze skupiny, takže signed URL z markdown exportu
  (skill `buguj`) vracela nepřihlášenému čtenáři redirect na login a
  screenshoty nešly zobrazit — v rozporu s deklarovaným účelem (WebFetch bez
  session). Podklad middleware je konfigurovatelný přes nový klíč
  `tickets.routes.attachment_middleware` (default `['web']`); `signed` se
  přidává vždy. Kontrola, že příloha patří k ticketu z URL, zůstává.
- Vytvoření tiketu už nepřesměrovává na jeho detail. Tiket se hlásí přes FAB
  z libovolné stránky host aplikace a redirect uživatele vytrhl z rozdělané
  práce. `TicketController::store()` nyní vrací `back()` (fallback na seznam
  tiketů, když chybí Referer) a uživatel dostane toast „Ticket #ID byl
  vytvořen" s tlačítkem „Zobrazit", které na detail pustí až na vyžádání.
  Data pro toast jdou přes per-user cache (`TicketCreatedFlash`) → Inertia
  prop `ticketsFlash.created` (sdílí `ShareTicketsBadge`). Bez zapojeného
  middleware toast degraduje na prostou hlášku bez odkazu.
- Payload pro toast se NEUKLÁDÁ do session flash, ale do krátkodobé per-user
  cache, odkud ho middleware vyzvedne atomickým `pull()` a výhradně pro
  skutečný Inertia GET. Session flash tuhle úlohu spolehlivě nezvládá: po
  `back()` letí souběžně s Inertia GET i další requesty aplikace (React Query
  refetche, polling) a session driver `database` nemá mezi requesty zamykání,
  takže flash spolyká kterýkoli z nich a toast nemá co vykreslit. Chyba je
  navíc náhodná podle časování. Vzor převzat z T4A (TASK-1258a-fix-2), kde na
  to narazili v ostrém provozu a řešili si to vlastním overridem controlleru —
  ten je teď zbytečný. TTL řídí `tickets.created_flash_ttl_seconds`
  (default 30 s).
- npm skript `prepare` (`npm run build`) — balíček lze instalovat přímo
  z gitu jako dependency; `dist/` se sestaví automaticky při `npm install`
  (zůstává mimo verzování).

### Opraveno
- FAB „Nahlásit problém" překrývaly modaly host aplikace — měl `z-50`,
  zatímco DaisyUI dává `.modal` z-index 999, takže z obrazovky s otevřeným
  modalem (kde se chyba typicky projeví) nešlo tiket založit. Vrstvy jsou
  nově v `resources/js/lib/z-layers.ts` (FAB 1100 < modal balíčku 1200 <
  screenshot picker 1300) a aplikují se inline stylem, aby nezávisely na
  Tailwind `content`/`@source` konfiguraci hostu.
- Tlačítko „Kopírovat Claude prompt" v detailu tiketu generovalo prompt
  začínající příkazem `/plan`. Nyní vrací `/validate` — vstupní krok bugfix
  workflow (analýza bugu a jeho příčiny). Upravena i závěrečná instrukce
  promptu, aby odpovídala validate workflow.
- `TicketMarkdownExporter::export()` zahazoval veškeré komentáře pod
  ticketem — diskuse s upřesněním/rozhodnutím se ztratila jak v exportu
  pro tlačítko „Kopírovat jako Claude prompt", tak v git exportu pro skill
  `buguj` (obě cesty sdílejí tento exportér). Přidána sekce „## Komentáře"
  (autor, datum, tělo jako blockquote proti rozbití struktury nadpisem
  v textu komentáře), vynechává se u ticketu bez komentářů. Eager-load
  `comments.author` doplněn i do `tickets:sync-export`, aby hromadný export
  nezpůsobil N+1 dotaz na ticket.

### Přidáno
- Skeleton balíčku — Spatie Package Tools, Composer + npm manifesty,
  service provider, Orchestra Testbench setup.
- Kontrakty multi-tenancy `TicketTenantResolver` + autorizace
  `TicketAuthorizer` + výchozí single-tenant implementace.
- Datová vrstva — 4 modely, 3 enumy, 4 migrace (`tenant_id` místo
  `organization_id`, bez FK).
- HTTP vrstva — 4 controllery, 5 requestů, 2 policy, routy web/api,
  middleware `ShareTicketsBadge`.
- Services, 3 notifikace, mailable, 4 e-mailové šablony, console
  commandy `tickets:notify-stale` a `tickets:sync-export`.
- npm balíček `@webyashopy/ticketing-system-ui` — Vite library mode, vlastní
  DaisyUI ui-primitivy, Sanctum CSRF API klient.
- React/Inertia frontend — 13 komponent/stránek, stránky jako čisté
  exportovatelné komponenty bez host layoutu.
- Testy — 133 testů na Orchestra Testbench, z toho 106 portováno
  z mateřské aplikace.
