<?php

namespace App\Form;

use App\Entity\DetectionDrone;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

class DetectionDroneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('espece', TextType::class, [
                'required' => true,
                'label' => 'Espèce',
                'attr' => [
                    'placeholder' => 'Entrez le nom de l\'espèce',
                    'minlength' => 3,
                    'maxlength' => 100,
                    'class' => 'form-input',
                ]
            ])
            ->add('nombre_individus', IntegerType::class, [
                'required' => true,
                'label' => 'Nombre individus',
                'attr' => [
                    'placeholder' => 'Ex: 5',
                    'min' => 1,
                    'max' => 10000,
                    'class' => 'form-input',
                ]
            ])
            ->add('latitude', NumberType::class, [
                'required' => false,
                'label' => 'Latitude',
                'attr' => [
                    'placeholder' => 'Ex: 43.296',
                    'min' => -90,
                    'max' => 90,
                    'step' => 0.0001,
                    'class' => 'form-input',
                ]
            ])
            ->add('longitude', NumberType::class, [
                'required' => false,
                'label' => 'Longitude',
                'attr' => [
                    'placeholder' => 'Ex: 5.369',
                    'min' => -180,
                    'max' => 180,
                    'step' => 0.0001,
                    'class' => 'form-input',
                ]
            ])
            ->add('comportement', ChoiceType::class, [
                'required' => false,
                'label' => 'Comportement',
                'choices' => [
                    'Normal' => 'Normal',
                    'Alerte' => 'Alerte',
                    'Danger' => 'Danger',
                ],
                'placeholder' => 'Sélectionnez (optionnel)',
                'attr' => [
                    'class' => 'form-input form-select',
                ]
            ])
            ->add('confiance_ia', ChoiceType::class, [
                'required' => false,
                'label' => 'Confiance IA',
                'choices' => [
                    'Très faible' => 'Très faible',
                    'Faible' => 'Faible',
                    'Moyen' => 'Moyen',
                    'Fort' => 'Fort',
                    'Très fort' => 'Très fort',
                ],
                'placeholder' => 'Sélectionnez (optionnel)',
                'attr' => [
                    'class' => 'form-input form-select',
                ]
            ])
            ->add('image_path', FileType::class, [
                'required' => false,
                'label' => 'Image',
                'mapped' => false,
                'attr' => [
                    'class' => 'form-input file-input',
                    'accept' => 'image/*',
                    'data-dragdrop' => true,
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                        'mimeTypesMessage' => 'Les formats autorisés sont: JPEG, PNG, GIF, WebP',
                    ])
                ]
            ])
            ->add('timestamp', TextType::class, [
                'required' => false,
                'label' => 'Timestamp',
                'attr' => [
                    'placeholder' => 'Automatique',
                    'maxlength' => 100,
                    'class' => 'form-input',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DetectionDrone::class,
        ]);
    }
}

