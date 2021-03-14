<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\AbstractType;

use App\Entity\Supplier;
use App\Entity\Recipient;
use App\Entity\Position;

use App\Repository\SupplierRepository;
use App\Repository\RecipientRepository;
use App\Repository\PositionRepository;

use App\Entity\Invoice;
use App\Form\Type\InvoiceType;
use App\Repository\InvoiceRepository;

use App\Entity\InvoicePosition;
use App\Form\Type\InvoicePositionType;
use App\Repository\InvoicePositionRepository;

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
        // flags note that all is OK:
        $note_position = 0;
        $note_invoice = 0;
        $integer = true;
        $zero = 1;
                
        if ( $this->session->get('sessionInvoicePositionsArray') != null)  {
            $invoicePositionsArray = $this->session->get('sessionInvoicePositionsArray');
        }
        else {
            $invoicePositionsArray = array();
        }

        $invoicePosition = new InvoicePosition;
        $form_position = $this  -> createForm(InvoicePositionType::class, $invoicePosition) //, ['method' => 'GET'])
                               
                                -> add('invoice_position_add', HiddenType::class, ['mapped' => false])
                                -> add ('send', SubmitType::class, ['label' => 'Add chosen position']);
                                
                                
        $form_position->handleRequest($request);
                
        if ($form_position->isSubmitted() ) {
            
            $position = $form_position->get('position')->getData();
            $quantity = $form_position->get('quantity')->getData();
            
        //validation of quantity field:
            if (!is_numeric($quantity) or  ($quantity - floor( $quantity) ) != 0) {
                $integer = false;  // notice flag "TYPE INTEGER NUMBER !! " if number is string or not integer
            }

            if ($quantity == '0') {
                $zero = 0;  // notice flag "TYPE MORE THAN 0 !!" if quantity =0
            }
               
            if ($position == null) {
                $note_position = 1;  // notice flag "Add the position", if position field is empty
            }
            else {            
                foreach ($invoicePositionsArray as $invoicePositionInArray) {

                    if ($position->getId() == $invoicePositionInArray->getPosition()->getId() ) {
                        $note_position = 2;     // notice flag "THE POSITION IS ALREADY IN THE TABLE!!"
                    }
                }
            }
            
            //saving chosen InvoicePosition into array and saving this array into session:
            if ($integer == true  and $zero == 1 and $note_position == 0) {
                
                array_push($invoicePositionsArray, $invoicePosition);
                $this->session->set('sessionInvoicePositionsArray', $invoicePositionsArray);
                
            //clearing form-fields after submit (just self-redirecting or refreshing):
                return $this->redirect($request->getUri());  
            }
        }
               
        $invoice = new Invoice();
        $form = $this->createForm (InvoiceType::class, $invoice)
                        ->add('supplier', EntityType::class, [      'label'=>'Supplier (type Name or NIP):',
                                                                    'class' => Supplier::class,
                                                                    'choices' =>[],
                                                                    'attr' => array('class' => 'js-select2-invoice-supplier')   
                                                                ])

                        ->add('recipient', EntityType::class, [     'label'=>'Recipient (type Name, Family or Address):',
                                                                    'class' => Recipient::class,
                                                                    'choices' =>[],
                                                                    'attr' => array('class' => 'js-select2-invoice-recipient')   
                                                            ])
                        ->add('invoicePosition', HiddenType::class, ['mapped' => false])
                        ->add('invoice_add', HiddenType::class, ['mapped' => false])
                        ->add('send', SubmitType::class, ['label'=>'Create invoice']);

        $form->handleRequest($request);
    

        if ($form->isSubmitted() ) {
            
            $supplier = $form->get('supplier')->getData();
            $recipient = $form->get('recipient')->getData();
            
            
    //validation of position field:        
            if ($supplier != null or $recipient != null) {
                
                $invoiceManager = $this->getDoctrine()->getManager();
                $invoiceManager->persist($invoice);
                $invoiceManager->flush();

                foreach ($invoicePositionsArray as $invoicePosition) {
                    
                    $invoicePosition->setInvoice($invoice);

                //???? persist for InvoicePositions doesn't work without this 3 lines!!
                // that is:  I have to add the position to the InvoicePosition in such way as below,
                //whereas my InvoicePosition object in each iteration ALREADY HAS associated position:  
                
                //FOUND!!! - the Position in made-from-form $InvoicePosition from array $invoicePositionsArray - has NO assotiated positionInvoices objects (allthough in DB this Position HAS  assotiated positionInvoices objects). So I have to get Position from DB again and add it to my $InvoicePosition Position-property:
                    $positionId=$invoicePosition->getPosition()->getId();
                    $repository=$this->getDoctrine()->getRepository(Position::class);
                    $position=$repository->find($positionId);

                    $invoicePosition->setPosition($position);
                    // *@ORM\ManyToOne(targetEntity=Position::class, inversedBy="positionInvoice", cascade={"persist"}) - was added to Position-property in InvoicePosition class!! - - WRONG!!! persists new duplicate positions after creating new Invoice!!

                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($invoicePosition);
                    $entityManager->flush();
                }
                
                $this->session->set('sessionInvoicePositionsArray', null);
                return $this->redirectToRoute( 'invoices');       
                            
            }
            else {
                $note_invoice = 1;  // notice flag "INVOICE HAS NOT ADDED!! Recipient or Supplier field CAN'T be empty !!!!!!!!!!!!!!", if one or both field are not chosen
            }
        }

        $contents = $this->renderView('invoice_add/invoice_add.html.twig', [
                        
            'form' => $form->createView(),
            'form_position' => $form_position -> createView(),
            'note_invoice' => $note_invoice,
            'note_position' => $note_position,
            'integer' => $integer,
            'zero' => $zero,
            'invoicePositionsArray' => $invoicePositionsArray,

        ]); 

        return new Response ($contents);
        
    } 
    
    public function invoice_clear_all_forms ()
    {
        $this->session->set('sessionInvoicePositionsArray', null);
        return $this->redirectToRoute( 'invoice_add');
    }
        
    public function invoice_add_position_add ($quantity, $id_position)
    {
        $invoicePositionsArray = $this->session->get('sessionInvoicePositionsArray');
            
            foreach ($invoicePositionsArray as $invoicePosition) {

                if ($invoicePosition->getPosition()->getId() ==  $id_position) {

                    $invoicePosition->setQuantity($quantity + 1);
                    //break;
                }
                
            }
            $this->session->set('sessionInvoicePositionsArray', $invoicePositionsArray);
        
        return $this->redirectToRoute( 'invoice_add' );  
    }

    public function invoice_add_position_delete ($quantity, $id_position)
    {
        $invoicePositionsArray = $this->session->get('sessionInvoicePositionsArray');
       
            $i=0;      
            foreach ($invoicePositionsArray as $invoicePosition) {

                if ($invoicePosition->getPosition()->getId() ==  $id_position) {

                    if ($quantity == 1) {
                        array_splice($invoicePositionsArray, $i, 1);
                    }
                    else {
                        $invoicePosition->setQuantity($quantity - 1);
                    }
                    //break;
                }
                $i=$i+1;
            }
            $this->session->set('sessionInvoicePositionsArray', $invoicePositionsArray);
        
        return $this->redirectToRoute( 'invoice_add' );  
    }

    public function invoice_add_position_delete_whole ($id_position)
    {
        $invoicePositionsArray = $this->session->get('sessionInvoicePositionsArray');
       
            $i=0;      
            foreach ($invoicePositionsArray as $invoicePosition) {

                if ($invoicePosition->getPosition()->getId() ==  $id_position) {

                    array_splice($invoicePositionsArray, $i, 1);
                    //break;
                }
                $i=$i+1;
            }
            $this->session->set('sessionInvoicePositionsArray', $invoicePositionsArray);
        
        return $this->redirectToRoute( 'invoice_add' ); 
    }
        
}
