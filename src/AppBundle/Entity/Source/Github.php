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
	
	public function create($settings = []) {
		$this->id = 'Github';
		$this->options = ['accessToken', 'vendor', 'repository'];
		$this->settings = ['clientId'=>'', 'clientSecret'=>''];
		if(!empty($settings)) {
			foreach($settings AS $key => $value) {
				if(array_key_exists($key, $this->settings)) {
					$this->settings[$key] = $value;
				}
			}
		}
	}
	
	/**
	 * @param       $action
	 * @param array $options
	 * @param array $params
	 * @return mixed
	 * @throws \Exception
	 */
	private function getFromURL($action, array $options=[], array $params=[]) {
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
		
		if(!empty($this->settings['clientId']) && !empty($this->settings['clientSecret'])) {
			$urlParams[] = 'client_id='.$this->settings['clientId'];
			$urlParams[] = 'client_secret='.$this->settings['clientSecret'];
		}
		foreach($params AS $param => $paramVal) {
			$urlParams[] = $param.'='.$paramVal;
		}
		
		$url .= '?'.implode('&', $urlParams);
		try {
			$curl_handle = curl_init();
			curl_setopt($curl_handle, CURLOPT_URL, $url);
			curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
			curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Changelog Browser');
			$content = curl_exec($curl_handle);
			curl_close($curl_handle);
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
			$pageCount = 1;
			while(true) {
				$tags = $this->getFromURL('tags', [], ['per_page' => 100, 'page' => $pageCount]);
				if(empty($tags)) {
					break;
				}
				foreach($tags AS $tag) {
					// semantic versioning - http://semver.org/
					$regex = '^v?(\d+)?\.?(\d+)?\.?(\d+)(-.*)?$';
					$projectOptions = $this->project->getOptions();
					if(!empty($projectOptions['alternativeVersionRegEx'])) {
						// use custom RegEx expression if version tag is not in "v1.2.3-dev" format (but still semantic!)
						// e.g. ^TYPO3_(\d)-(\d)-(\d)$ will match TYPO3_8-4-0 and turn it into 8.4.0
						$regex = $projectOptions['alternativeVersionRegEx'];
					}
					if(preg_match('/'.$regex.'/', $tag->name, $matches)) {
						$saveVersion = implode('.', [ $matches[1], $matches[2], $matches[3] ]);
						if(!empty($matches[4])) {
							$saveVersion .= $matches[4];
						}
						$this->versions[$tag->commit->sha] = $saveVersion;
					}
				}
				$pageCount++;
			}
		}
		return $this->versions;
	}
	
	/**
	 * @param $changeLogId
	 * @param $version
	 * @return array
	 */
	public function getChangeDetails($changeLogId, $version='') : array {
		$array = [];
		$commit = $this->getFromURL('changelogDetails', ['commitId' => $changeLogId]);
				
		foreach($commit->files AS $file) {
			$patch = '';
			if(!empty($file->patch)) {
				$patch = $file->patch;
			}
			$array[] = [
				'id' => $file->sha,
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
