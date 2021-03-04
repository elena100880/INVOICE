<?php

namespace App\Form\Type;

use App\Entity\Invoice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;


class InvoiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('supplier', ChoiceType::class, [  'label'=>'Supplier:',
                                                    'required' => false,
                                                    'mapped' => false,
                                                    'multiple' => true,
                                                    'attr' => array('class' => 'js-select2-invoice-supplier')   ])

            ->add('invoicePosition', TextType::class, ['label'=>'Position:', 
                                                ])

            ->add('recipient', TextType::class, ['label'=>'Recipient:',
                                                ])
        ;

        $builder->addEventListener(
            
            FormEvents::PRE_SUBMIT,
                                    
            function (FormEvent $event) 
            {
                $data = $event->getData();
                $form = $event->getForm();
                
                $choice_supplier = [$data['login'] => $data['login'] ];
                //$choice_i = [$data['i_name'] => $data['i_name'] ];
                //$choice_f = [$data['f_name'] => $data['f_name'] ];
                
                if ($form->has('form_person_like_product')) {   
                    $form->add  ('login', ChoiceType::class,  [ 
                                                                'label'=>'Supplier:',
                                                                'required' => false,
                                                                'choices' => $choice_supplier,
                                                                'mapped' => false,
                                                                'multiple' => true,
                                                                'attr' => array('class' => 'js-select2-invoice-supplier')   ]);

                   
                }
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
