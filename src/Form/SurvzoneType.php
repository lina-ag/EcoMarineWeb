<?php

namespace App\Form;

use App\Entity\Survzone;
use App\Entity\Zonep;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class SurvzoneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateSurv', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de surveillance',
            ])

            ->add('observation', TextareaType::class, [
    'required' => true,
    'label' => 'Observation',
    'attr' => [
        'rows' => 4,
        'placeholder' => 'Entrez vos observations ici...',
        'maxlength' => 1000,
    ],
])

            ->add('zone', EntityType::class, [
                'class' => Zonep::class,
                'choice_label' => 'nomZone',
                'placeholder' => 'Choisir une zone',
                'label' => 'Zone',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Survzone::class,
        ]);
    }
}