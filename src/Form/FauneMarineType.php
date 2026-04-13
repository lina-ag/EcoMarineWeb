<?php

namespace App\Form;

use App\Entity\FauneMarine;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class FauneMarineType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('espece', TextType::class, [
                'required' => true,
                'label' => 'Espèce',
                'attr' => [
                    'placeholder' => 'Entrez le nom de l\'espèce (lettres uniquement)',
                    'minlength' => 3,
                    'maxlength' => 100,
                    'pattern' => '^[a-zA-ZÀ-ÿ\s\-\']+$',
                    'title' => 'Lettres uniquement, pas de chiffres',
                ]
            ])
            ->add('etat', ChoiceType::class, [
                'required' => true,
                'label' => 'État',
                'choices' => [
                    'Sain' => 'Sain',
                    'Blessé' => 'Blessé',
                    'Malade' => 'Malade',
                    'Mort' => 'Mort',
                    'Autre' => 'Autre',
                ],
                'attr' => [
                    'class' => 'form-input form-select',
                ]
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Description',
                'attr' => [
                    'placeholder' => 'Ajoutez une description détaillée (optionnel - max 500 mots)',
                    'maxlength' => 3000,
                    'rows' => 5,
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FauneMarine::class,
        ]);
    }
}
