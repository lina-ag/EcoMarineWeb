<?php

namespace App\CleaningBundle\Form;

use App\CleaningBundle\Entity\ActionNettoyage;
use App\CleaningBundle\Entity\Volontaire;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VolontaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'placeholder' => 'Entrer le nom du volontaire',
                    'maxlength' => 100,
                ],
            ])
            ->add('contact', TextType::class, [
                'label' => 'Contact',
                'attr' => [
                    'placeholder' => 'Entrer le contact',
                    'maxlength' => 20,
                ],
            ])
            ->add('id_action', EntityType::class, [
                'class' => ActionNettoyage::class,
                'choice_label' => function (ActionNettoyage $action) {
                    $date = $action->getDateAction() ? $action->getDateAction()->format('Y-m-d') : '';
                    return $action->getLieu() . ' - ' . $date;
                },
                'placeholder' => 'Choisir une action de nettoyage',
                'label' => 'Action de nettoyage',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Volontaire::class,
        ]);
    }
}
