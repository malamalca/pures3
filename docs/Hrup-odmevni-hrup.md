# Odmevni hrup

Izračun temelji na SIST EN 12354-6:2004 in preverja zahteve TSG-1-005:2012.
Vhod je seznam prostorov v `projects/Hrup/<projekt>/podatki/odmevniHrup.json`.
Podprta je tudi mapa `podatki/odmevniHrup/`, v kateri je vsak prostor samostojen objekt JSON.
Delujoč primer je v `projects/Hrup/TestniProjekt/podatki/odmevniHrup.json`.

```sh
php bin/hrup.php IzracunOdmevnegaHrupa TestniProjekt
php bin/hrup.php IzracunProjekta TestniProjekt --noPdf
```

Prvi ukaz izračuna samo odmevni hrup, drugi celoten projekt. Brez `--noPdf` se izdelata
tudi izkaz in elaborat. Rezultat je v `izracuni/odmevniHrup.json`. Manjkajoč vhod se zaradi
združljivosti s starimi projekti preskoči; prazen seznam `[]` shrani prazen rezultat.

## Potek izračuna

Za vsak prostor se izračunata stanji `pred` in `po`. Prvo vsebuje prvotne površine,
pohištvo in osebe brez novih absorberjev. V drugem površina absorberja nadomesti enako
površino nosilnega elementa, zato se prvotna absorpcija pokritega dela ne podvoji.
Računanje poteka brez vmesnega zaokroževanja pri 125, 250, 500, 1000, 2000 in 4000 Hz.

Skupna ekvivalentna absorpcijska površina po enačbi (1) standarda je:

`A = Σ(Si · αi) + Σ(nj · Aobj,j) + Σ(Sk · αk) + Aair`

`Si` so mejne površine, `Aobj,j` absorpcijske površine posameznih predmetov ali oseb,
`Sk` površine razporeditev predmetov in `Aair` absorpcija zraka. Velja:

`Ψ = Σ(n · Vobj) / V`, `Vz = V · (1 − Ψ)` in `Aair = 4 · m · Vz`.

Če `absorpcijaZraka` ni podana, se uporabijo vrednosti preglednice 1 za 20 °C in
50–70 % relativne vlažnosti:

| Hz | 125 | 250 | 500 | 1000 | 2000 | 4000 |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| m [Np/m] | 0,0001 | 0,0003 | 0,0006 | 0,0010 | 0,0017 | 0,0041 |

Standardna enačba (5), uporabljena pri metodi `en12354`, je
`T = (55,3 / c0) · Vz / A`. Privzeto je `c0 = 345,6 m/s`, zato je faktor približno
0,16. Sprememba odmevne komponente hrupa je `ΔL = 10 log10(Tpred/Tpo)`.

## Osnovni podatki prostora

| Polje | Obvezno | Pomen |
| --- | --- | --- |
| `id`, `naziv` | da | Enolična oznaka in opis prostora |
| `prostornina` | da | Celotna prazna prostornina `V` v m³; število ali izraz, npr. `"8*6*3"` |
| `vrsta` | da | Vrsta presoje TSG ali `lastna` |
| `zasedenost` | da | `prazna`, `delna` ali `polna` |
| `metoda` | ne | Privzeto `en12354`; druge možnosti so spodaj |
| `hitrostZvoka` | ne | `c0` v m/s; privzeto 345,6 |
| `absorpcijaZraka` | ne | Enoten `m` ali spekter v Np/m; sicer se uporabi preglednica 1 |
| `mere` | za D.2 | `dolzina`, `sirina`, `visina`; produkt mora biti enak prostornini |

Mere so lahko števila ali matematični izrazi v nizih. Spekter je eno število za vse
oktave ali objekt z vsemi ključi `125`, `250`, `500`, `1000`, `2000`, `4000`.
`alfa` in `sipanje` sta med 0 in 1; absorpcijska površina predmeta ni omejena na 1 m².

## Metode

