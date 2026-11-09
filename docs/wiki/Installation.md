# Inštalácia a aktualizácia

## Požiadavky
- TikiWiki 30.1 (cieľová verzia integrácie).
- PHP 8.1 alebo novšie.
- Databáza Tiki s InnoDB a utf8mb4.
- PHP proces musí mať právo čítať package súbory a zapisovať do `temp/`, koreňa Tiki a `_custom/shared/` podľa vykonávanej operácie.
- Pri Apache musí byť povolený `mod_rewrite` a pravidlá `.htaccess`, alebo ekvivalentné pravidlá servera.

Integrácia zatiaľ nemá potvrdený integračný test na čistej živej inštalácii TikiWiki. Najskôr ju nasaďte na staging a vytvorte zálohu.

## 1. Nasadenie
Skopírujte **celý repozitár** do:
```text
TIKI_ROOT/vendor_custom/tiki-pkg-ajax-chat/
```
V koreňovom adresári musia zostať `composer.json` a `tiki-package.json`.

**Nekopírujte `src/` do `TIKI_ROOT/` ani ho nepremiestňujte do `_CHAT/`.** Nevytvárajte vlastnú Tiki route a neupravujte Tiki core.

## 2. Aktivácia
1. Prihláste sa ako Tiki administrátor.
2. Otvorte **Admin → Advanced → Packages**.
3. Vyhľadajte a aktivujte balík `edizontn/ajaxchat`.
4. Otvorte **AJAX Chat settings** v časti **Admin → Advanced**.

Povolenie package a zobrazenie odkazu na chat v používateľskom menu sú dve odlišné veci. Aktivácia balíka sama osebe nepridá položku AJAX Chat do hlavného navigačného menu webu. Ak chcete mať chat dostupný z menu, pridajte odkaz na `/chat/` do príslušného Tiki menu. Ak sa nezobrazuje ani administračná stránka **AJAX Chat settings**, skontrolujte, či je balík skutočne povolený a či používate účet s administrátorskými oprávneniami.

## 3. Inštalácia
Na stránke **AJAX Chat settings → Maintenance** kliknite na **Install / reinstall / repair AJAX Chat**. Inštalátor:
1. overí Tiki root a package root;
2. vytvorí chránený `TIKI_ROOT/temp/ajax-chat/`;
3. vygeneruje `Chat-config.php` a náhodný HMAC secret;
4. nastaví TikiDb ako databázové pripojenie;
5. vytvorí/skontroluje šesť chatových tabuliek;
6. vytvorí `TIKI_ROOT/chat/index.php`, `tiki-auth.php` a `.htaccess`;
7. skontroluje pripravenosť inštalácie.

Konfigurácia je mimo package root, aby ju aktualizácia repozitára neprepísala. Ak už existuje cudzí súbor v `/chat` alebo nespravovaná konfigurácia, inštalátor operáciu zastaví.

## 4. Overenie
- Stav schémy na karte Maintenance má byť **complete**.
- Tlačidlo **Open Chat** musí byť aktívne.
- Otvorte `https://vas-web/chat/` a overte prihlásenie.
- Otestujte vloženie pluginu do wiki stránky a oprávnenia bežného používateľa/moderátora.

## 5. Vloženie do wiki
```text
{package package="edizontn/ajaxchat" plugin="ajaxchat"}
```
S výškou iframe:
```text
{package package="edizontn/ajaxchat" plugin="ajaxchat" height="520"}
```
Parameter `height` je v pixeloch, rozsah 200–1200. Používateľ musí byť prihlásený.

## Aktualizácia
1. Zálohujte databázu a celý package adresár.
2. Aktualizujte `vendor_custom/tiki-pkg-ajax-chat/`.
3. Zachovajte lokálne úpravy a generovanú konfiguráciu v `temp/ajax-chat/`.
4. Podľa potreby spustite **Install / reinstall / repair AJAX Chat**.
5. Otestujte `/chat/` aj wiki plugin.

Verzovanie a automatické migrácie databázovej schémy zatiaľ nie sú plne rozpracované. Pred produkčnou aktualizáciou overte kompatibilitu na stagingu.
