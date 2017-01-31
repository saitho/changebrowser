<?php
namespace AppBundle\Form;

use AppBundle\Entity\Project;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectType extends AbstractType {
	/**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options = array()) {
        $builder
			->add('title', null, [
				'required' => true,
				'attr' => ['autofocus' => true, 'class'=>'form-control'],
				'label' => 'label.title',
			])
			->add('source', EntityType::class, [
				'required' => true,
				'attr' => ['class'=>'form-control'],
				'class' => 'AppBundle:Source\AbstractSource',
				'choice_label' => 'id',
			])
        ;
    }
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
        	'data_class' => Project::class
		]);
    }
}
