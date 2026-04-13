<?php

namespace App\Form;

use App\Entity\PredictionEchouage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class PredictionEchouageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date_prediction', DateType::class, [
                'required' => true,
                'label' => 'Date prédiction',
                'widget' => 'single_text',
                'input' => 'datetime',
                'attr' => [
                    'class' => 'form-input form-date',
                    'type' => 'date',
                    'min' => date('Y-m-d'),
                ]
            ])
            ->add('zone', ChoiceType::class, [
                'required' => true,
                'label' => 'Zone',
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
            ->add('niveau_risque', IntegerType::class, [
                'required' => true,
                'label' => 'Niveau risque',
                'attr' => [
                    'placeholder' => 'De 1 à 5',
                    'min' => 1,
                    'max' => 5,
                    'class' => 'form-input',
                ]
            ])
            ->add('espece_concernee', TextType::class, [
                'required' => false,
                'label' => 'Espèce concernée',
                'attr' => [
                    'placeholder' => 'Entrez l\'espèce (optionnel)',
                    'maxlength' => 100,
                    'class' => 'form-input',
                ]
            ])
            ->add('temperature_eau', NumberType::class, [
                'required' => false,
                'label' => 'Température eau (°C)',
                'attr' => [
                    'placeholder' => 'Ex: 15.5',
                    'min' => -50,
                    'max' => 50,
                    'step' => 0.1,
                    'class' => 'form-input',
                ]
            ])
            ->add('conditions_meteo', ChoiceType::class, [
                'required' => false,
                'label' => 'Conditions météo',
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
            ->add('recommandations', TextareaType::class, [
                'required' => false,
                'label' => 'Recommandations',
                'attr' => [
                    'placeholder' => 'Ajoutez vos recommandations (optionnel)',
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
            'data_class' => PredictionEchouage::class,
        ]);
    }
}

