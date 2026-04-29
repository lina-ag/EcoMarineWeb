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
                'label' => 'zonep.fields.name',
                'translation_domain' => 'messages',
                'trim' => true,
                'attr' => [
                    'maxlength' => 100,
                    'placeholder' => 'zonep.placeholders.name',
                ],
            ])
            ->add('categorieZone', TextType::class, [
                'label' => 'zonep.fields.category',
                'translation_domain' => 'messages',
                'trim' => true,
                'attr' => [
                    'maxlength' => 80,
                    'placeholder' => 'zonep.placeholders.category',
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'zonep.fields.status',
                'translation_domain' => 'messages',
                'placeholder' => 'zonep.placeholders.status',
                'choices' => [
                    'status.actif' => 'Actif',
                    'status.en_surveillance' => 'En surveillance',
                    'status.en_maintenance' => 'En maintenance',
                    'status.inactif' => 'Inactif',
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