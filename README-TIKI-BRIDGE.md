# AJAX Chat – TikiWiki integration

**Slovensky / English**

> **Stav / Status:** TikiWiki integration is under development. The package source and TikiDb adapter are initial implementations and have not yet been validated on a live TikiWiki 30.1 installation. Do not deploy this development branch to production.

## Slovensky

### Architektúra

- TikiWiki spravuje používateľov, prihlasovanie, skupiny, oprávnenia a nastavenia rozšírenia.
- Koreň repozitára je zároveň koreňom Tiki Extension Package: obsahuje `composer.json`, `tiki-package.json`, `lib/wiki-plugins/` a `prefs/`.
- Kompletný repozitár sa nasadí do `vendor_custom/tiki-pkg-ajax-chat/`; chatové jadro zostáva v `vendor_custom/tiki-pkg-ajax-chat/src/`.
- **Adresár `src/` sa nesmie kopírovať do koreňa TikiWiki ani presúvať do `_CHAT/`.**
- Package definuje vlastné preferencie Tiki a wiki plugin `ajaxchat`. Na wiki stránke sa používa syntax:

  ```text
  {package package="edizontn/ajax_chat" plugin="ajaxchat"}
  ```

- Composer metadata sú súčasťou koreňa balíka. Composer nebude požiadavkou na produkčnom serveri.
- Chat používa existujúce databázové pripojenie TikiWiki cez `TikiDb`; neotvára vlastné databázové pripojenie a nevyžaduje samostatnú databázu ani DB používateľa.
- Chatové tabuľky používajú prefix `tiki_ajaxchat_`.
- Nastavenia `chat_enabled`, `moderator_groups` a `admin_stealth_in_private_rooms` sú definované ako Tiki Extension Preferences.

### Inštalácia a aktualizácia

Cieľové umiestnenie kompletného repozitára je `TIKI_ROOT/vendor_custom/tiki-pkg-ajax-chat/`. Chat sa spúšťa priamo z jeho podadresára `src/`.

Cieľový postup:
1. Zálohovať súbory a databázu TikiWiki.
2. Nasadiť kompletný balík do `vendor_custom/tiki-pkg-ajax-chat/`.
3. Spustiť webový inštalátor ako Tiki administrátor.
4. Aktivovať package cez Tiki Packages.
5. Inštalátor overí databázovú schému a vytvorí chýbajúce vlastné tabuľky bez zásahu do existujúcich tabuliek TikiWiki.
6. Nastaviť skupiny moderátorov a ostatné možnosti v administrácii Tiki.

Aktualizáciu spúšťa administrátor ručne. Pred výmenou súborov sa vytvorí záloha. Zmeny databázovej schémy budú samostatné, s upozornením a potvrdením administrátora.

**Plný aktualizačný proces ešte nie je implementovaný.** Taktiež nebola vykonaná integračná skúška na TikiWiki 30.1. Tento dokument opisuje cieľovú architektúru.

### Databáza

Všetky chatové dáta sú v tej istej databáze ako TikiWiki. Používajú šesť vlastných tabuliek s prefixom `tiki_ajaxchat_`. TikiDb adapter prekladá API databázovej vrstvy AJAX Chat na TikiDb query/result API. Každá zmena schémy musí byť verzovaná a musí mať vlastnú kontrolu.

### Oprávnenia

- Administrátori TikiWiki majú vždy najvyššiu rolu.
- Moderátorské skupiny sa zadávajú ako presné názvy skupín TikiWiki, oddelené čiarkami.
- Moderátorská rola sama osebe neudeľuje prístup do súkromných miestností.
- Skrytie administrátora v súkromnej miestnosti je voliteľná preferencia.

## English

### Architecture

- TikiWiki manages users, authentication, groups, permissions and extension settings.
- The repository root is also the Tiki Extension Package root. It contains `composer.json`, `tiki-package.json`, `lib/wiki-plugins/` and `prefs/`.
- Deploy the complete repository to `vendor_custom/tiki-pkg-ajax-chat/`; the chat engine remains in `vendor_custom/tiki-pkg-ajax-chat/src/`.
- **Do not copy `src/` into the Tiki document root or move it to `_CHAT/`.**
- The package defines Tiki preferences and the `ajaxchat` wiki plugin. Use it on a wiki page as follows:

  ```text
  {package package="edizontn/ajax_chat" plugin="ajaxchat"}
  ```

- Composer metadata is included at the package root, but Composer will not be required on the production server.
- The chat reuses TikiWiki's existing database connection through `TikiDb`. It does not open a separate database connection and does not require a separate database or DB user.
- Chat tables use the `tiki_ajaxchat_` prefix.
- `chat_enabled`, `moderator_groups` and `admin_stealth_in_private_rooms` are defined as Tiki Extension Preferences.

### Installation and updates

Deploy the complete repository to `TIKI_ROOT/vendor_custom/tiki-pkg-ajax-chat/`. The chat engine runs directly from its `src/` subdirectory.

Target workflow:
1. Back up TikiWiki files and database.
2. Deploy the complete package to `vendor_custom/tiki-pkg-ajax-chat/`.
3. Run the web installer as a Tiki administrator.
4. Enable the package through Tiki Packages.
5. The installer checks the schema and creates missing extension tables without modifying existing TikiWiki tables.
6. Configure moderator groups and other options in Tiki administration.

Updates are started manually by an administrator. Files are backed up before replacement. Database schema migrations are separate operations and require an explicit warning and confirmation.

**The complete update workflow is not implemented yet.** Integration testing against TikiWiki 30.1 has not been completed. This document describes the target architecture.

### Database

All chat data lives in the same database as TikiWiki, in six dedicated tables prefixed with `tiki_ajaxchat_`. The TikiDb adapter maps the AJAX Chat database API to TikiDb's query/result API. Every schema change must be versioned and checked by the extension.

### Permissions

- TikiWiki administrators always have the highest chat role.
- Moderator groups are entered as exact TikiWiki group names, separated by commas.
- Moderator status alone does not grant access to private rooms.
- Hiding administrators in private rooms is an optional preference.
