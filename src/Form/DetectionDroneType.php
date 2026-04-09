<?php

namespace App\Form;

use App\Entity\DetectionDrone;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DetectionDroneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id_mission')
            ->add('espece')
            ->add('nombre_individus')
            ->add('latitude')
            ->add('longitude')
            ->add('comportement')
            ->add('confiance_ia')
            ->add('image_path')
            ->add('timestamp')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DetectionDrone::class,
        ]);
    }
}
