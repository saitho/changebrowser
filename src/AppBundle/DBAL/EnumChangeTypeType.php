<?php
namespace AppBundle\DBAL;

class EnumChangeTypeType extends EnumType
{
	protected $name = 'enumChangeType';
	static public $values = [null, 'feature', 'bugfix', 'task', 'cleanup'];
	
	public function getValues() {
		return self::$values;
	}
}