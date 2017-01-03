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
}
