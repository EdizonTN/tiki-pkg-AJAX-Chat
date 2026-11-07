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

### Administrácia

- Konfigurácia chatu je v `Settings → Packages → Extension Preferences` (Tiki môže túto kartu zobrazovať pod názvom `Extension Preferences`).
- Pri preferencii `Enable Ajax Chat` je kontextový administračný odkaz `urladmin`, ktorý otvorí `vendor_custom/tiki-pkg-ajax-chat/src/admin.php`. Balík tým nepridáva vlastnú položku do Tiki administračného menu ani nemení jadro Tiki.
- Stránka správy databázy je dostupná iba prihláseným administrátorom TikiWiki a všetky POST operácie sú chránené jednorazovým CSRF tokenom.
- Inštalácia vytvára/overuje presne šesť tabuliek `tiki_ajaxchat_*`; existujúce tabuľky a dáta zachováva. Odinštalácia vyžaduje najprv vypnúť chat a zadať potvrdzovaciu frázu; odstráni iba týchto šesť tabuliek.
- Preferencie sprístupňujú runtime nastavenia AJAX Chatu, ktoré sú bezpečné a zmysluplné v režime Tiki SSO. Databázové údaje, tajný kľúč, cesty, rezervované ID rozsahy a samostatné guest prihlasovanie zostávajú mimo UI, pretože ich riadi Tiki alebo sú štrukturálnymi bezpečnostnými hodnotami.

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

### Administration

- Chat configuration is available under `Settings → Packages → Extension Preferences` (the tab may be labelled `Extension Preferences`).
- The `Enable Ajax Chat` preference provides a contextual `urladmin` link to `vendor_custom/tiki-pkg-ajax-chat/src/admin.php`. The package does not add a custom Tiki administration menu entry or modify Tiki core.
- The database administration page is restricted to authenticated TikiWiki administrators, and all POST operations use a one-time CSRF token.
- Installation creates/verifies exactly six `tiki_ajaxchat_*` tables and preserves existing tables and data. Uninstallation requires the chat to be disabled first and an explicit confirmation phrase; it drops only those six tables.
- Preferences expose the AJAX Chat runtime settings that are safe and meaningful in Tiki SSO mode. Database credentials, the shared secret, filesystem paths, reserved ID ranges and standalone guest login remain outside the UI because they are controlled by Tiki or are structural security values.

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
