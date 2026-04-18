<?php

namespace App\Form;

use App\Entity\Observation;
use App\Entity\FauneMarine;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class ObservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('animal', EntityType::class, [
                'class' => FauneMarine::class,
                'choice_label' => function($animal) {
                    return $animal->getEspece() . ' (ID: ' . $animal->getId_animal() . ')';
                },
                'choice_value' => 'id_animal',
                'required' => true,
                'label' => 'Animal',
                'placeholder' => 'Sélectionnez un animal',
                'attr' => [
                    'class' => 'form-input form-select',
                ]
            ])
            ->add('date_observation', DateType::class, [
                'required' => true,
                'label' => 'Date observation',
                'widget' => 'single_text',
                'input' => 'datetime',
                'attr' => [
                    'class' => 'form-input form-date',
                    'type' => 'date',
                    'max' => date('Y-m-d'),
                ]
            ])
            ->add('temperature', NumberType::class, [
                'required' => false,
                'label' => 'Température (°C)',
                'attr' => [
                    'placeholder' => 'Ex: 15.5',
                    'min' => -50,
                    'max' => 50,
                    'step' => 0.1,
                    'class' => 'form-input',
                ]
            ])
            ->add('meteo', ChoiceType::class, [
                'required' => false,
                'label' => 'Météo',
                'choices' => [
                    'Ensoleillé' => 'Ensoleillé',
                    'Nuageux' => 'Nuageux',
                    'Pluvieux' => 'Pluvieux',
                    'Tempête' => 'Tempête',
                    'Brumeux' => 'Brumeux',
                    'Venteux' => 'Venteux',
                ],
                'placeholder' => 'Sélectionnez (optionnel)',
                'attr' => [
                    'class' => 'form-input form-select',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Observation::class,
        ]);
    }
}

