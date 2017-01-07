<?php
namespace AppBundle;

use Doctrine\DBAL\Types\Type;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AppBundle extends Bundle {
	public function boot() {
		parent::boot();
		Type::addType('enumChangeType', 'AppBundle\DBAL\EnumChangeTypeType');
	}
}
