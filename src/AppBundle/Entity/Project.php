<?php

namespace AppBundle\Entity;

use AppBundle\Entity\Source\AbstractSource;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="appbundle_entity_project")
 */
class Project extends AbstractEntity {
	/**
	 * @var string
	 *
	 * @ORM\Column(type="string")
	 */
	private $title;
	
	/**
	 * Many Projects have One Source.
	 * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Source\AbstractSource")
	 * @ORM\JoinColumn(name="source", referencedColumnName="id")
	 */
	private $source;
	
	/**
	 * One Project has Many Changes.
	 * @ORM\OneToMany(targetEntity="Change", mappedBy="project")
	 * @ORM\OrderBy({"date" = "DESC"})
	 */
	private $changes;
	
	public function getChanges() {
		return $this->changes;
	}
	
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
	protected $metadata = [];
	
	/**
	 * @return array
	 */
	public function getMetadata(): array {
		return $this->metadata;
	}
	
	/**
	 * @return array
	 */
	public function getOptions(): array {
		return $this->options;
	}
	
	/**
	 * @return AbstractSource
	 */
	public function getSource() {
		return $this->source;
	}
	
	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}
	
	/**
	 * @param array $options
	 */
	public function setOptions(array $options) {
		$this->options = $options;
	}
	
	/**
	 * @param AbstractSource $source
	 */
	public function setSource(AbstractSource $source) {
		$this->source = $source;
	}
	
	/**
	 * @param string $title
	 */
	public function setTitle(string $title) {
		$this->title = $title;
	}
}
