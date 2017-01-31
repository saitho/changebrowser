<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Project;
use AppBundle\Entity\Source\Github;
use AppBundle\Entity\User;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadFixtures implements FixtureInterface, ContainerAwareInterface {
    /** @var ContainerInterface */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager) {
        $this->loadUsers($manager);
	
		$github = new Github();
		$github->create([
			'clientId' => $this->container->getParameter('github_clientId'),
			'clientSecret' => $this->container->getParameter('github_clientSecret')
		]);
		$manager->persist($github);
	
		$project = new Project();
		$project->setSource($github);
		$project->setOptions(['source' => ['vendor' => 'saitho', 'repository' => 'watajax-doctrine']]);
		$project->setTitle('Doctrine Implementation for WATAJAX (public)');
		$manager->persist($project);
	
		$project = new Project();
		$project->setSource($github);
		$project->setOptions([
			'source' => [
				'vendor' => 'saitho',
				'repository' => 'changebrowser',
				'accessToken' => '172ebc7651b9daa618806c03fff8c25848e5a9c4'
				// 'accessToken' => '' // here you can enter a access token (required for private repositories)
			]
		]);
		$project->setTitle('Changebrowser (private)');
		$manager->persist($project);
		
		/*
		$phabricator = new \AppBundle\Entity\Source\Phabricator();
		$phabricator->create();
		$manager->persist($phabricator);
	
		$project = new Project();
		$project->setSource($phabricator);
		$project->setOptions([
			'source' => [
				'repository' => 'Git-Test',
				'conduitApiToken' => 'api-yfx5ss55a4ja5bgeaibyvjyvuwed',
				'phabricatorURL' => 'https://test-d63kbr46aiuq.phacility.com/'
			]
		]);
		$project->setTitle('Phabricator-Test');
		$manager->persist($project);
		*/
		
		$manager->flush();
    }

    private function loadUsers(ObjectManager $manager) {
        $passwordEncoder = $this->container->get('security.password_encoder');

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
