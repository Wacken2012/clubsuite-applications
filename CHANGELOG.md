# Changelog

Alle wesentlichen Änderungen an diesem Projekt werden in dieser Datei dokumentiert.

Das Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/1.0.0/),
und dieses Projekt hält sich an [Semantic Versioning](https://semver.org/lang/de/).

## [1.2.0] - 2026-01-28

### Hinzugefügt
- Vollständige Integration mit clubsuite-core (Mitgliederverwaltung)
- Vollständige Integration mit clubsuite-finance (Buchungen)
- Event-basierte Architektur (ApplicationApprovedEvent, InvoiceCreatedEvent)
- Automatische Mitgliederanlage bei Antragsgenehmigung
- Automatische Finanzbuchung bei Rechnungserstellung
- Neue API-Endpunkte: `/api/integration/status`, `/api/applications/{id}/invoice`
- Vue.js Frontend mit @nextcloud/vue 8.x Komponenten (NcButton, NcModal, etc.)

### Geändert
- info.xml auf Nextcloud Store Standards aktualisiert
- PHP-Abhängigkeit auf 8.1-8.3 eingegrenzt
- Nextcloud-Kompatibilität: 28-32
- ApplicationEntity: memberId jetzt integer (FK zu core)

### Behoben
- CSS 404 Fehler im Frontend
- Template fehlende div-Tags
- API-Routing Probleme

## [1.1.0] - 2026-01-16

### Hinzugefügt
- Release Candidate für Produktionsbetrieb
- DSGVO/GDPR-Compliance verifiziert (Privacy API)
- Role-Based Access Control (RBAC) auf allen Controllern

## [1.0.0] - 2026-01-01

### Hinzugefügt
- Initiale Version
- Grundlegende Antragsverwaltung
- Vue-Frontend und REST-API
- Event-basierte Inter-App-Kommunikation
- Nextcloud 28+ Unterstützung

---

Maintainer: Stefan Schulz