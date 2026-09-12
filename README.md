# # Sonka Bau & Sonnenimmobilien - Multi Administration

**Open-Source-Webanwendung zur Erstellung, Verwaltung und Dokumentation von Verkehrszeichenplänen nach RSA21.**

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![Bootstrap 5](https://img.shields.io/badge/Bootstrap-5.3-purple.svg)](https://getbootstrap.com)

---

## Über Sonka Bau & Sonnenimmobilien - Multi Administration

Sonka Bau & Sonnenimmobilien - Multi Administration ist eine vollständig browserbasierte Anwendung für die Erstellung und Verwaltung von Verkehrszeichenplänen gemäß den Richtlinien für die Sicherung von Arbeitsstellen an Straßen (RSA 21). Sie läuft ohne Docker, Node.js oder Build-Prozesse auf Standard-Shared-Hosting-Plattformen (IONOS, STRATO, ALL-INKL, netcup, u.a.).

> **Rechtlicher Hinweis:** Diese Software dient als Werkzeug zur Erstellung und Verwaltung von Unterlagen. Die Anwendung ersetzt keine fachkundige Prüfung. Alle mit dieser Software erstellten Pläne und Dokumente sind vor der Einreichung bei der zuständigen Behörde durch einen qualifizierten Fachmann zu prüfen. Die Software sichert keine behördliche Genehmigungsfähigkeit zu.

---

## Funktionen

### Projektverwaltung
- Projekte mit Kunden, Ansprechpartnern, GPS-Koordinaten und Zeitplan
- Mehrbenutzer-Unterstützung mit Rollen (Admin, Bearbeiter, Prüfer, Gast)
- Statusverwaltung (Entwurf, Aktiv, Prüfung, Abgeschlossen, Archiviert)
- Dateianhänge und Fotos pro Projekt

### Grafischer Planeditor
- Canvas-basierter Editor (Fabric.js 5.x)
- Drag & Drop von Symbolen aus der Bibliothek
- Zoom, Gitter, Fangfunktion, Hilfslinien, Lineal
- Drehen, Spiegeln, Gruppieren, Mehrfachauswahl
- Ebenensteuerung (Vordergrund/Hintergrund)
- Undo/Redo (50 Schritte)
- Export als SVG, PNG, PDF
- Auto-Speicherung alle 60 Sekunden

### Symbolbibliothek
- Upload eigener Symbole (SVG, PNG)
- Import von ZIP-Bibliotheken
- Kategorien, Suche, Favoriten
- **Kein urheberrechtlich geschütztes Material enthalten**

### Dokumentenverwaltung
- Editierbare Vorlagen für:
  - Antrag auf Verkehrsrechtliche Anordnung
  - Verkehrszeichenliste
  - Materialliste
  - Tagesbericht
  - Baustellenkontrolle
  - Abnahmeprotokoll
  - Projektbericht
- PDF-Export (reines PHP, keine externen Abhängigkeiten)
- Druckansicht

### Weitere Module
- Materialverwaltung mit Bestandsüberwachung
- Kundenverwaltung
- Benutzerverwaltung mit Rollen und Berechtigungen
- Zwei-Faktor-Authentifizierung (TOTP/RFC 6238)
- Passwort-zurücksetzen per E-Mail
- Backup & Restore (SQL-Dump + ZIP)
- REST API mit JWT-Authentifizierung (OpenAPI-Doku)
- Aktivitätsprotokoll
- Benachrichtigungen
- Dark Mode / Light Mode (Bootstrap 5.3)
- Responsive (Mobile-freundlich)
- Glassmorphism-Design

---

## Systemvoraussetzungen

- **PHP** 8.1 oder höher
- **Datenbank:** MySQL 5.7+ / MariaDB 10.3+
- **Webserver:** Apache mit `mod_rewrite` (oder nginx mit entsprechender Konfiguration)
- **PHP-Erweiterungen:** `pdo`, `pdo_mysql`, `json`, `mbstring`, `openssl`, `gd`, `zip`, `fileinfo`
- **Schreibrechte:** `dateien/`, `infos/`, `sicherungen/`, `speicher/`, und das Stammverzeichnis (für `config.php`)

---

## Installation

### 1. Dateien hochladen
Laden Sie alle Dateien per FTP auf Ihren Webserver hoch.

### 2. Datenbank importieren
Importieren Sie `database.sql` in Ihre MySQL/MariaDB-Datenbank.

### 3. `config.php` anlegen
Kopieren Sie `config.sample.php` nach `config.php` und tragen Sie Ihre DB-Zugangsdaten ein.

### 4. Starten
Rufen Sie die Domain im Browser auf. Weitere Installations- oder Build-Schritte sind nicht erforderlich.

---

## Fehlerbehebung

### Ursache
Die App erkennt die Konfiguration nicht. Dies passiert typischerweise wenn:
- `config.php` fehlt oder ungültig ist
- die Datenbankverbindung in `config.php` nicht stimmt

### Lösung

**1. `config.php` prüfen**  
Im Stammverzeichnis muss eine Datei `config.php` vorhanden sein. Erstellen Sie sie aus `config.sample.php`.

**2. Datenbank prüfen**  
Stellen Sie sicher, dass `database.sql` importiert wurde und die Zugangsdaten in `config.php` korrekt sind.

**3. Schreibrechte prüfen**  
Das Stammverzeichnis sowie `dateien/`, `infos/`, `sicherungen/`, `speicher/` müssen für den Webserver beschreibbar sein (Rechte 755 oder 775).

**4. Browser-Cookies löschen**  
Löschen Sie die Cookies für Ihre Domain und testen Sie im Inkognito-Fenster erneut.

**5. `APP_URL` prüfen**  
Der Wert in `config.php` muss exakt der aufgerufenen URL entsprechen. Bei HTTP-Betrieb ohne SSL muss `APP_URL` mit `http://` beginnen, z. B.:
```php
define('APP_URL', 'http://s983687985.online.de');
```
Kein `https://`, kein abschließender Schrägstrich.

**Hinweis:** Diese Anwendung erzwingt kein HTTPS. Falls Ihr Hoster automatisch auf HTTPS umleitet, muss `APP_URL` entsprechend auf `https://` gesetzt werden.

---

## Konfiguration

Nach der Installation befindet sich die Konfiguration in `config.php`. Alle Einstellungen können auch im Admin-Bereich unter **Einstellungen** angepasst werden.

Für SMTP-E-Mail (Passwort zurücksetzen, Benachrichtigungen) tragen Sie die SMTP-Zugangsdaten unter **Einstellungen → E-Mail** ein.

---

## Entwicklung

```bash
# Lokaler PHP-Entwicklungsserver
php -S localhost:8080
```

### Verzeichnisstruktur
```
RSA-Webversion/
├── assets/           # CSS, JS, Bilder
├── sicherungen/          # Backup-Dateien
├── api/
│   ├── plans.php     # Plan-API (Save/Export)
│   └── symbols.php   # Symbol-API
├── includes/         # DB, Auth, CSRF, Helper, Upload
├── infos/             # Anwendungsinfos
├── pages/            # Seitenmodule
│   ├── projects/
│   ├── plans/
│   ├── customers/
│   ├── materials/
│   ├── symbols/
│   ├── users/
│   ├── settings/
│   ├── backup/
│   └── notifications/
├── src/
│   ├── Controllers/  # Fachlogik
│   ├── Models/       # Datenbankmodelle
│   ├── Services/     # Dienste (PDF, Backup, Upload)
│   └── Views/        # PHP-Templates
├── speicher/          # Symbole, Vorlagen
├── dateien/          # Datei-Uploads
├── .htaccess
├── config.php
├── database.sql
├── config.sample.php
└── index.php
```

---

## REST API

Die REST API ist unter `/api/v1/` erreichbar. Vollständige OpenAPI-Dokumentation unter `/api/v1/docs`.

```bash
# Login
POST /api/v1/auth/login
{"email": "admin@example.com", "password": "secret"}
# → {"token": "eyJ...", "expires_in": 3600}

# Geschützte Anfrage
GET /api/v1/projects
Authorization: ******
```

---

## Rechtliche Hinweise

- Diese Software enthält **keine** urheberrechtlich geschützten Symbolbibliotheken oder Regelpläne
- Benutzer müssen eigene Symbole hochladen oder frei lizenzierte Bibliotheken importieren
- Maßgebliche Vorschriften: **RSA 21**, **StVO**, **ZTV-SA**, **RABT**, **TL-Absperreinrichtungen**
- Weiterführende Informationen: [rsa-online.com](https://www.rsa-online.com/)

---

## Lizenz

MIT License – Copyright (c) 2024 Sonka Bau & Sonnenimmobilien - Multi Administration Contributors
