# AJAX-Chat-MZ

**Slovenská dokumentácia | English documentation**

AJAX-Chat-MZ je upravená verzia AJAX-Chat určená na integráciu s CMS TikiWiki. Cieľom je poskytnúť klasický webový chat, ktorý používa účty a oprávnenia TikiWiki a dá sa spravovať z administrácie TikiWiki.

> **Stav projektu / Project status**
>
> Projekt je vo vývoji. Integrácia s TikiWiki 30.1, inštalátor, aktualizácie a databázové migrácie ešte nie sú kompletne implementované a overené. Nasledujúci postup opisuje plánovaný spôsob inštalácie, nie návod na nasadenie aktuálneho vývojového snapshotu na produkčný web. Na produkčnom webe zatiaľ neinštalujte túto verziu.

## Slovensky

### Čo projekt ponúka

- Chat v PHP a JavaScripte s priebežnou AJAX komunikáciou.
- Prihlasovanie cez používateľský účet TikiWiki – chat nebude mať samostatné heslá.
- Používanie skupín a oprávnení TikiWiki na určenie administrátorov a moderátorov.
- Súkromné miestnosti a pozývanie používateľov.
- Formátovanie blokov zdrojového kódu v správach.
- Samostatnú stránku chatu a možnosť vloženia chatu do stránok TikiWiki.
- Správu nastavení a aktualizácií cez administráciu TikiWiki.

### Požiadavky

Plánovaným cieľom je TikiWiki 30.1 alebo kompatibilná novšia verzia, PHP podporované danou verziou TikiWiki a databáza, ktorú už používa TikiWiki.

**Composer nebude požiadavkou na produkčnom serveri.** Inštalácia a aktualizácia majú byť dostupné cez webové administračné rozhranie. Composer môže vývojár použiť pri príprave balíka, ale správca webu ho nebude musieť spúšťať.

### Ako bude prebiehať inštalácia

Toto je plánovaný postup po dokončení inštalátora:

1. **Urobte zálohu.** Pred inštaláciou si zálohujte súbory TikiWiki aj celú databázu.
2. **Pripravte prvý inštalačný balík.** Jednorazovo sprístupnite malý inštalačný súbor (bootstrap). Podľa možností hostingu sa nahrá cez administračné rozhranie TikiWiki alebo cez FTP/správcu súborov.
3. **Spustite inštaláciu v TikiWiki.** Prihláste sa ako administrátor a otvorte stránku rozšírení. Inštalátor stiahne a overí balík AJAX-Chat-MZ a uloží jeho integračnú časť do adresára určeného pre vlastné rozšírenia TikiWiki.
4. **Nainštalujte chatové jadro.** Inštalátor uloží chat do adresára `vendor_custom/tiki-pkg-ajax-chat/src` v koreňovom adresári TikiWiki. Cestu a URL zistí pri inštalácii a uloží ich; pri bežnom používaní ich nebude opakovane zisťovať.
5. **Skontrolujte databázu.** Chat použije tú istú databázu a databázové pripojenie ako TikiWiki. Vytvorí vlastné tabuľky s prefixom `tikiwiki_ext_ajax_chat_`. Existujúce tabuľky a údaje TikiWiki sa nebudú meniť.
6. **Dokončite nastavenie.** V administrácii TikiWiki nastavíte skupiny moderátorov a ďalšie dostupné možnosti. Administrátori TikiWiki budú mať najvyššie oprávnenia aj v chate.
7. **Otestujte chat.** Overte prihlásenie, odhlásenie, odosielanie správ, moderovanie a súkromné miestnosti ešte pred sprístupnením používateľom.

### Aktualizácia

Aktualizácia sa bude spúšťať ručne administrátorom v TikiWiki – nie automaticky na pozadí.

- systém zobrazí dostupnú verziu a prehľad zmien;
- pred výmenou súborov vytvorí zálohu;
- zachová históriu správ, nastavenia, prihlasovacie údaje a tajné kľúče;
- prípadné zmeny databázovej štruktúry oddelí od aktualizácie súborov a vyžiada si potvrdenie;
- pri chybe umožní návrat k predchádzajúcej verzii.

Tieto funkcie sa ešte implementujú a musia prejsť testami.

### Databáza a zálohovanie

Chat nebude potrebovať samostatnú databázu ani samostatného databázového používateľa. Použije databázové pripojenie TikiWiki a vlastné tabuľky s prefixom:

`tikiwiki_ext_ajax_chat_`

Tým sa zjednoduší zálohovanie a obnova – záloha databázy TikiWiki bude obsahovať aj chat. Administrácia rozšírenia bude mať vlastnú kontrolu existencie tabuliek, stĺpcov, indexov a verzie schémy. Samotná kontrola databázy TikiWiki nemusí automaticky poznať štruktúru tohto rozšírenia.

### Bezpečnosť

- TikiWiki je autoritou pre identitu používateľa a jeho skupiny.
- Tajné kľúče a citlivé nastavenia nesmú byť priamo verejne dostupné cez web.
- Inštalátor a aktualizátor musia overovať oprávnenie administrátora a chrániť formuláre proti CSRF.
- Aktualizácia nesmie prepísať konfiguráciu ani databázové údaje.
- Pred produkčným nasadením je potrebný bezpečnostný a integračný test na konkrétnej verzii TikiWiki a PHP.

### Vývoj a testovanie

Tento repozitár obsahuje chatové jadro a rozpracovanú integráciu s TikiWiki. Jednotlivé časti sa priebežne upravujú. Aktuálne testy a ich výsledky treba považovať za orientačné; úspešný test samostatného chatu neznamená, že je overená integrácia s TikiWiki.

