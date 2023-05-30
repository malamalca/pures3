# Izračun gradbene fizike po PURES 3

[![CI](https://github.com/malamalca/pures3/actions/workflows/ci.yml/badge.svg)](https://github.com/malamalca/pures3/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/malamalca/pures3/branch/main/graph/badge.svg?token=RBTZLQY5Z2)](https://codecov.io/gh/malamalca/pures3)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%207-brightgreen.svg?style=flat-square)](https://github.com/phpstan/phpstan)

Projekt je PHP aplikacija za izračun gradbene fizike po PURES 3 pravilniku.

## Namestitev aplikacije

1. Prenesite [Composer](https://getcomposer.org/doc/00-intro.md) oz. ga posodobite na zadnjo verzijo `composer self-update`.
2. V ukazni vrstici se postavite v zeljeno mapo in zaženite `composer create-project --prefer-dist --no-dev malamalca/pures3`.

## Uporaba

Projekti za izračun gradbene fizike se nahajajo v podmapi `/projects`. Privzeto je v aplikaciji že vključen testni projekt v podmapi `/projects/TestniProjekt`.

Analizo projekta se izvede iz ukazne vrstice z ukazom:
```
bin/pures IzracunProjekta TestniProjekt
```
