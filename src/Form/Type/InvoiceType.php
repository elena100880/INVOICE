<?php

namespace App\Form\Type;

use App\Entity\Invoice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
//use Symfony\Component\Form\Extension\Core\Type\EntityType;

use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Supplier;
use App\Entity\Recipient;
use App\Entity\Position;
use App\Entity\InvoicePosition;

use App\Repository\SupplierRepository;
use App\Repository\RecipientRepository;
use App\Repository\InvoiceRepository;
use App\Repository\PositionRepository;
use App\Repository\InvoicePositionRepository;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class InvoiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('supplier', EntityType::class, [      'label'=>'Supplier (type Name or NIP):',
                                                        'class' => Supplier::class,
                                                        'choices' =>[],
                                                        'required' => false,
                                                        'multiple' => true,
                                                        'attr' => array('class' => 'js-select2-invoice-supplier')   
                                                    ])

            ->add('recipient', EntityType::class, [      'label'=>'Recipient (type Name, Family or Address):',
                                                        'class' => Recipient::class,
                                                        'choices' =>[],
                                                        'required' => false,
                                                        'multiple' => true,
                                                        'attr' => array('class' => 'js-select2-invoice-recipient')   
                                                ])

            ->add('invoicePosition', EntityType::class, ['label'=>'Position (type Name or Value):',
                                                        'class' => Position::class,
                                                        'choices' =>[],
                                                        'required' => false,
                                                        'multiple' => true,
                                                        'attr' => array('class' => 'js-select2-invoice-position')   
                                        ])
        ;

        $builder->addEventListener(
            
            FormEvents::PRE_SUBMIT,
                                    
            function (FormEvent $event) 
            {
                $data = $event->getData();
                $form = $event->getForm();
             
                if ($form->has('invoice_filter')) {   
                    $form->add  ('supplier', EntityType::class,  [ 
                                                                'label'=>'Supplier (type Name or NIP):',
                                                                'class' => Supplier::class,
                                                                'required' => false,

                                                                'query_builder' => function (SupplierRepository $er) use ($data) {
                                                                            
                                                                                    if (isset($data['supplier'])) {
                                                                                    return $er  ->createQueryBuilder('s')
                                                                                                -> where ('s.id in (:suppliersId)')
                                                                                                -> setParameter('suppliersId', $data['supplier']);
                                                                                    }
                                                                                    else return $er ->createQueryBuilder('s')
                                                                                                    -> where ('s.id = :id')
                                                                                                    -> setParameter('id', 0);
                                                                                    }, 
                                                                    
                                                                'choice_label' => function ($supplier) {
                                                                                            return $supplier->getName().' NIP: '.$supplier->getNip();
                                                                                        },

                                                                'mapped' => false,
                                                                'multiple' => true,
                                                                'attr' => array('class' => 'js-select2-invoice-supplier')   ]);
                    
                    $form->add  ('recipient', EntityType::class,  [ 
                                                                    'label'=>'Recipient (type Name, Family or Address):',
                                                                    'class' => Recipient::class,
                                                                    'required' => false,
    
                                                                    'query_builder' => function (RecipientRepository $er) use ($data) 
                                                                                {                                                                   
                                                                                    if (isset($data['recipient'])) {
                                                                                        return $er  ->createQueryBuilder('r')
                                                                                                    -> where ('r.id in (:recipientsId)')
                                                                                                    -> setParameter('recipientsId', $data['recipient']);
                                                                                    }
                                                                                    else return $er ->createQueryBuilder('r')
                                                                                                    -> where ('r.id = :id')
                                                                                                    -> setParameter('id', 0);
                                                                                }, 
                                                                        
                                                                    'choice_label' => function ($recipient) {
                                                                            return $recipient->getName().', '.$recipient->getFamily().', '.$recipient->getAddress();
                                                                            },
    
                                                                    'mapped' => false,
                                                                    'multiple' => true,
                                                                    'attr' => array('class' => 'js-select2-invoice-recipient')   ]);

                    $form->add  ('invoicePosition', EntityType::class, [ 
                                                                    'label'=>'Position (type Name or Value):',
                                                                    'class' => Position::class,
                                                                    'required' => false,
        
                                                                    'query_builder' => function (PositionRepository $er) use ($data) 
                                                                                {    
                                                                                    if (isset($data['invoicePosition'])) {
                                                                                        return $er  ->createQueryBuilder('pi')
                                                                                                    -> where ('pi.id in (:poditionsId)')
                                                                                                    -> setParameter('poditionsId', $data['invoicePosition']);
                                                                                    }
                                                                                    else return $er ->createQueryBuilder('pi')
                                                                                                    -> where ('pi.id = :id')
                                                                                                    -> setParameter('id', 0);
                                                                                }, 
                                                                            
                                                                        'choice_label' => function ($position) {
                                                                                return $position->getName().', '.$position->getValue().'zł';
                                                                                },
        
                                                                        'mapped' => false,
                                                                        'multiple' => true,
                                                                        'attr' => array('class' => 'js-select2-invoice-position')   ]);

                    
                }

                /*if (isset($data['invoicePosition'])) {
                        
                    $form->add ('empty_positions', HiddenType::class, ['mapped' => false]);

                }*/
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Invoice::class,
        ]);
    }
}
