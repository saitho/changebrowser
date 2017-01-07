<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="appbundle_entity_asset")
 */
class Asset extends AbstractEntity {
	/**
	 * @var string
	 *
	 * @ORM\Column(type="string")
	 */
	private $title;
	
	/**
	 * @var Change
	 *
	 * @ORM\ManyToOne(targetEntity="Change", inversedBy="assets")
	 * @ORM\JoinColumn(name="change_id", referencedColumnName="id")
	 */
	private $change;
	
	/**
	 * @var int
	 */
	private $change_id;
}
