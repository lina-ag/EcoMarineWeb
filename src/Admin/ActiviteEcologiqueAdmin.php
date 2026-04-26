<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

final class ActiviteEcologiqueAdmin extends AbstractAdmin
{
    protected function configureDatagridFilters(DatagridMapper $datagrid): void
    {
        $datagrid
            ->add('nomActivite')
            ->add('dateActivite')
            ->add('capacite');
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('nomActivite')
            ->add('dateActivite')
            ->add('capacite')
            ->add(ListMapper::NAME_ACTIONS, ListMapper::TYPE_ACTIONS, [
                'actions' => [
                    'show' => [],
                    'edit' => [],
                    'delete' => [],
                ],
            ]);
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('nomActivite')
            ->add('dateActivite')
            ->add('capacite')
            ->add('description');
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('nomActivite')
            ->add('dateActivite')
            ->add('capacite')
            ->add('description');
    }
}

