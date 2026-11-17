# AJAX Chat for TikiWiki

**Verzia balíka / Package version: 0.1.15**

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
- TikiWiki **30.2** (cieľová verzia integrácie; runtime test ešte neprebehol).
- PHP **8.1+**.
- Databáza Tiki s InnoDB a utf8mb4.
- Webserver s podporou PHP a pravidiel `.htaccess` (pri Apache).

Integrácia je vo vývoji. Pred produkčným nasadením ju otestujte na stagingu a zálohujte databázu aj súbory. CI vykonáva PHP lint, testy s MariaDB a kontrolu JavaScriptu.

### Rýchla inštalácia
1. Skopírujte celý repozitár do `TIKI_ROOT/vendor_custom/tiki-pkg-ajax-chat/`.
2. V Tiki otvorte **Admin → Packages** a aktivujte `edizontn/ajaxchat`.
3. Otvorte administračnú stránku AJAX Chat; balík pri prvom načítaní stránky automaticky vytvorí svoj navigačný bootstrap.
4. Kliknite na **Install / reinstall / repair AJAX Chat**.
5. Otvorte **Open Chat** alebo vložte chat do wiki stránky.

**Ochrana vlastných úprav Tiki:** Balík nikdy nenahrádza celý existujúci `_custom/shared/custom.php`. Ak súbor už existuje, pridá doň iba samostatný blok označený `BEGIN/END EdizonTN AJAX Chat admin navigation`; všetok ostatný obsah zostáva zachovaný. Pomocný súbor `ajaxchat-admin-navigation.php` sa vytvára samostatne a existujúci súbor s rovnakým názvom bez značky balíka sa neprepíše — inštalácia v takom prípade skončí chybou. Pri odinštalovaní sa odstráni iba označený blok a pomocný súbor balíka. Vlastné Tiki šablóny (`.tpl`) ani súbory témy sa nemenia.

**Odkaz po aktivácii package:** Package obsahuje Tiki profil `profiles/ajaxchat_admin_menu.yml`, ktorý sa podľa mechanizmu Extension Packages aplikuje pri povolení balíka. Vytvorí samostatné menu AJAX Chat určené skupine `Admins` v ľavom stĺpci Tiki; položka vedie priamo na `tiki-admin.php?page=tp_edizontn_ajaxchat`. Tým sa dá otvoriť administrácia bez predchádzajúcej návštevy jej URL. Viditeľnosť ľavého stĺpca závisí od rozloženia/šablóny stránky. **TikiWiki 30.2 runtime overenie ešte neprebehlo.**

**Navigácia v administrácii – doplnkové obmedzenie:** Aktuálny bootstrap vytvorí `_custom/shared/custom.php` a pomocný súbor `_custom/shared/ajaxchat-admin-navigation.php`, pričom zachová nesúvisiaci obsah existujúceho `custom.php`. Registruje stránku cez natívny `$admin_icons` register administračných Control Panels. Tento register nie je bočná navigácia UAB **Manage → Website Functions**; v UAB patrí položka do **Configure → Advanced**. Preto toto riešenie ešte nespĺňa požadované umiestnenie v **Manage → Website Functions**. Samotná existencia `custom.php` ani registrácia v `$admin_icons` nedokazuje, že sa položka zobrazí v bočnom menu. Priamy odkaz na stránku je `tiki-admin.php?page=tp_edizontn_ajaxchat`. Automatické vytvorenie bootstrapu prebieha pri prvom otvorení stránky nastavení alebo pri Install/repair; odinštalovanie odstráni iba balíkom označené časti a súbory.

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
- TikiWiki **30.2** (integration target under verification).
- PHP **8.1+**.
- Tiki database with InnoDB and utf8mb4.
- PHP-capable web server and `.htaccess` support (Apache).

The integration is under development. Test it on a staging instance and back up the database and files before production deployment. CI runs PHP linting, MariaDB-backed tests, and JavaScript checks.

### Quick installation
1. Copy the entire repository to `TIKI_ROOT/vendor_custom/tiki-pkg-ajax-chat/`.
2. In Tiki, open **Admin → Packages** and enable `edizontn/ajaxchat`.
3. Open the AJAX Chat administration page; on first load the package creates its navigation bootstrap automatically.
4. Click **Install / reinstall / repair AJAX Chat**.
5. Use **Open Chat** or embed the chat in a wiki page.

**Protection of existing Tiki customizations:** The package never replaces the entire existing `_custom/shared/custom.php`. If that file already exists, it adds only a separately marked `BEGIN/END EdizonTN AJAX Chat admin navigation` block; all other content is preserved. The `ajaxchat-admin-navigation.php` helper is a separate file, and an existing same-named file without the package marker is never overwritten — installation stops with an error instead. Uninstall removes only the marked block and the package-owned helper. Tiki template files (`.tpl`) and theme files are not modified.

**Link after enabling the package:** The package includes the Tiki profile `profiles/ajaxchat_admin_menu.yml`, which the Extension Package mechanism applies when the package is enabled. It creates a separate AJAX Chat menu for the `Admins` group in Tiki's left column; the item links directly to `tiki-admin.php?page=tp_edizontn_ajaxchat`. This makes the administration page reachable without already knowing its URL. Visibility of the left column depends on the site's layout/theme. **TikiWiki 30.2 runtime verification has not yet been performed.**

**Additional administration navigation limitation:** The current bootstrap creates `_custom/shared/custom.php` and its helper `_custom/shared/ajaxchat-admin-navigation.php`, preserving unrelated content in an existing `custom.php`. It registers the page in both the UAB backend child registry (when available) and the top-level `$admin_icons` Control Panels registry as a fallback. The item is intended to be reachable from the administration Control Panels area (typically **Configure → Advanced**); the exact placement and visibility in TikiWiki 30.2 still require runtime confirmation. Creating `custom.php` or registering an `$admin_icons` entry does not prove that an item is present in the UAB sidebar. The direct URL is `tiki-admin.php?page=tp_edizontn_ajaxchat`. The bootstrap is created on the first settings-page visit or by Install/repair; uninstall removes only package-marked blocks and files.

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
