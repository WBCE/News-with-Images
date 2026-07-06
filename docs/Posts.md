## Beitrag erstellen und bearbeiten

### Beitrag anlegen

Im Backend der Seite findest du im Modul news_img die Schaltfläche **Beitrag hinzufügen**. Sie öffnet das Bearbeitungsformular, das aus drei Bereichen besteht: den Grundeinstellungen oben, dem Textinhalt in der Mitte und dem Bildbereich unten.

---

### Grundeinstellungen

#### Titel *(Pflichtfeld)*
Der Titel des Beitrags, wie er in der Übersicht und auf der Detailseite angezeigt wird.

#### Link *(nur im erweiterten Modus sichtbar)*
Die URL-Komponente für den Beitrag wird automatisch aus dem Titel erzeugt (als lesbare „Slug"-Schreibweise, z. B. `vereinsfest-2025`). Im erweiterten Modus kannst du sie manuell anpassen. Alle anderen Modi setzen den Link automatisch.

#### Vorschaubild
Das Bild, das in der Listenansicht neben dem Beitrag erscheint. Wird hier kein Bild hochgeladen, verwendet das Modul automatisch das Bild der zugeordneten Gruppe – sofern diese eines hat.

- Hochladen: Datei über das Eingabefeld auswählen.
- Löschen: Vorhandenes Bild mit dem Papierkorb-Symbol entfernen; danach kann ein neues hochgeladen werden.

#### Gruppe
Ordnet den Beitrag einer Gruppe zu. Zur Auswahl stehen die Gruppen aller Sektionen des Moduls. Wird keine Gruppe benötigt, bleibt das Feld auf „Keine".

#### Aktiv
Steuert, ob der Beitrag im Frontend sichtbar ist.

- **Ja** – Beitrag ist öffentlich sichtbar (sofern auch die Gruppe aktiv ist).
- **Nein** – Beitrag ist im Frontend ausgeblendet, bleibt aber im Backend erhalten.

> Neu angelegte Beiträge sind standardmäßig aktiv.

#### Veröffentlichungsdatum (von/bis)
Beide Felder sind optional. Mit ihnen lässt sich ein Zeitfenster festlegen, in dem der Beitrag automatisch sichtbar ist:

| Feld | Bedeutung |
|------|-----------|
| **Veröffentlichung ab** | Beitrag erscheint erst ab diesem Datum/Uhrzeit |
| **Veröffentlichung bis** | Beitrag wird nach diesem Datum/Uhrzeit ausgeblendet |

Datum und Uhrzeit lassen sich über den Kalender-Button auswählen. Mit dem Uhr-mit-X-Symbol wird ein gesetztes Datum wieder gelöscht. Werden beide Felder leer gelassen, ist der Beitrag dauerhaft sichtbar (solange er auf „Aktiv" steht).

#### Tags *(nur im erweiterten Modus, nur wenn Tags vorhanden)*
Hier wählst du per Checkbox aus den vorhandenen Tags aus, die dem Beitrag zugeordnet werden sollen. Tags mit einem Stern-Symbol sind global und stehen in allen Sektionen zur Verfügung. Mehrfachauswahl ist möglich.

---

### Textinhalt

Der Beitrag besteht aus bis zu drei Textbereichen, die jeweils mit dem WYSIWYG-Editor bearbeitet werden:

| Bereich | Bedeutung |
|---------|-----------|
| **Kurztext** | Erscheint in der Listenansicht als Vorschau/Teaser |
| **Langtext** | Der vollständige Beitragstext, sichtbar auf der Detailseite |
| **Block 2** | Zweiter Inhaltsbereich auf der Detailseite *(nur wenn in den Einstellungen aktiviert)* |

---

### Galeriebilder

Unterhalb des Textes befindet sich der Bildbereich für die Galerie des Beitrags. Hier kannst du beliebig viele Bilder hochladen, die auf der Detailseite als Bildergalerie angezeigt werden.

**Bilder hochladen:**
- Dateien in die Dropzone ziehen (Drag & Drop)
- oder über die Schaltfläche **Klicken zum Hinzufügen** aus dem Dateisystem auswählen
- Mehrfachauswahl ist möglich

**Vorhandene Bilder verwalten:**
- Jedes Bild zeigt eine Vorschau; Hover vergrößert es kurz.
- Mit dem Eingabefeld unter jedem Bild kannst du eine Bildunterschrift (Beschreibung) hinterlegen.
- Die Reihenfolge der Bilder lässt sich per Drag & Drop oder über die Pfeil-Schaltflächen ändern.
- Einzelne Bilder werden über das Löschen-Symbol entfernt.

---

### Speichern

Am Ende des Formulars gibt es zwei Speicher-Optionen:

| Schaltfläche | Aktion |
|---|---|
| **Speichern** | Speichert den Beitrag und bleibt im Bearbeitungsformular |
| **Speichern und zurück** | Speichert und kehrt zur Seitenübersicht zurück |

Mit **Zurück** (ohne Speichern) wird das Formular geschlossen und alle ungespeicherten Änderungen gehen verloren.