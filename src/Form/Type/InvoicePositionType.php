<?php

namespace App\Form\Type;

use App\Entity\InvoicePosition;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

use App\Entity\Supplier;
use App\Entity\Recipient;
use App\Entity\Position;

use App\Repository\SupplierRepository;
use App\Repository\RecipientRepository;
use App\Repository\InvoiceRepository;
use App\Repository\PositionRepository;
use App\Repository\InvoicePositionRepository;

class InvoicePositionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
                -> add('position', EntityType::class, [
                                                        'label'=>'Choose Positions (type Name or Value):',
                                                        'class' => Position::class,
                                                        'choices' =>[],
                                                        'attr' => array('class' => 'js-select2-invoice-position')   
                                                        ])
                -> add ('quantity', NumberType::class, [
                                                        'label' => 'Add the quantity of chosen position:',
                                                        'data' => 1,
                                                        'attr' => ['step' => 1]
                                                        //'mapped' => false,
                                                    ])
                ->add('invoice', HiddenType::class)
        ;
        
        $builder->addEventListener(
            
            FormEvents::PRE_SUBMIT,
                                    
            function (FormEvent $event) 
            {
                $data = $event->getData();
                $form = $event->getForm();
                
                if ($form->has('invoice_position_add')) {   
                    
                    $form->add  ('position', EntityType::class,     [ 
                                                                        'label'=>'Choose Positions (type Name or Value):',
                                                                        'class' => Position::class,
                                                                        'query_builder' => function (PositionRepository $er) use ($data) 
                                                                                        {                                                                                    
                                                                                            if (isset($data['position'])) {
                                                                                            return $er  ->createQueryBuilder('p')
                                                                                                        -> where ('p.id = :positionId')
                                                                                                        -> setParameter('positionId', $data['position']);
                                                                                            }
                                                                                            else return $er ->createQueryBuilder('p')
                                                                                                            -> where ('p.id = :id')
                                                                                                            -> setParameter('id', 0);
                                                                                        }, 
                                                                        'choice_label' => function ($position) {
                                                                                                    return $position->getName().', '.$position->getValue().'zł';
                                                                                                },
                                                                        'attr' => array('class' => 'js-select2-invoice-position')   
                                                                    ]);
                }
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => InvoicePosition::class, 
            'validation_groups' => false,    //  to disable validating NumberType. when typing strings... !!!!   TODO: maybe sth  more custom 
            // another way  - just changed to TextType and added 'mapped' => false to quantity field
        ]);
    }
}
