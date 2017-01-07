<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="appbundle_entity_change_content")
 */
class ChangeContent extends AbstractEntity {
	/**
	 * @var Change
	 *
	 * Many ChangeContents have One Change.
	 * @ORM\ManyToOne(targetEntity="Change", inversedBy="changeContents")
	 * @ORM\JoinColumn(referencedColumnName="id")
	 */
	private $change;
	
	/**
	 * @return \AppBundle\Entity\Change
	 */
	public function getChange(): \AppBundle\Entity\Change {
		return $this->change;
	}
	
	/**
	 * @var string
	 *
	 * @ORM\Column(type="string")
	 */
	private $filename;
	/**
	 * @var string
	 *
	 * @ORM\Column(type="string")
	 */
	private $status;
	/**
	 * @var int
	 *
	 * @ORM\Column(type="integer")
	 */
	private $additions;
	/**
	 * @var int
	 *
	 * @ORM\Column(type="integer")
	 */
	private $deletions;
	/**
	 * @var int
	 *
	 * @ORM\Column(type="integer")
	 */
	private $changes;
	/**
	 * @var string
	 *
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $patch;
	
	/**
	 * @param string $patch
	 */
	public function setPatch(string $patch) {
		$this->patch = $patch;
	}
	
	/**
	 * @return string
	 */
	public function getPatch(): string {
		return $this->patch;
	}
	
	/**
	 * @return string
	 */
	public function getFilename(): string {
		return $this->filename;
	}
	
	/**
	 * @param string $filename
	 */
	public function setFilename(string $filename) {
		$this->filename = $filename;
	}
	
	/**
	 * @return string
	 */
	public function getStatus(): string {
		return $this->status;
	}
	
	/**
	 * @param string $status
	 */
	public function setStatus(string $status) {
		$this->status = $status;
	}
	
	/**
	 * @return int
	 */
	public function getAdditions(): int {
		return $this->additions;
	}
	
	/**
	 * @param int $additions
	 */
	public function setAdditions(int $additions) {
		$this->additions = $additions;
	}
	
	/**
	 * @return int
	 */
	public function getDeletions(): int {
		return $this->deletions;
	}
	
	/**
	 * @param int $deletions
	 */
	public function setDeletions(int $deletions) {
		$this->deletions = $deletions;
	}
	
	/**
	 * @return int
	 */
	public function getChanges(): int {
		return $this->changes;
	}
	
	/**
	 * @param int $changes
	 */
	public function setChanges(int $changes) {
		$this->changes = $changes;
	}
	
	/**
	 * @param \AppBundle\Entity\Change $change
	 */
	public function setChange(\AppBundle\Entity\Change $change) {
		$this->change = $change;
	}
}
