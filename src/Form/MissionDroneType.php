<?php

namespace App\Form;

use App\Entity\MissionDrone;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class MissionDroneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date_mission', DateType::class, [
                'required' => true,
                'label' => 'Date mission',
                'widget' => 'single_text',
                'input' => 'datetime',
                'attr' => [
                    'class' => 'form-input form-date',
                    'type' => 'date',
                    'min' => date('Y-m-d'),
                ]
            ])
            ->add('heure_debut', TimeType::class, [
                'required' => true,
                'label' => 'Heure début',
                'widget' => 'single_text',
                'input' => 'datetime',
                'attr' => [
                    'class' => 'form-input',
                    'type' => 'time',
                ]
            ])
            ->add('heure_fin', TimeType::class, [
                'required' => true,
                'label' => 'Heure fin',
                'widget' => 'single_text',
                'input' => 'datetime',
                'attr' => [
                    'class' => 'form-input',
                    'type' => 'time',
                ]
            ])
            ->add('zone_survolee', ChoiceType::class, [
                'required' => true,
                'label' => 'Zone survolée',
                'choices' => [
                    'Nord' => 'Nord',
                    'Nord-Est' => 'Nord-Est',
                    'Est' => 'Est',
                    'Sud-Est' => 'Sud-Est',
                    'Sud' => 'Sud',
                    'Sud-Ouest' => 'Sud-Ouest',
                    'Ouest' => 'Ouest',
                    'Nord-Ouest' => 'Nord-Ouest',
                ],
                'attr' => [
                    'class' => 'form-input form-select',
                ]
            ])
            ->add('distance_parcourue', NumberType::class, [
                'required' => false,
                'label' => 'Distance parcourue (km)',
                'attr' => [
                    'placeholder' => 'Ex: 45.5',
                    'min' => 0,
                    'max' => 1000,
                    'step' => 0.1,
                    'class' => 'form-input',
                ]
            ])
            ->add('altitude_vol', IntegerType::class, [
                'required' => false,
                'label' => 'Altitude vol (m)',
                'attr' => [
                    'placeholder' => 'Ex: 500',
                    'min' => 0,
                    'max' => 5000,
                    'class' => 'form-input',
                ]
            ])
            ->add('conditions_vol', ChoiceType::class, [
                'required' => false,
                'label' => 'Conditions vol',
                'choices' => [
                    'Bon' => 'Bon',
                    'Modéré' => 'Modéré',
                    'Mauvais' => 'Mauvais',
                ],
                'placeholder' => 'Sélectionnez (optionnel)',
                'attr' => [
                    'class' => 'form-input form-select',
                ]
            ])
            ->add('observations', TextareaType::class, [
                'required' => false,
                'label' => 'Observations',
                'attr' => [
                    'placeholder' => 'Ajoutez vos observations (optionnel)',
                    'maxlength' => 3000,
                    'rows' => 5,
                    'class' => 'form-input',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MissionDrone::class,
        ]);
    }
}

