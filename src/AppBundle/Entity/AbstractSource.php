<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="appbundle_sources")
 *
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"github" = "GithubSource"})
 */
abstract class AbstractSource {
	/**
	 * @var string
	 *
	 * @ORM\Id
	 * @ORM\Column(type="string", unique=true)
	 */
	protected $id;
	
	/**
	 * @var array
	 *
	 * @ORM\Column(type="array")
	 */
	protected $options = [];
	
	
	abstract public function create();
	abstract public function getChangelogs($projectName, $lastId=null);
}
