<?php
/**
 * Demo-Daten-Pack: Stadtverwaltung
 *
 * Format:
 *   groups: liste von group definitions; referenziert per `title`
 *   tags:   globale tags (section_id=0) — werden bei Bedarf angelegt; referenziert per `tag`
 *   posts:  liste von posts mit
 *           - slug (eindeutig im Pack; bildet auch den Ordnernamen unter images/)
 *           - link (URL-Pfad, der als Page-Datei angelegt wird)
 *           - group (Title aus groups[])
 *           - tags (Liste von Namen aus tags[])
 *           - images (Liste von Dateinamen unter images/<slug>/; leer = nur Text-Post)
 *           - preview_image (Dateiname in images/<slug>/ für das Beitrags-Vorschaubild
 *                            oder leer)
 *
 * Direkter Zugriff blockieren:
 */
if (!defined('WB_PATH')) {
    exit('Cannot access this file directly');
}

$thisyear = date('Y');
$lastyear = $thisyear - 1;

// Liefert einen Unix-Timestamp innerhalb der letzten 90 Tage. Wird pro Post-
// Definition einmal aufgerufen, damit die Beispieldaten beim Import frisch
// aussehen statt mit längst vergangenen Hardcoded-Daten daherzukommen.
$random_recent = function () {
    return time() - mt_rand(0, 90 * 86400);
};

