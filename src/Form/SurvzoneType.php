<?php

namespace App\Form;

use App\Entity\Survzone;
use App\Entity\Zonep;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
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
                'constraints' => [
                    new NotBlank(['message' => 'La date est obligatoire']),
                    new LessThanOrEqual([
                        'value' => new \DateTime('today'),
                        'message' => 'La date ne peut pas être dans le futur',
                    ]),
                    new GreaterThanOrEqual([
                        'value' => new \DateTime('-10 years'),
                        'message' => 'La date ne peut pas être antérieure à 10 ans',
                    ]),
                ],
            ])

            ->add('observation', TextareaType::class, [
    'required' => true,
    'label' => 'Observation',
    'attr' => [
        'rows' => 4,
        'placeholder' => 'Entrez vos observations ici...',
        'maxlength' => 1000,
    ],
    'constraints' => [
        new NotBlank(['message' => 'L\'observation est obligatoire']),
        new Length([
            'max' => 1000,
            'maxMessage' => 'L\'observation ne peut pas dépasser {{ limit }} caractères',
        ]),
    ],
])

            ->add('zone', EntityType::class, [
                'class' => Zonep::class,
                'choice_label' => 'nomZone',
                'placeholder' => 'Choisir une zone',
                'label' => 'Zone',
                'constraints' => [
                    new NotBlank(['message' => 'La zone est obligatoire']),
                ],
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