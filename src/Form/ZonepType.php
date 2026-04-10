<?php

namespace App\Form;

use App\Entity\Zonep;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class ZonepType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomZone', TextType::class, [
                'label' => 'Nom de la zone',
                'trim' => true,
                'attr' => [
                    'maxlength' => 100,
                    'placeholder' => 'Ex: Zone Kuriat Nord',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le nom de la zone est obligatoire.']),
                    new Length([
                        'min' => 3,
                        'max' => 100,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caracteres.',
                        'maxMessage' => 'Le nom ne doit pas depasser {{ limit }} caracteres.',
                    ]),
                    new Regex([
                        'pattern' => '/^[\p{L}\p{N}\s\-\'\.]+$/u',
                        'message' => 'Le nom contient des caracteres non autorises.',
                    ]),
                ],
            ])
            ->add('categorieZone', TextType::class, [
                'label' => 'Categorie',
                'trim' => true,
                'attr' => [
                    'maxlength' => 80,
                    'placeholder' => 'Ex: Protection, Tourisme, Surveillance',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La categorie est obligatoire.']),
                    new Length([
                        'min' => 3,
                        'max' => 80,
                        'minMessage' => 'La categorie doit contenir au moins {{ limit }} caracteres.',
                        'maxMessage' => 'La categorie ne doit pas depasser {{ limit }} caracteres.',
                    ]),
                    new Regex([
                        'pattern' => '/^[\p{L}\p{N}\s\-\'\.]+$/u',
                        'message' => 'La categorie contient des caracteres non autorises.',
                    ]),
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'placeholder' => 'Choisir un statut',
                'choices' => [
                    'Actif' => 'Actif',
                    'En surveillance' => 'En surveillance',
                    'En maintenance' => 'En maintenance',
                    'Inactif' => 'Inactif',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le statut est obligatoire.']),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Zonep::class,
        ]);
    }
}
