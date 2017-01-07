<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="appbundle_entity_change")
 */
class Change extends AbstractEntity {
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
	private $title;
	
	/**
	 * @var Project
	 *
	 * Many Changes have One Project.
	 * @ORM\ManyToOne(targetEntity="Project")
	 * @ORM\JoinColumn(referencedColumnName="id")
	 */
	private $project;
	
	/**
	 * @var string
	 *
	 * @ORM\Column(type="string")
	 */
	private $version;
	
	/**
	 * @var string
	 *
	 * @ORM\Column(type="string", columnDefinition="ENUM('feature', 'bugfix', 'task', 'cleanup')")
	 */
	private $type;
	
	/**
	 * @var string
	 *
	 * @ORM\Column(type="string")
	 */
	private $author;
	
	/**
	 * @var ArrayCollection
	 *
	 * One Change has Many Assets.
	 * @ORM\OneToMany(targetEntity="Asset", mappedBy="change_id")
	 */
	private $assets;
	
	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(type="datetime")
	 */
	private $date;
	
	/**
	 * @param string $externalId
	 */
	public function setExternalId($externalId) {
		$this->externalId = $externalId;
	}
	
	/**
	 * @return string
	 */
	public function getExternalId() {
		return $this->externalId;
	}
	
	/**
	 * @param \DateTime $date
	 */
	public function setDate($date) {
		$this->date = $date;
	}
	
	/**
	 * @return \DateTime
	 */
	public function getDate() {
		return $this->date;
	}
	
	/**
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}
	
	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}
	
	/**
	 * @param string $version
	 */
	public function setVersion($version) {
		$this->version = $version;
	}
	
	/**
	 * @return string
	 */
	public function getVersion() {
		return $this->version;
	}
	
	/**
	 * @return \AppBundle\Entity\Project
	 */
	public function getProject() {
		return $this->project;
	}
	
	/**
	 * @param \AppBundle\Entity\Project $project
	 */
	public function setProject($project) {
		$this->project = $project;
	}
	
	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}
	
	/**
	 * @param string $type
	 */
	public function setType($type) {
		$this->type = $type;
	}
	
	/**
	 * @return \Doctrine\Common\Collections\ArrayCollection
	 */
	public function getAssets() {
		return $this->assets;
	}
	
	/**
	 * @param \Doctrine\Common\Collections\ArrayCollection $assets
	 */
	public function setAssets($assets) {
		$this->assets = $assets;
	}
	
	/**
	 * @param string $author
	 */
	public function setAuthor($author) {
		$this->author = $author;
	}
	
	/**
	 * @return string
	 */
	public function getAuthor() {
		return $this->author;
	}
}