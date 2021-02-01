<?php

namespace App\Form\Type;

use App\Entity\Position;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;


class PositionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', EntityType::class, ['label'=>'Filter with Name:',
                                                'class'=> Position::class,
                                                'choice_label' => 'name',
                                                'required' => false,
                                                'placeholder'=>"all"
                                                ]) 
            ->add('value', EntityType::class, ['label'=>'Filter with Value:',
                                                'class'=> Position::class,
                                                'choice_label' => 'value',
                                                'required' => false,
                                                'placeholder'=>"all"
                                                ] );  
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Position::class,
        ]);
    }
}
