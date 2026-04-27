<?php

namespace App\CleaningBundle\Form;

use App\CleaningBundle\Entity\ActionNettoyage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActionNettoyageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date_action', DateType::class, [
                'label' => 'Date de l’action',
                'widget' => 'single_text',
                'html5' => true,
            ])
            ->add('lieu', TextType::class, [
                'label' => 'Lieu',
                'attr' => [
                    'placeholder' => 'Entrer le lieu de l’action',
                    'maxlength' => 255,
                ],
            ])

            // ->add('limiteBenevoles', IntegerType::class, [
            //     'label' => 'Limite de bénévoles',
            //     'attr' => [
            //         'placeholder' => 'Entrer la limite',
            //         'min' => 1,
            //     ],
            // ])

            ->add('limiteBenevoles', IntegerType::class, [
                'label' => 'Limite de bénévoles',
                'attr' => [
                    'placeholder' => 'Entrer la limite',
                    'min' => 1,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ActionNettoyage::class,
        ]);
    }
}
