# Údržba a riešenie problémov

## Stav inštalácie
Na karte **Maintenance** sa zobrazuje stav šiestich tabuliek:
- **complete** – všetkých šesť tabuliek je prítomných;
- **partial** – časť tabuliek chýba;
- **missing** – chatové tabuľky neboli vytvorené.

Stav complete sám osebe ešte nepotvrdzuje dostupnosť chatu. Kontroluje sa aj konfigurácia, vstupné súbory v `/chat` a pravidlá pre assety.

## Install / repair
Použite **Install / reinstall / repair AJAX Chat**, ak chýbajú tabuľky alebo package-managed súbory. Inštalátor kontroluje schému a dopĺňa chýbajúce súčasti. Cudzie súbory neprepisuje.

## Odinštalovanie
Odinštalovanie odstráni správy, miestnosti, členstvá, zákazy, pozvánky a online stav.
1. Vypnite **Enable Ajax Chat** v General Preferences.
2. V časti Maintenance zadajte presnú frázu `DELETE AJAX CHAT DATA`.
3. Potvrďte **Uninstall and delete chat data**.

Operácia odstráni šesť runtime tabuliek, tabuľku nastavení a súbory označené ako vytvorené balíkom. Pred akciou zálohujte databázu aj súbory.

## Časté problémy
### Chat hlási, že nie je nakonfigurovaný
- Skontrolujte `TIKI_ROOT/temp/ajax-chat/Chat-config.php`.
- Overte zapisovateľnosť adresára `TIKI_ROOT/temp/` pre PHP.
- Spustite Install / repair.
- Skontrolujte ochranu adresára `temp/ajax-chat/` pred webovým prístupom.

### Inštalátor nevie vytvoriť `/chat`
- Overte práva PHP procesu na vytváranie adresárov a súborov v koreňovom adresári Tiki.
- Skontrolujte, či `/chat/` nepoužíva iná aplikácia.
- Cudzie `index.php`, `tiki-auth.php` ani `.htaccess` neprepisujte; vyriešte kolíziu URL.

### Chat sa otvorí, ale nenačíta CSS alebo JavaScript
- Skontrolujte `TIKI_ROOT/chat/.htaccess`.
- Overte Apache `mod_rewrite` a povolené `.htaccess`, prípadne nakonfigurujte ekvivalentné pravidlá servera.
- Skontrolujte URL `/chat/css/`, `/chat/js/` a `/chat/img/`.

### Používateľ sa nevie prihlásiť
- Používateľ musí byť prihlásený do TikiWiki.
- Skontrolujte stav účtu a **Enable Ajax Chat**.
- Pri problémoch s rolami skontrolujte presný názov moderátorskej skupiny.

### Chýba administračná položka
- Overte aktiváciu Extension Package.
- Otvorte `tiki-admin.php?page=tp_edizontn_ajaxchat` ako Tiki administrátor.
- Skontrolujte práva zápisu do `_custom/shared/`.

## Zálohovanie
Pred aktualizáciou alebo odinštalovaním zálohujte:
- databázové tabuľky `tiki_ajaxchat_*`;
- `vendor_custom/tiki-pkg-ajax-chat/`;
- `temp/ajax-chat/Chat-config.php`;
- package-managed súbory v `/chat/`;
- package-owned zmeny v `_custom/shared/custom.php`.

Konfigurácia obsahuje HMAC secret. Uchovávajte ju mimo verejného webového priestoru a nezverejňujte ju v issue ani logoch.

## Testy a CI
Workflow `.github/workflows/chat-checks.yml` vykonáva PHP syntax check, PHP testy nad MariaDB a kontrolu JavaScriptu. CI nenahrádza integračný test na cieľovej verzii TikiWiki.