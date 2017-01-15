<?php

namespace AppBundle\Entity\Source;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Github extends AbstractSource {
	const changelogUrl = 'https://api.github.com/repos/{vendor}/{repository}/commits';
	const tagsUrl = 'https://api.github.com/repos/{vendor}/{repository}/tags';
	const changelogDetailsUrl = 'https://api.github.com/repos/{vendor}/{repository}/commits/{commitId}';
	private $versions = [];
	
	public function create($options = []) {
		$this->id = 'Github';
		$this->options = ['accessToken', 'vendor', 'repository', 'clientId', 'clientSecret'];
		if(!empty($options)) {
			foreach($options AS $option => $value) {
				if(in_array($option, $this->options)) {
					$this->options[$option] = $value;
				}
			}
		}
	}
	
	/**
	 * @param                           $action
	 * @param array                     $options
	 * @return Object
	 * @throws \Exception
	 */
	private function getFromURL($action, $options=[]) {
		// '{commitId}' => $changeLogId
		$url = constant('self::'.$action.'Url');
		
		$urlParams = [];
		$projectOptions = $this->project->getOptions();
		$replacements = array_merge($projectOptions['source'], $options);
		
		$keys = array_keys($replacements);
		$keys = array_map(
			function ($el) {
				return '{'.$el.'}';
			},
			$keys
		);
		$url = str_replace($keys, array_values($replacements), $url);
		
		if(!empty($projectOptions['source']['accessToken'])) {
			$urlParams[] = 'access_token='.$projectOptions['source']['accessToken'];
		}
		
		if(!empty($this->options['clientId']) && !empty($this->options['clientSecret'])) {
			$urlParams[] = 'client_id='.$this->options['clientId'];
			$urlParams[] = 'client_secret='.$this->options['clientSecret'];
		}
		
		$url .= '?'.implode('&', $urlParams);
		
		$context = stream_context_create([
			'http'=> [
				'method'=>"GET",
				'header'=>"User-Agent: Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B334b Safari/531.21.102011-10-16 20:23:10\r\n" // i.e. An iPad
			]
		]);
		try {
			$content = file_get_contents($url, false, $context);
		} catch(\Throwable $e) {
			$response_header = [];
			if(empty($http_response_header)) {
				throw new \Exception($e->getMessage());
			}
			
			foreach($http_response_header AS $headerLine) {
				preg_match('/^(.*): (.*)$/', $headerLine, $matches);
				if(!empty($matches[1]) && !empty($matches[2])) {
					$response_header[$matches[1]] = $matches[2];
				}
			}
			if(empty($response_header['X-RateLimit-Remaining'])) {
				$response_header['X-RateLimit-Remaining'] = 0;
			}
			throw new \Exception('Failed GitHub Request ('.$response_header['X-GitHub-Request-Id'].') | '.
				'Requests left per hour: '.$response_header['X-RateLimit-Remaining'].'/'.$response_header['X-RateLimit-Limit'].' | '.
				'Limit resets at: '.date('Y-m-d, H:i:s', $response_header['X-RateLimit-Reset']));
		}
		return json_decode($content);
	}
	
	public function getFirstChangeExternalId() : string {
		$commits = $this->getFromURL('changelog');
		return $commits[0]->sha;
	}
	
	private function getVersions() : array {
		if(empty($this->versions)) {
			$tags = $this->getFromURL('tags');
			foreach($tags AS $tag) {
				// Only semantic versioning accepted!
				// see: http://semver.org/
				if(preg_match('/^v?(\d+\.)?(\d+\.)?(\d+)(-.*)?$/', $tag->name)) {
					$this->versions[$tag->commit->sha] = $tag->name;
				}
			}
		}
		return $this->versions;
	}
	
	/**
	 * @param $changeLogId
	 * @return array
	 */
	public function getChangeDetails($changeLogId) : array {
		$array = [];
		$commit = $this->getFromURL('changelogDetails', ['commitId' => $changeLogId]);
				
		foreach($commit->files AS $file) {
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
		$id = $commit->sha;
		$author = $commit->commit->committer->name;
		$message = $commit->commit->message;
		$date = $commit->commit->committer->date;
		
		$versions = $this->getVersions();
		$version = '';
		// works under the assumption that $commits is ordered by commit date DESC (newest commit on top)
		if(array_key_exists($id, $versions)) {
			$version = $versions[$id];
		}
		$parents = [];
		foreach($commit->parents as $parent) {
			$parents[] = $parent->sha;
		}
		$change = [
			'id' => $id,
			'author' => $author,
			'title' => $message,
			'date' => $date,
			'version' => $version,
			'parents' => $parents
		];
		return ['change' => $change, 'contents' => $array];
	}
}
