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
	
	/**
	 * @var array
	 *
	 * @ORM\Column(type="array")
	 */
	protected $settings = [];
	
	/** @var Project $project */
	protected $project = null;
	public function setProject(Project $project) {
		$this->project = $project;
	}
	public function getProject() {
		return $this->project;
	}
	
	abstract public function create($settings = []);
	abstract public function getFirstChangeExternalId() : string;
	abstract public function getChangeDetails($changeLogId, $version='') : array;
	
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
	/**
	 * @return array
	 */
	public function getSettings(): array {
		return $this->settings;
	}
}
