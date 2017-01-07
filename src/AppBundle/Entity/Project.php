<?php

namespace AppBundle\Entity;

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
	 * @var string
	 *
	 * @ORM\Column(type="string")
	 */
	private $externalId;
	
	/**
	 * @var string
	 *
	 * @ORM\Column(type="string")
	 */
	private $source;
	
	/**
	 * @return string
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
	 * @return string
	 */
	public function getExternalId() {
		return $this->externalId;
	}
	
	/**
	 * @param string $externalId
	 */
	public function setExternalId(string $externalId) {
		$this->externalId = $externalId;
	}
	
	/**
	 * @param string $source
	 */
	public function setSource(string $source) {
		$this->source = $source;
	}
	
	/**
	 * @param string $title
	 */
	public function setTitle(string $title) {
		$this->title = $title;
	}
}
