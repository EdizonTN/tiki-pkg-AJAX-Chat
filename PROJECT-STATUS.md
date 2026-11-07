# PROJECT-STATUS.md — AJAX Chat

> Pracovný stav AJAX Chat enginu a jeho integrácie do TikiWiki. Tento dokument slúži ako handoff pre ďalšie pracovné relácie.
> TikiWiki core, jeho migrácia a všeobecná konfigurácia sú evidované v repozitári [New-MZ-tikiwiki](https://github.com/PetoJanis/New-MZ-tikiwiki).

## Základné informácie

- Hlavný súkromný repozitár: https://github.com/PetoJanis/tiki-AJAX-Chat
- Pôvodný verejný fork/archív: https://github.com/PetoJanis/AJAX-Chat-MZ
- Pôvodný projekt: AJAX-Chat (JavaScript + PHP + MySQL/MariaDB).
- Cieľ: modernizovaný, bezpečný, samostatne testovateľný chat pre TikiWiki, neskôr integrovaný s TikiWiki.
- Hostingový cieľ: Active24 shared hosting; riešenie nesmie vyžadovať vlastný VPS ani samostatnú socket server infraštruktúru.
- Preferované testovanie: najprv lokálne Debian prostredie, až potom nasadenie na hosting.

## Funkčné požiadavky

- AJAX chat s prijateľným oneskorením aktualizácie približne 1–2 sekundy.
- Nie Telegram/Discord štýl; zachovať klasický webový chat.
- Možnosť otvoriť chat v samostatnom plávajúcom/oddelenom okne.
- Možnosť zobraziť chat aj v spodnej časti všetkých stránok TikiWiki.
- Súkromné správy.
- Používateľmi vytvárané súkromné miestnosti.
- Administrátori majú najvyššie oprávnenia.
- Moderátor/admin môže blokovať používateľov a mazať správy; úprava už odoslaných správ nie je požadovaná.
- Voliteľné posielanie súborov.
- Formátovanie/bloky kódu v správach (CODE-like).
- Databáza je akceptovateľná; pôvodne obľúbené PHPFreeChat file storage/search nebolo nevyhnutnou podmienkou.
- Súkromné miestnosti majú mať voliteľné skrytie existencie/prítomnosti pred používateľmi, ktorí do nich neboli pozvaní.

## Architektonické a implementačné preferencie

- PHP + JavaScript/AJAX; kompatibilita s bežným shared hostingom.
- Žiadna povinná VPS alebo samostatná služba.
- Cesty a URL nesmú byť natvrdo zakódované.
- Inštalátor má byť webový PHP inštalátor, nie CLI.
- Inštalátor môže pri inštalácii raz zistiť aktuálnu cestu a zapísať ju do konfigurácie; runtime nemá opakovane zisťovať cesty.
- Konfigurovateľné hodnoty majú byť v konfiguračnom súbore.
- Konfigurácia práv/moderátorských skupín má byť neskôr dostupná Tiki bridge vrstve (napr. cez čitateľný `Bridge-config.php` alebo ekvivalent).
- Najprv samostatná inštalácia a testovanie chatu; Tiki plugin/bridge až následne.
- Integrácia má byť udržiavateľná a čo najmenej invazívna voči jadru TikiWiki.

## Stav vývoja — vetvy a míľniky

### Presun do súkromného repozitára (2026-11-04)

- Vytvorený súkromný hlavný repozitár `PetoJanis/tiki-AJAX-Chat`.
- Úspešne vykonaný `git push --mirror` z lokálnej mirror kópie pôvodného `AJAX-Chat-MZ`.
- Overené cez GitHub: v novom repozitári existuje 11 vývojových vetiev vrátane `master`, všetkých vetiev `mz/phase-*` a `mz/tiki-admin-integration`.
- Prenos Git objektov a histórie bol úspešný. Interné GitHub `refs/pull/*` sa neprenášajú; GitHub ich spravuje samostatne.
- Nový repozitár je private. Pôvodný verejný fork zostáva ako archív; ďalší vývoj pokračuje v `tiki-AJAX-Chat`.
- **Dôležité nastavenie:** po mirror push GitHub uvádzal ako default branch `mz/phase-1-safe-db-installer`. Pred ďalšou prácou treba v Settings → General → Default branch nastaviť `master`.
- Autentifikácia pri prenose: Personal Access Token s oprávneniami umožňujúcimi zápis obsahu a workflow súborov (`.github/workflows/chat-checks.yml`). Token sa nesmie ukladať do repozitára ani projektovej dokumentácie.


V predchádzajúcich pracovných reláciách boli zaznamenané tieto vetvy:

- `mz/phase-1-safe-db-installer`
- `mz/phase-2-php82`
- `mz/phase-3-security`
- `mz/phase-4-tiki-auth`
- `mz/phase-5-private-rooms`
- `mz/phase-6-web-installer-chat-dir`
- `mz/phase-7-relocatable-config`
- `mz/phase-8-security-admin`
- `mz/phase-8-tw-ajax-chat-identity`
- `mz/tiki-admin-integration`

Zaznamenané commity:
- `d9e007b` — “Document MariaDB integration test”
- `030038e` — “Use stored chat URL path for generated chat links”

**Pozor:** vyššie uvedené názvy vetiev a commity sú stav známy z predchádzajúcich pracovných relácií, nie potvrdenie aktuálneho HEAD. Pred ďalším zásahom treba skontrolovať aktuálny stav GitHub repozitára a zistiť, ktorá vetva je aktívna a čo už bolo zlúčené.

## Prostredie testovania

Zaznamenané lokálne Debian testovacie prostredie:
- PHP 8.2.35 CLI.
- Rozšírenia: mysqli, pdo_mysql, session.
- Apache.
- MariaDB 10.11.19.
- TikiWiki má čerstvú lokálnu inštaláciu na účely neskoršej integrácie.

## Súkromné miestnosti a oprávnenia

Požiadavky:
- Administrátor má vždy najvyššie práva.
- Moderátorské oprávnenia musia byť konfigurovateľné.
- Súkromné miestnosti môžu byť skryté používateľom, ktorí neboli pozvaní.
- Skrytie miestnosti/prítomnosti má byť voliteľné nastavenie, nie neoddeliteľne natvrdo zakódované pravidlo.
- Overiť, že skryté miestnosti neunikajú cez zoznam miestností, prítomnosť používateľov, počítadlá, vyhľadávanie ani iné rozhrania/API.

## Plánovaná integrácia s TikiWiki

Tiki integrácia sa eviduje v repozitári https://github.com/PetoJanis/New-MZ-tikiwiki.

Známy plán:
- Tiki/Composer package ID: `edizontn/ajaxchat`.
- Tiki package namespace: `EdizonTN\AjaxChat`.
- Package root: repository root; package metadata, wiki plugin and preferences are stored at repository root.
- Adresár balíka: `vendor_custom/tiki-pkg-ajax-chat/`.
- Chat engine runs from `vendor_custom/tiki-pkg-ajax-chat/src/`; do not extract it into Tiki root or `_CHAT/`.
- Chat má používať Tiki autentifikáciu/identitu namiesto samostatného prihlasovania, keď bude bridge dokončený.
- Konfigurácia bridge a mapovanie Tiki skupín/rolí majú byť spravovateľné z Tiki administrácie, ak to architektúra umožní.
- Chat sa má dať vložiť do spodnej časti stránok Tiki a zároveň otvoriť samostatne.
- Pred plnou migráciou obsahu musí byť chat bridge samostatne otestovaný.

Tieto body sú požiadavky/plán, nie automaticky potvrdený stav implementácie. Pri ďalšej práci ich overiť voči aktuálnemu kódu.

## Tiki administrácia a runtime preferencie (2026-11-07)

Vetva: `feature/tiki-admin-settings`

Implementované v tejto vetve:
- `prefs/tp_edizontn__ajaxchat.php` definuje Tiki Extension Preferences pre bezpečné runtime nastavenia AJAX Chatu.
- `src/lib/tiki-bootstrap.php` prenáša a validuje tieto preferencie do konfigurácie AJAX Chat. Základné ovládacie prvky (`chat_enabled`, `moderator_groups`, `admin_stealth_in_private_rooms`) sú označené ako základné preferencie.
- Preferencia `chat_enabled` používa podporovaný Tiki atribút `urladmin` a poskytuje kontextový odkaz na `vendor_custom/tiki-pkg-ajax-chat/src/admin.php`. Balík nepridáva vlastnú položku do Tiki administračného menu a nemení Tiki core.
- Ak ešte neexistuje `src/lib/Chat-config.php`, `src/admin.php` presmeruje GET požiadavku na webový inštalátor `src/install.php`. Inštalátor následne vykonáva vlastnú kontrolu Tiki administrátora a CSRF ochranu.
- `src/admin.php` je dostupný iba prihláseným Tiki administrátorom. Inštalácia/oprava vytvára presne šesť tabuliek s prefixom `tiki_ajaxchat_` cez `CREATE TABLE IF NOT EXISTS`; odinštalácia vyžaduje vypnutý chat a explicitnú potvrdzovaciu frázu a maže iba týchto šesť tabuliek.
- Databázové údaje, HMAC secret, cesty, rezervované ID rozsahy a guest login zostávajú mimo UI. Sú štrukturálne, bezpečnostné alebo v režime Tiki SSO nepoužívané.
- `README-TIKI-BRIDGE.md` opisuje umiestnenie preferencií, kontextový odkaz, prvú inštaláciu a pravidlá správy tabuliek.

Automatické overenie:
- GitHub Actions workflow `PHP and chat checks` na HEAD `db7e3073dbcff645ec9a51e3d6b51323aadbd235` skončil úspešne.
- Úspešné kroky: PHP 8.2 lint všetkých PHP súborov, PHP testy vrátane databázovej schémy/TikiDb adaptéra/súkromných miestností a JavaScript syntax + code-block testy.
- Prostredie CI používa MariaDB 11.4.

Zostáva manuálne overiť na skutočnej TikiWiki 30.1 inštalácii:
1. Zobrazenie kontextového odkazu `urladmin` v karte Extension Preferences.
2. Prvý vstup cez odkaz a presmerovanie na `src/install.php`.
3. Inštaláciu, stav všetkých šiestich tabuliek a opakované spustenie bez straty dát.
4. Odinštaláciu: blokovanie, kým je chat povolený; potvrdenie frázy; odstránenie iba chatových tabuliek.
5. Ukladanie preferencií v Tiki a ich účinnosť v chate.

Táto vetva zatiaľ nie je zlúčená do `main`; pred merge treba dokončiť manuálny test v TikiWiki 30.1.

## Aktuálny otvorený kontext

- V Tiki administrácii sa objavila chyba pri zobrazení balíkov po načítaní `package_custom-tiki-pkg-ajac_chat`:
  `TypeError: Cannot access offset of type string on string` v kompilovanej Smarty šablóne `file_include_packages.tpl.php`.
- Predbežná analýza ukazuje problém v dátovej štruktúre, ktorú vracia Tiki `ComposerManager::getCustomPackages()`, nie v chat engine samotnom.
- Tiki-side diagnostika a navrhovaná oprava sú zdokumentované v `PROJECT-STATUS.md` repozitára New-MZ-tikiwiki.
- Nevykonávať zmeny v chat engine len na základe tejto Tiki Smarty chyby, kým sa nepotvrdí, že príčina súvisí s balíkom.

## Ďalšie kroky

1. Skontrolovať aktuálne vetvy, HEAD a posledné commity repozitára.
2. Zrekonštruovať stav jednotlivých fáz a overiť, ktoré zmeny sú už zlúčené.
3. Spustiť/zdokumentovať samostatné testy na Debian + PHP 8.2 + MariaDB.
4. Overiť inštalátor, relocatable URL/path konfiguráciu a bezpečnostné opatrenia.
5. Otestovať súkromné miestnosti vrátane voliteľného skrývania ich existencie a prítomnosti.
6. Overiť admin/moderátor práva, blokovanie používateľov a mazanie správ.
7. Dokončiť/otestovať Tiki autentifikačný bridge a následne Tiki package/admin konfiguráciu.
8. Pred nasadením overiť kompatibilitu s aktuálnym PHP a MariaDB na Active24.
9. Aktualizovať tento dokument po každom významnom commite, rozhodnutí alebo teste.

## Pravidlá aktualizácie tohto dokumentu

- Zapisovať overené fakty oddelene od návrhov a predpokladov.
- Pri každej zmene uviesť branch, commit, dotknuté súbory a stručný opis.
- Pri testoch uviesť prostredie, príkazy/postup a výsledok.
- Nezapisovať sem podrobnosti TikiWiki core migrácie; tie patria do repozitára New-MZ-tikiwiki.
- Pred ukončením väčšej pracovnej relácie aktualizovať stav a ďalšie kroky. Automatické uloženie pri náhlom ukončení konverzácie nie je zaručené.


## Tiki-native package administration and standalone URL (2026-11-07)

Branch: `feature/standalone-chat-route` (PR #4)

PR #4 has been reworked to remove the direct PHP installer/admin endpoints and the unverified `_custom/shared/custom.php` route dispatcher.

Current implementation on the branch:
- Composer package ID is `edizontn/ajaxchat`, allowing Tiki's `tp_edizontn_ajaxchat` extension admin page identifier to map unambiguously to the package ID.
- Native Tiki admin page: `tiki-admin.php?page=tp_edizontn_ajaxchat`.
- The package admin controller checks `tiki_p_admin` and uses Tiki's CSRF ticket validation for POST actions.
- Install/repair creates the protected config and random HMAC secret in `TIKI_ROOT/temp/ajax-chat/`, creates/verifies the six `tiki_ajaxchat_*` tables, and creates `TIKI_ROOT/chat/index.php`.
- The physical `/chat` directory avoids attempting to intercept a Tiki route from the `_custom/shared/custom.php` hook, which Tiki loads only at the end of `tiki-setup.php`.
- An existing unowned `/chat/index.php` is not overwritten. Uninstall requires disabling chat and confirming the deletion phrase; it drops only the six chat tables and removes files carrying package ownership markers.
- Direct `src/install.php` and `src/admin.php` endpoints and the standalone router were removed from this branch.
- Package preferences remain in Tiki Extension Preferences; the contextual `urladmin` link opens the native extension admin page.

Validation status:
- Code and documentation were updated through GitHub API commits.
- No live TikiWiki 30.1 integration test has been run from this session. In particular, verify extension preference discovery, extension admin page loading, filesystem permissions for `temp/ajax-chat/` and `/chat/`, install/reinstall/uninstall, and chat access via both `/chat` and the wiki plugin.
- Do not treat the branch as production-ready until those manual checks pass.


### Preference namespace correction (2026-11-07)

The Composer package ID was normalized to `edizontn/ajaxchat`, but the preference definitions initially retained the old `tp_edizontn__ajax_chat__` keys. Tiki Extension Packages require the preference file, callback and preference-key namespace to match the Composer package ID. This caused undefined preferences and exposed raw preference identifiers in Extension Preferences.

Corrected on `feature/standalone-chat-route`:
- Preference overlay: `prefs/tp_edizontn__ajaxchat.php`.
- Callback: `prefs_tp_edizontn__ajaxchat_list()`.
- All preference keys and runtime references now use `tp_edizontn__ajaxchat__`.
- The package admin page migrates existing values from the old `tp_edizontn__ajax_chat__` keys to the new keys, then deletes the obsolete rows. Existing user settings are preserved when no new-key value exists.
- Open the package admin page once after updating to run this migration, then reload Extension Preferences.
