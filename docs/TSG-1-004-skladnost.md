# Skladnost izračuna Pures s TSG-1-004:2022

Pregled skladnosti modula `src/Calc/GF` (gradbena fizika / energijska učinkovitost)
z zahtevami tehnične smernice TSG-1-004:2022. Modul `src/Calc/Hrup` ni zajet
(akustika sodi pod TSG-1-005).

Legenda: ✅ odpravljeno · 🟡 odprto · 🔵 potrjeno skladno

---

## Odpravljene neskladnosti

| # | Opis | Lokacija | TSG |
|---|------|----------|-----|
| 1 | Letno število ur razsvetljave industrijskih stavb popravljeno `1800/200` → `2500/1500` | `IndustrijskaKlasifikacijaCone::letnoSteviloUrDelovanjaRazsvetljave` | tab. 8.17 |
| 2 | St-1 nočne ure razsvetljave `1820` → `1680` (metoda zamenjana z `refRazsvetljava`) | `EnostanovanjskaKlasifikacijaCone::referencniTSSRazsvetljava` | tab. 8.17 / 11.1 |
| 3 | Svetlobni izkoristek (hL) in osvetljenost referenčne stavbe poenotena med potjo QH/QC in EP; hL določen glede na leto projekta (80 do 2025, 95 po 2025), osvetljenost po klasifikaciji (300/500 lx) | `KlasifikacijaCone::ucinkovitostViraSvetlobeZaLeto`, `::referencnaOsvetlitevDelovnePovrsine`, `Cona::parseConfig`, `IzracunCone`/`IzracunTSS` | tab. 11.1–11.10 |
| 4 | Prostornina solarnega hranilnika TSV `0,8/0,6·Ause` → `50·ASSE` | `KlasifikacijaCone::dodajSolarniTSV` | tab. 11.1, vrstica OVE |
| 5 | Iz-1 fotonapetostne celice `monokristalne` → `polikristalne` | `IzobrazevalnaKlasifikacijaCone::referencniTSSFotovoltaika` | tab. 11.4 / 11.8 |
| 8 | Implementirane referenčne klasifikacije Tr-1, Bo-1, Sp-1, Ra-1 | `KlasifikacijaConeFactory` + nove klase | tab. 11.5, 11.9, 11.10, 11.8 |
| – | Popravljen obstoječi hrošč: neinicializirana lastnost `ElementOvoja::$id` pri konstrukcijah proti zemljini | `ElementOvoja::$id` | – |

---

## Odprte neskladnosti

### 🟡 #6 — Referenčna temperatura TSV pri stanovanjskih stavbah
TSG tabela 11.1 navaja temperaturo TSV **45 °C** (enostanovanjske) oz. **55 °C**
(večstanovanjske); koda uporablja **42 °C**.

- **Stanje:** v kodi je dodan le komentar (`Enostanovanjska/Vecstanovanjska KlasifikacijaCone`).
- **Razlog za odlog:** sprememba ruši validacijski test `IzracunConeTest`, ki preverja
  cono St-1 proti referenčnemu Excelu, izračunanemu pri 42 °C. Konflikt med besedilom
  TSG (45/55 °C) in obstoječim validacijskim virom (42 °C) zahteva odločitev naročnika.
- **Potreben poseg ob potrditvi:** `toplaVodaT = 45` (St-1) / `55` (St-2/St-3) + uskladitev
  pričakovanih vrednosti TSV v testu (faktor 35/32 oz. 45/32).

### 🟡 #7 — Tesnost referenčne stavbe pri rekonstrukcijah (`n50 = 2,0`)
- `Cona::parseConfig` nastavi `n50 = 1.5` (`// todo: za rekonstrukcije n50=2`).
- `Cona` nima dostopa do vrste gradnje (`Stavba::$tip` je na nivoju stavbe) — ni enostavnega popravka.
- Dodatno: TSG je notranje neskladen — tabela 8.10.3 navaja `n50 = 2,0`, poglavje 11 pa `1,5`.
  Koda sledi poglavju 11 (1,5).

