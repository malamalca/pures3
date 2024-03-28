# Izračun gradbene fizike po PURES 3

[![CI](https://github.com/malamalca/pures3/actions/workflows/ci.yml/badge.svg)](https://github.com/malamalca/pures3/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/malamalca/pures3/branch/main/graph/badge.svg?token=RBTZLQY5Z2)](https://codecov.io/gh/malamalca/pures3)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%207-brightgreen.svg?style=flat-square)](https://github.com/phpstan/phpstan)

Projekt je PHP aplikacija za izračun gradbene fizike po PURES 3 pravilniku.

## NOVO - Energijske kartice

![Energijske kartice](https://github.com/malamalca/pures3/blob/1f34d5514117c985e1f841e68ea164e1963e6686/images/kartice.png)

## Namestitev aplikacije v sistemu Windows

1. Prenesite [PHP](https://windows.php.net/downloads/releases/php-8.2.6-nts-Win32-vs16-x64.zip)
2. Zip datoteko razširite na trdi disk računalnika, v mapo C:\php
3. Zaženite ukazno vrstico (tipka "Win"+R, vpišete `cmd` in "Enter")
4. PHP dodajte v seznam privzetih poti z ukazom `setx path "%path%;C:\php\"`
5. Prenesite in namestite [Composer](https://getcomposer.org/Composer-Setup.exe).
6. Namestite Pures 3 z ukazom `composer create-project --no-dev malamalca/pures3`.

## Uporaba

Projekti za izračun gradbene fizike se nahajajo v podmapi `/projects`. Privzeto je v aplikaciji že vključen testni projekt v podmapi `/projects/TestniProjekt`.

Analizo projekta se izvede iz ukazne vrstice z ukazom:
```
bin/pures IzracunProjekta TestniProjekt
```

Po preračunu se izkaz gradbene fizike shrani v podmapo /projects/TestniProjekt/pdf/izkaz.pdf
