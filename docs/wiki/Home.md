# AJAX Chat – dokumentácia

Dokumentácia balíka **AJAX Chat for TikiWiki**.

## Obsah
- [Architektúra a štruktúra súborov](Architecture.md)
- [Inštalácia a aktualizácia](Installation.md)
- [Nastavenia a oprávnenia](Configuration.md)
- [Údržba a riešenie problémov](Maintenance.md)

## Rýchly prehľad
AJAX Chat je TikiWiki Extension Package. Celý balík sa nasadzuje do `vendor_custom/tiki-pkg-ajax-chat/`. Tiki spravuje identitu a oprávnenia používateľov; chat používa databázové pripojenie Tiki cez TikiDb.

**Dôležité:** adresár `src/` zostáva v balíku. Nekopírujte ho do koreňa Tiki ani do `_CHAT/`.

Cieľová platforma: TikiWiki 30.1 a PHP 8.1+. Integrácia je vo vývoji; pred produkčným nasadením ju otestujte na stagingu.