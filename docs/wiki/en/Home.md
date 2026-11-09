# AJAX Chat documentation

English documentation for the AJAX Chat for TikiWiki package.

## Contents
- [Architecture and repository structure](Architecture.md)
- [Installation and upgrades](Installation.md)
- [Configuration and permissions](Configuration.md)
- [Maintenance and troubleshooting](Maintenance.md)

## Overview
AJAX Chat is a TikiWiki Extension Package. Deploy the complete package to vendor_custom/tiki-pkg-ajax-chat/. TikiWiki provides user identity, sessions, groups, permissions, and database connectivity through TikiDb.

**Important:** Keep the src directory inside the package. Do not copy it into the Tiki root or move it to _CHAT/.

Target platform: TikiWiki 30.1 and PHP 8.1+. The integration is under development; validate it on staging before production use.