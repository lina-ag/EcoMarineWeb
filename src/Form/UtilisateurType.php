<?php

namespace App\Form;

use App\Entity\Role;
use App\Entity\Utilisateur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UtilisateurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom *',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le nom',
                ],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prenom *',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le prenom',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email * (doit etre @gmail.com)',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'exemple@gmail.com',
                    'pattern' => '.*@(gmail)\.com$',
                    'title' => 'L\'email doit etre une adresse gmail (@gmail.com)',
                ],
            ])
            ->add('mot_de_passe', PasswordType::class, [
                'label' => $isEdit
                    ? 'Mot de passe (laisser vide pour conserver l\'ancien)'
                    : 'Mot de passe * (min 8 caracteres)',
                'required' => !$isEdit,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le mot de passe',
                    'minlength' => 8,
                ],
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Telephone * (8 chiffres : commence par 2, 5 ou 9)',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 51234567',
                    'pattern' => '[259][0-9]{7}',
                    'maxlength' => 8,
                ],
            ])
            ->add('role', EntityType::class, [
                'class' => Role::class,
                'choice_label' => 'nomRole',
                'label' => 'Role',
                'required' => true,
                'placeholder' => 'Choisissez un role',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('date_naissance', DateType::class, [
                'label' => 'Date de naissance * (doit etre dans le passe)',
                'required' => true,
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                    'max' => (new \DateTime())->modify('-1 day')->format('Y-m-d'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
            'is_edit' => false,
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
