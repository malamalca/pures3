# Skladnost z validacijskimi primeri TSG-1-004:2022

Tehnična smernica TSG-1-004:2022 vsebuje vrsto kontrolnih primerov za validacijo programske
opreme ("Kontrolni primer za validacijo programske opreme" oz. "Primer izračuna … za validacijo").
Ta dokument popisuje te primere, njihovo pripadnost mesečni metodi izračuna in pokritost s
testi v projektu (`tests/`).

Povezan dokument: [TSG-1-004-skladnost.md](TSG-1-004-skladnost.md) (splošne neskladnosti izračuna).

Legenda: ✅ pokrito in testi uspejo · 🟡 delno · ❌ ni pokrito

---

## Validacijski primeri mesečne metode

To so primeri, ki preverjajo mesečni (stacionarni) izračun – v fokusu te validacije.

| TSG primer | Veličina | Test | Status |
|------------|----------|------|--------|
| tab. 8.6 | `Hgr,an,m` – toplotni tok skozi tla na terenu (SIST EN ISO 13370, t. H.4); `Hpi = 165,8`, `Hpe = 75,1` W/K | `IzracunKonstrukcijeTSG004Test::testValidacijaTSGProtiZemlji` | ✅ |
| tab. 8.12.1 | `QHU,nd,m` / `QHU,nd,an` – potrebna toplota za navlaževanje; `QHU,nd,an ≈ 1929` kWh/an | `IzracunNavlazevanjaTest::testValidacijaTSG` + `::testValidacijaTSG_QHU` | ✅ |
| tab. 8.12.2 | `QDHU,nd,m` / `QDHU,nd,an` – potrebna toplota za razvlaževanje; `QDHU,nd,an ≈ 21932` kWh/an | `IzracunNavlazevanjaTest::testValidacijaTSG_QDHU` | ✅ |
| tab. 9.3 | `fmatch,m` + `Eel,pr-use,m` – PV sistem brez baterije (samooskrba) | `FotonapetostniSistemTest::testValidacijaTSG93FaktorUjemanja` | ✅ |

