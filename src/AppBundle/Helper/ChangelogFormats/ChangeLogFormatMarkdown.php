<?php
namespace AppBundle\Helper\ChangelogFormats;

use AppBundle\Entity\Change;

class ChangeLogFormatMarkdown extends AbstractChangeLogFormat {
	
	public function getFileName() : string {
		return 'CHANGELOG.md';
	}
	public function getContentType() : string {
		return 'text/markdown';
	}
	
	public function generateHeader() : string {
		return '# Changelog'.PHP_EOL.PHP_EOL;
	}
	public function generateFooter() : string {
		return '';
	}
	
	public function generateVersionHeader($version) : string {
		return '## ['.$version.']'.PHP_EOL;
	}
	public function generateVersionFooter($version) : string {
		return PHP_EOL;
	}
	
	public function generateChangeEntry(Change $change) : string {
		$editedTitle = $change->getEditedTitle();
		if(!empty($editedTitle)) {
			return '- '.$editedTitle.PHP_EOL;
		}
		return '- '.$change->getTitle().PHP_EOL;
	}
}