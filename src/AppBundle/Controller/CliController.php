<?php
namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Route("/cli")
 * @Security("has_role('ROLE_ADMIN')")
 */
class CliController extends Controller {
	/**
	 * @Route("/fetch_data", name="ajax_cli_fetchData")
	 * @Method({"POST", "GET"})
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function fetchDataAction(Request $request) {
		$configuration = ['command' => 'app:changes:fetch'];
		$configuration['project'] = $request->get('project_id');
		if(empty($request->get('project_id'))) {
			return new Response(json_encode(['status' => false, 'message' => 'Missing project id.']));
		}
		
		$kernel = $this->get('kernel');
		$application = new Application($kernel);
		$application->setAutoExit(false);
		
		$input = new ArrayInput($configuration);
		// You can use NullOutput() if you don't need the output
		$output = new BufferedOutput();
		$application->run($input, $output);
		
		// return the output, don't use if you used NullOutput()
		$content = $output->fetch();
		$status = true;
		
		$split = explode(PHP_EOL, $content);
		$changes_count = count($split)-2;
		if(preg_match('/No new changes/', $split[count($split)-2])) {
			$changes_count = 0;
		}
		
		// return new Response(""), if you used NullOutput()
		return new Response(json_encode(['status' => $status, 'log' => $content, 'changes_count' => $changes_count]));
	}
}