| `metoda` | Uporaba |
| --- | --- |
| `en12354` | Privzeti model SIST EN 12354-6, enačbe (1)–(5) |
| `en12354D2` | Izboljšani model dodatka D.2 za pravokoten prostor z neenakomerno absorpcijo |
| `sabine` | Sabinova enačba TSG: `T = 0,163 · Vz/A` |
| `eyring` | Eyringova enačba TSG z logaritemsko korekcijo mejnih površin |
| `samodejno` | Sabine pri α mejnih površin pod 0,2, sicer Eyring, za vsak pas posebej |

Osnovni model predpostavlja difuzno polje, pravilno oblikovan prostor, porazdeljeno
absorpcijo in prostorninski delež predmetov pod 0,2. Nobena mera ne sme biti več kot
petkrat večja od druge. Če Ψ doseže 0,2, rezultat ni označen kot skladen in zahteva D.3.

`en12354D2` zahteva pravokoten prostor in popoln opis šestih ploskev. Vsak mejni element
ima `ploskev`: `x0`, `x1`, `y0`, `y1`, `z0` ali `z1`; njihove vsote morajo ustrezati
meram prostora. Neobvezni `sipanje` je faktor δ. Predmet ali razporeditev ima lahko
`polozaj`: `x`, `y`, `z` ali `sredina`. Pod `ft = 8,7 · c0 / V^(1/3)` se uporabita
D.6 in D.9b, nad njo pa štiri polja x, y, z in d po D.2–D.9. Končni čas je povprečje
štirih efektivnih časov, vendar ne manj kot čas difuznega polja.

## Površine, razporeditve in predmeti

| Skupina | Obvezna polja | Absorpcijski podatek |
| --- | --- | --- |
| `mejniElementi` | `id`, `naziv`, `povrsina` | `alfa` ali `idAbsorpcije` iz B.1 |
| `povrsinskoPohistvo` | `id`, `naziv`, `povrsina` | `alfa` ali `idAbsorpcije` iz C.2 |
| `posamezniElementi` | `id`, `naziv`, `stevilo` | `absorpcijskaPovrsina`, `idAbsorpcije` iz C.1 ali `ocenaIzProstornine: true` |

Pri površinah je `stevilo` neobvezno in privzeto 1. Uporabnik vnese neto površine:
okna in vrata že odšteje od stene. Površina razporeditve je dodaten absorpcijski
prispevek in sama ne odšteva tal. `prostornina` predmeta ali razporeditve je prostornina
ene enote; če manjka, je 0. Za trd predmet lahko `ocenaIzProstornine: true` izračuna
`Aobj = Vobj^(2/3)` po enačbi (4). Ta ocena ni dovoljena za osebe.

Oseb in zasedenega pohištva se ne upošteva dvakrat. Če razporeditev že vključuje
publiko ali otroke, istih oseb ni treba dodati še med `posamezniElementi`.

Vsak novi absorber vsebuje `id`, `naziv`, `idElementa`, `povrsina` in `alfa` ali
`idAbsorpcije`. Površina je skupna, brez množitelja. Več absorberjev na istem elementu
je dovoljenih, njihova vsota pa ne sme preseči nosilne površine.

## Knjižnica SIST EN 12354-6

Knjižnica je JSON v `config/HrupAbsorpcija.json`. Vnos vsebuje id, naziv, šestoktavni
spekter in `vir`. Vrednosti dodatkov B in C so informativne značilne vrednosti;
prednost imajo meritve EN ISO 354 za dejanski proizvod.

### Površine B.1 – koeficient α

