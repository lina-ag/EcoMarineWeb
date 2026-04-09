<?php

namespace App\Form;

use App\Entity\MissionDrone;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MissionDroneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date_mission')
            ->add('heure_debut')
            ->add('heure_fin')
            ->add('zone_survolee')
            ->add('distance_parcourue')
            ->add('altitude_vol')
            ->add('conditions_vol')
            ->add('observations')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MissionDrone::class,
        ]);
    }
}
