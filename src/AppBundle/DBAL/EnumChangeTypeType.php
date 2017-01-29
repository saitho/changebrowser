<?php
namespace AppBundle\DBAL;

class EnumChangeTypeType extends EnumType
{
	protected $name = 'enumChangeType';
	static public $values = ['feature', 'bugfix', 'task', 'cleanup', null];
}