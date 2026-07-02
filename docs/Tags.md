## Tags (Stichworte) in news_img

### Was sind Tags aka Stichworte?

Tags (Stichworte) sind freie Beschriftungen, die du einem Beitrag zuordnen kannst, um ihn thematisch einzuordnen. Im Gegensatz zu Gruppen – bei denen ein Beitrag genau einer Gruppe angehört – kann ein Beitrag **beliebig viele Tags** erhalten. Tags eignen sich daher für quer liegende Themen, die sich nicht sauber in eine einzige Kategorie pressen lassen.

Im Frontend werden Tags in der Regel als klickbare Badges am Beitrag angezeigt. Ob Tags im Frontend überhaupt angezeigt werden und wie sie aussehen, hängt vom entsprechenden Markup ab, welches in den **Einstellungen** bearbeitet werden kann.

---

### Tags anlegen und bearbeiten

Wechsle im Backend in der Sektion "news_img" auf den Reiter **Stichworte**.

**Wichtig:** Den Reiter **Stichworte** siehst du nur, wenn du unter **Einstellungen** den **Expertenmodus** aktiviert hast!

Dort kannst du:

- **Stichwort hinzufügen** – gib ein Stichwort ein und lege optional Farben fest.
- **Stichwort bearbeiten** – Bezeichnung und Farben nachträglich ändern.
- **Stichwort löschen** – entfernt das Stichwort aus allen Beiträgen, denen er zugeordnet war.

Jeder Tag hat:

| Feld | Bedeutung |
|------|-----------|
| **Tag** | Das Stichwort (z. B. „Vereinsnews", „Ergebnis") |
| **Hintergrundfarbe** | Farbe des Badge-Hintergrunds im Frontend |
| **Schriftfarbe** | Farbe des Badge-Textes; leer lassen für automatischen Kontrast |

Die Farben werden mit einem Farbwähler eingegeben (Hex-Wert, z. B. `#3a5f8a`). Wird kein Wert angegeben, greift das Standard-Styling des Themes.

---

### Tag einem Beitrag zuordnen

Beim Anlegen oder Bearbeiten eines Beitrags gibt es den Bereich **Tags**. Dort wählst du aus den vorhandenen Tags der aktuellen Sektion aus – oder legst direkt im selben Formular einen neuen Tag an, der sofort zugeordnet werden kann.

Ein Beitrag kann **keinen, einen oder mehrere Tags** erhalten. Die Reihenfolge der Tags am Beitrag ist alphabetisch.

---

### Geltungsbereich: sektionslokal vs. global

Beim Anlegen eines neuen Tags kannst du festlegen, ob er **nur für die aktuelle Sektion** oder **seitenübergreifend** (global) gelten soll:

- **Sektionslokal:** Der Tag erscheint nur in der Sektion, in der er angelegt wurde.
- **Global (Checkbox „Global"):** Der Tag steht in allen Sektionen des Moduls zur Verfügung.

Globale Tags sind praktisch, wenn dieselben Stichworte auf mehreren Seiten konsistent verwendet werden sollen (z. B. „Ankündigung" oder „Ergebnis" für mehrere Abteilungsbereiche).

---

### Wirkung im Frontend

Tags beeinflussen **nicht** die Sichtbarkeit eines Beitrags – ein Tag kann nicht dazu genutzt werden, Beiträge auszublenden (das ist Aufgabe der Gruppen bzw. des Beitrag-eigenen Aktiv-Status). Tags dienen ausschließlich der **Kennzeichnung und Filterung**.

---

### Beispiel

Ein Sportverein nutzt Tags, um Beiträge quer zu den Abteilungen einzuordnen. Neben den Gruppen „Fußball", „Schwimmen" und „Tennis" gibt es Tags wie „Ergebnis", „Ankündigung", „Ehrung" und „Saisonstart". Ein Bericht über die Jahresabschlussfeier der Fußballabteilung bekommt so die Gruppe *Fußball* und die Tags *Ehrung* sowie *Saisonstart*. Leserinnen, die nach allen Ehrungen im Verein suchen – abteilungsübergreifend –, finden über den Tag *Ehrung* auf einen Blick alle relevanten Beiträge.

---

### Zusammenfassung

```
Beitrag kann 0–n Tags haben          → flexibler als Gruppen (1 Gruppe)
Tags steuern keine Sichtbarkeit      → nur Kennzeichnung / Filterung
Farben pro Tag konfigurierbar        → visuelle Unterscheidung im Frontend
Global-Tag verfügbar in allen        → sektionsübergreifend nutzbar
  Sektionen des Moduls
```