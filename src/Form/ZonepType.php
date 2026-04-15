<?php

namespace App\Form;

use App\Entity\Zonep;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
            ])
            ->add('categorieZone', TextType::class, [
                'label' => 'Categorie',
                'trim' => true,
                'attr' => [
                    'maxlength' => 80,
                    'placeholder' => 'Ex: Protection, Tourisme, Surveillance',
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
