<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 */
abstract class AbstractEntity {
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;
    
	/**
	 * @var \DateTime
	 * @ORM\Column(type="datetime")
	 */
	protected $dateAdded;

	public function getId() {
		return $this->id;
	}
	
	/**
	 * @return \DateTime
	 */
	public function getDateAdded(): \DateTime {
		return $this->dateAdded;
	}
	
	/**
	 * @ORM\PrePersist
	 */
	public function onPrePersistSetDateAdded() {
		$this->dateAdded = new \DateTime();
	}
}
