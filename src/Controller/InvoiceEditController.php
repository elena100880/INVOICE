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

class InvoiceEditController extends AbstractController
{
    private $session;
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function invoice_edit(Request $request, $id_invoice) : Response
    {    
        //flags:
        $note_position = 0;
        $note_invoice = 0;
        $integer = true;
        $zero = 1;
        $note_positions_not_saved = 0;

        $invoiceManager = $this->getDoctrine()->getManager();
        $invoice = $invoiceManager->getRepository(Invoice::class)->find($id_invoice);

        $invoicePositionsCollection = $invoice->getInvoicePosition();  //collection of Invoice_position objects associated to this invoice
        $invoicePositionsArrayDB = $invoicePositionsCollection->toArray();  // change the Collection into Array
        
        // if invoice was deleted or such id_invoice is not exist - no way to get to this page:
        if ( $invoice==null ) {
            return $this->redirectToRoute('invoices');
        }
     
        // getting Array of InvoicePositions objects for the Invoice from session if session variable is exists:
        if ( $this->session->get('sessionInvoicePositionsArray'.$id_invoice) != null)  {
            $invoicePositionsArray = $this->session->get('sessionInvoicePositionsArray'.$id_invoice);
           
        }
        //if not in session - get Collection from DB and change the Collection into Array and write it to the session:
        else {
            $invoicePositionsArray = $invoicePositionsArrayDB;
            $this->session->set('sessionInvoicePositionsArray'.$id_invoice, $invoicePositionsArrayDB);
            //$invoicePositionsArray = $this->session->get('sessionInvoicePositionsArray'.$id_invoice);
        }

        // form for adding positions to the table:
        $invoicePositionFromForm = new InvoicePosition;
        $form_position = $this  -> createForm(InvoicePositionType::class, $invoicePositionFromForm)
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
                foreach ($invoicePositionsArray as $invoicePosition) {

                    if ($position->getId() == $invoicePosition->getPosition()->getId() ) {
                        $note_position = 2;     // notice flag "THE POSITION IS ALREADY IN THE TABLE!!"
                    }
                }
            }
            
