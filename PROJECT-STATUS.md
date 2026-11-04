# PROJECT-STATUS.md — AJAX-Chat-MZ

> Pracovný stav samostatného chat enginu AJAX-Chat-MZ a jeho budúcej integrácie do TikiWiki. Tento dokument slúži ako handoff pre ďalšie pracovné relácie.
> TikiWiki core, jeho migrácia a všeobecná konfigurácia sú evidované v repozitári [New-MZ-tikiwiki](https://github.com/PetoJanis/New-MZ-tikiwiki).

## Základné informácie

- Fork: https://github.com/PetoJanis/AJAX-Chat-MZ
- Pôvodný projekt: AJAX-Chat (JavaScript + PHP + MySQL/MariaDB).
- Cieľ: modernizovaný, bezpečný, samostatne testovateľný chat pre Mikrozone, neskôr integrovaný s TikiWiki.
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

V predchádzajúcich pracovných reláciách boli zaznamenané tieto vetvy:

- `mz/phase-1-safe-db-installer`
- `mz/phase-2-php82`
- `mz/phase-3-security`
- `mz/phase-4-tiki-auth`
- `mz/phase-5-private-rooms`
- `mz/phase-7-relocatable-config`

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

Produkčné/hostingové prostredie Mikrozone:
- Active24 shared hosting.
- Historicky PHP 5.6.40 FPM pre pôvodný e107; cieľová dostupná PHP verzia pre chat/Tiki treba vždy overiť pred nasadením.
- MariaDB 11.4 (zaznamenaný stav hostingu).

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
- Tiki package ID: `edizontn/tw-ajax-chat`.
- Plánovaný adresár balíka: `vendor_custom/tw-ajax-chat/`.
- Chat core má byť umiestnený pod `vendor_custom/tw-ajax-chat/chat/`.
- Chat má používať Tiki autentifikáciu/identitu namiesto samostatného prihlasovania, keď bude bridge dokončený.
- Konfigurácia bridge a mapovanie Tiki skupín/rolí majú byť spravovateľné z Tiki administrácie, ak to architektúra umožní.
- Chat sa má dať vložiť do spodnej časti stránok Tiki a zároveň otvoriť samostatne.
- Pred plnou migráciou Mikrozone obsahu musí byť chat bridge samostatne otestovaný.

Tieto body sú požiadavky/plán, nie automaticky potvrdený stav implementácie. Pri ďalšej práci ich overiť voči aktuálnemu kódu.

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
