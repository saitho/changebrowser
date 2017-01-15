<?php

namespace AppBundle\Entity\Source;

use AppBundle\Entity\Project;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Github extends AbstractSource {
	const changelogUrl = 'https://api.github.com/repos/{vendor}/{repository}/commits';
	const tagsUrl = 'https://api.github.com/repos/{vendor}/{repository}/tags';
	const changelogDetailsUrl = 'https://api.github.com/repos/{vendor}/{repository}/commits/{commitId}';
	
	public function create() {
		$this->id = 'Github';
		$this->options = ['accessToken', 'vendor', 'repository'];
	}
	
	private function getFromURL($action, Project $project, $options=[]) {
		// '{commitId}' => $changeLogId
		$url = constant('self::'.$action.'Url');
		
		$projectOptions = $project->getOptions();
		$replacements = array_merge($projectOptions['source'], $options);
		if(!empty($projectOptions['source']['accessToken'])) {
			$url .= '?access_token='.$projectOptions['source']['accessToken'];
		}
		
		$keys = array_keys($replacements);
		$keys = array_map(
			function ($el) {
				return '{'.$el.'}';
			},
			$keys
		);
		$url = str_replace($keys, array_values($replacements), $url);
		
		$context = stream_context_create([
			'http'=> [
				'method'=>"GET",
				'header'=>"User-Agent: Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B334b Safari/531.21.102011-10-16 20:23:10\r\n" // i.e. An iPad
			]
		]);
		return file_get_contents($url, false,$context);
	}
	
	public function getChangeLogs(Project $project) {
		$array = [];
		$versions = [];
		$versionResult = $this->getFromURL('tags', $project);
		$tags = json_decode($versionResult);
		foreach($tags AS $tag) {
			// Only semantic versioning accepted!
			// see: http://semver.org/
			if(preg_match('/^v?(\d+\.)?(\d+\.)?(\d+)(-.*)?$/', $tag->name)) {
				$versions[$tag->commit->sha] = $tag->name;
			}
		}
		
		$result = $this->getFromURL('changelog', $project);
		$commits = json_decode($result);
		
		$version = '';
		foreach($commits AS $commit) {
			$id = $commit->sha;
			$author = $commit->commit->committer->name;
			$message = $commit->commit->message;
			$date = $commit->commit->committer->date;
			// works under the assumption that $commits is ordered by commit date DESC (newest commit on top)
			if(array_key_exists($id, $versions)) {
				$version = $versions[$id];
			}
			$array[] = [
				'id' => $id,
				'author' => $author,
				'title' => $message,
				'date' => $date,
				'version' => $version
			];
		}
		
		return $array;
	}
	
	public function getChangeContent(Project $project, $changeLogId) {
		$array = [];
		$result = $this->getFromURL('changelogDetails', $project, ['commitId' => $changeLogId]);
		$commits = json_decode($result);
		
		foreach($commits->files AS $file) {
			$patch = '';
			if(!empty($file->patch)) {
				$patch = $file->patch;
			}
			$array[] = [
				'externalId' => $file->sha,
				'filename' => $file->filename,
				'status' => $file->status,
				'additions' => $file->additions,
				'deletions' => $file->deletions,
				'changes' => $file->changes,
				'patch' => $patch
			];
		}
		
		return $array;
	}
}