| id | Površina | 125 | 250 | 500 | 1000 | 2000 | 4000 Hz |
| --- | --- | ---: | ---: | ---: | ---: | ---: | ---: |
| B.1.1 | Beton oziroma ometana opeka | 0,01 | 0,01 | 0,01 | 0,02 | 0,02 | 0,03 |
| B.1.2 | Neometan opečni zid | 0,02 | 0,02 | 0,03 | 0,04 | 0,05 | 0,07 |
| B.1.3 | Trda talna obloga na masivni konstrukciji | 0,02 | 0,03 | 0,04 | 0,05 | 0,05 | 0,06 |
| B.1.4 | Mehka talna obloga do 5 mm | 0,02 | 0,03 | 0,06 | 0,15 | 0,30 | 0,40 |
| B.1.5 | Mehka talna obloga od 10 mm | 0,04 | 0,08 | 0,15 | 0,30 | 0,45 | 0,55 |
| B.1.6 | Lesena tla, parket na letvah | 0,12 | 0,10 | 0,06 | 0,05 | 0,05 | 0,06 |
| B.1.7 | Okno oziroma steklena fasada | 0,12 | 0,08 | 0,05 | 0,04 | 0,03 | 0,02 |
| B.1.8 | Lesena vrata | 0,14 | 0,10 | 0,08 | 0,08 | 0,08 | 0,08 |
| B.1.9 | Mrežasta zavesa 0–200 mm pred trdo podlago | 0,05 | 0,04 | 0,03 | 0,02 | 0,02 | 0,02 |
| B.1.10 | Lahka zavesa pod 0,2 kg/m², minimum | 0,05 | 0,06 | 0,09 | 0,12 | 0,18 | 0,22 |
| B.1.11 | Tkana nabrana zavesa 0,4 kg/m², maksimum | 0,10 | 0,40 | 0,70 | 0,90 | 0,95 | 1,00 |
| B.1.12 | Odprtina z najmanjšo mero nad 1 m | 1,00 | 1,00 | 1,00 | 1,00 | 1,00 | 1,00 |
| B.1.13 | Prezračevalna rešetka, 50 % odprta | 0,30 | 0,50 | 0,50 | 0,50 | 0,50 | 0,50 |

Pri B.1.9 in B.1.10 lahko kombinacija pred oknom doseže vrednosti samega okna.

### Posamezni elementi C.1 – Aobj enega predmeta [m²]

| id | Predmet | 125 | 250 | 500 | 1000 | 2000 | 4000 Hz |
| --- | --- | ---: | ---: | ---: | ---: | ---: | ---: |
| C.1.1 | Lesen stol | 0,02 | 0,02 | 0,03 | 0,04 | 0,04 | 0,04 |
| C.1.2 | Oblazinjen stol | 0,10 | 0,20 | 0,25 | 0,30 | 0,35 | 0,35 |
| C.1.3 | Oseba v skupini, sede ali stoje, minimum | 0,05 | 0,10 | 0,20 | 0,35 | 0,50 | 0,65 |
| C.1.4 | Sedeča oseba v skupini, maksimum | 0,12 | 0,45 | 0,80 | 0,90 | 0,95 | 1,00 |
| C.1.5 | Stoječa oseba v skupini, maksimum | 0,12 | 0,45 | 0,80 | 1,20 | 1,30 | 1,40 |

Vrednosti oseb veljajo za eno osebo na 6 m² površine.

### Razporeditve C.2 – koeficient α

| id | Razporeditev | 125 | 250 | 500 | 1000 | 2000 | 4000 Hz |
| --- | --- | ---: | ---: | ---: | ---: | ---: | ---: |
| C.2.1 | Leseni/plastični stoli v vrstah 0,9–1,2 m | 0,06 | 0,08 | 0,10 | 0,12 | 0,14 | 0,16 |
| C.2.2 | Oblazinjeni stoli v vrstah, minimum | 0,10 | 0,20 | 0,30 | 0,40 | 0,50 | 0,50 |
| C.2.3 | Oblazinjeni stoli v vrstah, maksimum | 0,50 | 0,70 | 0,80 | 0,90 | 1,00 | 1,00 |
| C.2.4 | Sedeča publika v vrstah, minimum | 0,20 | 0,40 | 0,50 | 0,60 | 0,70 | 0,70 |
| C.2.5 | Sedeča publika v vrstah, maksimum | 0,60 | 0,70 | 0,80 | 0,90 | 0,90 | 0,90 |
| C.2.6 | Otroci v razredu s trdo opremo, eden na m² | 0,10 | 0,20 | 0,25 | 0,35 | 0,40 | 0,40 |

Primer uporabe: `{"id":"tla","naziv":"Parket","povrsina":48,"idAbsorpcije":"B.1.3"}`.

## Vrste prostorov in presoja