### 🟡 A — Referenčna fotovoltaika ne vsiljuje `fmatch = 1`
Referenčni PV (`refFotovoltaika`) ima `oddajaVOmrezje = false` brez hranilnika/TSV,
zato je `vplivUjemanja = true` in se izračuna faktor ujemanja `< 1`.
TSG zahteva za **referenčno stavbo** `fmatch,t = 1` ne glede na metodo modeliranja
(TSG t. 9, opombe; tab. 11.4).

- **Lokacija:** `FotonapetostniSistem::parseConfig:94`, `::analiza:132`.
- **Posledica:** referenčna stavba lastno porabi manj PV, kot predpisuje smernica.

### 🟡 B — Faktor oblike referenčne razsvetljave `1,4` namesto `k = 1`
`Razsvetljava::analiza` uporabi `faktorOblike = 1.4`, kadar je `faktorOblike` nenastavljen
in gre za referenčno stavbo (pot `Cona::izracunRazsvetljave`). TSG tab. 11.1–11.10 zahtevajo
`k = 1` (in `refRazsvetljava` pravilno uporablja 1).

- **Lokacija:** `Razsvetljava::analiza:157-158`.
- **Opomba:** vpliv odvisen od tega, ali se `Cona->energijaRazsvetljava` referenčne cone
  dejansko upošteva v končnem EPtot (merodajna EP-pot uporablja `referencniTSS('razsvetljava')`
  s `k = 1`). Pred posegom preveriti porabo te vrednosti.

### 🟡 C — Stanovanjsko prezračevanje brez spodnje meje 7 l/s na osebo
`EnostanovanjskaKlasifikacijaCone::kolicinaSvezegaZrakaZaPrezracevanje` uporablja
`Ause · 0,42 l/s·m²`, brez upoštevanja "vendar ne manj kot 7 l/s na osebo" (TSG t. 6, opis
mehanskega prezračevanja). Manjši pomen — redko merodajno.

### 🟡 D — Referenčni izkoristki vračanja toplote po tabeli 8.7 niso privzeti v izračunu
Tabela 8.7 (0,6 / 0,85 / 0,69 / 0,67 / 0,71 glede na vrsto prenosnika) ni vgrajena kot
privzetek; izkoristek se prevzame iz vhodnih podatkov (referenčna stavba pravilno vsiljena
na 0,65). Preveriti, ali se privzetek po vrsti prenosnika zagotavlja na nivoju vnosa.

---

## Potrjeno skladno

| Področje | Preverjeno | Vir |
|----------|------------|-----|
| Operativne temperature | 20/26 (stanovanjske), 22/25 (nestanovanjske) | tab. 6.1.2 |
| Sveži zrak (nestanovanjske) | V′, gostota oseb, fu, td, tt za Po/Go/Ho/In/Iz/Kn/Sd | tab. 6.1.4 |
| Potrebna toplota TSV | faktorji na m²/osebo za vse klasifikacije (vključno hotel 3★/5★, šole s tuši) | tab. 8.11.1 |
| Ure razsvetljave | po vrstah | tab. 8.17 |
| Mesečni utežni faktorji vₘ | `[1.25,1.1,0.94,0.86,0.83,0.73,0.79,0.87,0.94,1.09,1.21,1.35]` | tab. 8.16 |
| Specifična moč razsvetljave P′L | `1/hL · Etask · k′ · FCA · FMF` | t. 8 / tab. 8.20 |
| Referenčni ovoj | sloji, U, gₜₒₜ, αₛ=0,5, Ffr,w=0,25, ΔΨ=0, Cₘ=260000·Ause | tab. 8.10.3 |
| SFP transporta zraka | `[0.211, 0.142]` (SFP3/SFP2) | pogl. 11 |
| PV vršna moč Kpk | polikristalne 0,18 = `href` | tab. 11.4 / EN 15316-4-3 C.3 |

---

*Zadnja posodobitev: 2026-06-17.*
