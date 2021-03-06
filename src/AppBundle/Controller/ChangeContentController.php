<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Change;
use AppBundle\Entity\ChangeContent;
use AppBundle\Helper\ReWatajaxDoctrine;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/changes/content")
 * @Security("has_role('ROLE_ADMIN')")
 */
class ChangeContentController extends Controller {
	/**
	 * @param ChangeContent $content
	 * @return Response
	 *
	 * @Route("/diff/{id}", name="ajax_changecontent_diff")
	 * @Method("GET")
	 */
	public function diffAction(ChangeContent $content) {
		/** @var $content ChangeContent */
		$lines = explode("\n", $content->getPatch());
		$newLines_left = [];
		$newLines_right = [];
		$newLines_spacers = [];
		$start_line = [];
		foreach ($lines as $line) {
			if (preg_match('/^\@\@ -(\d+),\d+ \+\d+,\d+ \@\@/', $line, $match)) {
				$st_ln_num = $match[1];
				$start_line['original'] = $st_ln_num;
				$start_line['left'] = $st_ln_num;
				$start_line['right'] = $st_ln_num;
				$line = str_replace($match[0], '', $line);
				$line = trim($line);
				$newLines_spacers[] = $st_ln_num+1;
				if(empty($line)) {
					continue;
				}
			}
			$char = strlen($line) ? $line[0] : '~';
			$line = ltrim($line, '+-');
			switch ($char) {
				case '-':
					$line_left = ++$start_line['left'];
					$type = 'removed';
					$newLines_left[$line_left] = ['type' => $type, 'content' => $line];
					break;
				case '+':
					$line_right = ++$start_line['right'];
					$type = 'added';
					$newLines_right[$line_right] = ['type' => $type, 'content' => $line];
					break;
				default:
					$line_left = ++$start_line['left'];
					$line_right = ++$start_line['right'];
					$type = 'neutral';
					$newLines_left[$line_left] = ['type' => $type, 'content' => $line];
					$newLines_right[$line_right] = ['type' => $type, 'content' => $line];
					break;
			}
		} // end foreach
		//handle data
		return $this->render('@App/home/diff.html.twig', [
			'content' => $content,
			'newLines_left' => $newLines_left,
			'newLines_right' => $newLines_right,
			'newLines_spacers' => $newLines_spacers
		]);
	}
}
