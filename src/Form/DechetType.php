<?php

namespace App\Form;

use App\Entity\Dechet;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DechetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Type de déchet',
                'choices' => [
                    'Plastique' => 'plastique',
                    'Verre' => 'verre',
                    'Métal' => 'metal',
                    'Organique' => 'organique',
                    'Papier' => 'papier',
                    'Autre' => 'autre',
                ],
                'placeholder' => 'Choisir un type',
                'attr' => [
                    'class' => 'form-input',
                    'style' => 'color:#ffffff;background:rgba(255,255,255,0.08);'
                ],
                'choice_attr' => function () {
                    return [
                        'style' => 'color:#111827;background:#ffffff;'
                    ];
                }
            ])
            ->add('quantite', NumberType::class, [
                'label' => 'Quantité',
                'attr' => [
                    'placeholder' => 'Entrer la quantité',
                    'class' => 'form-input'
                ]
            ])
            ->add('zone', TextType::class, [
                'label' => 'Localisation / plage',
                'attr' => [
                    'placeholder' => 'Entrer la zone ou la plage',
                    'class' => 'form-input'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description du signalement',
                'attr' => [
                    'placeholder' => 'Décrire le déchet ou la situation',
                    'class' => 'form-input'
                ]
            ])
            ->add('dateSignalement', DateType::class, [
                'label' => 'Date de signalement',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-input'
                ]
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Signalé' => 'signale',
                    'En cours' => 'en_cours',
                    'Traité' => 'traite',
                ],
                'placeholder' => 'Choisir un statut',
                'attr' => [
                    'class' => 'form-input',
                    'style' => 'color:#ffffff;background:rgba(255,255,255,0.08);'
                ],
                'choice_attr' => function () {
                    return [
                        'style' => 'color:#111827;background:#ffffff;'
                    ];
                }
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Dechet::class,
        ]);
    }
}