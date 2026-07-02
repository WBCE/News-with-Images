## Gruppen in news_img

### Was sind Gruppen?

Gruppen sind eine optionale Einteilungsmöglichkeit für Beiträge. Mit ihnen kannst du thematisch zusammengehörige Beiträge bündeln – ähnlich wie Kategorien in einem Blog.

Ein Beitrag gehört immer zu **genau einer Gruppe** (oder zu keiner Gruppe).

---

### Gruppen anlegen und bearbeiten

Im Backend der Seite findest du im Modul news_img den Bereich **Gruppen**. Dort kannst du:

- **Neue Gruppe anlegen** – gib einen Titel ein, lade optional ein Bild hoch und lege fest, ob die Gruppe aktiv sein soll.
- **Gruppe bearbeiten** – Titel, Bild und Aktiv-Status nachträglich ändern.
- **Gruppe löschen** – nur möglich, wenn ihr keine Beiträge mehr zugeordnet sind.

Jede Gruppe hat:

| Feld | Bedeutung |
|------|-----------|
| **Titel** | Anzeigename der Gruppe |
| **Bild** | Optionales Gruppenbild (z. B. für Kategorieübersichten) |
| **Aktiv** | Schaltet alle Beiträge der Gruppe sichtbar oder unsichtbar |

---

### Beitrag einer Gruppe zuordnen

Beim Anlegen oder Bearbeiten eines Beitrags gibt es das Auswahlfeld **Gruppe**. Dort wählst du aus allen verfügbaren Gruppen aus – auch gruppenübergreifend, wenn das Modul auf mehreren Seiten genutzt wird.

> **Hinweis zu „Ohne Gruppe":** Du kannst einen Beitrag auch keiner Gruppe zuordnen. Solche Beiträge sind vom Aktiv-Status einer Gruppe völlig unabhängig und immer sichtbar (sofern der Beitrag selbst aktiv ist).

---

### Was bewirkt der Aktiv-Schalter einer Gruppe?

Das ist der wichtigste Mechanismus:

- **Gruppe aktiv (Ja):** Alle Beiträge der Gruppe erscheinen im Frontend – in der Liste, im Teaser, in der Detailansicht und im RSS-Feed.
- **Gruppe inaktiv (Nein):** **Alle Beiträge dieser Gruppe werden überall ausgeblendet** – unabhängig davon, ob der einzelne Beitrag selbst auf „aktiv" steht.

Das ist praktisch, wenn du z. B. eine ganze Themenserie temporär verbergen möchtest, ohne jeden Beitrag einzeln deaktivieren zu müssen. Zum Reaktivieren reicht es, die Gruppe wieder auf „Aktiv" zu stellen.

> **Technischer Hinweis für Admins:** Beim Deaktivieren einer Gruppe werden die internen Zugriffsdateien der betroffenen Beiträge sofort entfernt – Direktlinks liefern dann eine 404-Seite, keinen leeren Inhalt. Beim Reaktivieren werden die Dateien beim nächsten Seitenaufruf automatisch neu angelegt.

---

### Gruppen auf anderen Seiten/Sektionen

Wenn das Modul news_img auf mehreren Seiten eingebunden ist, tauchen im Gruppen-Auswahlfeld auch die Gruppen der anderen Sektionen auf (mit Sektionsnummer zur Unterscheidung). So können Beiträge sektionsübergreifend einer gemeinsamen Gruppe zugeordnet werden.

---

### Zusammenfassung

```
Gruppe aktiv  + Beitrag aktiv  → sichtbar im Frontend
Gruppe inaktiv               → alle Beiträge der Gruppe unsichtbar (Frontend)
Kein Gruppe zugeordnet       → Beitrag-Aktiv-Status entscheidet allein
```