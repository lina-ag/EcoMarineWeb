<?php

namespace App\Form;

use App\Entity\Survzone;
use App\Entity\Zonep;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class SurvzoneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateSurv', DateType::class, [
                'widget' => 'single_text',
                'label' => 'survzone.fields.date',
                'translation_domain' => 'messages',
                'required' => false,
            ])
            ->add('observation', TextareaType::class, [
                'required' => false,
                'label' => 'survzone.fields.observation',
                'translation_domain' => 'messages',
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'survzone.placeholders.observation',
                    'maxlength' => 1000,
                ],
            ])
            ->add('zone', EntityType::class, [
                'class' => Zonep::class,
                'choice_label' => 'nomZone',
                'placeholder' => 'survzone.placeholders.zone',
                'label' => 'survzone.fields.zone',
                'translation_domain' => 'messages',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Survzone::class,
        ]);
    }
}