| `vrsta` | Cilj in presoja |
| --- | --- |
| `ucilnica` | `Topt = 0,32 log10 V − 0,17`; diagram 2; polna zasedenost |
| `glasbenaUcilnica` | Približek diagrama 3: `0,45 log10 V + 0,07`, 30–30000 m³; diagram 4 |
| `vecnamenskiPouk` | Preglednica 12: 0,5 / 0,6 / 0,8 / 0,9 s pri 200 / 400 / 800 / 1600 m³ |
| `vecnamenskiGovor` | Preglednica 12: 0,7 / 0,8 / 0,9 / 1,0 s |
| `vecnamenskiGlasba` | Preglednica 12: 1,1 / 1,3 / 1,4 / 1,5 s |
| `sportniProstor1` | Ena skupina: `1,27 log10 V − 2,49`; prazen prostor |
| `sportniProstor2` | Več skupin: `0,95 log10 V − 1,74`; prazen prostor |
| `lastna` | Uporabniški `nazivVrste` in `mejniOdmevniCas` |

Za preglednico 12 se linearno interpolira in zaokroži na desetinko; zunaj 200–1600 m³
se ne ekstrapolira. Pri večnamenskih in športnih prostorih je po dogovorjenem projektnem
kriteriju optimalni čas zgornja meja sredine pri 500 in 1000 Hz.

Pri učilnicah se preveri vsaka oktava, tudi spodnja meja. Grafično odčitani faktorji so:

| T/Topt | 125 | 250 | 500 | 1000 | 2000 | 4000 Hz |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| Učilnica min/max | 0,65/1,2 | 0,8/1,2 | 0,8/1,2 | 0,8/1,2 | 0,8/1,2 | 0,6/1,2 |
| Glasbena min/max | 1,0/1,4 | 0,8/1,2 | 0,8/1,2 | 0,8/1,2 | 0,8/1,2 | 0,6/1,2 |

Lasten tip, na primer hodnik, se zapiše tako:

```json
{"vrsta":"lastna","nazivVrste":"Hodnik","mejniOdmevniCas":1.0}
```

Število je zgornja meja povprečja pri 500 in 1000 Hz. Če uporabnik poda spekter šestih
mej, se vsaka oktava preveri posebej.

## Robni primeri

- Podane mere prostora morajo biti strogo pozitivne, tudi kadar niso obvezne za izbrani model.
- Pri ničelni absorpciji je čas `null`, ne 0 s, in presoja te oktave ni določljiva.
  Pri presoji po oktavah znana prekoračitev v drugi oktavi pomeni skupni rezultat »NE«.
  Če ni prekoračitev, vendar vsaj ene oktave ni mogoče presoditi, je skupni rezultat nedoločljiv.
- Pri neuporabnem modelu ali napačni zahtevani zasedenosti so tudi presoje posameznih oktav
  nedoločljive; izračunani časi in mejne vrednosti ostanejo prikazani.
- Pri popolni absorpciji je Eyringov čas limita 0 s.
- Negativna `ΔL` pomeni, da je prostor po posegu bolj odmeven.
- Presoja časa ne preverja zgodnjih odbojev, razumljivosti govora ali vseh drugih zahtev.

## Preverjanje implementacije

Test vsebuje celoten dodatek E standarda pri 1000 Hz:

- prazen prostor: `A = 2,26 m²`, `T = 2,1 s`;
- trdi predmeti: `Ψ = 0,072`, `A = 5,03 m²`, `T = 0,9 s`;
- absorpcijska stena v osnovnem modelu: `A = 10,21 m²`, `T = 0,5 s`;
- po D.2: `Ax = 13,69`, `Ay = 2,04`, `Az = 13,22`, `Ad = 10,21 m²`,
  `Tx = 0,35`, `Ty = 2,34`, `Tz = 0,36`, `Td = 0,47` in `Teff = 0,9 s`.

Viri so SIST EN 12354-6:2004, poglavje 4 in dodatki B–E; TSG-1-005:2012,
poglavji 6.1 in 7.3; ter zavihek `4.1 Odmevni hrup` v `Hrup13_hudernik_doo.xls`.
Referenčna preglednica izpusti absorpcijo razporeditev iz končne vsote, čeprav jo vmes
izračuna; implementacija jo skladno z enačbo (1) standarda vključi.
