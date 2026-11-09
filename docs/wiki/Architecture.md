# Architektúra a štruktúra súborov

## Prehľad
Repozitár obsahuje Tiki Extension Package (metadata, administráciu, nastavenia, wiki plugin a inštalátor) a AJAX Chat engine v `src/`, doplnený o Tiki autentifikačný a databázový mostík.

Tiki poskytuje používateľskú identitu, session, skupiny a databázové pripojenie. Chat nevytvára samostatnú databázu.

## Štruktúra repozitára
```text
tiki-pkg-ajax-chat/
├── composer.json
├── tiki-package.json
├── README.md
├── README-TIKI-BRIDGE.md
├── admin/                         # administračná PHP logika
├── templates/admin/               # Smarty šablóna administrácie
├── lib/
│   ├── PackageInstaller.php       # install/repair/uninstall
│   ├── SettingsDefinitions.php    # katalóg nastavení a limity
│   ├── SettingsStore.php          # ukladanie a migrácia nastavení
│   └── wiki-plugins/ajaxchat.php  # wiki plugin
├── prefs/                         # package preference súbory
├── tests/                         # PHP a JavaScript testy
├── .github/workflows/             # CI
└── src/
    ├── index.php                  # vstup AJAX Chat engine
    ├── tiki-auth.php              # Tiki autentifikačný endpoint
    ├── chat.sql                   # schéma šiestich runtime tabuliek
    ├── lib/tiki-bootstrap.php     # Tiki runtime a TikiDb adaptér
    ├── css/ js/ img/               # štýly, skripty a obrázky
    ├── sounds/ flash/ socket/      # ďalšie zdroje a voliteľný socket server
    └── lib/                       # triedy, jazykové súbory a šablóny
```

## Kľúčové súbory
| Súbor/adresár | Úloha |
|---|---|
| `composer.json` | Identita `edizontn/ajaxchat`, typ `tiki-package`, PHP požiadavka a PSR-4 |
| `tiki-package.json` | Tiki package namespace |
| `admin/` a `templates/admin/` | Administračná stránka a jej UI |
| `lib/PackageInstaller.php` | Inštalácia, kontrola, oprava a odinštalovanie |
| `lib/SettingsDefinitions.php` | Definície, predvolené hodnoty a limity nastavení |
| `lib/SettingsStore.php` | Ukladanie nastavení a migrácia starších Tiki preferences |
| `lib/wiki-plugins/ajaxchat.php` | Vloženie chatu do wiki stránky |
| `src/lib/tiki-bootstrap.php` | Načítanie Tiki runtime a databázového adaptéra |
| `src/tiki-auth.php` | Overenie Tiki session a vydanie krátkodobého podpísaného tokenu |

## Cesty na serveri
- Package root: `TIKI_ROOT/vendor_custom/tiki-pkg-ajax-chat/`
- Chat engine: `.../vendor_custom/tiki-pkg-ajax-chat/src/`
- Chránená konfigurácia: `TIKI_ROOT/temp/ajax-chat/Chat-config.php`
- Verejný endpoint: `TIKI_ROOT/chat/index.php`
- Auth endpoint: `TIKI_ROOT/chat/tiki-auth.php`
- Asset routing: `TIKI_ROOT/chat/.htaccess`

Inštalátor vytvorí v `/chat` malé vstupné súbory, ktoré odovzdajú spracovanie súborom v balíku. Assety `css`, `js`, `img`, `sounds` a `flash` sa mapujú na `src/`. Cudzie existujúce súbory neprepisuje.

## Autentifikácia
1. Používateľ sa prihlási do TikiWiki.
2. Chat načíta generovanú konfiguráciu a spustí Tiki bootstrap.
3. `src/tiki-auth.php` overí Tiki session a stav účtu.
4. Rola sa určí podľa Tiki administrátorských práv a moderátorských skupín.
5. Vydá sa podpísaný token s platnosťou 120 sekúnd.

Anonymný prístup nie je povolený.

## Databáza
Chat používa TikiDb a rovnakú databázu ako Tiki. Šesť runtime tabuliek: `tiki_ajaxchat_online`, `tiki_ajaxchat_messages`, `tiki_ajaxchat_bans`, `tiki_ajaxchat_invitations`, `tiki_ajaxchat_rooms`, `tiki_ajaxchat_room_members`. Nastavenia balíka sú v `tiki_ajaxchat_settings`.

## Bezpečnosť
Konfigurácia a HMAC secret sú mimo package root v chránenom `temp/ajax-chat/`. Admin POST akcie vyžadujú Tiki administrátorské oprávnenie a CSRF ticket. Inštalátor odmieta prepísať cudzie súbory. Neupravujte Tiki core.