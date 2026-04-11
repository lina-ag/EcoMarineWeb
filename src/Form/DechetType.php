<?php

namespace App\Form;

use App\Entity\Dechet;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DechetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', TextType::class, [
                'label' => 'Type de déchet',
                'attr' => [
                    'placeholder' => 'Entrer le type de déchet'
                ]
            ])
            ->add('quantite', NumberType::class, [
                'label' => 'Quantité',
                'attr' => [
                    'placeholder' => 'Entrer la quantité'
                ]
            ])
            ->add('zone', TextType::class, [
                'label' => 'Zone',
                'attr' => [
                    'placeholder' => 'Entrer la zone'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Dechet::class,
        ]);
    }
}