<?php
namespace AppBundle\DBAL;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Class EnumType
 * @package AppBundle\DBAL
 * @see http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/cookbook/mysql-enums.html
 */
abstract class EnumType extends Type {
	protected $name;
	static public $values = [];
	
	public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform) {
		$values = array_map(function($val) { return "'".$val."'"; }, $this->getValues());
		return "ENUM(".implode(", ", $values).")";
	}
	
	public function convertToPHPValue($value, AbstractPlatform $platform) {
		return $value;
	}
	
	public function convertToDatabaseValue($value, AbstractPlatform $platform) {
		if (!in_array($value, $this->getValues())) {
			throw new \InvalidArgumentException("Invalid '".$this->name."' value: ".$value);
		}
		return $value;
	}
	
	public function getName() {
		return $this->name;
	}
	public function getValues() {
		return self::$values;
	}
	
	public function requiresSQLCommentHint(AbstractPlatform $platform) {
		return true;
	}
}