# news_img – View „default"

Die Standard-Ansicht des Moduls `news_img`. Sie rendert sowohl die
**Beitragsliste** (Übersicht einer Section) als auch die **Leseansicht**
eines einzelnen Beitrags.

## Dateien

| Datei          | Zweck                                                                 |
|----------------|-----------------------------------------------------------------------|
| `config.php`   | Default-Templates (Listen-Loop, Header/Footer, Post-Header/-Content/-Footer). |
| `frontend.css` | Optik der Ausgabe. Wird im Frontend automatisch eingebunden.          |

> **Wichtig:** `config.php` liefert nur die **Vorlagen**. Im laufenden Betrieb
> kommen die tatsächlich gerenderten Templates aus den **Section-Settings in
> der Datenbank** (siehe `mod_nwi_settings_get()`), nicht direkt aus dieser
> Datei. Änderungen an `config.php` greifen erst nach einem Settings-Reset der
> Section. Die `frontend.css` wirkt dagegen sofort (ggf. Browser-Cache mit
> Strg+F5 leeren).

## Optik / Designkonzept

Bewusst zurückhaltend gehalten, damit sich die Ausgabe in das umgebende
Seiten-Template einfügt. Akzente kommen aus einer einheitlichen **Navy-Palette**,
die auch die Tags einfärbt.

### Farbpalette

| Verwendung            | Hex        |
|-----------------------|------------|
| Akzent / Tag 1        | `#314B68`  |
| Tag 2                 | `#49627E`  |
| Tag 3                 | `#6F839A`  |
| Tag (weitere)         | `#9BA9B9`  |
| Teaser-Box Hintergrund| `#f3f5f8`  |
| Teaser-Box Rahmen     | `#e2e8f0`  |

Der Akzent `#314B68` wird durchgängig genutzt: erster Tag, „Weiterlesen"-Button
und Platzhalter-Kachel.

### Komponenten

- **Beitragsliste** (`.mod_nwi_group`)
  Vorschaubild links als quadratische, beschnittene Kachel
  (`clamp(120px, 22%, 180px)`, `aspect-ratio: 1/1`, `object-fit: cover`),
  Titel/Teaser rechts. Unten eine Flex-Zeile (`.mod_nwi_bottom`) mit Tags links
  und „Weiterlesen"-Button rechts.

- **„Weiterlesen"-Button** (`.mod_nwi_readmore a`)
  Schlanker Outline-Button im Tag-Stil (Navy, `border-radius: 3px`), Pfeil per
  CSS (`::after { content: "\2192" }`). Über `margin-left: auto` rechtsbündig;
  bricht bei vielen Tags / schmalem Viewport sauber in eine eigene Zeile um.

- **Leseansicht – Teaser-Box** (`.mod_nwi_content_short`)
  Heller, kühler Lead-Block (`#f3f5f8`, dezenter Rahmen, abgerundet) mit
  Lead-Typografie. Das Beitragsbild wird einheitlich gerahmt
  (`aspect-ratio: 4/3`, `object-fit: cover`, abgerundet).

- **Navigation** (`.mod_nwi_nav`)
  Vor/Zurück/Übersicht unter dem Beitrag bzw. die Paginierung der Liste.
  Drei gleich breite Flex-Zonen (links/mitte/rechts) – die Mitte bleibt
  zentriert, auch wenn eine Seite leer ist. Neutral gehalten: nur eine dünne
  Trennlinie oben, Links erben Farbe/Schrift, Hover = Unterstreichung.
  Richtungspfeile (← / →) kommen per CSS an Prev/Next-Links.

- **Tags** (`.mod_nwi_tag`)
  Kompakte Pills, die ersten drei in abgestuftem Navy, weitere in `#9BA9B9`.

### Fehlende Beitragsbilder

Ist weder ein Beitrags-, Gruppen- noch ein Section-Standardbild gesetzt, wird
**kein** Platzhalter-Pixel mehr skaliert:

- **Leseansicht:** gar kein Bild – der Teasertext nutzt die volle Breite.
- **Liste / Grid:** eine schlichte **Platzhalter-Kachel** mit der Titel-Initiale
  auf Navy (`.mod_nwi_noimage`), damit das Raster optisch gleichmäßig bleibt.

Die Unterscheidung erfolgt in `mod_nwi_post_process()` über `defined('POST_ID')`
(in der Leseansicht gesetzt, in der Liste nicht). Die Kachel-Optik liegt inline
am Element, damit sie in allen Views ohne zusätzliches CSS einheitlich aussieht.

## Anpassen

- **Akzentfarbe ändern:** `#314B68` in `frontend.css` (Tag 1, Button,
  Trennlinie-Hover) und ggf. die Inline-Farbe der Platzhalter-Kachel in
  `mod_nwi_post_process()` anpassen.
- **Eigene Optik ohne Core-Änderung:** am besten eine **eigene View** anlegen
  (Verzeichnis unter `views/` kopieren) und in den Section-Settings auswählen,
  statt diese Default-Dateien zu überschreiben – so bleiben Updates problemlos.