return [

    'groups' => [
        ['title' => 'Aktuelles',         'active' => 1, 'position' => 1],
        ['title' => 'Veranstaltungen',   'active' => 1, 'position' => 2],
        ['title' => 'Pressemitteilungen','active' => 1, 'position' => 3],
    ],

    'tags' => [
        ['tag' => 'Stadtentwicklung', 'tag_color' => '#4a90d9'],
        ['tag' => 'Veranstaltung',    'tag_color' => '#e67e22'],
        ['tag' => 'Presse',           'tag_color' => '#8e44ad'],
        ['tag' => 'Verwaltung',       'tag_color' => '#27ae60'],
    ],

    'posts' => [

        // ---------- Aktuelles ----------
        [
            'slug'            => 'fruehjahrsputz-stadtpark',
            'title'           => 'Frühjahrsputz im Stadtpark',
            'link'            => '/fruehjahrsputz-stadtpark',
            'group'           => 'Aktuelles',
            'tags'            => ['Verwaltung'],
            'active'          => 1,
            'preview_image'   => '',
            'images'          => [],
            'content_short'   => '<p>Am kommenden Samstag findet der alljährliche Frühjahrsputz im Stadtpark statt. Alle Bürgerinnen und Bürger sind herzlich eingeladen, mitzumachen.</p>',
            'content_long'    => '<p>Treffpunkt ist um 9:00 Uhr am Haupteingang. Werkzeug und Handschuhe werden gestellt. Im Anschluss gibt es für alle Helfer eine kleine Stärkung.</p>',
            'content_block2'  => '',
            'published_when'  => $random_recent(),
            'published_until' => 0,
        ],
        [
            'slug'            => 'neue-oeffnungszeiten-mai',
            'title'           => 'Neue Öffnungszeiten ab Mai',
            'link'            => '/neue-oeffnungszeiten-mai',
            'group'           => 'Aktuelles',
            'tags'            => ['Verwaltung'],
            'active'          => 1,
            'preview_image'   => '',
            'images'          => [],
            'content_short'   => '<p>Das Bürgeramt passt ab dem 1. Mai seine Öffnungszeiten an. Die neuen Zeiten gelten für alle Standorte im Stadtgebiet.</p>',
            'content_long'    => '<p>Montag bis Freitag: 8:00 – 18:00 Uhr, Samstag: 9:00 – 13:00 Uhr. Termine können weiterhin online gebucht werden.</p>',
            'content_block2'  => '',
            'published_when'  => $random_recent(),
            'published_until' => 0,
        ],
        [
            'slug'            => 'bauprojekt-hauptstrasse',
            'title'           => 'Bauprojekt Hauptstraße startet',
            'link'            => '/bauprojekt-hauptstrasse',
            'group'           => 'Aktuelles',
            'tags'            => ['Stadtentwicklung'],
            'active'          => 1,
            'preview_image'   => '',
            'images'          => [],
            'content_short'   => '<p>Ab dem 15. April beginnen die Bauarbeiten zur Erneuerung der Hauptstraße. Mit Einschränkungen im Straßenverkehr ist zu rechnen.</p>',
            'content_long'    => '<p>Die Baumaßnahme umfasst Fahrbahn, Gehwege sowie die Erneuerung der Leitungsinfrastruktur. Geplante Dauer: 6 Monate. Eine Umleitung ist ausgeschildert.</p>',
            'content_block2'  => '',
            'published_when'  => $random_recent(),
            'published_until' => 0,
        ],
        [
            'slug'            => 'digitalisierung-verwaltung',
            'title'           => 'Digitalisierung der Verwaltung schreitet voran',
            'link'            => '/digitalisierung-verwaltung',
            'group'           => 'Aktuelles',
            'tags'            => ['Verwaltung', 'Stadtentwicklung'],
            'active'          => 1,
            'preview_image'   => '',
            'images'          => [],
            'content_short'   => '<p>Die Stadt hat einen weiteren Meilenstein bei der Digitalisierung ihrer Verwaltungsleistungen erreicht. Über 40 Services sind nun online verfügbar.</p>',
            'content_long'    => '<p>Bürgerinnen und Bürger können Anträge, Ummeldungen und Genehmigungen künftig vollständig digital beantragen. Das neue Portal ist unter service.beispielstadt.de erreichbar.</p>',
            'content_block2'  => '',
            'published_when'  => $random_recent(),
            'published_until' => 0,
        ],

        // ---------- Veranstaltungen ----------
        [
            'slug'            => 'stadtfest-'.$thisyear,
            'title'           => 'Stadtfest '. $thisyear .' – alle Infos',
            'link'            => '/stadtfest-'. $thisyear,
            'group'           => 'Veranstaltungen',
            'tags'            => ['Veranstaltung'],
            'active'          => 1,
            'preview_image'   => '',
            'images'          => [],
            'content_short'   => '<p>Das diesjährige Stadtfest findet vom 20. bis 22. Juni auf dem Marktplatz statt. Drei Tage Musik, Kulinarik und Programm für die ganze Familie.</p>',
            'content_long'    => '<p>Auf vier Bühnen treten lokale und überregionale Künstler auf. Der Eintritt ist frei. Das vollständige Programm wird ab Mai auf der städtischen Website veröffentlicht.</p>',
            'content_block2'  => '',
            'published_when'  => $random_recent(),
            'published_until' => 0,
        ],
        [
            'slug'            => 'buergerversammlung-april',
            'title'           => 'Bürgerversammlung am 28. April',
            'link'            => '/buergerversammlung-april',
            'group'           => 'Veranstaltungen',
            'tags'            => ['Verwaltung', 'Stadtentwicklung'],
            'active'          => 1,
            'preview_image'   => '',
            'images'          => [],
            'content_short'   => '<p>Die nächste Bürgerversammlung findet am 28. April um 19:00 Uhr in der Stadthalle statt. Thema: Stadtentwicklung 2030.</p>',
            'content_long'    => '<p>Alle Einwohnerinnen und Einwohner sind eingeladen, Fragen zu stellen und Anregungen einzubringen. Eine vorherige Anmeldung ist nicht erforderlich.</p>',
            'content_block2'  => '',
            'published_when'  => $random_recent(),
            'published_until' => 0,
        ],
        [
            'slug'            => 'konzert-kurpark-open-air',
            'title'           => 'Konzert im Kurpark – Open Air Saison startet',
            'link'            => '/konzert-kurpark-open-air',
            'group'           => 'Veranstaltungen',
            'tags'            => ['Veranstaltung'],
            'active'          => 1,
            'preview_image'   => '',
            'images'          => [],
            'content_short'   => '<p>Die Open-Air-Saison im Kurpark beginnt am 1. Mai mit einem klassischen Konzert des Stadtorchesters. Eintritt frei, Spenden willkommen.</p>',
            'content_long'    => '<p>Das Programm umfasst Werke von Mozart, Vivaldi und Beethoven. Beginn ist um 17:00 Uhr, bei schlechtem Wetter findet das Konzert in der Stadthalle statt.</p>',
            'content_block2'  => '',
            'published_when'  => $random_recent(),
            'published_until' => 0,
        ],

        // ---------- Pressemitteilungen ----------
        [
            'slug'            => 'jahresbericht-'.$lastyear,
            'title'           => 'Jahresbericht '. $lastyear .' veröffentlicht',
            'link'            => '/jahresbericht-'. $lastyear,
            'group'           => 'Pressemitteilungen',
            'tags'            => ['Presse'],
            'active'          => 1,
            'preview_image'   => '',
            'images'          => [],
            'content_short'   => '<p>Der Jahresbericht ' . $lastyear . ' der Stadtverwaltung ist ab sofort zum Download verfügbar. Er fasst die wichtigsten Projekte und Zahlen des vergangenen Jahres zusammen.</p>',
            'content_long'    => '<p>Schwerpunkte des Berichts sind die Bereiche Wohnen, Mobilität und Klimaschutz. Der Bericht kann als PDF auf der städtischen Website heruntergeladen werden.</p>',
            'content_block2'  => '',
            'published_when'  => $random_recent(),
            'published_until' => 0,
        ],
        [
            'slug'            => 'auszeichnung-nachhaltige-stadtentwicklung',
            'title'           => 'Auszeichnung für nachhaltige Stadtentwicklung',
            'link'            => '/auszeichnung-nachhaltige-stadtentwicklung',
            'group'           => 'Pressemitteilungen',
            'tags'            => ['Presse', 'Stadtentwicklung'],
            'active'          => 1,
            'preview_image'   => '',
            'images'          => [],
            'content_short'   => '<p>Die Stadt wurde beim Bundeswettbewerb „Nachhaltige Kommunen" mit dem ersten Platz ausgezeichnet. Die Jury würdigte besonders das Klimaschutzkonzept 2040.</p>',
            'content_long'    => '<p>Bürgermeisterin Dr. Müller nahm den Preis in Berlin entgegen: „Diese Auszeichnung ist Ansporn und Verpflichtung zugleich. Wir werden unseren Weg konsequent fortsetzen."</p>',
            'content_block2'  => '',
            'published_when'  => $random_recent(),
            'published_until' => 0,
        ],
        [
            'slug'            => 'statement-haushaltsdebatte-'.$thisyear,
            'title'           => 'Statement zur Haushaltsdebatte '. $thisyear,
            'link'            => '/statement-haushaltsdebatte-'. $thisyear,
            'group'           => 'Pressemitteilungen',
            'tags'            => ['Presse'],
            'active'          => 1,
            'preview_image'   => '',
            'images'          => [],
            'content_short'   => '<p>Im Rahmen der aktuellen Haushaltsdebatte hat die Stadtspitze ein Statement zur Finanzlage und den geplanten Investitionen veröffentlicht.</p>',
            'content_long'    => '<p>Trotz angespannter Haushaltslage sollen die Investitionen in Bildung und Infrastruktur aufrechterhalten werden. Details werden in der Stadtratssitzung am 5. Mai vorgestellt.</p>',
            'content_block2'  => '',
            'published_when'  => $random_recent(),
            'published_until' => 0,
        ],

    ],
];
