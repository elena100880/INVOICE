<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\AbstractType;

use App\Entity\Supplier;
use App\Entity\Recipient;
use App\Entity\Position;
use App\Entity\InvoicePosition;

use App\Repository\SupplierRepository;
use App\Repository\RecipientRepository;
use App\Repository\PositionRepository;
use App\Repository\InvoicePositionRepository;

use App\Entity\Invoice;
use App\Form\Type\InvoiceType;
use App\Repository\InvoiceRepository;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityManagerInterface;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class InvoiceAddController extends AbstractController
{
    private $session;
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function invoice_add(Request $request) : Response
    {     
        $invoice = new Invoice();
        $form = $this->createForm (InvoiceType::class, $invoice)
                        ->add('supplier', EntityType::class, [      'label'=>'Supplier (type Name or NIP):',
                                                                    'class' => Supplier::class,
                                                                    'choices' =>[],
                                                                    'required' => false,
                                                                    'attr' => array('class' => 'js-select2-invoice-supplier')   
                                                                ])

                        ->add('recipient', EntityType::class, [     'label'=>'Recipient (type Name, Family or Address):',
                                                                    'class' => Recipient::class,
                                                                    'choices' =>[],
                                                                    'required' => false,
                                                                    'attr' => array('class' => 'js-select2-invoice-recipient')   
                                                            ])
                        ->add('invoicePosition', HiddenType::class, ['mapped' => false])
                        ->add('invoice_add', HiddenType::class, ['mapped' => false])
                        ->add('send', SubmitType::class, ['label'=>'Create invoice']);

        $form->handleRequest($request);

        if ($form->isSubmitted() ) {
            
            $suppliers = $form->get('supplier')->getData();
            $recipients = $form->get('recipient')->getData();
            //$positions = $form->get('invoicePosition')->getData();
                        
            $entityManager = $this->getDoctrine()->getManager();
            $queryBuilder = $entityManager->createQueryBuilder()
                                            -> select('pos')
                                            -> from ('App\Entity\Position', 'pos');
            if (isset($value)) {
                $value=$value->getValue();
                $queryBuilder=$queryBuilder->setParameter('value', $value)
                                            ->andwhere ('pos.value = :value');
            }
            if (isset($name)) {
                $name=$name->getName();
                $queryBuilder=$queryBuilder->setParameter('name', $name)
                                            ->andwhere ('pos.name = :name');
            }
            $positions = $queryBuilder->getQuery()->getResult();
        }


    /*        //saving info about chosen/not chosen products (by saving GET form parameters) 
            $request= Request::createFromGlobals();
            $requestForm=$request->query->get('form');
            $this->session->set('sessionForm', $requestForm  );
              
        }   */


    /*
   
                
    //saving info about chosen/not chosen positions (by saving GET 'form' parameters) 
        $request= Request::createFromGlobals();
        $requestForm=$request->query->get('form');
        $this->session->set('sessionForm', $requestForm  );
                
    //filtering positions  
        $positions=array();  */

        
        
        $contents = $this->renderView('invoice_add/invoice_add.html.twig', [
                    
            'form' => $form->createView(),
            //'positions' => $positions,
            //'invoice' => $invoice,
            //'invoicePositions'=>$invoicePositions,
                    
        ]);     
       
               
        return new Response ($contents);  
    } 
    
    public function position_add ($id_invoice, $id_position)
    {
                      
    // extracting array of InvoicePosition objects for the chosen invoice and position        
        $invoicePositionManager = $this->getDoctrine()->getManager();
        $invoicePositionArray = $invoicePositionManager->getRepository(InvoicePosition::class)
                                                        ->findBy ([
                                                            'invoice' => $id_invoice, 
                                                            'position' => $id_position 
                                                        ]);
        

    //checking if this position is already exist in the invoice and adding new position if not exist
        if (empty($invoicePositionArray) ) { 
                
            $positionManager = $this->getDoctrine()->getManager();
            $position = $positionManager->getRepository(Position::class)->find($id_position);

            $invoiceManager = $this->getDoctrine()->getManager();
            $invoice = $invoiceManager->getRepository(Invoice::class)->find($id_invoice);
            
            $invoicePosition = new InvoicePosition();
            $invoicePosition->setInvoice($invoice);
            $invoicePosition->setPosition($position);
            $invoicePosition->setQuantity(1);
                        
        }
        else {
    // changing the quantity for +1 
            $invoicePosition =  $invoicePositionArray[0];
            $quantity=$invoicePosition->getQuantity();
            $invoicePosition->setQuantity($quantity + 1);
        }

        $invoicePositionManager ->persist($invoicePosition);
        $invoicePositionManager ->flush();
        
    //reconstraction of chosen/not chosen positions (by getting saved GET form parameters) 
        $requestForm=$this->session->get('sessionForm'); 
        
        return $this->redirectToRoute( 'invoice_edit', ['id_invoice'=> $id_invoice,
                                                        'form'=>$requestForm]);
    }

    public function position_delete ($id_invoice, $id_position)
    {
    // extracting array of InvoicePosition objects for the chosen invoice and position     
        $invoicePositionManager = $this->getDoctrine()->getManager();
        $invoicePositionArray = $this->getDoctrine()->getRepository(InvoicePosition::class)
                                                ->findBy ([
                                                    'invoice' => $id_invoice, 
                                                    'position' => $id_position 
                                                ]);
        $invoicePosition =  $invoicePositionArray[0];

    // delete the position 
        if ($invoicePosition->getQuantity()==1) {
            $invoicePositionManager->remove($invoicePosition);
            $invoicePositionManager->flush();

        }
        else {
            $quantity=$invoicePosition->getQuantity();
            $invoicePosition->setQuantity($quantity - 1);
            $invoicePositionManager ->persist($invoicePosition);
            $invoicePositionManager ->flush();
        }
    
    //reconstraction of chosen/not chosen positions (by getting saved GET 'form' parameters) 
        $requestForm=$this->session->get('sessionForm'); 
        
        return $this->redirectToRoute( 'invoice_edit', ['id_invoice'=> $id_invoice,
                                                        'form'=>$requestForm]);
            
        }

        
}
