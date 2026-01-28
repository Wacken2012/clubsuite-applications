# ClubSuite Applications

[![Nextcloud Version](https://img.shields.io/badge/Nextcloud-28--32-blue.svg)](https://nextcloud.com)
[![PHP Version](https://img.shields.io/badge/PHP-8.1--8.3-purple.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-AGPL%20v3-green.svg)](LICENSE)

> Digitaler Workflow für Mitgliedsanträge mit automatisierter Rechnungsstellung.

## 📋 Übersicht

ClubSuite Applications ermöglicht die digitale Erfassung und Bearbeitung von Mitgliedsanträgen:

- **Online-Anträge**: Web-Formular für Neumitglieder
- **Workflow**: Prüfung, Genehmigung, Ablehnung
- **Rechnungen**: Automatische Erstellung von Aufnahmegebühren
- **Core-Integration**: Automatische Mitgliederanlage bei Genehmigung
- **Finance-Integration**: Automatische Buchung der Aufnahmegebühr

## 🚀 Installation

### Über den Nextcloud App Store (empfohlen)

1. Stellen Sie sicher, dass **ClubSuite Core** installiert ist
2. Navigieren Sie zu **Apps** → **Organisation**
3. Suchen Sie nach "ClubSuite Applications"
4. Klicken Sie auf **Herunterladen und aktivieren**

### Manuelle Installation

```bash
cd /path/to/nextcloud/apps
git clone https://github.com/clubsuite/clubsuite-applications.git
cd clubsuite-applications
composer install --no-dev
npm ci && npm run build
```

## 📦 Anforderungen

| Komponente | Version |
|------------|---------|
| Nextcloud | 28 - 32 |
| PHP | 8.1 - 8.3 |
| **clubsuite-core** | erforderlich |
| clubsuite-finance | optional (für Buchungen) |

## 🔧 API-Endpunkte

| Endpunkt | Methode | Beschreibung |
|----------|---------|--------------|
| `/api/applications` | GET | Liste aller Anträge |
| `/api/applications/{id}` | GET | Einzelner Antrag (mit Mitgliederdaten) |
| `/api/applications/{id}/approve` | POST | Antrag genehmigen |
| `/api/applications/{id}/invoice` | POST | Rechnung erstellen |
| `/api/integration/status` | GET | Integrationsstatus |

## 🔒 DSGVO / Datenschutz

- Personenbezogene Daten werden DSGVO-konform verarbeitet
- Datenexport über Nextcloud Privacy API
- Anonymisierung bei Löschanfragen

## 📄 Lizenz

AGPL v3 – Siehe [LICENSE](LICENSE)

## 🐛 Bugs & Feature Requests

[GitHub Issues](https://github.com/clubsuite/clubsuite-applications/issues)

---

© 2026 Stefan Schulz
