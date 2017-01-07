<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Project;
use AppBundle\Entity\User;
use AppBundle\Entity\GithubSource;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the sample data to load in the database when running the unit and
 * functional tests.
 *
 * Execute this command to load the data:
 *
 *   $ php bin/console doctrine:fixtures:load
 *
 * See http://symfony.com/doc/current/bundles/DoctrineFixturesBundle/index.html
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class LoadFixtures implements FixtureInterface, ContainerAwareInterface {
    /** @var ContainerInterface */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager) {
        #$this->loadUsers($manager);
		$source = new GithubSource();
		$source->create();
		$manager->persist($source);
		
		$project = new Project();
		$project->setExternalId('saitho/watajax-doctrine');
		$project->setSource('github');
		$project->setTitle('Doctrine Implementation for WATAJAX (public)');
		$manager->persist($project);
	
		$project = new Project();
		$project->setExternalId('saitho/changebrowser');
		$project->setSource('github');
		$project->setTitle('This project (private)');
		$manager->persist($project);
		
		$manager->flush();
    }

    private function loadUsers(ObjectManager $manager) {
        $passwordEncoder = $this->container->get('security.password_encoder');

        $johnUser = new User();
        $johnUser->setUsername('john_user');
        $johnUser->setEmail('john_user@symfony.com');
        $encodedPassword = $passwordEncoder->encodePassword($johnUser, 'kitten');
        $johnUser->setPassword($encodedPassword);
        $manager->persist($johnUser);

        $annaAdmin = new User();
        $annaAdmin->setUsername('anna_admin');
        $annaAdmin->setEmail('anna_admin@symfony.com');
        $annaAdmin->setRoles(['ROLE_ADMIN']);
        $encodedPassword = $passwordEncoder->encodePassword($annaAdmin, 'kitten');
        $annaAdmin->setPassword($encodedPassword);
        $manager->persist($annaAdmin);

        $manager->flush();
    }
	
    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null) {
        $this->container = $container;
    }
}
