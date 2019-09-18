# TODO

## Index page

* Movie/Show Update
    * in Modal anzeigen
    * angezeigte Daten reduzieren
    * Layout
    * show update beschleunigen, zumindest die Darstellung
* Einstellungen
    * in Modal anzeigen
    * Labels für die Optionen
    * Check der Api-Keys
* Inhalt
    * eigener Balken für jede Kategorie
    * die letzten X Neuzugänge anzeigen
    * discover-Funktion: x zufällige Vorschläge

## Shows

* **Ok** Layout show cards
    * Titel vernünftig ausrichten
    * Titel ggf. abschneiden, wenn länger als 1 Zeile
    * Titel-Styling
* **Ok** Layout der Übersichtsseite
    * mehr Spalten ab FHD
* mehr Optionen edit dialog -> Api
    * Auswahl, welche Staffeln/Staffelarten angezeigt werden sollen    
* Usability edit dialog
    * Suchfunktion
* Tags, inkl. einer Filterfunktion
* Episodeninfo per ESC schließen

    
## Movies

* Layout Detailsbar (Infobereich mit Zusatzinfos)
* Poster per Click vergrößern
* Usability edit dialog
* infinite scrolling sollte Url ändern?
* **Ok** Suche per Click auf Darsteller bzw. Regisseur funktioniert nicht richtig (vermutlich wegen nbsp;)
* **Ok** dynamisches Suchfeld in Topbar
  * **Ok** Grundfunktion
  * **Ok** Aktive Suche kennzeichnen
  * **Ok** Wenn keine Treffer, dann Liste leeren
* Tags (als Ersatz für die Listenfunktion)

## Allgemein

* main.js aufräumen, das meiste kann vermutlich weg
* style.css entfernen
* video.php entfernen
* Templates aufräumen
* Templatestruktur ist noch nicht so richtig geil
* RestApi: Gruppieren, Aufteilen, besser strukturieren
* Util.php entfernen
* index.php aufteilen in Application-Setup und Routing -> Routing extrahieren?
* less + gulp
* css strukturieren
* bei Fehler im Frontend nicht direkt auf die /install weiterleiten, vor allem nicht bei Ajax-Abfragen
* deployment
* npm/yarn
* bootstrap lokal einbinden