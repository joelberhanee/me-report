# me-report

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/joelberhanee/me-report/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/joelberhanee/me-report/?branch=main)

[![Code Coverage](https://scrutinizer-ci.com/g/joelberhanee/me-report/badges/coverage.png?b=main)](https://scrutinizer-ci.com/g/joelberhanee/me-report/?branch=main)

[![Build Status](https://scrutinizer-ci.com/g/joelberhanee/me-report/badges/build.png?b=main)](https://scrutinizer-ci.com/g/joelberhanee/me-report/build-status/main)

<img src="./assets/images/IMG_8789.WEBP" alt="Projektbild" width="400">

Detta är ett Symfony-projekt som använder MVC-struktur för att bygga en webbapplikation. Följ stegen nedan för att klona och köra webbplatsen lokalt.

## Om projektet

Detta är ett Symfony-baserat webbprojekt. Applikationen innehåller både traditionella sidor och ett JSON-API, samt ett kortspel (Blackjack). Projektet är en del av kursen Objektorienterade webbteknologier.

Projektet innehåller bland annat:

- Formulärhantering och sessionshantering
- Ett Blackjack-spel där spelaren kan spela 1–3 händer samtidigt
- En API-del med POST/GET-routes
- Täckningstester med PHPUnit
- Dokumentation och kodkvalitetsgranskning med Scrutinizer, PHPDoc och PhpMetrics

## Installera och Komma Igång

För att komma igång med projektet, följ dessa steg:

1. Klona repot:
   ```bash
   git clone https://github.com/joelberhanee/me-report

   cd "me-report"

2. composer install

3. npm install

4. composer require twig
- composer require symfony/webpack-encore-bundle

5. Alternativ A – Symfony CLI: 
- symfony server:start

6. Alternativ B – Inbyggd PHP-server: 
- php -S localhost:8000 -t public

7. https://127.0.0.1:8000/

