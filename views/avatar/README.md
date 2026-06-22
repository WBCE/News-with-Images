# news_img – View „avatar"

Karten-Liste mit **rundem Avatar** je Eintrag – gedacht für inhaltlich
personen-/beitragsnahe Übersichten wie **Blog** oder **Teamvorstellung**.
Der Name bezieht sich – wie „grid" – auf die **Darstellung** (rundes
Porträtbild + Media-Object-Zeile), nicht auf den Einsatzzweck.

> Hervorgegangen aus der früheren View „faq". Wer FAQ-artige Inhalte
> abbilden will, ist mit den Views **default** oder **grid** gut bedient.

## Dateien

| Datei          | Zweck                                                                 |
|----------------|-----------------------------------------------------------------------|
| `config.php`   | Default-Templates (Karten-Loop, Header/Footer, Post-Header/-Content/-Footer). |
| `frontend.css` | Optik der Ausgabe. Wird im Frontend automatisch eingebunden.          |

> **Wichtig:** `config.php` liefert nur die **Vorlagen**. Im laufenden Betrieb
> kommen die tatsächlich gerenderten Templates aus den **Section-Settings in
> der Datenbank** (siehe `mod_nwi_settings_get()`), nicht direkt aus dieser
> Datei. Änderungen an `config.php` greifen erst nach einem Settings-Reset der
> Section. Die `frontend.css` wirkt dagegen sofort (ggf. Browser-Cache mit
> Strg+F5 leeren).

## Optik / Designkonzept

Jeder Eintrag ist eine **Karte** im Media-Object-Muster: runder Avatar links,
Inhalt rechts. Eigenständige, etwas verspieltere Farbwelt als default/grid
(grüne/lila/gelbe Tags), die Struktur ist aber dieselbe. Alle Regeln sind auf
`.mod_nwi_avatar` gescoped (jede View lädt nur ihre eigene `frontend.css`).

### Komponenten

- **Karte** (`.mod_nwi_group`)
  Flex-Zeile, weißer Hintergrund, dezenter Rahmen (`#e2e8f0`), abgerundet.

- **Avatar** (`.mod_nwi_teaserpic`)
  Feste, runde 84×84-Kachel (`border-radius: 50%`, `object-fit: cover`).
  Fehlt ein Bild, füllt die **Platzhalter-Kachel** mit der Titel-Initiale den
  Kreis automatisch als runder Initial-Avatar (siehe unten).

- **Inhalt** (`.mod_nwi_teasertext`)
  Titel, Metadaten (`[TEXT_POSTED_BY] … [PUBLISHED_DATE]` – für Blog nützlich,
  für reine Team-Listen leicht aus dem `$post_loop` entfernbar) und Kurztext.
  Unten die `.mod_nwi_bottom`-Zeile: **Tags links, „Weiterlesen"-Button rechts**
  (umbruchfähig).

- **Tags** (`.mod_nwi_tag`)
  Eigene Palette (Lila/Gelb/Navy/Grün). Schriftfarbe ist je nach Tag-Helligkeit
  gesetzt – helle Tags (Grün/Gelb) dunkle Schrift, dunkle (Lila/Navy) weiß –
  für ausreichenden Kontrast.

- **„Weiterlesen"-Button** (`.mod_nwi_readmore a`)
  Gefülltes Navy mit `»`-Pfeil und dezentem Hover.

- **Leseansicht – Teaser-Box** (`.mod_nwi_content_short`)
  Helle Lead-Box wie default/grid. Bilder kommen hier aus dem Fließtext und
  werden **schonend gerahmt** (`max-width`, `height: auto`, gerundete Ecken)
  statt fest beschnitten, um Inhaltsbilder nicht zu verzerren.

- **Navigation** (`.mod_nwi_nav`)
  Vor/Zurück/Übersicht bzw. Paginierung als neutrale Flex-`<nav>` mit drei
  gleich breiten Zonen. Eigene Pfeil-Glyphen: `«` / `»` für vor/zurück,
  `^ … ^` um den Übersicht-Link (per CSS, nicht aus dem Template).

### Fehlende Beitragsbilder

Ohne Beitrags-, Gruppen- und Section-Standardbild wird **kein** Platzhalter-
Pixel mehr skaliert:

- **Leseansicht:** gar kein Bild – der Text nutzt die volle Breite.
- **Liste:** eine **Platzhalter-Kachel** mit der Titel-Initiale auf Navy
  (`.mod_nwi_noimage`), die der runde Avatar-Rahmen automatisch zum runden
  Initial-Avatar beschneidet.

Die Unterscheidung erfolgt in `mod_nwi_post_process()` über `defined('POST_ID')`
(in der Leseansicht gesetzt, in der Liste nicht). Die Kachel-Optik liegt inline
am Element, damit sie in allen Views einheitlich aussieht.

## Anpassen

- **Avatar-Größe:** `width`/`height` bei `.mod_nwi_avatar .mod_nwi_teaserpic`
  (Standard 84×84). Für eckige Bilder `border-radius` reduzieren.
- **Metadaten-Zeile:** im `$post_loop` der `config.php` entfernen, wenn nur
  Name/Titel ohne Datum gewünscht ist (z. B. Teamvorstellung).
- **Tag-Farben:** in `frontend.css` (`.mod_nwi_avatar .mod_nwi_tag …`); Schrift-
  farbe je Tag entsprechend der Helligkeit mit anpassen.
- **Eigene Optik ohne Core-Änderung:** am besten eine **eigene View** anlegen
  (Verzeichnis unter `views/` kopieren) und in den Section-Settings auswählen,
  statt diese Dateien zu überschreiben – so bleiben Updates problemlos.
