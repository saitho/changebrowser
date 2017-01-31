# Changebrowser

## Vorwort

Dieses Projekt entstand im Rahmen der Code Competition Januar 2017 von IT-Talents.de. Werde hier je nach Lust und Laune weiterentwickeln.

## Systemanforderungen
* PHP 7
* MySQL-Server

## Installation

1. Lade die Dateien auf Deinen Server
2. Verbinde dich mit Deinem Server und wechsle in das Projektverzeichnis
3. Installiere die Pakete über Composer

`php bin/composer.phar install`

Nach dem Installieren der Pakete wird die Paramater-Konfigurationsdatei erstellt.
Hierbei wirst du nach folgenden Eingaben gefragt:

* locale
  * Standardsprache (de oder en)
  * auf der Seite lässt sich die Sprache ebenfalls umstellen
  * für Demozwecke einfach Enter drücken
* SYMFONY_SECRET
  * für Demozwecke einfach Enter drücken
* database_host
  * Host deiner MySQL-Datenbank (WICHTIG!)
* database_dbname
  * Name deiner MySQL-Datenbank (WICHTIG!)
* database_user
  * Benutzername für deine MySQL-Datenbank (WICHTIG!)
* database_password
  * Passwort des MySQL-Benutzers (WICHTIG!)
* github_clientId und github_clientSecret
  * siehe https://github.com/settings/developers

Ggf. dem Ordner "var/" Schreibrechte geben, z.B. mit:
``sudo chmod -R 0770 var/``

4. Tabellen erstellen:

``php bin/console doctrine:schema:create``

5. Beispieldaten laden:

``php bin/console doctrine:fixtures:load``

Hierbei wird ein Adminnutzer für den Login angelegt sowie zwei Beispielprojekte.

Über folgenden Command können die Änderungen importiert werden (CLI-Ausführung für Erstimport empfohlen!):

`php bin/console app:changes:fetch`

* Weitere Optionen
  * --complete (-c): sucht nach Änderungen vor dem ältesten gespeicherten Change
  * --update (-u): aktualisiert die gespeicherten Daten (nützlich wenn z.B. eine Version gesetzt wurde)


## Weitere Ideen, zu denen ich bisher noch nicht gekommen bin

* Auflistung aller Änderungen zu einer Datei
* Integration weiterer Repositories
  * Phabricator
  * GitLab
* Weitere Statistiken