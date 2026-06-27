# Smart Site Monitor

Drupal-modul der eksponerer et JSON-status-endpoint til ekstern overvågning af et websted. Endpointet samler oplysninger om drift, opdateringer, cron og fejl i ét svar, så et overvågningsværktøj (fx Smart Site Monitor) kan hente status uden at logge ind i Drupal.

## Krav

- Drupal 10 eller 11
- Modul **Update** (`drupal:update`) — påkrævet afhængighed
- Modul **Database Logging** (`dblog`) — valgfrit; uden dblog returneres `errors.available: false`

## Installation

1. Aktivér modulet: **Extend** → **Smart Site Monitor**, eller via Drush:

   ```bash
   drush en smart_site_monitor -y
   ```

2. Konfigurér API-token under **Configuration** → **System** → **Smart Site Monitor**  
   (`/admin/config/system/smart-site-monitor`).

   Uden et konfigureret token returnerer API'et altid `401 Unauthorized`.

## Autentificering

Alle status-ruter kræver **Bearer-token** i `Authorization`-headeren:

```http
GET /smart-site-monitor/status.json HTTP/1.1
Authorization: Bearer <dit-api-token>
```

Token sammenlignes med `api_token` i config `smart_site_monitor.settings` via `hash_equals`.

En event subscriber fjerner `Authorization`-headeren tidligt i request-livscyklussen, så Drupal Basic Auth ikke afviser requestet, når webserveren videresender headeren.

## API-endpoints

| Rute | Beskrivelse |
|------|-------------|
| `GET /smart-site-monitor/status` | JSON-status |
| `GET /smart-site-monitor/status.json` | Samme som ovenfor |
| `GET /smart-site-monitor/status.json` (format-parameter) | `_format=json` |

Alle endpoints:

- Accepterer kun `GET`
- Har `no_cache: TRUE` og svar-header `Cache-Control: no-store`
- Kræver ikke Drupal-login (`_access: TRUE`); adgang styres udelukkende via API-token

### Fejlsvar

| HTTP | Body | Årsag |
|------|------|-------|
| `401` | `{"message":"Unauthorized"}` | Manglende/ugyldigt token, eller token ikke konfigureret |
| `200` | Se nedenfor | Gyldigt token |

## Eksempel på svar

```json
{
  "site_status": {
    "site_name": "Mit websted",
    "is_online": true,
    "is_offline": false,
    "maintenance_mode": false
  },
  "cms": {
    "type": "Drupal",
    "version": "10.3.0",
    "current": "10.3.0",
    "latest": "10.3.1",
    "update_status": "update_available",
    "security_updates": 0,
    "available": true,
    "last_checked_timestamp": 1717488000,
    "last_checked_iso8601": "2024-06-04T08:00:00+00:00"
  },
  "module_summary": {
    "total_modules": 42,
    "updates_available": 3,
    "security_updates": 1,
    "last_checked_timestamp": 1717488000,
    "last_checked_iso8601": "2024-06-04T08:00:00+00:00"
  },
  "modules": [
    {
      "name": "example_module",
      "current": "1.0.0",
      "latest": "1.1.0",
      "security": false
    }
  ],
  "cron": {
    "last_run_timestamp": 1717485000,
    "last_run_iso8601": "2024-06-04T07:10:00+00:00"
  },
  "errors": {
    "last_24h": 2,
    "source": "dblog",
    "available": true
  },
  "generated_at": "2024-06-04T08:15:00+00:00"
}
```

### Felter

| Sektion | Indhold |
|---------|---------|
| `site_status` | Sidenavn, online/offline (vedligeholdelsestilstand) |
| `cms` | Drupal-version, anbefalet version, `update_status` (`current`, `update_available`, `security_update_required`, `not_supported`, `revoked`), antal relevante sikkerhedsopdateringer for core |
| `module_summary` | Antal moduler, tilgængelige opdateringer og sikkerhedsopdateringer |
| `modules` | Liste sorteret alfabetisk: navn, `current`, `latest`, `security` |
| `cron` | Seneste cron-kørsel (timestamp + ISO 8601 UTC) |
| `errors` | Antal logposter med severity ≤ ERROR i de sidste 24 timer (dblog) |
| `generated_at` | Tidspunkt for svaret (ISO 8601 UTC) |

## Opdateringsdata

`UpdateProjectDataProvider` invaliderer Drupals cachede `update_project_data` ved hvert API-kald og genberegner status, så svaret ikke er op til en time gammelt (som standard update-cache). Det svarer til adfærden på **Reports** → **Available updates**, men uden at besøge admin-siden.

## Arkitektur

```
StatusController
    └── StatusResponseBuilder (service_collector)
            ├── SiteStatusCollector
            ├── DrupalUpdateCollector
            ├── ModulesStatusCollector
            ├── CronStatusCollector
            └── ErrorCountCollector
```

Nye datapunkter kan tilføjes ved at oprette en service, der implementerer `StatusCollectorInterface` og tagges med `smart_site_monitor.status_collector` i `smart_site_monitor.services.yml`.

## Konfiguration og tilladelser

| Config / permission | Beskrivelse |
|---------------------|-------------|
| `smart_site_monitor.settings` → `api_token` | Hemmeligt token til API |
| `administer smart site monitor` | Adgang til indstillingssiden |

## Sikkerhed

- Brug et langt, tilfældigt API-token og roter det ved kompromittering.
- Endpointet er offentligt tilgængeligt på URL-niveau; kun token beskytter data.
- Overvej IP-whitelist eller reverse proxy-begrænsning i produktion, hvis det passer til jeres infrastruktur.
- Svaret kan indeholde modulnavne og versionsoplysninger — behand det som følsom driftinformation.

## Drush

```bash
# Vis/sæt token (eksempel)
drush config:get smart_site_monitor.settings api_token
drush config:set smart_site_monitor.settings api_token 'dit-hemmelige-token' -y
```

## Licens

Følger projektets øvrige custom-moduler og Drupal-kodebase.
