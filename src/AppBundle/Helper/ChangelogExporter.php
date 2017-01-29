<?php
namespace AppBundle\Helper;

use AppBundle\Entity\Change;
use AppBundle\Entity\Project;
use AppBundle\Helper\ChangelogFormats\AbstractChangeLogFormat;
use Doctrine\ORM\EntityManager;
use Symfony\Component\CssSelector\XPath\TranslatorInterface;
use Symfony\Component\Translation\Translator;

class ChangelogExporter {
	/** @var AbstractChangeLogFormat $formatGeneratorClass */
	private $formatGenerator;
	private $tags;
	private $versions;
	
	/** @var \AppBundle\Entity\Project $project */
	private $project;
	/** @var EntityManager $entityManager */
	private $entityManager;
	
	/** @var Translator */
	private $translator;
	
	public function __construct($translator, $entityManager, Project $project, $format, $tags, $versions) {
		foreach($tags AS &$tag) {
			if(empty($tag)) {
				$tag = null;
			}
		}
		
		$this->tags = $tags;
		$this->versions = $versions;
		$this->project = $project;
		$this->entityManager = $entityManager;
		$this->translator = $translator;
		
		$formatGeneratorClass = '\AppBundle\Helper\ChangelogFormats\ChangeLogFormat'.ucfirst(strtolower($format));
		$this->formatGenerator = new $formatGeneratorClass($project);
	}
	
	public function getMetadata() {
		return [
			'fileName' => $this->formatGenerator->getFileName(),
			'contentType' => $this->formatGenerator->getContentType()
		];
	}
	
	public function generateContent() {
		$content = '';
		$content .= $this->formatGenerator->generateHeader();
		
		$changeRepo = $this->entityManager->getRepository(Change::class);
		$criteria = [
			'project' => $this->project,
			'type' => $this->tags
		];
		
		foreach ($this->versions AS $version) {
			$changes = $changeRepo->findBy(array_merge($criteria, ['version' => $version]));
			if(!count($changes)) {
				continue;
			}
			$versionText = $version;
			if(empty($versionText)) {
				$versionText = $this->translator->trans('label.unreleased');
			}
			$content .= $this->formatGenerator->generateVersionHeader($versionText);
			foreach($changes AS $change) {
				$content .= $this->formatGenerator->generateChangeEntry($change);
			}
			$content .= $this->formatGenerator->generateVersionFooter($versionText);
		}
		$content .= $this->formatGenerator->generateFooter();
		
		return $content;
	}
	
	public function startDownload() {
		$metadata = $this->getMetadata();
		$file_out = $this->generateContent();
		
		$out = strlen($file_out);
		header('Cache-Control: public'); // needed for internet explorer
		header('Content-Length: '.$out);
		header('Content-type: '.$metadata['contentType']);
		header('Content-Disposition: attachment; filename='.$metadata['fileName']);
		echo $file_out;
		die;
	}
}