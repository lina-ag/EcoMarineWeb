<?php

namespace App\Form;

use App\Entity\ActiviteEcologique;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActiviteEcologiqueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom_activite', TextType::class, [
                'label' => 'Nom de l\'activité',
            ])
            ->add('date_activite', DateType::class, [
                'label' => 'Date de l\'activité',
                'widget' => 'single_text',
            ])
            ->add('capacite', IntegerType::class, [
                'label' => 'Capacité',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('is_recurring', CheckboxType::class, [
                'label' => 'Activer la récurrence',
                'mapped' => false,
                'required' => false,
            ])
            ->add('recurrence_days', ChoiceType::class, [
                'label' => 'Jours de répétition',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'choices' => [
                    'Lun' => 1,
                    'Mar' => 2,
                    'Mer' => 3,
                    'Jeu' => 4,
                    'Ven' => 5,
                    'Sam' => 6,
                    'Dim' => 7,
                ],
            ])
            ->add('recurrence_end_date', DateType::class, [
                'label' => 'Date de fin',
                'mapped' => false,
                'required' => false,
                'widget' => 'single_text',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ActiviteEcologique::class,
        ]);
    }
}