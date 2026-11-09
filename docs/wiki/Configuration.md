# Nastavenia a oprávnenia

Nastavenia sa spravujú na stránke `tiki-admin.php?page=tp_edizontn_ajaxchat`. Prístup má Tiki administrátor. Hodnoty sa ukladajú do `tiki_ajaxchat_settings`, nie do globálnej tabuľky `tiki_preferences`.

## General Preferences
- **Enable Ajax Chat** – povolí alebo zakáže chat.
- **Default language** – predvolený jazyk; výber vychádza z jazykov dostupných v engine.
- **Default chat style** – predvolený vzhľad.
- **Socket Server** – zapnutie a parametre voliteľného socket servera (hostname, IP, port, Chat ID). Vyžaduje samostatne prevádzkovaný server; inak nechajte vypnuté.

## General Settings
- **Logs** – automatické mazanie starých logov, prístup používateľov k logom, obdobie vyhľadávania, počet záznamov a retenčná lehota.
- **Cookies** – životnosť preferenčných cookies a požiadavka na HTTPS cookie.
- **Open/close** – otváracia/zatváracia hodina a dni dostupnosti.
- **Others** – časové pásmo, časové okno histórie a počet správ načítaných v jednej požiadavke.

Dni týždňa: 0 = nedeľa, 1 = pondelok, …, 6 = sobota. Otváracia hodina je 0–23, zatváracia 1–24.

## Users/Group Settings
- **Maximum concurrent users** – limit bežných súčasne prihlásených používateľov.
- **Default ban duration** – predvolená dĺžka zákazu v minútach.
- **Inactive user timeout/check interval** – čas neaktivity a interval kontroly.
- **User privileges** – zmena prezývky, jej maximálna dĺžka a mazanie vlastných správ.
- **Administrator/Moderator** – moderátorská Tiki skupina, skrytie administrátora v súkromných miestnostiach, uzavretie chatu pre neadministrátorov a kontrola IP adresy.

Moderátorská skupina sa vyberá z existujúcich Tiki skupín. Tiki administrátori majú vždy najvyššiu rolu. Moderátor automaticky nezískava prístup do súkromnej miestnosti.

## Chat Settings
- maximálna dĺžka správy a počet správ za minútu;
- povolenie súkromných miestností, súkromných kanálov a súkromných správ;
- zobrazovanie systémových správ a načítanie histórie pri vstupe do kanála;
- názov a ID predvolenej verejnej miestnosti;
- limity počtu a dĺžky názvov súkromných miestností;
- prefixy/suffixy miestností a zmenených prezývok;
- názov ChatBotu a zoznamy ID kanálov pre zobrazenie, históriu a logy.

Číselné limity a dĺžkové obmedzenia validuje katalóg nastavení; neplatné hodnoty sa pri ukladaní odmietnu.

## Odporúčané základné nastavenie
1. Zapnite **Enable Ajax Chat**.
2. Vyberte predvolený jazyk a štýl.
3. Nastavte moderátorskú skupinu, ak chcete delegovať moderovanie.
4. Socket server nechajte vypnutý, pokiaľ ho samostatne neprevádzkujete.
5. Pri HTTPS ponechajte **Require HTTPS for chat cookies** zapnuté.
6. Nastavte históriu, limity správ a pravidlá súkromných miestností.
7. Uložte nastavenia a otestujte prístup používateľa aj moderátora.

## Roly a autentifikácia
- **Tiki Admin** – najvyššia rola v chate.
- **Moderator** – člen nakonfigurovanej Tiki skupiny.
- **User** – prihlásený Tiki používateľ bez moderátorskej roly.

Anonymné prihlásenie je zakázané. Chat nepoužíva samostatné heslá ani používateľské účty.

## Uloženie dát
- Nastavenia: `tiki_ajaxchat_settings`.
- Runtime konfigurácia a secret: `temp/ajax-chat/Chat-config.php`.
- Šablóna konfigurácie: `src/lib/Chat-config.php.example`.

Bežné nastavenia meňte cez administračné rozhranie; generovaný konfiguračný súbor neupravujte ručne.