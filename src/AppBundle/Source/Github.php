<?php

namespace AppBundle\Source;

use Doctrine\ORM\Mapping as ORM;

class Github extends AbstractSource {
	const changelogUrl = 'https://api.github.com/repos/{projectName}/commits';
	
	public function getChangelogs($projectName, $lastId=null) {
		$replacements = [
			'{projectName}' => $projectName
		];
		$array = [];
		$url = str_replace(array_keys($replacements), array_values($replacements), self::changelogUrl);
		
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