            //if all above validation is OK, then saving chosen InvoicePosition into $invoicePositionsArray and saving this array into session:
            if ($integer == true  and $zero == 1 and $note_position == 0) {
                
                array_push($invoicePositionsArray, $invoicePositionFromForm);
                $this->session->set('sessionInvoicePositionsArray'.$id_invoice, $invoicePositionsArray);
                
            //clearing form-fields after submit (just self-redirecting  like refreshing page):
                return $this->redirect($request->getUri());  
            }
        }

        /**
         * if Array  from DB is not equal to the Array from session - stage NOTE:
         * 
         * @todo
         * After first visit to this page, Array of InvoicePositions from DB for this invoice( $invoicePositionsArrayDB) is wrote down to the session!
         * After refreshing the page, that array is getting from the session and writing down to the $invoicePositionsArray variable.
         * BUT!!
         * !!!???!! I could not just compare arrays: $invoicePositionsArrayDB and $invoicePositionsArray from session 
         * because if even they consist of the same array of InvoicePosition objects - 
         * that is: corresponding InvoicePosition objects in both arrays have equal quantity and equal id_position and id_invoice - 
         * but !! - positions/invoice objects IN corresponding InvoicePositions from both arrays-  are not equal! - 
         * they have different content of property PositionInvoice/invoicePosition in them, although have the same id;
         * and i haven't found the the cause of it; becuase it is - the same array!!. First - from DB, second - from session, but to the session was saved the aaray from DB!!!
         * So the property PositionInvoice/invoicePosition has changed after writing down to the session and after getting from the session!!!??
         * 
         * maybe it is the same problem as when I have the problem with persisting InvoicePositions in INVOICE_ADD page.
         * So, below - the comparison of two arrays by id_position and quantity:
         * 
         * Maybe another way to do the comparison???
         */
        
        if ( count($invoicePositionsArrayDB) == count($invoicePositionsArray) ) {
            
            //if arrays are the same length:
            for ($i = 0; $i<count($invoicePositionsArrayDB); $i=$i+1) {

                    $j=0;
                    foreach ($invoicePositionsArray as $invoicePosition) {
                        if (
                                ($invoicePositionsArrayDB[$i]->getPosition()->getId() ==  $invoicePosition->getPosition()->getId() 
                                and
                                $invoicePositionsArrayDB[$i]->getQuantity() == $invoicePosition->getQuantity() )
                                 
                            ) {
                            $note_positions_not_saved = 0;
                            break;
                        }
                        else {
                            $note_positions_not_saved = 1; 
                            $j = $j +1;
                            if ($j == count($invoicePositionsArray) ) {
                                goto outer;
                            }
                        }
                    }
            }
        }
        
        else {
            $note_positions_not_saved = 1;
        }
        outer:
        
        $supplier = $invoice->getSupplier();
        $recipient = $invoice->getRecipient();
        $invoice1 = new Invoice();
        $form = $this->createForm (InvoiceType::class, $invoice1)
                        ->add('supplier', EntityType::class, [      'label'=>'Supplier (type Name or NIP):',
                                                                    'class' => Supplier::class,
                                                                    'query_builder' => function (SupplierRepository $er) use ($supplier) 
                                                                                    {
                                                                                        return $er  ->createQueryBuilder('s')
                                                                                                    -> where ('s.id = :supplierId')
                                                                                                    -> setParameter('supplierId', $supplier->getId());
                                                                                    }, 
                                                                        
                                                                    'choice_label' => function ($supplier) 
                                                                                    {
                                                                                        return $supplier->getName().' NIP: '.$supplier->getNip();
                                                                                    },
                                                                    'attr' => array('class' => 'js-select2-invoice-supplier')   
                                                                ])

                        ->add('recipient', EntityType::class, [     'label'=>'Recipient (type Name, Family or Address):',
                                                                    'class' => Recipient::class,
                                                                    'query_builder' => function (RecipientRepository $er) use ($recipient) 
                                                                                    {
                                                                                        return $er  ->createQueryBuilder('r')
                                                                                                    -> where ('r.id = :recipientId')
                                                                                                    -> setParameter('recipientId', $recipient->getId());
                                                                                    }, 
                                                                        
                                                                    'choice_label' => function ($recipient) 
                                                                                    {
                                                                                        return $recipient->getName().', '.$recipient->getFamily().', '.$recipient->getAddress();
                                                                                    },
                                                                    'attr' => array('class' => 'js-select2-invoice-recipient')   
                                                            ])
                        ->add('invoicePosition', HiddenType::class, ['mapped' => false])

                        ->add('invoice_add', HiddenType::class, ['mapped' => false])
                        ->add('send', SubmitType::class, ['label'=>'SAVE ALL CHANGES IN THE INVOICE']);

        $form->handleRequest($request);

        if ($form->isSubmitted() ) {
            
            $supplier = $form->get('supplier')->getData();
            $recipient = $form->get('recipient')->getData();

        }

        $contents = $this->renderView('invoice_edit/invoice_edit.html.twig', [
                    
            'form_position' => $form_position->createView(),
            'form' => $form->createView(),
            'note_invoice' => $note_invoice,
            'note_position' => $note_position,
            'integer' => $integer,
            'zero' => $zero,
            'note_positions_not_saved' => $note_positions_not_saved,
            'invoice' => $invoice,
            'invoicePositionsArray'=>$invoicePositionsArray,
                    
        ]);
       
               
        return new Response ($contents);
    } 
    
    public function invoice_edit_clear_all($id_invoice)
    {
        $this->session->set('sessionInvoicePositionsArray'.$id_invoice, null);
        return $this->redirectToRoute( 'invoice_edit', ['id_invoice' => $id_invoice]);
    }

    public function invoice_edit_save_positions($id_invoice)
    {
        //saving changes from the table(changed InvoicePositions)  into DB (but befor - deleting all previous InvoicePositions):
        $invoicePositionsArray = $this->session->get('sessionInvoicePositionsArray'.$id_invoice);

        $invoiceManager = $this->getDoctrine()->getManager();
        $invoice = $invoiceManager->getRepository(Invoice::class)->find($id_invoice);

        $invoicePositionManager = $this->getDoctrine()->getManager();
        $queryBuilder = $invoicePositionManager->createQueryBuilder()
                                                    -> delete ('App\Entity\InvoicePosition','ip')
                                                    -> andwhere ('ip.invoice = :id_invoice')
                                                    -> setParameter('id_invoice', $id_invoice);
        $query = $queryBuilder->getQuery();
        $query->execute();   

        foreach ($invoicePositionsArray as $invoicePosition) {
            
            // for persisting Invoiceposition into DB I have to add Invoice and Position to the InvoicePosition again.
            // See TODO in InvoiceAddController woth the same problem:
                $invoicePosition->setInvoice($invoice);
                     
                $positionId=$invoicePosition->getPosition()->getId();
                $repository=$this->getDoctrine()->getRepository(Position::class);
                $position=$repository->find($positionId); 
                $invoicePosition->setPosition($position);
                
                $invoicePositionManager->persist($invoicePosition);
                $invoicePositionManager->flush();  
        }
        
        $this->session->set('sessionInvoicePositionsArray'.$id_invoice, null);

        return $this->redirectToRoute( 'invoice_edit', ['id_invoice' => $id_invoice]);
    }
    
    public function invoice_edit_position_add ($quantity, $id_position, $id_invoice)
    {
        $invoicePositionsArray = $this->session->get('sessionInvoicePositionsArray'.$id_invoice);
            
        foreach ($invoicePositionsArray as $invoicePosition) {

            if ($invoicePosition->getPosition()->getId() ==  $id_position) {

                $invoicePosition->setQuantity($quantity + 1);
                
            }
                
        }

        $this->session->set('sessionInvoicePositionsArray'.$id_invoice, $invoicePositionsArray);
        
        return $this->redirectToRoute( 'invoice_edit', ['id_invoice' => $id_invoice] );  
    }

    public function invoice_edit_position_delete ($quantity, $id_position, $id_invoice)
    {
        $invoicePositionsArray = $this->session->get('sessionInvoicePositionsArray'.$id_invoice);
       
        $i=0;      
        foreach ($invoicePositionsArray as $invoicePosition) {

            if ($invoicePosition->getPosition()->getId() ==  $id_position) {

                if ($quantity == 1) {
                    array_splice($invoicePositionsArray, $i, 1);
                }
                else {
                    $invoicePosition->setQuantity($quantity - 1);
                }
                
            }
            $i=$i+1;
        }

        $this->session->set('sessionInvoicePositionsArray'.$id_invoice, $invoicePositionsArray);
        
        return $this->redirectToRoute( 'invoice_edit', ['id_invoice' => $id_invoice] );  
    }

    public function invoice_edit_position_delete_whole ($id_position, $id_invoice)
    {
        $invoicePositionsArray = $this->session->get('sessionInvoicePositionsArray'.$id_invoice);
       
        $i=0;      
        foreach ($invoicePositionsArray as $invoicePosition) {

            if ($invoicePosition->getPosition()->getId() ==  $id_position) {
                
                array_splice($invoicePositionsArray, $i, 1);
                //break;
            }

            $i=$i+1;
        }

        $this->session->set('sessionInvoicePositionsArray'.$id_invoice, $invoicePositionsArray);
        
        return $this->redirectToRoute( 'invoice_edit', ['id_invoice' => $id_invoice] );  
    }
}
    

    /*
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
   */
        

