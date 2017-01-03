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
	private $title;
	
	/**
	 * @var Project
	 *
	 * Many Changes have One Project.
	 * @ManyToOne(targetEntity="Project")
	 * @JoinColumn(referencedColumnName="id")
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
	 * @var ArrayCollection
	 *
	 * One Change has Many Assets.
	 * @OneToMany(targetEntity="Asset")
	 */
	private $assets;
}
