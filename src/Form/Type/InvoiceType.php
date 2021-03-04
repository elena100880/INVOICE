<?php

namespace App\Form\Type;

use App\Entity\Invoice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;


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
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Invoice::class,
        ]);
    }
}
