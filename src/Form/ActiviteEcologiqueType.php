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
<<<<<<< HEAD
                'label' => 'Nom de l\'activitÃƒÂ©',
                'constraints' => [
                    new Assert\NotBlank(message: 'Le nom est obligatoire'),
                    new Assert\Length(
                        min: 3,
                        max: 100,
                        minMessage: 'Le nom doit contenir au moins 3 caractÃƒÂ¨res',
                        maxMessage: 'Le nom ne peut pas dÃƒÂ©passer 100 caractÃƒÂ¨res'
                    ),
                    new Assert\Regex(
                        pattern: '/^[a-zA-ZÃƒâ‚¬-ÃƒÂ¿0-9\s\-]+$/',
                        message: 'Le nom ne doit pas contenir de caractÃƒÂ¨res spÃƒÂ©ciaux'
                    ),
                ],
=======
                'label' => 'Nom de l\'activité',
>>>>>>> 163f23d3ff1ed003948dbcee8c3696b66c6027ca
            ])
            ->add('date_activite', DateType::class, [
                'label' => 'Date de l\'activitÃƒÂ©',
                'widget' => 'single_text',
<<<<<<< HEAD
                'constraints' => [
                    new Assert\NotBlank(message: 'La date est obligatoire'),
                    new Assert\GreaterThanOrEqual(
                        value: 'today',
                        message: 'La date doit ÃƒÂªtre aujourd\'hui ou dans le futur'
                    ),
                ],
            ])
            ->add('capacite', IntegerType::class, [
                'label' => 'CapacitÃƒÂ©',
                'constraints' => [
                    new Assert\NotBlank(message: 'La capacitÃƒÂ© est obligatoire'),
                    new Assert\Positive(message: 'La capacitÃƒÂ© doit ÃƒÂªtre un nombre positif'),
                    new Assert\Range(
                        min: 1,
                        max: 500,
                        notInRangeMessage: 'La capacitÃƒÂ© doit ÃƒÂªtre entre 1 et 500'
                    ),
                ],
=======
            ])
            ->add('capacite', IntegerType::class, [
                'label' => 'Capacité',
>>>>>>> 163f23d3ff1ed003948dbcee8c3696b66c6027ca
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
<<<<<<< HEAD
                'constraints' => [
                    new Assert\Length(
                        max: 1000,
                        maxMessage: 'La description ne peut pas dÃƒÂ©passer 1000 caractÃƒÂ¨res'
                    ),
=======
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
>>>>>>> 163f23d3ff1ed003948dbcee8c3696b66c6027ca
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