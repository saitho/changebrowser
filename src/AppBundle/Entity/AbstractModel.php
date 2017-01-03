<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

abstract class AbstractModel {
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

	public function getId()
	{
		return $this->id;
	}
}
