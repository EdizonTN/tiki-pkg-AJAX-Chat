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
  {package package="edizontn/ajaxchat" plugin="ajaxchat"}
  ```

- Composer metadata sú súčasťou koreňa balíka. Composer nebude požiadavkou na produkčnom serveri.
- Chat používa existujúce databázové pripojenie TikiWiki cez `TikiDb`; neotvára vlastné databázové pripojenie a nevyžaduje samostatnú databázu ani DB používateľa.
- Chatové tabuľky používajú prefix `tiki_ajaxchat_`.
- Nastavenia `chat_enabled`, `moderator_groups` a `admin_stealth_in_private_rooms` sú definované ako Tiki Extension Preferences.
- Samostatná URL `/chat` vzniká ako fyzický adresár s `index.php`, ktorý vytvorí Tiki administračná stránka. Nie je potrebné zachytávať požiadavku v `_custom/shared/custom.php`, vytvárať Custom Route ani meniť Tiki core.

### Administrácia

- Administrácia balíka je natívna Tiki Extension Admin Page: `tiki-admin.php?page=tp_edizontn_ajaxchat`. Odkaz na ňu je dostupný pri preferencii `Enable Ajax Chat`.
- Inštalácia/oprava sa vykonáva tlačidlom **Install / repair AJAX Chat**. Tiki automaticky vytvorí chránenú konfiguráciu a náhodný HMAC secret v `TIKI_ROOT/temp/ajax-chat/`, šesť databázových tabuliek a fyzický vstupný bod `TIKI_ROOT/chat/index.php`.
- Pri existujúcom `/chat/index.php`, ktorý nepatrí balíku, sa inštalácia zastaví a súbor neprepíše.
- Odinštalácia vyžaduje vypnutý chat a potvrdzovaciu frázu. Odstráni iba šesť tabuliek AJAX Chatu a súbory označené ako vytvorené balíkom. Cudzie súbory a Tiki dáta zostanú nedotknuté.
- Všetky mutačné POST akcie používajú Tiki CSRF ticket a kontrolu oprávnenia administrátora.



### Inštalácia a aktualizácia

Cieľové umiestnenie kompletného repozitára je `TIKI_ROOT/vendor_custom/tiki-pkg-ajax-chat/`.

1. Nasadiť kompletný balík do `vendor_custom/tiki-pkg-ajax-chat/`.
2. Aktivovať balík cez Tiki Packages.
3. Otvoriť Admin → Packages → Extension Preferences a cez kontextový odkaz prejsť do administrácie AJAX Chatu.
4. Stlačiť **Install / repair AJAX Chat**. Tiki vytvorí konfiguráciu, secret, databázové tabuľky a URL `/chat`.
5. Otestovať `/chat` aj vložený chat cez wiki plugin.

Aktualizačný proces pre zmeny databázovej schémy ešte nie je plne verzovaný. Pred nasadením na produkciu treba vykonať integračný test na TikiWiki 30.1 a zálohovať súbory aj databázu.



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
  {package package="edizontn/ajaxchat" plugin="ajaxchat"}
  ```

- Composer metadata is included at the package root, but Composer will not be required on the production server.
- The chat reuses TikiWiki's existing database connection through `TikiDb`. It does not open a separate database connection and does not require a separate database or DB user.
- Chat tables use the `tiki_ajaxchat_` prefix.
- `chat_enabled`, `moderator_groups` and `admin_stealth_in_private_rooms` are defined as Tiki Extension Preferences.
- The standalone clean URL defaults to `/chat`. The first run of the web installer registers a dispatcher through Tiki 30.1's supported `_custom/shared/custom.php` hook. The path is read from the preference on every request; no wiki page or Custom Route is needed. Standard Tiki SEF URL / rewrite support is required.

### Administration

- The package uses Tiki's native extension admin page: `tiki-admin.php?page=tp_edizontn_ajaxchat`. The contextual link is available beside the `Enable Ajax Chat` preference.
- **Install / repair AJAX Chat** creates the protected configuration and random HMAC secret in `TIKI_ROOT/temp/ajax-chat/`, the six database tables, and a physical `TIKI_ROOT/chat/index.php` entry point.
- If an existing `/chat/index.php` is not owned by this package, installation stops without overwriting it.
- Uninstall requires the chat to be disabled and an explicit confirmation phrase. It removes only the six AJAX Chat tables and files marked as package-owned. Unrelated files and Tiki data remain untouched.
- Mutating POST actions use Tiki's CSRF ticket and administrator permission check.



### Installation and updates

Deploy the complete repository to `TIKI_ROOT/vendor_custom/tiki-pkg-ajax-chat/`.

1. Deploy the package to `vendor_custom/tiki-pkg-ajax-chat/`.
2. Enable it through Tiki Packages.
3. Open Admin → Packages → Extension Preferences and follow the contextual AJAX Chat administration link.
4. Click **Install / repair AJAX Chat**. Tiki creates the configuration, secret, database tables and `/chat` URL entry point.
5. Test both `/chat` and the embedded wiki plugin.

Database schema migrations are not yet fully versioned. Before production deployment, perform integration testing on TikiWiki 30.1 and back up files and the database.



### Database

All chat data lives in the same database as TikiWiki, in six dedicated tables prefixed with `tiki_ajaxchat_`. The TikiDb adapter maps the AJAX Chat database API to TikiDb's query/result API. Every schema change must be versioned and checked by the extension.

### Permissions

- TikiWiki administrators always have the highest chat role.
- Moderator groups are entered as exact TikiWiki group names, separated by commas.
- Moderator status alone does not grant access to private rooms.
- Hiding administrators in private rooms is an optional preference.
