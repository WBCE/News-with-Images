# news_img – View „grid"

Karten-Ansicht des Moduls `news_img`. Die **Beitragsliste** wird als
responsives Karten-Raster ausgegeben; die **Leseansicht** eines einzelnen
Beitrags entspricht der default-View.

## Dateien

| Datei          | Zweck                                                                 |
|----------------|-----------------------------------------------------------------------|
| `config.php`   | Default-Templates (Grid-Wrapper, Karten-Loop, Header/Footer, Post-Header/-Content/-Footer). |
| `frontend.css` | Optik der Ausgabe. Wird im Frontend automatisch eingebunden.          |

> **Wichtig:** `config.php` liefert nur die **Vorlagen**. Im laufenden Betrieb
> kommen die tatsächlich gerenderten Templates aus den **Section-Settings in
> der Datenbank** (siehe `mod_nwi_settings_get()`), nicht direkt aus dieser
> Datei. Änderungen an `config.php` greifen erst nach einem Settings-Reset der
> Section. Die `frontend.css` wirkt dagegen sofort (ggf. Browser-Cache mit
> Strg+F5 leeren).

## Optik / Designkonzept

Gleiche zurückhaltende **Navy-Palette** wie die default-View, nur als
Karten-Raster statt als Liste. Jede View lädt ausschließlich ihre eigene
`frontend.css` – die grid-Regeln sind daher auf `.mod_nwi_grid` gescoped und
bewusst eigenständig (kein Erben von `.mod_nwi_default`).

### Farbpalette

| Verwendung            | Hex        |
|-----------------------|------------|
| Akzent / Tag 1        | `#314B68`  |
| Tag 2                 | `#49627E`  |
| Tag 3                 | `#6F839A`  |
| Tag (weitere)         | `#9BA9B9`  |
| Karte/Box Hintergrund | `#fff` / `#f3f5f8` (Lead-Box) |
| Rahmen                | `#e2e8f0`  |

### Raster

- Spaltenzahl über die Wrapper-Klasse `mod_nwi_grid_2columns` …
  `mod_nwi_grid_5columns` (im `$header` der `config.php` gesetzt).
- **Responsive:** unter 600px Breite immer einspaltig, unabhängig von der
  gewählten Spaltenzahl.

### Komponenten

- **Karte** (`section.mod_nwi_grid_box`)
  Weißer Hintergrund, dezenter Rahmen, abgerundet, Bild randlos oben.
  Als Flex-Spalte aufgebaut → Karten einer Reihe sind gleich hoch, der
  „Weiterlesen"-Button sitzt per `margin-top: auto` immer am unteren Rand.

- **Bildbereich / Header** (`.mod_nwi_teaserpic`)
  Feste `aspect-ratio: 3/2` mit `object-fit: cover`. Dadurch hat der Header
  **mit Bild und mit Platzhalter-Kachel die gleiche Höhe** – Beiträge ohne
  Bild bekommen keinen schmaleren Kopf.

- **Textbereich** (`.mod_nwi_teasertext`)
  Titel, Metadaten, Teaser. Unten der gestapelte Bereich (`.mod_nwi_bottom`):
  **erst die Tags, darunter in eigener Zeile der „Weiterlesen"-Button**
  (linksbündig) – passt besser in schmale Kacheln als nebeneinander.

- **Tags** (`.mod_nwi_tag`)
  Kompakte Navy-Pills, die ersten drei abgestuft, weitere `#9BA9B9`.
  Der Abstand `margin-top` greift nur in der **Detailansicht** (Abstand zur
  Vor/Zurück-Navigation); in der Listen-Kachel ist er via
  `.mod_nwi_bottom .mod_nwi_tags` auf 0 gesetzt.

- **„Weiterlesen"-Button** (`.mod_nwi_readmore a`)
  Schlanker Outline-Button im Tag-Stil (Navy, `border-radius: 3px`), Pfeil per
  CSS (`::after { content: "\2192" }`), Hover füllt sich mit Navy.

- **Leseansicht – Teaser-Box** (`.mod_nwi_content_short`)
  Identisch zur default-View: heller, kühler Lead-Block (`#f3f5f8`, dezenter
  Rahmen, abgerundet) mit Lead-Typografie; Beitragsbild einheitlich gerahmt
  (`aspect-ratio: 4/3`, `object-fit: cover`).

- **Navigation** (`.mod_nwi_nav`)
  Vor/Zurück/Übersicht bzw. Paginierung als neutrale Flex-`<nav>` mit drei
  gleich breiten Zonen – die Mitte bleibt zentriert, auch wenn eine Seite
  leer ist. Richtungspfeile (← / →) per CSS an Prev/Next-Links.

### Fehlende Beitragsbilder

Ist weder ein Beitrags-, Gruppen- noch ein Section-Standardbild gesetzt, wird
**kein** Platzhalter-Pixel mehr skaliert:

- **Leseansicht:** gar kein Bild – der Teasertext nutzt die volle Breite.
- **Liste (Karten):** eine schlichte **Platzhalter-Kachel** mit der
  Titel-Initiale auf Navy (`.mod_nwi_noimage`), die den Bildbereich der Karte
  voll ausfüllt – das Raster bleibt optisch gleichmäßig.

Die Unterscheidung erfolgt in `mod_nwi_post_process()` über `defined('POST_ID')`
(in der Leseansicht gesetzt, in der Liste nicht). Die Kachel-Optik liegt inline
am Element, damit sie in allen Views ohne zusätzliches CSS einheitlich aussieht;
grid passt sie zusätzlich an den Kartenkopf an (`min-height: 0`, kein
Eck-Radius, größere Initiale).

## Anpassen

- **Spaltenzahl:** Wrapper-Klasse im `$header` der `config.php` bzw. in den
  Section-Settings.
- **Bildproportion des Headers:** `aspect-ratio` bei `.mod_nwi_teaserpic`
  (Standard `3 / 2`).
- **Akzentfarbe ändern:** `#314B68` in `frontend.css` (Tag 1, Button) und ggf.
  die Inline-Farbe der Platzhalter-Kachel in `mod_nwi_post_process()`.
- **Eigene Optik ohne Core-Änderung:** am besten eine **eigene View** anlegen
  (Verzeichnis unter `views/` kopieren) und in den Section-Settings auswählen,
  statt diese Dateien zu überschreiben – so bleiben Updates problemlos.