### Opombe k mesečni metodi
- **tab. 8.12.1 / 8.12.2:** med implementacijo testov je bila odkrita in odpravljena napaka –
  mesečna energija za navlaževanje/razvlaževanje se prej ni omejila na ≥ 0 (`Cona::izracunNavlazevanje`,
  glej skladnost #E). TSG: "QHU,nd,m je nič, kadar je mH2O,m manjši od 0."
- V testih navlaževanja/razvlaževanja so robne vlažnosti (`Xi,a,min`) podane neposredno
  (`minNotranjaVlaznostOgrevanje = 4,960`, `minNotranjaVlaznostHlajenje = 12,00`), da se izolira
  preverjana mesečna metoda od manjšega odstopanja v konstantah enačbe nasičenega parnega tlaka
  (izračunani Xi ≈ 4,897 proti 4,960 v primeru) ter privzete učinkovitosti entalpijskega prenosnika
  (`ηHU = 0,55` proti `0` v primeru).
- Primer **tab. 8.12.2** ima mesečne vrednosti v izvornem md zajete kot slika; vrednosti so bile
  vnesene ročno iz smernice (`QDHU,nd,m = [0,0,0,0, 591, 6701, 7798, 6841, 0,0,0,0]`).

---

## Ostali validacijski primeri (stacionarni / urni)

| TSG primer | Veličina | Test | Status | Opomba |
|------------|----------|------|--------|--------|
| tab. 8.3 | difuzija vodne pare: `qSi`, `fRsi = 0,939`, `fRsi,min`, mesečni kondenzat `gc = [9,4,-8,-21,…]` | `IzracunKonstrukcijeTSG004Test::testValidacijaTSG` | ✅ | gc preverjen po popravku vhoda (μ = sd/d; sd = 9/5,25/9 m → μ = 60/35/60); poletni meseci brez kondenzacije = 0 |
| tab. 8.4 | `U = 0,130` W/(m²K) ; faktor toplotne stabilnosti `f = 0,449` | `IzracunKonstrukcijeTSG004Test::testValidacijaTSG84` | ✅ | `U` in `f` (SIST EN ISO 13786, t. 8.1.5) preverjena; Rsi = 0,13, Rse = 0,04 |
| tab. 5.1.1 | urno sončno obsevanje na nagnjeno ploskev (SIST EN ISO 52010, metoda 1) | – | ❌ | obsevanje se v izračun prevzame iz vhodnih podatkov (okolje); ni ločenega testa |
| tab. 5.7 | kontrolni primer – metoda 1 (senčenje) | – | ❌ | ni ločenega testa |
| tab. 8.11.3 | poraba TSV po conah (SIST ISO 18523-1) | – | ❌ | ni ločenega testa |

### Ugotovitve (8.3 / 8.4)
- **8.3:** prejšnji test je imel vrednosti `sd` (m) vnesene v polje `difuzijskaUpornost`, ki ga koda
  razume kot μ (`Sd = debelina × μ`), zato je bil kondenzat ~6,6× prevelik (zato je bila trditev `gc`
  zakomentirana). Po vnosu pravilnega μ se `gc` ujema s primerom. (Napaka v testu, ne v izračunu.)
- **8.4:** faktor toplotne stabilnosti `f` (dušilni/dekrementni faktor, SIST EN ISO 13786, točka 8.1.5)
  je bil **implementiran** (`CalcKonstrukcije::faktorToplotneStabilnosti`, kompleksna toplotna matrika,
  perioda 24 h, `f = |Yie| / U`); za primer 8.4 (Rsi = 0,13, Rse = 0,04) da `f = 0,449`. Faktor je dostopan
  kot `$kons->f`.
- **Robustnost:** dodan je varovalni pogoj – prehod vodne pare se preskoči, če je skupni `Sd = 0`
  (`CalcKonstrukcije::konstrukcija`), s čimer se prepreči `DivisionByZeroError` pri konstrukcijah brez
  difuzijskih podatkov.

---

## Odstopanje izračuna od vrednosti TSG

Primerjava izračunanih vrednosti z vrednostmi iz kontrolnih primerov TSG.

| Primer | Veličina | TSG | Izračun | Odstopanje |
|--------|----------|-----|---------|------------|
| 8.3 | `fRsi` | 0,939 | 0,939 | 0,0 % |
| 8.3 | `gc` (mesečni kondenzat) | 9/4/−8/−21/2/7 | enako (zaokroženo) | ~0 % |
| 8.4 | `U` (W/(m²K)) | 0,130 | 0,1293 | −0,54 % |
| 8.4 | `f` (faktor toplotne stabilnosti) | 0,449 | 0,4493 | +0,06 % |
| 8.6 | `Hpi` (W/K) | 165,8 | 165,77 | −0,02 % |
| 8.6 | `Hpe` (W/K) | 75,1 | 75,11 | +0,02 % |
| 8.12.1 | `QHU,nd,an` (kWh/an) | 1929 | 1918,5 | −0,55 % |
| 8.12.2 | `QDHU,nd,an` (kWh/an) | 21932 | 21951,3 | +0,09 % |
| 9.3 | `fmatch,m` / `Eel,pr-use,m` | tab. 9.3 | ujemanje na 2 decimalki | ~0 % |

Vsa odstopanja so ≤ 0,6 % in izhajajo predvsem iz zaokroževanja vhodnih podatkov v smernici
(npr. zunanja absolutna vlažnost `Xe` na 2 decimalki, površinski upori). Robne vlažnosti `Xi` so
v testih navlaževanja/razvlaževanja podane neposredno (4,960 oz. 12,00), zato preostalo odstopanje
izvira iz zaokroženih mesečnih vrednosti `Xe`.

## Sorodni (ne-validacijski) testi izračuna

Dodatni testi, ki niso vezani na kontrolne primere TSG, a preverjajo metodo izračuna:

| Test | Predmet |
|------|---------|
| `IzracunConeTest::testValidacijaIzracunaCone` | celovit mesečni izračun cone (St-1) proti referenčnemu Excelu (QH,nd, QC,nd, TSV, razsvetljava) |
| `IzracunNotranjegaOkoljaTest` | notranje okolje (TSG ter SIST EN ISO 13788, primera B1/B2) |
| `IzracunKonstrukcijeISO13788Test::testValidacijaISO13788_C2` | kondenzacija v konstrukciji (SIST EN ISO 13788, primer C2) |
| `Ovoj/ProtiNeogrevaniKletiTest::testValidacijaTSG` | gradnik proti neogrevani kleti |
| `TSS/*` | posamezni podsistemi TSS (generatorji, razvodi, hranilniki, prenosniki, prezračevanje) |

---

## Povzetek

- Vsi štirje **mesečni** validacijski primeri (8.6, 8.12.1, 8.12.2, 9.3) so pokriti in testi uspejo.
- Med delom je bila odpravljena napaka pri omejevanju mesečne energije navlaževanja/razvlaževanja (#E).
- **8.3** (difuzija/kondenzat) in **8.4** (`U` + faktor toplotne stabilnosti `f`) sta sedaj v celoti pokrita.
- Nepokriti ostajajo: urno sončno obsevanje (5.1.1, 5.7) in poraba TSV po SIST ISO 18523-1 (8.11.3).

*Zadnja posodobitev: 2026-06-17.*
