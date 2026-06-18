<?php
/**
 * Demo-Daten-Pack: Restaurant
 *
 * Top-Level-Keys:
 *   settings (optional) — wird auf mod_news_img_settings angewendet
 *                         (allowlist + Validierung im Importer); mode='advanced'
 *                         wird automatisch gesetzt, wenn der Pack Tags hat
 *   groups, tags, posts — Referenzen per Title bzw. Tag-Name
 *
 * Bildquellen werden zusätzlich in <slug>/copyright.csv abgelegt
 * (Sidecar-Format) — der Importer ignoriert die CSV (nur jpg/jpeg/gif/
 * png/webp werden kopiert). Das geplante Bild-Credit-Feature soll später
 * diese CSV-Daten als Quellenangabe in die DB ziehen.
 */
if (!defined('WB_PATH')) {
    exit('Cannot access this file directly');
}

// Zufalls-Timestamp innerhalb der letzten 90 Tage; pro Aufruf neu, damit
// die Demo-Posts beim Import jeweils mit frischen Daten erscheinen.
$random_recent = function () {
    return time() - mt_rand(0, 90 * 86400);
};

return [

    // Section-Settings, die beim Import auf mod_news_img_settings angewendet
    // werden. Allowlist + per-Key-Validierung im Importer; unbekannte oder
    // ungültige Keys werden ignoriert (und in den Import-Errors gemeldet).
    // mode='advanced' wird automatisch gesetzt, weil dieser Pack Tags nutzt —
    // muss hier also nicht explizit aufgeführt werden.
    'settings' => [
        'imgthumbsize'   => '200x200',
        'resize_preview' => '300x300',
    ],

    'groups' => [
        ['title' => 'Wochenkarte',    'active' => 1, 'position' => 1],
        ['title' => 'Events',         'active' => 1, 'position' => 2],
        ['title' => 'Aus der Küche',  'active' => 1, 'position' => 3],
    ],

    'tags' => [
        ['tag' => 'Saisonal', 'tag_color' => '#5d9e6c'],
        ['tag' => 'Event',    'tag_color' => '#e67e22'],
        ['tag' => 'Region',   'tag_color' => '#27ae60'],
        ['tag' => 'Wein',     'tag_color' => '#8e44ad'],
    ],

    'posts' => [

        // ---------- Wochenkarte ----------
        [
            'slug'            => 'fruehlingskarte',
            'title'           => 'Frühlingskarte ist da',
            'link'            => '/restaurant-fruehlingskarte',
            'group'           => 'Wochenkarte',
            'tags'            => ['Saisonal'],
            'active'          => 1,
            'preview_image'   => 'preview.jpg',
            'images'          => ['gericht-01.jpg', 'gericht-02.jpg', 'gericht-03.jpg'],
            'content_short'   => '<p>Die neue Frühlingskarte ist da: junge Salate, Bärlauch, erster Spargel und frische Kräuter aus der Region. Wir laden ein, die leichte Saison zu probieren.</p>',
            'content_long'    => '<p>Auf unserer aktuellen Karte stehen unter anderem ein Wildkräutersalat mit pochiertem Ei, Bärlauch-Tagliatelle mit gerösteten Pinienkernen sowie ein Frühlingsgemüse-Risotto mit gebratener Forelle aus heimischer Zucht. Dazu empfehlen wir einen jungen Grauburgunder aus dem Markgräflerland. Reservierungen telefonisch oder über unser Online-Formular.</p>',
            'content_block2'  => '',
            'published_when'  => $random_recent(),
            'published_until' => 0,
        ],
        [
            'slug'            => 'spargelzeit',
            'title'           => 'Spargelzeit – die Klassiker und Neues',
            'link'            => '/restaurant-spargelzeit',
            'group'           => 'Wochenkarte',
            'tags'            => ['Saisonal', 'Region'],
            'active'          => 1,
            'preview_image'   => 'preview.jpg',
            'images'          => ['gericht-01.jpg', 'gericht-02.jpg'],
            'content_short'   => '<p>Vom Spargelhof Müller, nur wenige Kilometer entfernt: stangenfrisch geernteter weißer und grüner Spargel. Klassisch mit Sauce Hollandaise oder ungewöhnlich kombiniert.</p>',
            'content_long'    => '<p>Klassiker bleiben Klassiker: weißer Spargel mit Sauce Hollandaise, neuen Kartoffeln und entweder Schinken aus dem Schwarzwald oder einem Pochiertem Ei. Daneben experimentieren wir mit grünem Spargel im Risotto, als Bestandteil einer asiatisch inspirierten Wokpfanne und als feines Spargel-Carpaccio mit Parmesan und Olivenöl. Die Spargelsaison läuft – wie immer – bis Johannistag, dem 24. Juni.</p>',
            'content_block2'  => '',
            'published_when'  => $random_recent(),
            'published_until' => 0,
        ],
        [
            'slug'            => 'sonntagsbrunch',
            'title'           => 'Sonntagsbrunch ist zurück',
            'link'            => '/restaurant-sonntagsbrunch',
            'group'           => 'Wochenkarte',
            'tags'            => ['Event'],
            'active'          => 1,
            'preview_image'   => 'preview.jpg',
            'images'          => ['gericht-01.jpg', 'gericht-02.jpg', 'gericht-03.jpg', 'ritae-eclair-3366430_1280.jpg'],
            'content_short'   => '<p>Jeden Sonntag von 10 bis 14 Uhr: unser ausgiebiges Brunch-Buffet mit allem, was zu einem entspannten Sonntagmorgen gehört. Reservierung empfohlen.</p>',
            'content_long'    => '<p>Frisches Brot vom Holzofenbäcker, hausgemachte Marmeladen, regionale Käse- und Wurstplatten, Räucherfisch, eine kleine warme Auswahl (Rührei, Bratkartoffeln, Mini-Pancakes) und natürlich Kuchen und Süßes für danach. Kaffee, Tee, Säfte und ein Glas Sekt sind im Preis inbegriffen. Erwachsene 29,50 €, Kinder bis 12 Jahre die Hälfte, unter 6 essen kostenfrei mit.</p>',
            'content_block2'  => '',
            'published_when'  => $random_recent(),
            'published_until' => 0,
        ],

        // ---------- Events ----------
        [
            'slug'            => 'weinabend',
            'title'           => 'Weinabend am Freitag',
            'link'            => '/restaurant-weinabend',
            'group'           => 'Events',
            'tags'            => ['Event', 'Wein'],
            'active'          => 1,
            'preview_image'   => 'preview.jpg',
            'images'          => [],
            'content_short'   => '<p>An jedem ersten Freitag im Monat öffnen wir eine ausgewählte Weinregion – mit fünf Weinen, kleinen Begleitspeisen und ein paar Worten zur Erzeugerin oder zum Erzeuger.</p>',
            'content_long'    => '<p>Im kommenden Monat geht es ins Piemont: Arneis, Barbera, ein junger und ein gereifter Nebbiolo sowie ein Moscato zum Abschluss. Dazu hauchdünner Vitello Tonnato, Vitello mit Salbei, Risotto Milanese und ein kleines Dolce. Beginn 19:00 Uhr, Dauer ca. zweieinhalb Stunden, 65 € pro Person inkl. allem.</p>',
            'content_block2'  => '',
            'published_when'  => $random_recent(),
            'published_until' => 0,
        ],
        [
            'slug'            => 'live-musik',
            'title'           => 'Live-Musik am Wochenende',
            'link'            => '/restaurant-live-musik',
            'group'           => 'Events',
            'tags'            => ['Event'],
            'active'          => 1,
            'preview_image'   => '',
            'images'          => [],
            'content_short'   => '<p>Samstags ab 20 Uhr Live-Musik bei uns – meistens akustisch, immer dezent: vom Jazz-Standard bis zur entspannten Singer-Songwriter-Nummer.</p>',
            'content_long'    => '<p>Die Auftritte sind im Restaurant integriert, kein Eintritt, keine Anmeldung. Reservierungen sind an Live-Abenden besonders zu empfehlen – die Plätze rund um die kleine Bühne sind schnell belegt. Das aktuelle Programm hängt im Gastraum aus und steht auf unserer Website.</p>',
            'content_block2'  => '',
            'published_when'  => $random_recent(),
            'published_until' => 0,
        ],
        [
            'slug'            => 'pasta-kochkurs',
            'title'           => 'Kochkurs: Pasta selbstgemacht',
            'link'            => '/restaurant-pasta-kochkurs',
            'group'           => 'Events',
            'tags'            => ['Event'],
            'active'          => 1,
            'preview_image'   => '',
            'images'          => [],
            'content_short'   => '<p>Einen Vormittag lang Nudeln machen wie in Italien: Teig kneten, ausrollen, formen – und am Ende selber essen. Maximal acht Plätze pro Termin.</p>',
            'content_long'    => '<p>Unser Sous-Chef Marco zeigt die Basics (klassischer Eiernudelteig, glutenfreie Variante mit Hartweizengrieß) und zwei einfache, beeindruckende Formen: Tagliatelle und Tortellini. Danach kochen wir gemeinsam eine Sauce und essen das Ergebnis zu Mittag. 95 € pro Person inkl. Schürze zum Mitnehmen, einem Glas Wein und einem Rezeptheft. Termine alle zwei Monate – aktuelle Daten siehe Veranstaltungsseite.</p>',
            'content_block2'  => '',
            'published_when'  => $random_recent(),
            'published_until' => 0,
        ],
        [
            'slug'            => 'weihnachtsfeiern',
            'title'           => 'Weihnachtsfeiern buchbar',
            'link'            => '/restaurant-weihnachtsfeiern',
            'group'           => 'Events',
            'tags'            => ['Event'],
            'active'          => 1,
            'preview_image'   => '',
            'images'          => [],
            'content_short'   => '<p>Für Firmen, Vereine und Familien: Geschlossene Gesellschaften und Tisch­reservierungen für die Adventszeit sind ab sofort buchbar. Wir empfehlen frühe Reservierung.</p>',
            'content_long'    => '<p>Wir bieten drei Menü-Varianten (3, 4 und 5 Gänge), eine vegetarische und eine vegane Hauptgang-Alternative pro Variante sowie ein Buffet ab 25 Personen. Räume: Hauptgastraum bis 40 Personen, oberer Salon bis 20 Personen, Wintergarten bis 12 Personen. Anfragen am besten per E-Mail mit Wunschdatum und Personenzahl – wir melden uns innerhalb von 24 Stunden zurück.</p>',
            'content_block2'  => '',
            'published_when'  => $random_recent(),
            'published_until' => 0,
        ],

        // ---------- Aus der Küche ----------
        [
            'slug'            => 'neue-lieferanten',
            'title'           => 'Neue Lieferanten aus der Region',
            'link'            => '/restaurant-neue-lieferanten',
            'group'           => 'Aus der Küche',
            'tags'            => ['Region'],
            'active'          => 1,
            'preview_image'   => '',
            'images'          => [],
            'content_short'   => '<p>Wir freuen uns über zwei neue Partner: einen kleinen Bio-Hof aus dem Nachbarort und eine Käserei aus dem Tal. Beide liefern jetzt wöchentlich frisch zu uns.</p>',
            'content_long'    => '<p>Vom Bio-Hof Wiedemann kommen ab sofort Kartoffeln in fünf Sorten, Wurzelgemüse und im Sommer Salate. Die Sennerei Hoch&shy;berg liefert uns einen Bergkäse, der bei ihnen mindestens 12 Monate reift, sowie einen jungen Camembert aus Rohmilch. Wir sind dankbar, dass es solche Höfe und Hand­werks­betriebe in der Nähe noch gibt – und werden weiter daran arbeiten, möglichst viele unserer Zutaten von hier zu beziehen.</p>',
            'content_block2'  => '',
            'published_when'  => $random_recent(),
            'published_until' => 0,
        ],
        [
            'slug'            => 'team',
            'title'           => 'Das Team wächst',
            'link'            => '/restaurant-team',
            'group'           => 'Aus der Küche',
            'tags'            => [],
            'active'          => 1,
            'preview_image'   => '',
            'images'          => [],
            'content_short'   => '<p>Wir freuen uns über Verstärkung: Seit diesem Monat gehören Lisa (Service) und Tobias (Patisserie) zum Team. Beide bringen viel Erfahrung mit – und gute Laune sowieso.</p>',
            'content_long'    => '<p>Lisa hat zuletzt in einem Hotel am Bodensee gearbeitet und kennt sich besonders gut mit Wein aus. Tobias hat eine klassische Patisserie-Ausbildung in Wien gemacht und entwickelt gerade unsere Dessertkarte weiter – die nächsten Wochen werden also süß. Sagt gerne Hallo, wenn ihr das nächste Mal da seid.</p>',
            'content_block2'  => '',
            'published_when'  => $random_recent(),
            'published_until' => 0,
        ],
        [
            'slug'            => 'hinter-kulissen',
            'title'           => 'Hinter den Kulissen',
            'link'            => '/restaurant-hinter-kulissen',
            'group'           => 'Aus der Küche',
            'tags'            => [],
            'active'          => 1,
            'preview_image'   => '',
            'images'          => [],
            'content_short'   => '<p>Was passiert eigentlich vor 18 Uhr, wenn die ersten Gäste kommen? Ein kleiner Blick in unseren Tagesablauf – vom Markt­einkauf bis zum „Mise en Place".</p>',
            'content_long'    => '<p>Der Tag beginnt früh: um 7 Uhr ist Marco auf dem Großmarkt, zurück gegen 9. Bis 11 Uhr werden Gemüse geputzt, Fonds angesetzt, Brot geknetet. Dann Mittagessen fürs Team (das wichtigste Essen des Tages, sagen wir). Zwischen 14 und 17 Uhr ist Vor­bereitungs­zeit: anrichten, vor­portionieren, die Karte fürs Abend­geschäft fertig­machen. Und dann geht\'s los. So sieht ein typischer Tag aus – bis auf Sonntag, da läuft alles eine Stunde später.</p>',
            'content_block2'  => '',
            'published_when'  => $random_recent(),
            'published_until' => 0,
        ],

    ],
];
