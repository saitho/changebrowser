<?php
namespace AppBundle\Helper\ChangelogFormats;

use AppBundle\Entity\Change;
use AppBundle\Entity\Project;

abstract class AbstractChangeLogFormat {
	/** @var \AppBundle\Entity\Project $project */
	protected $project;
	public function __construct(Project $project) {
		$this->project = $project;
	}
	
	abstract public function getFileName() : string;
	abstract public function getContentType() : string;
	abstract public function generateHeader() : string;
	abstract public function generateFooter() : string;
	abstract public function generateVersionHeader($version) : string;
	abstract public function generateVersionFooter($version) : string;
	abstract public function generateChangeEntry(Change $change) : string;
}