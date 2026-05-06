<?php

namespace App\Form;

use App\Entity\ActiviteEcologique;
use App\Entity\Reservation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom complet',
                'constraints' => [
                    new Assert\NotBlank(message: 'Le nom est obligatoire'),
                    new Assert\Length(
                        min: 3,
                        max: 100,
                        minMessage: 'Le nom doit contenir au moins 3 caractÃƒÂ¨res',
                        maxMessage: 'Le nom ne peut pas dÃƒÂ©passer 100 caractÃƒÂ¨res'
                    ),
                    new Assert\Regex(
                        pattern: '/^[a-zA-ZÃƒâ‚¬-ÃƒÂ¿\s\-]+$/',
                        message: 'Le nom ne doit contenir que des lettres'
                    ),
                ],
            ])
            ->add('date_reservation', DateType::class, [
                'label' => 'Date de rÃƒÂ©servation',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank(message: 'La date est obligatoire'),
                    new Assert\GreaterThanOrEqual(
                        value: 'today',
                        message: 'La date doit ÃƒÂªtre aujourd\'hui ou dans le futur'
                    ),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new Assert\NotBlank(message: 'L\'email est obligatoire'),
                    new Assert\Email(message: 'Veuillez entrer un email valide'),
                    new Assert\Length(
                        max: 180,
                        maxMessage: 'L\'email ne peut pas dÃƒÂ©passer 180 caractÃƒÂ¨res'
                    ),
                ],
            ])
            ->add('nombre_personnes', IntegerType::class, [
                'label' => 'Nombre de personnes',
                'constraints' => [
                    new Assert\NotBlank(message: 'Le nombre de personnes est obligatoire'),
                    new Assert\Positive(message: 'Le nombre de personnes doit ÃƒÂªtre positif'),
                    new Assert\Range(
                        min: 1,
                        max: 50,
                        notInRangeMessage: 'Le nombre de personnes doit ÃƒÂªtre entre 1 et 50'
                    ),
                ],
            ])
            ->add('activiteEcologique', EntityType::class, [
                'class' => ActiviteEcologique::class,
                'choice_label' => 'nom_activite',
                'label' => 'ActivitÃƒÂ© ÃƒÂ©cologique',
                'placeholder' => '-- Choisissez une activitÃƒÂ© --',
                'constraints' => [
                    new Assert\NotNull(message: 'Veuillez sÃƒÂ©lectionner une activitÃƒÂ©'),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}