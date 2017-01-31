<?php

namespace AppBundle\Command;

use AppBundle\Entity\Change;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteChangesCommand extends ContainerAwareCommand {
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
            ->setName('app:changes:delete')
            ->setDescription('Deletes changes')
            ->setHelp($this->getCommandHelp())
            ->addArgument('project', InputArgument::OPTIONAL, 'ID of the project of which you want to delete the changes')
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
    
    private function deleteChange(Change $change) {
    	// Remove change contents
		foreach ($change->getChangeContents() AS $changeContent) {
			$this->entityManager->remove($changeContent);
		}
    	// Remove assets
		foreach ($change->getAssets() AS $asset) {
			$this->entityManager->remove($asset);
		}
    	// Remove change
    	$this->entityManager->remove($change);
	}
	
	/**
	 * @param \Symfony\Component\Console\Input\InputInterface   $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 * @return void
	 */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $projectId = $input->getArgument('project');
	
		$changeRepo = $this->entityManager->getRepository(Change::class);
		if(!empty($projectId)) {
			$changes = $changeRepo->findBy(['project' => $projectId]);
		}else{
			$changes = $changeRepo->findAll();
		}
		if(count($changes)) {
			$output->writeln('[INFO] Removing '.count($changes).' changes.');
			foreach($changes AS $change) {
				$this->deleteChange($change);
			}
			$this->entityManager->flush();
		}else{
			$output->writeln('[INFO] No changes found.');
		}
    }

    /**
     * The command help is usually included in the configure() method, but when
     * it's too long, it's better to define a separate method to maintain the
     * code readability.
     */
    private function getCommandHelp() {
        return <<<'HELP'
The <info>%command.name%</info> command deletes changes corresponding data:

  <info>php %command.full_name%</info>

By default the command deletes all changes. To delete changes from a certain project, add its ID to the command:

  <info>php %command.full_name%</info> <comment>projectId</comment>
HELP;
    }
}
