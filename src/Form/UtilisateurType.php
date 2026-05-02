<?php

namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use App\Entity\Role;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UtilisateurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom *',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le nom'
                ]
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom *',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le prénom'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email * (doit être @gmail.com)',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'exemple@gmail.com',
                    'pattern' => '.*@gmail\.com$',
                    'title' => 'L\'email doit être une adresse Gmail'
                ]
            ])
            ->add('mot_de_passe', PasswordType::class, [
                'label' => 'Mot de passe * (min 8 caractères)',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le mot de passe',
                    'minlength' => 8
                ]
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone * (8 chiffres : commence par 2,5 ou 9)',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 51234567',
                    'pattern' => '[259][0-9]{7}',
                    'maxlength' => 8
                ]
            ])
            ->add('role', EntityType::class, [
                'class' => Role::class,
                'choice_label' => 'nomRole',  // Affiche 'admin', 'chercheur', 'utilisateur'
                'label' => 'Rôle',
                'required' => true,
                'placeholder' => 'Choisissez un rôle',
                'attr' => ['class' => 'form-control']
            ])
            ->add('date_naissance', DateType::class, [
    'label' => 'Date de naissance * (doit être dans le passé - pas aujourd\'hui)',
    'required' => true,
    'widget' => 'single_text',
    'html5' => true,
    'attr' => [
        'class' => 'form-control',
        'max' => (new \DateTime())->modify('-1 day')->format('Y-m-d') // Date maximum = hier
    ]
]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }
}