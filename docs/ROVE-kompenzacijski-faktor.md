# Razmernik ROVE in kompenzacijski faktor Y<sub>ROVE</sub>

Kako se v izračunu obravnava neizpolnjen razmernik obnovljivih virov energije
(ROVE) in kdaj se sme uporabiti kompenzacijski faktor Y<sub>ROVE</sub>.

Vir: Pravilnik o učinkoviti rabi energije v stavbah (Ur. l. RS, št. 70/22),
14. člen in tabela 4 priloge 1; TSG-1-004:2022, točka 10 in str. 103–106.
Novela Ur. l. RS 2024-01-3304 spreminja 10., 17. in 22. člen — 14. člena in
tabele 4 se ne dotakne.

Povezan dokument: [TSG-1-004-skladnost.md](TSG-1-004-skladnost.md).

---

## Zaporedje po 14. členu pravilnika

Pravilnik določa vrstni red poti, po katerih se ROVE dosega. Kompenzacija je
zadnja možnost, ne prva.

| Odstavek | Vsebina |
|---|---|
| (1) | OVE naj bodo proizvedeni v, na ali ob stavbi. |
| (2) | Če to ni mogoče, se ROVE lahko doseže in dokazuje **s tehnologijami OVE v bližini stavbe**. Sem po TSG spada tudi priključitev na daljinski sistem ogrevanja ali hlajenja, priključitev na plinovod s certificiranim deležem biometana ali vodika ter OVE na isti transformatorski postaji. |
| (3) | Če tudi to ni mogoče, se ROVE<sub>min</sub> lahko dokazuje **z lastniškim ali solastniškim deležem oddaljenih sistemov** za pridobivanje energentov OVE. |
| (4) | Šele če ROVE<sub>min</sub> ni mogoče zagotoviti in pogojev iz (2) in (3) ni mogoče izpolniti, se za določanje E'<sub>Ptot,kor,an</sub> uporabi **kompenzacijski faktor Y<sub>ROVE</sub>** iz tabele 4 priloge 1. *V tem primeru mora biti dosežen vsaj polovični zahtevani delež ROVE<sub>min</sub>.* |

> **Opomba o številčenju.** TSG-1-004 se sklicuje na *dvanajsti in trinajsti*
> odstavek 14. člena, objavljeni pravilnik pa ima ti dve določbi kot **2. in
> 3. odstavek**. Vsebina se ujema; gre le za razliko v številčenju.

Daljinsko ogrevanje je torej pot, po kateri se ROVE **doseže** (odstavek 2), in
ne razlog za kompenzacijo. Y<sub>ROVE</sub> pride v poštev šele, ko tudi ta pot
odpove.

## Kaj pomeni "faktor 1,44 (31 %)"

TSG na str. 105 navaja:

> Stavba, ki ne dosega kriterija potrebne toplote za ogrevanje Q'<sub>H,nd</sub>
> niti ne dosega zahtevanega ROVE, mora imeti specifično potrebno skupno primarno
> energijo za delovanje TSS za faktor 1,44 (31 %) nižjo od stavbe, pri kateri bi
> bila oba kriterija ustrezna.

To ni dodatno pravilo, ampak posledica **zmnožka obeh kompenzacijskih faktorjev**
iz tabele 4:

- stavba ne dosega Q'<sub>H,nd</sub> → Y<sub>H,nd</sub> = 1,2
- stavba ne dosega ROVE → Y<sub>ROVE</sub> = 1,2

Ker velja E'<sub>Ptot,kor,an</sub> = E'<sub>Ptot,an</sub> · Y<sub>H,nd</sub> · Y<sub>ROVE</sub>,
je ob odpovedi obeh kriterijev množitelj 1,2 · 1,2 = **1,44**. Iz pogoja
E'<sub>Ptot,kor,an</sub> ≤ E'<sub>Ptot,kor,dov,an</sub> sledi, da sme biti dejanska
E'<sub>Ptot,an</sub> največ 1/1,44 = 0,694 dovoljene, torej **30,6 % ≈ 31 % nižja**.

Pri meji 75 kWh/(m²an) sme taka stavba doseči le 75/1,44 = 52,1 kWh/(m²an).

## Izvedba v kodi

Vse skupaj je v [`ManjzahtevnaStavba`](../src/Calc/GF/Stavbe/ManjzahtevnaStavba.php)
in [`Stavba`](../src/Calc/GF/Stavbe/Stavba.php); `ZahtevnaStavba` deduje isto logiko.

