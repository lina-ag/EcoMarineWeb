<?php

namespace App\Form;

use App\Entity\PredictionEchouage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PredictionEchouageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date_prediction')
            ->add('zone')
            ->add('niveau_risque')
            ->add('espece_concernee')
            ->add('temperature_eau')
            ->add('conditions_meteo')
            ->add('recommandations')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PredictionEchouage::class,
        ]);
    }
}
