<?php

namespace AppBundle\Entity\Source;

use AppBundle\Entity\Project;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="appbundle_sources")
 *
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
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
	abstract public function getChangeLogs(Project $project);
	abstract public function getChangeContent(Project $project, $changeLogId);
	
	/**
	 * @return string
	 */
	public function getId(): string {
		return $this->id;
	}
	
	/**
	 * @return array
	 */
	public function getOptions(): array {
		return $this->options;
	}
}
