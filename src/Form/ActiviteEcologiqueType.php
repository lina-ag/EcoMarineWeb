<?php

namespace App\Form;

use App\Entity\ActiviteEcologique;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ActiviteEcologiqueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom_activite', TextType::class, [
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
            ])
            ->add('date_activite', DateType::class, [
                'label' => 'Date de l\'activitÃƒÂ©',
                'widget' => 'single_text',
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
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'constraints' => [
                    new Assert\Length(
                        max: 1000,
                        maxMessage: 'La description ne peut pas dÃƒÂ©passer 1000 caractÃƒÂ¨res'
                    ),
                ],
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