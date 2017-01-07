<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class GithubSource extends AbstractSource {
	const changelogUrl = 'https://api.github.com/repos/{projectName}/commits';
	
	public function create() {
		$this->id = 'github';
		$this->options = [
			'accessToken' => ''
		];
	}
	
	public function getChangelogs($projectName, $lastId=null) {
		$replacements = [
			'{projectName}' => $projectName
		];
		$array = [];
		$url = self::changelogUrl;
		if(!empty($this->options['accessToken'])) {
			$url .= '?access_token='.$this->options['accessToken'];
		}
		$url = str_replace(array_keys($replacements), array_values($replacements), $url);
		
		$context = stream_context_create([
			'http'=> [
				'method'=>"GET",
				'header'=>"User-Agent: Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B334b Safari/531.21.102011-10-16 20:23:10\r\n" // i.e. An iPad
			]
		]);
		$result = file_get_contents($url, false, $context);
		$commits = json_decode($result);
		
		foreach($commits AS $commit) {
			$id = $commit->sha;
			$author = $commit->commit->committer->name;
			$message = $commit->commit->message;
			$date = $commit->commit->committer->date;
			$array[] = ['id' => $id, 'author' => $author, 'title' => $message, 'date' => $date];
		}
		
		return $array;
	}
}
