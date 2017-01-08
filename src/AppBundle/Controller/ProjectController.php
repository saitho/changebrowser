<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Project;
use AppBundle\Form\ProjectType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/project")
 * @Security("has_role('ROLE_ADMIN')")
 */
class ProjectController extends Controller
{
	/**
	 * @Route("/", name="project_index")
	 * @Method("GET")
	 */
	public function indexAction() {
		$entityManager = $this->getDoctrine()->getManager();
		$projects = $entityManager->getRepository(Project::class)->findBy([], ['id' => 'ASC']);
		
		return $this->render('project/index.html.twig', ['projects' => $projects]);
	}
	
	/**
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
	 *
	 * @Route("/new", name="project_new")
	 * @Method({"GET", "POST"})
	 */
    public function newAction(Request $request)
    {
        $project = new Project();

        // See http://symfony.com/doc/current/book/forms.html#submitting-forms-with-multiple-buttons
        $form = $this->createForm(ProjectType::class, $project);

        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($project);
            $entityManager->flush();

            $this->addFlash('success', 'platform.created_successfully');

            if ($form->get('saveAndCreateNew')->isClicked()) {
                return $this->redirectToRoute('project_new');
            }

            return $this->redirectToRoute('project_index');
        }

        return $this->render('project/new.html.twig', [
            'project' => $project,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a Project entity.
     *
	 * @param Project $project
	 * @return Response
	 *
     * @Route("/{id}", requirements={"id": "\d+"}, name="project_show")
     * @Method("GET")
     */
    public function showAction(Project $project) {
        $deleteForm = $this->createDeleteForm($project);
        
        return $this->render('project/show.html.twig', [
            'project' => $project,
            'delete_form' => $deleteForm->createView(),
        ]);
    }
	
	/**
	 * @param \AppBundle\Entity\Project                 $project
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
	 *
	 * @Route("/{id}/edit", requirements={"id": "\d+"}, name="project_edit")
	 * @Method({"GET", "POST"})
	 */
    public function editAction(Project $project, Request $request) {
        $entityManager = $this->getDoctrine()->getManager();

        $editForm = $this->createForm(ProjectType::class, $project);
        $deleteForm = $this->createDeleteForm($project);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'project.updated_successfully');

            return $this->redirectToRoute('project_edit', ['id' => $project->getId()]);
        }

        return $this->render('project/edit.html.twig', [
            'project' => $project,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }
	
	/**
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @param \AppBundle\Entity\Project                 $project
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 *
	 * @Route("/{id}", name="project_delete")
	 * @Method("DELETE")
	 */
    public function deleteAction(Request $request, Project $project) {
        $form = $this->createDeleteForm($project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            $entityManager->remove($project);
            $entityManager->flush();

            $this->addFlash('success', 'project.deleted_successfully');
        }

        return $this->redirectToRoute('project_index');
    }

    /**
     * @param Project $project The Project object
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Project $project) {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('project_delete', ['id' => $project->getId()]))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
