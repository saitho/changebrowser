# Changelog

## [1.0.0]
- Feature: reWATAJAX - adds option to set the fields that are searched to an array of other fields
- Bugfix: Fixes searching in REWATAJAX for non-persistent fields
- Bugfix: Sets correct url for adding a project
- Task: Updated readme
- Cleanup: Removed unused settings and minor fixes
- Task: Initial draft of readme
- Cleanup: Removes unneeded parameter
- Cleanup: Minifies CSS and JavaScript
- Cleanup: Removes unneeded code
- Feature: Adds functionality for adjusting the change type
- Bugfix: Sets correct twig path for exporter
- Task: Adds information to README

## [0.1.0]
- Task: Updates fixtures
- Feature: Adds command for deleting changes and related data
- Cleanup: Moves views to bundle
- Cleanup: Removes unused services
- Cleanup: Adds PhpDoc and removes unused imports
- Task: Adds custom change title to exporter and updates data-attribute
- Feature: Adds option to rename change titles
- [Cleanup] [Bugfix] Removes regular user, code formatting, fixes 500 error on production
- Feature: Removes subtable, adds change details instead
- Bugfix: Export modal shows correctly after switching projects
- Feature: Replaces bootstrap-growl with boostrap-notify; adds notification for missing changes
- Cleanup: Renamed label translation
- Bugfix: Fixes importer error
- Issue #3: Adds changelog exporter functionality
- Cleanup: Removed unused code and tidied styling
- Task: Moves modal close button to the right
- Task: Renamed tableobject.js to rewatajax.js
- Feature #3: Adds button for exporting changes
- Task: Adds parent to Change
- Bugfix: Variable replacements are now global
- Bugfix: Fixes wrong graph representation
- Bugfix: Sets correct graph colors and statistic data
- Cleanup: Removes debug
- Feature: Sets date filter when selecting a data point in graph
- Cleanup: Sets Content-Type header for Ajax pages
- Issue #2: Adds line graph for changes
- Feature: Adds date filter via daterangepicker
- Cleanup: Minor code formatting and documentation
- Feature: Adds statistics configuration
- Feature: Adds button for data import in backend (CLI use still recommended for initial data import!); other changes and adjustments to Javascript
- Feature: Adds sorting and search functionality
- Feature: Replaces Bootstrap 3 with Boostrap 4.0.0-alpha.6
- Task: Adds external id to ChangeContent field
- Feature: Configurable version formating
- Task: Adds Source settings
- Bugfix: Improves request stability and fixes errors when importing from scratch
- Feature: Create new projects
- Feature: New strategy of indexing changes
- Issue #5: Adds versions to Changes
- Feature: Adds file changes viewer
- Feature: Adds template for adding and editing projects
- Cleanup: Minor improvements to output
- Cleanup: Moves methods into their respective controllers
- Bugfix: Sets correct locale for JSTranslationBundle
- Feature: Adds  willdurand/js-translation-bundle for JS translation
- Revert: Removes version from Change entity
- Task: CSS improvements and adds toggler for change details
- Task: CSS style improvements
- Cleanup: Removes unused javascript, adds javascript objects for translations and paths
- Cleanup: Moved table HTML javascript to own file and it now uses internal functions
- Feature: Adds Chart.js
- Feature: Adds change browser to admin homepage
- Issue: Sets required PHP version to 7.0.9, Requires Phabricator API
- Task: Adjusted Fixtures to new Source structure
- Cleanup: PHPDoc and code cleanup
- Feature: Project configuration for admins
- Bugfix: Fixes entity mapping error
- Feature: Adds ChangeContent (e.g. File changes) for each Change entry
- Bugfix: Consistent routing of homepage
- Feature: changes:fetch CLI command now supports the externalId as input value
- Removes version from Change entity

## [0.0.1]
- Feature: Configures GitHub access token in parameters file
- Cleanup: Moved Source classes to Entities\Source
- Feature: Resolves "type hinting" from commit messages (Bugfix #..., [Cleanup], etc)
- Cleanup: Code formatting
- Bugfix: Sets correct Namespace for Slugger and Markdown
- Bugfix: Fixes Doctrine enum error
- Feature: Adds home page
- Feature #xxx: Adds CLI task for fetching changes
- [Bugfix] Fixed filter for app_dev access
- [Feature] Adds Source configurations
- Initial entities and UML2 class diagram
- Renamed from AbstractEntity
- Initial commit: Basic Symfony page
- Initial commit