```php
// ManjzahtevnaStavba::analiza()
$this->korigiranaSpecificnaPrimarnaEnergija =
    $this->specificnaPrimarnaEnergija * $this->Y_Hnd() * $this->Y_ROVE();
```

Faktor 1,44 nikjer ni zapisan kot konstanta — nastane sam od sebe iz zmnožka.

### `Y_ROVE()`

| Pogoj | Vrednost |
|---|---|
| `ROVE < minROVE` **in** `roveNiMogoceZagotoviti` | 1,2 |
| `ROVE > 50 · X_OVE(2026)` | 0,8 |
| sicer | 1,0 |

Veji se ne prekrivata: pri nejavni stavbi do leta 2025 je `minROVE` = 50 in prag
za 0,8 je 65, pri javni stavbi po letu 2025 sta oba praga 72 — `ROVE` ne more biti
hkrati pod prvim in nad drugim.

### Zastavica `roveNiMogoceZagotoviti`

Nova neobvezna lastnost v `splosniPodatki.stavba` (glej
[`schemas/Pures/splosniPodatkiSchema.json`](../schemas/Pures/splosniPodatkiSchema.json)):

```json
{
    "stavba": {
        "javna": false,
        "roveNiMogoceZagotoviti": true
    }
}
```

Pomeni: *predpisanega ROVE ni mogoče zagotoviti niti po 2. niti po 3. odstavku
14. člena.* Šele takrat je kompenzacija po 4. odstavku dopustna in `Y_ROVE()`
vrne 1,2.

**Privzeta vrednost je `true`** — kadar zastavice v projektu ni, se predpostavi,
da stavba nima oddaljenih sistemov oziroma solastniških deležev, s katerimi bi
ROVE lahko dokazala. Obstoječi projekti zato ohranijo dosedanje obnašanje in jih
ni treba dopolnjevati.

Zastavico nastavi na `false`, kadar stavba ROVE **lahko** doseže po 2. ali
3. odstavku (npr. priključek na daljinski sistem ogrevanja, delež oddaljene
sončne elektrarne). Takrat se Y<sub>ROVE</sub> ne uporabi in neizpolnjen ROVE
pomeni neustreznost.

### `roveKompenzacijaDopustna()`

Vrne `true`, kadar je `ROVE >= minROVE / 2`, torej kadar je izpolnjen pogoj
polovičnega deleža iz 4. odstavka. Vrednost se izvozi v `stavba.json`.

**Na izračun ne vpliva** — Y<sub>ROVE</sub> = 1,2 se uporabi tudi pod polovičnim
pragom. Razlog: če se faktor v tem primeru ne bi uporabil, bi bila
E'<sub>Ptot,kor,an</sub> *manjša* in energijski kriterij bi postal **lažji**, kar
bi bilo v nasprotju z namenom določbe. Namesto tega se izpiše opozorilo in
presoja se prepusti projektantu.

## Opozorila v izpisih

[`templates/elements/opozoriloROVE.php`](../templates/elements/opozoriloROVE.php)
izriše opozorilo samo, kadar je `ROVE < minROVE`:

| Stanje | Opozorilo |
|---|---|
| `roveNiMogoceZagotoviti = false` | Y<sub>ROVE</sub> ni upoštevan, ker je navedeno, da je ROVE mogoče zagotoviti po 2. oz. 3. odstavku. |
| `ROVE < minROVE / 2` | Uporaba Y<sub>ROVE</sub> po 4. odstavku ni dopustna; korigirana vrednost je zgolj informativna. |

Opozorilo je vključeno v izkazu ([`Izkazi/podrocjeSNES.php`](../templates/Pures/Izkazi/podrocjeSNES.php))
in na strani kazalnikov ([`Projekti/kazalniki.php`](../templates/Pures/Projekti/kazalniki.php)).
Na strani `Projekti/snes.php` (analiza sNES) opozorila zaenkrat ni.

## Grafični prikaz

Neizpolnjevanje pogoja ROVE se po TSG (str. 105 in 106) označi z **obarvanim
(rumenim) poljem** na grafičnem prikazu kazalnikov — tudi kadar se uporabi
Y<sub>ROVE</sub>. To velja kljub določbi 4. odstavka, da ROVE v tem primeru
"postane informativni kazalnik". Kartica v
[`templates/elements/kazalnikSNES.php`](../templates/elements/kazalnikSNES.php)
to upošteva: polje ROVE je zeleno pri izpolnjenem in rumeno pri neizpolnjenem
kriteriju.
