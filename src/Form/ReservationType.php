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
                        minMessage: 'Le nom doit contenir au moins 3 caractères',
                        maxMessage: 'Le nom ne peut pas dépasser 100 caractères'
                    ),
                    new Assert\Regex(
                        pattern: '/^[a-zA-ZÀ-ÿ\s\-]+$/',
                        message: 'Le nom ne doit contenir que des lettres'
                    ),
                ],
            ])
            ->add('date_reservation', DateType::class, [
                'label' => 'Date de réservation',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank(message: 'La date est obligatoire'),
                    new Assert\GreaterThanOrEqual(
                        value: 'today',
                        message: 'La date doit être aujourd\'hui ou dans le futur'
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
                        maxMessage: 'L\'email ne peut pas dépasser 180 caractères'
                    ),
                ],
            ])
            ->add('nombre_personnes', IntegerType::class, [
                'label' => 'Nombre de personnes',
                'constraints' => [
                    new Assert\NotBlank(message: 'Le nombre de personnes est obligatoire'),
                    new Assert\Positive(message: 'Le nombre de personnes doit être positif'),
                    new Assert\Range(
                        min: 1,
                        max: 50,
                        notInRangeMessage: 'Le nombre de personnes doit être entre 1 et 50'
                    ),
                ],
            ])
            ->add('activiteEcologique', EntityType::class, [
                'class' => ActiviteEcologique::class,
                'choice_label' => 'nom_activite',
                'label' => 'Activité écologique',
                'placeholder' => '-- Choisissez une activité --',
                'constraints' => [
                    new Assert\NotNull(message: 'Veuillez sélectionner une activité'),
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