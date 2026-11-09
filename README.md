# AJAX Chat for TikiWiki

> **Autori / Credits:** Tento repozitár bol vytvorený a dokumentovaný s pomocou ChatGPT od OpenAI. / This repository was created and documented with the assistance of ChatGPT by OpenAI.

## 🇸🇰 Slovenčina

TikiWiki Extension Package, ktorý integruje **AJAX Chat** priamo do TikiWiki. Využíva existujúcich používateľov, session, skupiny, oprávnenia a databázové pripojenie TikiWiki.

### Funkcie
- Samostatný chat na URL `/chat/` aj vloženie do wiki stránky.
- Verejné a súkromné miestnosti, súkromné správy a história.
- Roly používateľ, moderátor a administrátor odvodené od Tiki účtu a skupín.
- Administrácia nastavení, inštalácia, oprava a odinštalovanie cez Tiki.
- TikiDb – samostatná databáza nie je potrebná.

### Požiadavky
- TikiWiki **30.1** (cieľová verzia integrácie).
- PHP **8.1+**.
- Databáza Tiki s InnoDB a utf8mb4.
- Webserver s podporou PHP a pravidiel `.htaccess` (pri Apache).

Integrácia je vo vývoji. Pred produkčným nasadením ju otestujte na stagingu a zálohujte databázu aj súbory. CI vykonáva PHP lint, testy s MariaDB a kontrolu JavaScriptu.

### Rýchla inštalácia
1. Skopírujte celý repozitár do `TIKI_ROOT/vendor_custom/tiki-pkg-ajax-chat/`.
2. V Tiki otvorte **Admin → Packages** a aktivujte `edizontn/ajaxchat`.
3. Otvorte **AJAX Chat settings → Maintenance**.
4. Kliknite na **Install / reinstall / repair AJAX Chat**.
5. Otvorte **Open Chat** alebo vložte chat do wiki stránky.

**Dôležité:** Nekopírujte `src/` do koreňa Tiki ani do `_CHAT/`. Celý balík musí zostať v `vendor_custom/tiki-pkg-ajax-chat/`.

### Vloženie do wiki stránky
```text
{package package="edizontn/ajaxchat" plugin="ajaxchat"}
```
Voliteľná výška iframe (200–1200 px):
```text
{package package="edizontn/ajaxchat" plugin="ajaxchat" height="520"}
```
Používateľ musí byť prihlásený do TikiWiki; anonymný prístup nie je povolený.

### Umiestnenie a dáta
- Balík: `TIKI_ROOT/vendor_custom/tiki-pkg-ajax-chat/`
- Jadro: `vendor_custom/tiki-pkg-ajax-chat/src/`
- Konfigurácia a HMAC secret: `TIKI_ROOT/temp/ajax-chat/Chat-config.php`
- Verejný endpoint: `TIKI_ROOT/chat/`
- Runtime tabuľky: `tiki_ajaxchat_*`
- Nastavenia: `tiki_ajaxchat_settings`

Konfigurácia a secret sa generujú mimo repozitára, aby ich aktualizácia neprepísala.

### Dokumentácia
- [Úvod](docs/wiki/Home.md)
- [Architektúra](docs/wiki/Architecture.md)
- [Inštalácia a aktualizácia](docs/wiki/Installation.md)
- [Nastavenia a oprávnenia](docs/wiki/Configuration.md)
- [Údržba a riešenie problémov](docs/wiki/Maintenance.md)

## 🇬🇧 English

A TikiWiki Extension Package that integrates **AJAX Chat** directly into TikiWiki. It reuses TikiWiki users, sessions, groups, permissions, and the existing database connection.

### Features
- Standalone chat at `/chat/` or embedded in a wiki page.
- Public and private rooms, private messages, and message history.
- User, moderator, and administrator roles derived from Tiki accounts and groups.
- Tiki administration for settings, installation, repair, and uninstall.
- Uses TikiDb; no separate database is required.

### Requirements
- TikiWiki **30.1** (integration target).
- PHP **8.1+**.
- Tiki database with InnoDB and utf8mb4.
- PHP-capable web server and `.htaccess` support (Apache).

The integration is under development. Test it on a staging instance and back up the database and files before production deployment. CI runs PHP linting, MariaDB-backed tests, and JavaScript checks.

### Quick installation
1. Copy the entire repository to `TIKI_ROOT/vendor_custom/tiki-pkg-ajax-chat/`.
2. In Tiki, open **Admin → Packages** and enable `edizontn/ajaxchat`.
3. Open **AJAX Chat settings → Maintenance**.
4. Click **Install / reinstall / repair AJAX Chat**.
5. Use **Open Chat** or embed the chat in a wiki page.

**Important:** Do not copy `src/` into the Tiki root or move it to `_CHAT/`. Keep the complete package in `vendor_custom/tiki-pkg-ajax-chat/`.

### Embed in a wiki page
```text
{package package="edizontn/ajaxchat" plugin="ajaxchat"}
```
Optional iframe height (200–1200 px):
```text
{package package="edizontn/ajaxchat" plugin="ajaxchat" height="520"}
```
The visitor must be logged in to TikiWiki. Anonymous access is not supported.

### Paths and data
- Package: `TIKI_ROOT/vendor_custom/tiki-pkg-ajax-chat/`
- Chat engine: `vendor_custom/tiki-pkg-ajax-chat/src/`
- Generated configuration and HMAC secret: `TIKI_ROOT/temp/ajax-chat/Chat-config.php`
- Public endpoint: `TIKI_ROOT/chat/`
- Runtime tables: `tiki_ajaxchat_*`
- Package settings: `tiki_ajaxchat_settings`

Configuration and the secret are generated outside the repository so package updates do not overwrite local secrets.

### Documentation
- [Home](docs/wiki/Home.md)
- [Architecture](docs/wiki/Architecture.md)
- [Installation and upgrades](docs/wiki/Installation.md)
- [Configuration and permissions](docs/wiki/Configuration.md)
- [Maintenance and troubleshooting](docs/wiki/Maintenance.md)

## License

See [THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md) and the license files in `src/`.
