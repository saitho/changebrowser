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

use AppBundle\DBAL\EnumChangeTypeType;
use AppBundle\Entity\Change;
use AppBundle\Entity\ChangeContent;
use AppBundle\Entity\Project;
use AppBundle\Entity\Source\AbstractSource;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FetchChangesCommandOutput extends \Exception {
}

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
     * @var EntityManager
     */
    private $entityManager;
    
    private $complete = false;
    private $update = false;

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
			->addOption('complete', 'c')
			->addOption('update', 'u')
        ;
    }
	
	/**
	 * @param \Symfony\Component\Console\Input\InputInterface   $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 */
    protected function initialize(InputInterface $input, OutputInterface $output) {
        $this->entityManager = $this->getContainer()->get('doctrine')->getManager();
        if($input->getOption('complete')) {
        	$this->complete = true;
		}
        if($input->getOption('update')) {
        	$this->update = true;
		}
    }
	
    private function fetchChangesForProject(Project $project, OutputInterface $output) {
		$output->writeln(sprintf(
			'[INFO] Fetching changes from project %s (ID %s)',
			$project->getTitle(),
			$project->getId()
		));
		try {
			$sourceRepo = $this->entityManager->getRepository(AbstractSource::class);
			/** @var AbstractSource $source */
			$source = $sourceRepo->find($project->getSource());
			$source->setProject($project);
			$changeRepo = $this->entityManager->getRepository(Change::class);
			
			$version = '';
			if($this->complete) {
				// if complete: start at the last inserted change
				$lastPersistedChange = $changeRepo->findOneBy(['project' => $project], ['date' => 'ASC']);
				if(empty($lastPersistedChange)) {
					throw new FetchChangesCommandOutput('No entries found to complete. Please run command without --complete option.');
				}
				$startId = $lastPersistedChange->getExternalId();
				$version = $lastPersistedChange->getVersion();
			}else{
				// if regular fetch: start with the first change from change list
				$startId = $source->getFirstChangeExternalId();
				$firstChangeLocal = $changeRepo->findOneBy(['externalId' => $startId]);
				if(!empty($firstChangeLocal) && !$this->update) {
					throw new FetchChangesCommandOutput('No new changes.');
				}
				if(!empty($firstChangeLocal)) {
					$version = $firstChangeLocal->getVersion();
				}
			}
			
			$this->fetchChangesByParents($source, $output, [$startId], $version);
		} catch (FetchChangesCommandOutput $e) {
			$output->writeln($e->getMessage());
		}
	}
	
	private function fetchChangesByParents(AbstractSource $source, OutputInterface $output, $parents, $version='') {
    	if(empty($parents)) {
    		if($output->isVerbose()) {
    			$output->writeln('[INFO] Finished.');
			}
    		return;
		}
    	$newParents = [];
		foreach($parents AS $externalId) {
			$thisParents = $this->loadDataForExternalId($source, $output, $externalId, $version);
			$newParents = array_merge($newParents, $thisParents);
		}
		$this->fetchChangesByParents($source, $output, $newParents, $version);
	}
		
	private function loadDataForExternalId(AbstractSource $source, OutputInterface $output, $externalId, &$_version='') {
		$project = $source->getProject();
		$newEntry = false;
		$changeDetails = $source->getChangeDetails($externalId, $_version);
		$changeRepo = $this->entityManager->getRepository(Change::class);
				
		// ['id' => $externalId, 'title' => $title, 'author' => $author, 'date' => $date, 'version' => $version] = $changeDetails['change'];
		$externalId = $changeDetails['change']['id'];
		$title = $changeDetails['change']['title'];
		$author = $changeDetails['change']['author'];
		$date = $changeDetails['change']['date'];
		$version = $changeDetails['change']['version'];
		$parents = $changeDetails['change']['parents'];
		$_version = $version;
		
		// only get first line to avoid very long commit messages (e.g. TYPO3 CMS)
		$title = strstr($title."\n", "\n", true);
		
		$change = $changeRepo->findOneBy(['project' => $project, 'externalId' => $externalId]);
		if(!$change) {
			$change = new Change();
			$newEntry = true;
		}
		$change->setTitle($title);
		$change->setAuthor($author);
		$change->setExternalId($externalId);
		$change->setProject($project);
		$change->setVersion($version);
		if(!empty($parents)) {
			$change->setParent($parents[0]);
		}
		
		$dateObject = new \DateTime($date);
		$dateObject->setTimezone(new \DateTimeZone('UTC'));
		if($change->getDate() != $dateObject) {
			$change->setDate($dateObject);
		}
		
		$allowedTypes = EnumChangeTypeType::$values;
		foreach($allowedTypes AS $allowedType) {
			if($allowedType === null) {
				continue;
			}
			if(preg_match('/^(('.$allowedType.'|\['.$allowedType.'\])(.*:)?)/i', $title)) {
				$change->setType($allowedType);
				break;
			}
		}
		$this->entityManager->persist($change);
		
		if($newEntry) {
			foreach($changeDetails['contents'] AS $content) {
				$changeContent = new ChangeContent();
				$changeContent->setExternalId($content['id']);
				$changeContent->setFilename($content['filename']);
				$changeContent->setStatus($content['status']);
				$changeContent->setAdditions($content['additions']);
				$changeContent->setDeletions($content['deletions']);
				$changeContent->setChanges($content['changes']);
				$changeContent->setPatch($content['patch']);
				$changeContent->setChange($change);
				$this->entityManager->persist($changeContent);
			}
			$msg = sprintf('[OK] Change %s was successfully created: %s', $externalId, $title);
		}elseif($this->update) {
			// computeChangeSets is used internally and we want to avoid errors there
			// that's why we unfortunately have to clone the UnitOfWork... :(
			$uow = clone $this->entityManager->getUnitOfWork();
			$uow->computeChangeSets();
			if(count($uow->getEntityChangeSet($change))) {
				$msg = sprintf('[OK] Change %s was successfully updated: %s', $externalId, $title);
			}else if($output->isVerbose()) {
				$msg = sprintf('[INFO] No updates for Change id %s: %s', $change->getId(), $change->getTitle());
			}
		}
		$this->entityManager->flush();
		if(!empty($msg)) {
			$output->writeln($msg);
		}
		
		if(empty($changeDetails['change']['parents'])) {
			$changeDetails['change']['parents'] = [];
		}
		return $changeDetails['change']['parents'];
	}
	
	/**
	 * @param \Symfony\Component\Console\Input\InputInterface   $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 * @return void
	 */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $projectId = $input->getArgument('project');

        if(!empty($projectId)) {
        	$project = null;
        	if(is_numeric($projectId)) {
				$project = $this->entityManager->getRepository(Project::class)->find($projectId);
			}
        	if(empty($project)) {
				$project = $this->entityManager->getRepository(Project::class)->findOneBy(['externalId'=>$projectId]);
			}
			$this->fetchChangesForProject($project, $output);
		}else{
			$projects = $this->entityManager->getRepository(Project::class)->findAll();
			if(count($projects)) {
				foreach($projects AS $project) {
					$this->fetchChangesForProject($project, $output);
				}
			}else{
				$output->writeln('[INFO] No projects found.');
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
