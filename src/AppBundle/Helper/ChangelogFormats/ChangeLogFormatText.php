<?php
namespace AppBundle\Helper\ChangelogFormats;

use AppBundle\Entity\Change;

class ChangeLogFormatText extends AbstractChangeLogFormat {
	public function getFileName() : string {
		return 'CHANGELOG.txt';
	}
	public function getContentType() : string {
		return 'text/plain';
	}
	
	public function generateHeader() : string {
		return '';
	}
	public function generateFooter() : string {
		return '';
	}
	
	public function generateVersionHeader($version) : string {
		return $this->project->getTitle().' - '.$version.''.PHP_EOL.'-----------------------'.PHP_EOL;
	}
	public function generateVersionFooter($version) : string {
		return PHP_EOL;
	}
	
	public function generateChangeEntry(Change $change) : string {
		return '- '.$change->getTitle().PHP_EOL;
	}
}