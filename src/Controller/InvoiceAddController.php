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

    public function invoiceAdd(Request $request) : Response
    {     
        // flags:
        $note_position = 1; // notes that position was chosen in input field for position
        $note_invoice = 1;  //notes that recipient and supplier were chosen in input fields
        $integer = true;  //notes that inputed quantity of the position is integer
        $zero = 1; //notes that inputed quantity is not zero or empty
        
        /* 
         * if some time ago we have began to create an invoice, but leave the page, created table with the positions in it is saved in session;
         *
         * and after open this page again we will see this table again.
         * It is because of for the present time I haven't think out  the way to unset this session variable after leaving Invoice-add page
         * For the momemt I only add deleting this session variable when getting to Invoice-add page by link at Invoices-list page
         */
        if ( $this->session->get('sessionInvoicePositionsArray') !== null)  {
            $invoicePositionsArray = $this->session->get('sessionInvoicePositionsArray');
        }
        else {
            $invoicePositionsArray = array();
        }

    //form for adding chosen positions to the virtual table:
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
                $zero = 0;  // notice flag "TYPE MORE THAN 0 !!" if quantity == 0. The validation for 'empty field' I left for the browser for the moment
            }
               
            if ($position == null) {
                $note_position = 0;  // notice flag "Add the position", if position field is empty
            }
            else {            
                foreach ($invoicePositionsArray as $invoicePositionInArray) {

                    if ($position->getId() == $invoicePositionInArray->getPosition()->getId() ) {
                        $note_position = 2;     // notice flag "THE POSITION IS ALREADY IN THE TABLE!!"
                    }
                }
            }
            
        //if all is OK, adding the chosen InvoicePosition to the table and saving array of InvoicePositions objects to the session:
            if ($integer == true  and $zero == 1 and $note_position == 1) {
                
                array_push($invoicePositionsArray, $invoicePosition);
                $this->session->set('sessionInvoicePositionsArray', $invoicePositionsArray);
                
            //clearing form-fields after submit (just self-redirecting, that is refreshing):
                return $this->redirect($request->getUri());  
            }
        }
         
    //form for adding recipient and supplier to the invoice:
        $invoice = new Invoice();
        $form = $this->createForm (InvoiceType::class, $invoice) 
        /* left createForm, instead of createFormBuilder for the possiblity of using eventListener in InvoiceType.php, 
         * but have to rewrite 'adds' without multiple choice and hide the 'invoicePosition' field:
         */
                        ->add('supplier', EntityType::class, [      'label'=>'Supplier (type Name or NIP):',
                                                                    'class' => Supplier::class,
                                                                    'choices' =>[],  //It is for not showing  all select options in html in browser
                                                                    'attr' => array('class' => 'js-select2-invoice-supplier')   
                                                                ])

                        ->add('recipient', EntityType::class, [     'label'=>'Recipient (type Name, Family or Address):',
                                                                    'class' => Recipient::class,
                                                                    'choices' =>[],  //It is for not showing  all select options in html in browser
                                                                    'attr' => array('class' => 'js-select2-invoice-recipient')   
                                                            ])
                        ->add('invoicePosition', HiddenType::class, ['mapped' => false])
                        ->add('invoice_add', HiddenType::class, ['mapped' => false])
                        ->add('send', SubmitType::class, ['label'=>'Create invoice']);

        $form->handleRequest($request);
    

        if ($form->isSubmitted() ) {
            
            $supplier = $form->get('supplier')->getData();
            $recipient = $form->get('recipient')->getData();
            
            
    //validation of supplier/recipient fields:        
            if ($supplier != null and $recipient != null) {
                
        //saving invoice into DB if all is OK:
                $invoiceManager = $this->getDoctrine()->getManager();
                $invoiceManager->persist($invoice);
                $invoiceManager->flush();

        //saving invoicePositions from the table to DB for our created invoice:
                foreach ($invoicePositionsArray as $invoicePosition) {
                    
                    $invoicePosition->setInvoice($invoice);
                           
                    $positionId=$invoicePosition->getPosition()->getId();
                    $repository=$this->getDoctrine()->getRepository(Position::class);
                    $position=$repository->find($positionId);

                    $invoicePosition->setPosition($position);
                    // *@ORM\ManyToOne(targetEntity=Position::class, inversedBy="positionInvoice", cascade={"persist"}) - was added to Position-property in InvoicePosition class!! - but thay was WRONG!!! as persists new duplicate positions after creating new Invoice!!

                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($invoicePosition);
                    $entityManager->flush();
                }
                
                $this->session->set('sessionInvoicePositionsArray', null);
                return $this->redirectToRoute( 'invoices');       
                            
            }
            else {
                $note_invoice = 0;  // notice flag "INVOICE HAS NOT ADDED!! Recipient or Supplier field CAN'T be empty !!!!!!!!!!!!!!", if one or both field are not chosen
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
    
    //deleting session variable with array of InvoicePositions objects (that is deleting all the table with chosen positions):
    public function invoiceAddClearAll ()
    {
        $this->session->set('sessionInvoicePositionsArray', null);
        return $this->redirectToRoute( 'invoice_add');
    }
    
     //adding +1 item to the position in the table, but not save in DB yet (by pressing '+' in the table):
    public function invoiceAddPositionAdd ($quantity, $id_position)
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

   //deleting -1 item to the position in the table, but not save in DB yet (by pressing '-' in the table):
    public function invoiceAddPositionDelete ($quantity, $id_position)
    {
        $invoicePositionsArray = $this->session->get('sessionInvoicePositionsArray');
       
            $i=0;      
            foreach ($invoicePositionsArray as $invoicePosition) {

                if ($invoicePosition->getPosition()->getId() ==  $id_position) {

                    if ($quantity == 1) {
                        array_splice($invoicePositionsArray, $i, 1); //deleting all the InvoicePosition object in the array
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

    //deleting the whole position in the table with all its quantity at once (by pressing 'X' in the table):
    public function invoiceAddPositionDeleteWhole ($id_position)
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

/**
 * @todo for future study!!
 * 
 * 1. make fields for enter the  quantity next to every item in  the table- mayby customized build-in form??? (the same as in Invoice_Edit page)
 * 
 * 2. How to make saving inputs in the fields for Supplier and Recipient after refreshing page but before Submit of the Invoice (the same as in Invoice_Edit page), because after adding or deleting quantity in the table the page is also refreshed
 * 
 * 3. How to make unset session variable for Array with positions after: leaving the page with 'back' or closing the page. 
 * + maybe pop-up message: Are you sure to quit without saving? (the same as in Invoice_Edit page). Maybe JS here??..
 * 
 * 4. how to make different (mayby some self-generated id) session variables for Array with positions for enabling opening several Invoice_add pages
 * 
 * 
 */
