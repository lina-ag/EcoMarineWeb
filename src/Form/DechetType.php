<?php

namespace App\Form;

use App\Entity\Dechet;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\UX\Dropzone\Form\DropzoneType;

class DechetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Type de dechet',
                'choices' => [
                    'Plastique' => 'plastique',
                    'Verre' => 'verre',
                    'Metal' => 'metal',
                    'Organique' => 'organique',
                    'Papier' => 'papier',
                    'Autre' => 'autre',
                ],
                'placeholder' => 'Choisir un type',
            ])
            ->add('quantite', NumberType::class, [
                'label' => 'Quantite',
                'attr' => ['placeholder' => 'Entrer la quantite'],
            ])
            ->add('zone', TextType::class, [
                'label' => 'Localisation / plage',
                'attr' => ['placeholder' => 'Entrer la zone ou la plage'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description du signalement',
                'attr' => ['placeholder' => 'Decrire le dechet ou la situation'],
            ])
            ->add('dateSignalement', DateType::class, [
                'label' => 'Date de signalement',
                'widget' => 'single_text',
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Signale' => 'signale',
                    'En cours' => 'en_cours',
                    'Traite' => 'traite',
                ],
                'placeholder' => 'Choisir un statut',
            ])
            ->add('photoFile', DropzoneType::class, [
                'label' => 'Photo du signalement',
                'mapped' => false,
                'required' => false,
                'attr' => ['placeholder' => 'Deposez une image ici'],
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                    ])
                ],
            ])
            ->add('latitude', HiddenType::class, ['required' => false])
            ->add('longitude', HiddenType::class, ['required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Dechet::class,
        ]);
    }
}