Pred produkčným nasadením musia byť otestované minimálne:
- inštalácia na čistej TikiWiki;
- aktualizácia a návrat po neúspešnej aktualizácii;
- kontrola a migrácia databázovej schémy;
- práva používateľov, administrátorov a moderátorov;
- súkromné miestnosti a pozvánky;
- embed chatu na stránkach TikiWiki.

### Pôvod a pomoc pri vývoji

Projekt vychádza z open-source projektu [AJAX Chat](https://github.com/frug/AJAX-Chat) od Frug/AJAX-Chat. Obsahuje úpravy a rozšírenia pre potreby Mikrozone a integráciu s TikiWiki. Rešpektujte licenciu pôvodného projektu a informácie v súbore [THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md).

**Projekt bol vytvorený a upravovaný s pomocou ChatGPT od OpenAI (GPT-6).** Návrhy, generovanie a kontrola častí kódu a dokumentácie prebiehajú v spolupráci s autorom projektu. Zodpovednosť za výsledný kód, jeho kontrolu, testovanie a nasadenie zostáva na správcovi projektu.

---

## English

### Overview

AJAX-Chat-MZ is an adapted version of AJAX Chat intended to integrate with the TikiWiki CMS. The goal is to provide a traditional web-based chat that uses TikiWiki accounts and permissions and can be managed from the TikiWiki administration interface.

### Features

- PHP and JavaScript chat with AJAX-based updates.
- TikiWiki-based authentication; no separate chat passwords.
- TikiWiki groups and permissions used to identify administrators and moderators.
- Private rooms and user invitations.
- Formatted source-code blocks in messages.
- A standalone chat page and an option to embed the chat in TikiWiki pages.
- Administration and updates managed through TikiWiki.

### Requirements

The target is TikiWiki 30.1 or a compatible newer release, a PHP version supported by that TikiWiki release, and the database already used by TikiWiki.

**Composer will not be required on the production server.** Installation and updates are intended to be handled through a web-based administration interface. Developers may use Composer while preparing the package, but site administrators will not need to run it.

### Installation procedure

The following is the planned procedure once the installer is complete:

1. **Back up your site.** Back up both the TikiWiki files and the complete database.
2. **Prepare the initial installer.** Make the small bootstrap installer available. Depending on hosting capabilities, it will be uploaded through TikiWiki administration or by FTP/file manager.
3. **Start installation in TikiWiki.** Sign in as a TikiWiki administrator and open the extensions administration page. The installer will download and verify the AJAX-Chat-MZ package and place its integration files in TikiWiki's custom extension location.
4. **Install the chat engine.** The installer will deploy the chat into the `vendor_custom/tiki-pkg-ajax-chat/src` directory in the TikiWiki document root. It will detect and store the filesystem path and URL during installation, rather than detecting them on every request.
5. **Check the database.** The chat will reuse TikiWiki's database connection. It will create its own tables using the `tikiwiki_ext_ajax_chat_` prefix. Existing TikiWiki tables and data will not be modified.
6. **Configure the integration.** Use TikiWiki administration to select moderator groups and configure the available options. TikiWiki administrators will always have the highest chat privileges.
7. **Test before launch.** Verify login, logout, message sending, moderation and private rooms before making the chat available to users.

### Updates

Updates will be started manually by an administrator in TikiWiki; they will not run silently in the background.

- the interface will show the available version and a change summary;
- files will be backed up before replacement;
- chat history, settings, credentials and secrets will be preserved;
- database schema changes will be separated from file updates and require explicit confirmation;
- a failed update will provide a way to restore the previous version.

These features are still planned and must be implemented and tested.

### Database and backups

The chat will not require a separate database or database user. It will reuse TikiWiki's database connection and create its own tables with this prefix:

`tikiwiki_ext_ajax_chat_`

A normal TikiWiki database backup will therefore include chat data. The extension will provide its own schema checks for required tables, columns, indexes and schema version. TikiWiki's built-in database checker may not automatically understand the schema of a third-party extension.

### Security

- TikiWiki remains the source of truth for user identity and group membership.
- Secrets and sensitive configuration must not be directly accessible over the web.
- Installer and updater actions must require administrator privileges and CSRF protection.
- Updates must not overwrite configuration or database content.
- Production deployment requires security and integration testing against the exact TikiWiki and PHP versions in use.

### Development and testing status

This repository contains the chat engine and work in progress on the TikiWiki integration. Components are being revised continuously. Existing tests are indicative only: a successful standalone chat test does not mean that the TikiWiki integration has been validated.

Before production deployment, at least the following must be tested:
- installation on a clean TikiWiki instance;
- update and rollback after a failed update;
- database schema checks and migrations;
- user, administrator and moderator permissions;
- private rooms and invitations;
- embedding the chat in TikiWiki pages.

### Origin and development assistance

This project is based on the open-source [AJAX Chat](https://github.com/frug/AJAX-Chat) project by Frug/AJAX-Chat. It contains adaptations and extensions for Mikrozone and TikiWiki integration. Please observe the original project's license and see [THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md).

**This project was created and developed with assistance from OpenAI ChatGPT (GPT-6).** Design proposals, code and documentation generation, and code review are carried out in collaboration with the project author. Responsibility for the final code, its review, testing and deployment remains with the project maintainer.


## TikiWiki package layout

The Tiki Extension Package is maintained in [tiki-pkg-ajax-chat/](tiki-pkg-ajax-chat/). Its `src/` directory contains the complete application previously stored in the repository's `chat/` directory. In TikiWiki, keep it at `vendor_custom/tiki-pkg-ajax-chat/src/`; do not extract it into the document root.
