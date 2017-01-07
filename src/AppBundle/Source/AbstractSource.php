<?php

namespace AppBundle\Source;

use Doctrine\ORM\Mapping as ORM;

abstract class AbstractSource {
	protected $changelogUrl;
	
	abstract public function getChangelogs($projectName, $lastId=null);
}
