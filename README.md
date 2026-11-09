# AJAX Chat for TikiWiki

TikiWiki Extension Package, ktorý integruje **AJAX Chat** priamo do TikiWiki. Používa Tiki používateľov, prihlasovaciu reláciu, skupiny, oprávnenia a existujúce databázové pripojenie.

## Čo balík poskytuje

- Samostatný chat na URL `/chat/` aj vloženie do wiki stránky.
- Verejné a súkromné miestnosti, súkromné správy a históriu.
- Roly používateľ, moderátor a administrátor odvodené od Tiki účtu a skupín.
- Administráciu nastavení, inštaláciu, opravu a odinštalovanie cez Tiki.
- Vlastnú databázovú vrstvu postavenú na TikiDb; samostatná databáza nie je potrebná.

## Požiadavky a stav

- TikiWiki **30.1** (cieľová verzia integrácie).
- PHP **8.1 alebo novšie**.
- Databáza používaná TikiWiki; chatové tabuľky vyžadujú InnoDB a utf8mb4.
- Webserver musí vedieť obslúžiť PHP a pravidlá `.htaccess` v adresári `/chat` (pri Apache).

Integrácia je vo vývoji a nebola potvrdená integračným testom na čistej živej inštalácii TikiWiki 30.1. Pred produkčným nasadením ju otestujte na stagingu a zálohujte databázu aj súbory. Automatické kontroly v GitHub Actions pokrývajú PHP lint, testy s MariaDB a kontrolu JavaScriptu.

## Rýchla inštalácia

1. Skopírujte **celý repozitár** do `TIKI_ROOT/vendor_custom/tiki-pkg-ajax-chat/`.
2. V Tiki otvorte **Admin → Packages** a balík aktivujte.
3. Prejdite na administračnú stránku **AJAX Chat settings**.
4. V časti **Maintenance** kliknite na **Install / reinstall / repair AJAX Chat**.
5. Po úspešnej inštalácii otvorte **Open Chat** alebo vložte chat do wiki stránky.

> Nekopírujte `src/` do koreňa Tiki a nepremiestňujte ho do `_CHAT/`. Balík musí zostať celý v `vendor_custom/tiki-pkg-ajax-chat/`.

## Vloženie do wiki stránky

Základné vloženie:

```text
{package package="edizontn/ajaxchat" plugin="ajaxchat"}
```

Vlastná výška iframe (200–1200 px):

```text
{package package="edizontn/ajaxchat" plugin="ajaxchat" height="520"}
```

Používateľ musí byť prihlásený do TikiWiki. Anonymný prístup chatový plugin nepovoľuje.

## Umiestnenie a databáza

- Balík: `TIKI_ROOT/vendor_custom/tiki-pkg-ajax-chat/`
- Jadro chatu: `vendor_custom/tiki-pkg-ajax-chat/src/`
- Vygenerovaná konfigurácia a HMAC secret: `TIKI_ROOT/temp/ajax-chat/Chat-config.php`
- Verejný vstup: `TIKI_ROOT/chat/` (vytvára ho inštalátor)
- Šesť runtime tabuliek: `tiki_ajaxchat_*`
- Nastavenia balíka: `tiki_ajaxchat_settings`

Konfiguráciu ani secret neupravujte v repozitári. Inštalátor ich generuje mimo balíka, aby aktualizácia zdrojových súborov neprepísala lokálne tajomstvá.

## Dokumentácia

Podrobné návody sú v [dokumentačnej wiki](docs/wiki/Home.md):

- [Architektúra a štruktúra súborov](docs/wiki/Architecture.md)
- [Inštalácia a aktualizácia](docs/wiki/Installation.md)
- [Nastavenia a oprávnenia](docs/wiki/Configuration.md)
- [Údržba a riešenie problémov](docs/wiki/Maintenance.md)

## Licencia

Pozri [THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md) a licenčné súbory v `src/`.