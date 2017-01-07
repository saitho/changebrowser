<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Command;

use AppBundle\Entity\Change;
use AppBundle\Entity\Project;
use AppBundle\Entity\AbstractSource;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A command console that fetches changes and stores them in the database.
 *
 * To use this command, open a terminal window, enter into your project
 * directory and execute the following:
 *
 *     $ php bin/console app:changes:fetch
 *
 * To output detailed information, increase the command verbosity:
 *
 *     $ php bin/console app:changes:fetch -vv
 */
class FetchChangesCommand extends ContainerAwareCommand {
    const MAX_ATTEMPTS = 5;

    /**
     * @var ObjectManager
     */
    private $entityManager;

    /**
     * {@inheritdoc}
     */
    protected function configure() {
        $this
            // a good practice is to use the 'app:' prefix to group all your custom application commands
            ->setName('app:changes:fetch')
            ->setDescription('Fetches changes and stores them in the database')
            ->setHelp($this->getCommandHelp())
            // commands can optionally define arguments and/or options (mandatory and optional)
            // see http://symfony.com/doc/current/components/console/console_arguments.html
            ->addArgument('project', InputArgument::OPTIONAL, 'ID of the project you want to fetch changes')
        ;
    }

    /**
     * This method is executed before the interact() and the execute() methods.
     * It's main purpose is to initialize the variables used in the rest of the
     * command methods.
     *
     * Beware that the input options and arguments are validated after executing
     * the interact() method, so you can't blindly trust their values in this method.
     */
    protected function initialize(InputInterface $input, OutputInterface $output) {
        $this->entityManager = $this->getContainer()->get('doctrine')->getManager();
    }
	
    private function fetchChangesForProject(Project $project, OutputInterface $output) {
		$output->writeln(sprintf(
			'[INFO] Fetching changes from project %s (ID %s)',
			$project->getTitle(),
			$project->getId()
		));
		$startTime = microtime(true);
    	$changeRepo = $this->entityManager->getRepository(Change::class);
    	$className = '\AppBundle\Entity\GithubSource';
		/** @var AbstractSource $source */
		$source = new $className();
	
		$changes = $source->getChangelogs($project->getExternalId());
		foreach($changes AS $changeData) {
			// Enable on PHP 7.1 instead...
			// ['id' => $externalId, 'title' => $title, 'author' => $author, 'date' => $date] = $changeData;
			
			$externalId = $changeData['id'];
			$title = $changeData['title'];
			$author = $changeData['author'];
			$date = $changeData['date'];
						
			$change = new Change();
			$change->setTitle($title);
			$change->setAuthor($author);
			$change->setDate(new \DateTime($date));
			$change->setExternalId($externalId);
			$change->setProject($project);
			$change->setVersion('0');
			
			if(!$changeRepo->findBy(['project' => $project, 'externalId' => $externalId])) {
				$this->entityManager->persist($change);
				$this->entityManager->flush();
				$output->writeln(sprintf('[OK] Change %s was successfully created: %s', $externalId, $title));
				
				if ($output->isVerbose()) {
					$finishTime = microtime(true);
					$elapsedTime = $finishTime - $startTime;
					
					$output->writeln(sprintf(
						'[INFO] New user database id: %d / Elapsed time: %.2f ms',
						$change->getId(),
						$elapsedTime * 1000
					));
				}
			}else{
				$output->writeln(sprintf('[INFO] Change %s skipped as it already exists.', $externalId));
			}
		}
	}
    
    /**
     * This method is executed after interact() and initialize(). It usually
     * contains the logic to execute to complete this command task.
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $projectId = $input->getArgument('project');

        if(!empty($projectId)) {
			$project = $this->entityManager->getRepository(Project::class)->find($projectId);
			$this->fetchChangesForProject($project, $output);
		}else{
			$projects = $this->entityManager->getRepository(Project::class)->findAll();
			foreach($projects AS $project) {
				$this->fetchChangesForProject($project, $output);
			}
		}
    }

    /**
     * The command help is usually included in the configure() method, but when
     * it's too long, it's better to define a separate method to maintain the
     * code readability.
     */
    private function getCommandHelp() {
        return <<<'HELP'
The <info>%command.name%</info> command fetches changes and saves them in the database:

  <info>php %command.full_name%</info>

By default the command fetches changes for all projects. To fetch changes for a certain project, add its ID to the command:

  <info>php %command.full_name%</info> <comment>projectId</comment>
HELP;
    }
}
