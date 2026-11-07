# AJAX-Chat-MZ – TikiWiki integration

**Slovensky / English**

> **Stav / Status:** TikiWiki integration is under development. The package source and TikiDb adapter are initial implementations and have not yet been validated on a live TikiWiki 30.1 installation. Do not deploy this development branch to production.

## Slovensky

### Architektúra

- TikiWiki spravuje používateľov, prihlasovanie, skupiny, oprávnenia a nastavenia rozšírenia.
- Chatové jadro bude nasadené v adresári `vendor_custom/tiki-pkg-ajax-chat/src/` v koreňovom adresári TikiWiki.
- Zdrojový kód Tiki Extension Package je v `tiki-pkg-ajax-chat/`. Cieľové umiestnenie po inštalácii je `vendor_custom/tiki-pkg-ajax-chat/`.
- Package definuje vlastné preferencie Tiki a wiki plugin `mzchat`. Na wiki stránke sa používa syntax:
  
  ```text
  {package package="mikrozone/ajax_chat" plugin="mzchat"}
  ```

- Composer metadata sú súčasťou zdrojového balíka, ale Composer nebude požiadavkou na produkčnom serveri. Plánovaný webový inštalátor uloží súbory balíka na správne miesto a umožní jeho aktiváciu cez Tiki Packages.
- Chat používa existujúce databázové pripojenie TikiWiki cez `TikiDb`; neotvára vlastné databázové pripojenie a nevyžaduje samostatnú databázu ani DB používateľa.
- Chatové tabuľky používajú prefix `tikiwiki_ext_ajax_chat_`.
- Nastavenia chat_enabled, moderator_groups a admin_stealth_in_private_rooms sú definované ako Tiki Extension Preferences.

### Inštalácia a aktualizácia

Cieľový postup:
1. Zálohovať súbory a databázu TikiWiki.
2. Jednorazovo sprístupniť bootstrap inštalátor cez Tiki admin alebo FTP/správcu súborov.
3. Spustiť inštaláciu ako Tiki administrátor.
4. Nasadiť package do `vendor_custom/tiki-pkg-ajax-chat/` a chatové jadro do `vendor_custom/tiki-pkg-ajax-chat/src/`.
5. Aktivovať package cez Tiki Packages.
6. Inštalátor overí databázovú schému a vytvorí chýbajúce vlastné tabuľky bez zásahu do existujúcich tabuliek TikiWiki.
7. Nastaviť skupiny moderátorov a ostatné možnosti v Admin → Packages → Extensions Preferences.

Aktualizáciu spúšťa administrátor ručne. Pred výmenou súborov sa vytvorí záloha. Zmeny databázovej schémy budú samostatné, s upozornením a potvrdením administrátora.

**Bootstrap inštalátor a plný aktualizačný proces ešte nie sú implementované.** Taktiež nebola vykonaná integračná skúška na TikiWiki 30.1. Tento dokument opisuje cieľovú architektúru.

### Databáza

Všetky chatové dáta sú v tej istej databáze ako TikiWiki. Používajú vlastné tabuľky s prefixom `tikiwiki_ext_ajax_chat_`. TikiDb adapter prekladá API databázovej vrstvy AJAX Chat na TikiDb query/result API. Každá zmena schémy musí byť verzovaná a musí mať vlastnú kontrolu; všeobecný Tiki database checker nemusí poznať štruktúru rozšírenia.

### Oprávnenia

- Administrátori TikiWiki majú vždy najvyššiu rolu.
- Moderátorské skupiny sa zadávajú ako presné názvy skupín TikiWiki, oddelené čiarkami.
- Moderátorská rola sama osebe neudeľuje prístup do súkromných miestností.
- Skrytie administrátora v súkromnej miestnosti je voliteľná preferencia.

## English

### Architecture

- TikiWiki manages users, authentication, groups, permissions and extension settings.
- The chat engine will be deployed in `vendor_custom/tiki-pkg-ajax-chat/src/` under the TikiWiki document root.
- Tiki Extension Package source is in `tiki-pkg-ajax-chat/`; the intended installed location is `vendor_custom/tiki-pkg-ajax-chat/`.
- The package defines Tiki preferences and the `mzchat` wiki plugin. Use it on a wiki page as follows:

  ```text
  {package package="mikrozone/ajax_chat" plugin="mzchat"}
  ```

- Composer metadata is included in the source package, but Composer will not be required on the production server. The planned web installer will place the package files and make it available through Tiki Packages.
- The chat reuses TikiWiki's existing database connection through `TikiDb`. It does not open a separate database connection and does not require a separate database or DB user.
- Chat tables use the `tikiwiki_ext_ajax_chat_` prefix.
- `chat_enabled`, `moderator_groups` and `admin_stealth_in_private_rooms` are defined as Tiki Extension Preferences.

### Installation and updates

Target workflow:
1. Back up TikiWiki files and database.
2. Make the bootstrap installer available once through Tiki administration or FTP/file manager.
3. Run the installer as a Tiki administrator.
4. Deploy the package to `vendor_custom/tiki-pkg-ajax-chat/` and the chat engine to `vendor_custom/tiki-pkg-ajax-chat/src/`.
5. Enable the package through Tiki Packages.
6. The installer checks the schema and creates missing extension tables without modifying existing TikiWiki tables.
7. Configure moderator groups and other options under Admin → Packages → Extensions Preferences.

Updates are started manually by an administrator. Files are backed up before replacement. Database schema migrations are separate operations and require an explicit warning and confirmation.

**The bootstrap installer and complete update workflow are not implemented yet.** Integration testing against TikiWiki 30.1 has not been completed. This document describes the target architecture.

### Database

All chat data lives in the same database as TikiWiki, in dedicated tables prefixed with `tikiwiki_ext_ajax_chat_`. The TikiDb adapter maps the AJAX Chat database API to TikiDb's query/result API. Every schema change must be versioned and checked by the extension; Tiki's general database checker may not understand the extension schema.

### Permissions

- TikiWiki administrators always have the highest chat role.
- Moderator groups are entered as exact TikiWiki group names, separated by commas.
- Moderator status alone does not grant access to private rooms.
- Hiding administrators in private rooms is an optional preference.
