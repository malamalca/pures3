# Analiza sistema za izračun gradbene fizike (GF) — odprte postavke

> Pregled kode `src/Calc/GF`, `src/Lib` in povezanih delov (PURES 3 / TSG-1-004).
> Datum: 2026-07-17. Že popravljene postavke so odstranjene; spodaj so le **odprte**.
>
> Legenda resnosti: 🔴 verjeten bug (napačen rezultat/izjema) · 🟠 nedoslednost/tveganje · 🟡 čistost kode / izboljšava.

---

## 🔴 Verjetni bugi

### 1. Potencialno deljenje z nič pri faktorju izkoristka za hlajenje (PHP 8 vrže izjemo)  ⬜ ODPRTO
**Datoteka:** [src/Calc/GF/Cone/Cona.php:615-622](src/Calc/GF/Cone/Cona.php#L615)

```php
if ($vsotaPonorov_hlajenje == 0.0) {
    $gama_hlajenje = -1;
} else {
    $gama_hlajenje = $vsotaVirov_hlajenje / $vsotaPonorov_hlajenje;   // lahko = 0.0
}
...
if (1 / $gama_hlajenje <= 2) {   // če je gama == 0 → DivisionByZeroError (PHP >= 8.0)
```

Če so dobitki hlajenja natanko 0 (npr. cona brez oken, `notranjiViri->hlajenje = 0`, čisto adiabatni ovoj) in ponori ≠ 0, je `gama_hlajenje = 0.0`, izraz `1 / $gama_hlajenje` pa v PHP 8 **vrže `DivisionByZeroError`** (composer zahteva `php: ^8.1`).

Veja za **ogrevanje** je robustna ([Cona.php:594](src/Calc/GF/Cone/Cona.php#L594): `$gama_ogrevanje > -0.1 && $gama_ogrevanje < 2`), veja za hlajenje pa najprej deli in šele nato preverja predznak — nedosledno.

**Predlog:** preveri `$gama_hlajenje` pred deljenjem, npr.:
```php
if ($gama_hlajenje > 0 && 1 / $gama_hlajenje <= 2) { ... }
elseif ($gama_hlajenje <= 0) { $this->ucinekPonorov[$mesec] = 1; }
```

---

## 🟠 Nedoslednosti in tveganja

### 2. Dve različni definiciji "ogrevalne/hladilne sezone"
- [Calc::jeMesecBrezOgrevanja()](src/Lib/Calc.php#L73): `$mesec > 2 && $mesec < 9` → apr–sep (indeks 3–8).
- [TransparentenElementOvoja.php:261](src/Calc/GF/Cone/ElementiOvoja/TransparentenElementOvoja.php#L261): `$mesec > 4 && $mesec < 9` → jun–sep (indeks 5–8) za izbiro faktorja senčenja ovir.

Če je razlika namenska (senčenje ovir vs. temperaturni režim), naj bo dokumentirana; sicer je vir subtilnih napak. Priporočilo: obe meji definiraj kot poimenovani konstanti in dodaj komentar, zakaj se razlikujeta.

### 3. Singleton `EvalMath::getInstance()` ignorira `$options` po prvi inicializaciji
**Datoteka:** [src/Lib/EvalMath.php:152-159](src/Lib/EvalMath.php#L152)

```php
public static function getInstance($options = []) {
    if (static::$instance === null) { static::$instance = new EvalMath($options); }
    return static::$instance;   // ob naslednjih klicih se $options zavržejo
}
```

Vsi klici sicer podajajo enake opcije `['decimalSeparator' => '.', 'thousandsSeparator' => '']`, zato trenutno ni napake. Vseeno je to "foot-gun": v konstruktorju ([:173-177](src/Lib/EvalMath.php#L173)) se najprej preberejo lokalni ločila iz `localeconv()`, ki bi na napačnem locale-u (npr. decimalna vejica) prevladala, če ne bi prvi klic pravočasno podal `.`. Priporočilo: opcije naj bodo eksplicitne in neodvisne od locale, ali pa naj se instanca ne cache-a z locale-odvisnim stanjem.

### 4. `array_sum_values(&$a, $b)` — zavajajoča referenca
**Datoteka:** [config/funcs.php:45](config/funcs.php#L45) — parameter `&$a` je podan po referenci, a funkcija `$a` **ne spreminja** (kopira v `$ret` in vrača). Vsi klicatelji uporabljajo `$x = array_sum_values($x, $y)`, zato dela, a je `&` odveč in zavaja. Enako [array_subtract_values()](config/funcs.php#L61). Priporočilo: odstrani `&`.

### 5. Trda (hardcoded) zemljepisna širina 40° za senčenje
**Datoteka:** [src/Calc/GF/Cone/ElementiOvoja/TransparentenElementOvoja.php:203](src/Calc/GF/Cone/ElementiOvoja/TransparentenElementOvoja.php#L203)

`$zemljepisnaSirina = 40;` je fiksno vzidano v izračun senčenja nadstreška/stranskih ovir. Slovenija je na ~45,5–46,9° S. Če je 40° namensko izbrana kalibracijska vrednost tabele pomožnih faktorjev, naj bo to komentirano; sicer je smiselno vrednost prevzeti iz podatkov o okolju/lokaciji projekta.

---

## 🟡 Čistost kode in manjše izboljšave

### 6. Magične konstante v fiziki sevanja
V [NetransparentenElementOvoja.php:231-233](src/Calc/GF/Cone/ElementiOvoja/NetransparentenElementOvoja.php#L231) in [TransparentenElementOvoja.php:283-287](src/Calc/GF/Cone/ElementiOvoja/TransparentenElementOvoja.php#L283) se ponavljajo `$hri = 4.14`, `$dTsky = 11`, `$Rse = 0.04`, `$Fic = 0.9`. Priporočilo: centraliziraj kot poimenovane konstante (npr. v `Calc` ali TSG lookup) — trenutno je `Rse` enkrat vzeto iz `konstrukcija->Rse`, drugič trdo `0.04`, kar je lahko nedosledno.

### 7. `@phpstan-ignore-next-line` na več mestih v `CalcKonstrukcije::konstrukcija()`
[CalcKonstrukcije.php:70-80](src/Lib/CalcKonstrukcije.php#L70) — vzorec `$material->x = $material->x ?? $libraryMaterial->x` na dinamičnem `stdClass`. Razmislek o tipiziranem DTO (npr. `Material` value-object) bi odpravil ignore-je in dvignil raven varnosti (projekt je na PHPStan level 7).

### 8. Ponavljajoč se `cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023)`
Klic je raztresen po ~8 mestih ([Cona.php](src/Calc/GF/Cone/Cona.php), [NetransparentenElementOvoja.php](src/Calc/GF/Cone/ElementiOvoja/NetransparentenElementOvoja.php) ...) z vzidanim letom 2023. Priporočilo: `Calc::steviloDni($mesec)` ali predizračunana konstanta `Calc::DNI_V_MESECU`, da je referenčno leto enotno določeno na enem mestu (in se ne pozabi popraviti v prestopnem letu, če bo kdaj relevantno).

---

## Predlog prioritet (odprto)

| # | Resnost | Vpliv na rezultat | Stanje |
|---|---------|-------------------|--------|
| 1 (deljenje z 0) | 🔴 | Izjema v robnem primeru | ⬜ |
| 2, 3, 4, 5 | 🟠 | Odvisno od konteksta | ⬜ |
| 6, 7, 8 | 🟡 | Brez (kakovost/vzdrževanje) | ⬜ |

**Nujno:** #1 (deljenje z nič pri hlajenju).

## Priporočilo glede testov

Odprti bug #1 je danes neviden, ker so testni fixture-i v "sladki točki". Predlagam ciljni regresijski test:
- cona brez notranjih/solarnih dobitkov hlajenja (ujame #1).

> Že popravljene postavke (`kontrolaKond`, dvojno štetje `stevilo`, vlažnost razvlaževanja, solarni razvod, typo `crpaka`, `povrsina/povrsina`, docblock) so bile odstranjene iz tega dokumenta. Zgodovina je v git-u.
