# Changelog

Všechny významné změny tohoto balíčku jsou dokumentovány zde.

Formát vychází z [Keep a Changelog](https://keepachangelog.com/cs/1.1.0/),
verzování dle [SemVer](https://semver.org/lang/cs/).

## [Nezveřejněno]

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
