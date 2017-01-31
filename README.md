# Changebrowser

## Vorwort

## Installation

1. Datenbankverbindung in config/parameters.yml anpassen

2. Dateien auf den Server laden

`php bin/console doctrine:database:create`

`php bin/console doctrine:schema:create`

`php bin/console doctrine:fixtures:load`

`php bin/console app:changes:fetch`

* options
  * --complete (-c): looks for changes before the earliest indexed change
  * --update (-u): updates already indexed changes (e.g. version numbers)




## Weitere Ideen

* Auflistung aller Änderungen zu einer Datei
* Integration weiterer Repositories
  * Phabricator
  * GitLab
* Weitere Statistiken