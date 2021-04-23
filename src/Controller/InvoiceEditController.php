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
        $note_position = 1;  // notes that proper position was chosen in input field for position
        $note_invoice = 1;   //notes that proper recipient and supplier were chosen in input fields
        $integer = true;     //notes that inputed quantity of the position is integer
        $zero = 1;           //notes that inputed quantity is not zero or empty

        $note_positions_not_saved = 0;  //notes that Array of invoicePosition from DB is EQUAL to the Array from session 
        $note_sup_recip_saved = 0;      //notes that  new Supplier/Recipient were NOT saved to DB
        $note_invoice_saved =0;         // notes that both changes (from table and in Supplier/Recipient) were NOT saved

        $invoiceManager = $this->getDoctrine()->getManager();
        $invoice = $invoiceManager->getRepository(Invoice::class)->find($id_invoice);

        $invoicePositionsCollection = $invoice->getInvoicePosition();  //collection of Invoice_position objects associated to this invoice
        $invoicePositionsArrayDB = $invoicePositionsCollection->toArray();  // change the Collection into Array
        
        // if invoice was deleted or such id_invoice is not exist - no way to get to this page:
        if ( $invoice==null ) {
            return $this->redirectToRoute('invoices');
        }
     
        /* if some time ago we have began to edit this invoice, but leave the page, edited table with the new positions in it is saved in session; 
         * and after open this page again we will see this changed table again.
         * It is because of for the present time I haven't think out  the way to unset this session variable after leaving Invoice-edit page.
         * For the momemt I only add deleting this session variable when getting to Invoice-edit page by link at Invoices-list page.
         * So here getting Array of InvoicePositions objects for the Invoice from session if session variable is exists:
         */
        if ( $this->session->get('sessionInvoicePositionsArray'.$id_invoice) !== null)   {
            $invoicePositionsArray = $this->session->get('sessionInvoicePositionsArray'.$id_invoice);
           
        }
        //if not in session - get Collection from DB and change the Collection into Array and write it to the session:
        else {
            $invoicePositionsArray = $invoicePositionsArrayDB;
            $this->session->set('sessionInvoicePositionsArray'.$id_invoice, $invoicePositionsArrayDB);
            //$invoicePositionsArray = $this->session->get('sessionInvoicePositionsArray'.$id_invoice);
        }

        // form for adding new positions to the table:
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
                $note_position = 0;  // notice flag "Add the position", if position field is empty
            }
            else {            
                foreach ($invoicePositionsArray as $invoicePosition) {

                    if ($position->getId() == $invoicePosition->getPosition()->getId() ) {
                        $note_position = 2;     // notice flag "THE POSITION IS ALREADY IN THE TABLE!!"
                    }
                }
            }
            
        //if all above validation is OK, then saving chosen InvoicePosition into $invoicePositionsArray and saving this array into session:
            if ($integer == true  and $zero == 1 and $note_position == 1) {
                
                array_push($invoicePositionsArray, $invoicePositionFromForm);
                $this->session->set('sessionInvoicePositionsArray'.$id_invoice, $invoicePositionsArray);
                
            //clearing form-fields after submit (just self-redirecting  like refreshing page):
                return $this->redirect($request->getUri());  
            }
        }

        /**
         * !!COMPARISON of Array from DB and  Array from session!!
         * 
         * if Array  from DB is not equal to the Array from session - stage  '$note_positions_not_saved==1':
         * it's needded for the NOTE - " Your positions were changed!!! Save to DB or skip changes!!!"

         * 
         * @todo
         * After first visit to this page, Array of InvoicePositions from DB for this invoice( $invoicePositionsArrayDB) is wrote down to the session!
         * After refreshing the page, that array is getting from the session and writing down to the $invoicePositionsArray variable.
         * BUT!!
         * !!!???!! I can't just compare arrays: $invoicePositionsArrayDB and $invoicePositionsArray from session 
         * 
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
         * @todo Maybe later will find another way to do this comparison???
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
                            $note_positions_not_saved = 0; //note that arrays are equal
                            break;
                        }
                        else {
                            $note_positions_not_saved = 1; //note that the arrays are NOT equal
                            $j = $j +1;
                            if ($j == count($invoicePositionsArray) ) {
                                goto outer;
                            }
                        }
                    }
            }
        }
        //if arrays are NOT the same length:
        else {
            $note_positions_not_saved = 1; //note that the arrays are NOT equal
        }
        outer:
        
        //form for editing recipient and supplier of the invoice:
        $supplier = $invoice->getSupplier();
        $recipient = $invoice->getRecipient();
        $invoice1 = new Invoice();
        $form = $this->createForm (InvoiceType::class, $invoice1) //if using $invoice, fields Supplier and Recipient demand Collection object!!! ???
        /* left createForm, instead of createFormBuilder for the possiblity of using eventListener in InvoiceType.php, 
         * but have to rewrite 'adds' without multiple choice and hide the 'invoicePosition' field:
         */
                        ->add('supplier', EntityType::class, [      'label'=>'Supplier (type Name or NIP):',
                                                                    'class' => Supplier::class,
                                                                    'query_builder' => function (SupplierRepository $er) use ($supplier) //for showing in the input the Supplier saved in DB fot this Invoice
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
                                                                    'query_builder' => function (RecipientRepository $er) use ($recipient) //for showing in the input the Recipient saved in DB fot this Invoice
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
                        ->add('send_sup_recip', SubmitType::class, ['label'=>'SAVE CHANGES IN SUPPLIER/ RECIPIENT TO DB'])
                        ->add('send_all', SubmitType::class, ['label'=>'SAVE ALL CHANGES TO DB']);
                        
        $form->handleRequest($request);

        if ($form->isSubmitted() ) {
            
            $supplier = $form->get('supplier')->getData();
            $recipient = $form->get('recipient')->getData();

            //validation of supplier/recipient fields:        
            if ($supplier == null or $recipient == null) {

                $note_invoice = 0;  // notice flag "INVOICE HAS NOT ADDED!! Recipient or Supplier field CAN'T be empty !!!!!!!!!!!!!!", if one or both field are not chosen
            
            }
            else {

                //saving only new Supplier and Recipient from FORM to DB:
                $invoiceManager = $this->getDoctrine()->getManager();
                $invoice->setSupplier ($supplier); //because in form was $invoice1
                $invoice->setRecipient($recipient);  //because in form was $invoice1
    
                $invoiceManager->persist($invoice);
                $invoiceManager->flush();
                
                // saving to DB also changed positions from the table, if the second SUBMIT button ''SAVE ALL CHANGES TO DB'' clicked:
                // but befor that - deleting all previous InvoicePositions):
                if ($form->get('send_all')->isClicked() ) {
                   
                    if ($note_positions_not_saved == 1) {   //note that Array of invoicePosition from DB is NOT equal to the Array from session
                        
                        //deleting all previous InvoicePositions:
                        $invoicePositionManager = $this->getDoctrine()->getManager();
                        $queryBuilder = $invoicePositionManager->createQueryBuilder()
                                                                    -> delete ('App\Entity\InvoicePosition','ip')
                                                                    -> andwhere ('ip.invoice = :id_invoice')
                                                                    -> setParameter('id_invoice', $id_invoice);
                        $query = $queryBuilder->getQuery();
                        $query->execute();   
    
                        //saving new InvoicePositions:
                        foreach ($invoicePositionsArray as $invoicePosition) {
                            
                            // for persisting Invoiceposition into DB I have to add Invoice and Position to the InvoicePosition again.
                            // See @TODO in InvoiceAddController where the same problem:
                                $invoicePosition->setInvoice($invoice);
                                    
                                $positionId=$invoicePosition->getPosition()->getId();
                                $repository=$this->getDoctrine()->getRepository(Position::class);
                                $position=$repository->find($positionId); 
                                $invoicePosition->setPosition($position);
                                
                                $invoicePositionManager->persist($invoicePosition);
                                $invoicePositionManager->flush();  
                        }
                    }
                    $note_positions_not_saved = 0; //changes in the table were saved - notes that Array of invoicePosition from DB is EQUAL to the Array from session 
                    $note_invoice_saved = 1;       //notes that all changes (from table and in Supplier/Recipient) were saved

                    $invoiceManager = $this->getDoctrine()->getManager();
                    $invoice = $invoiceManager->getRepository(Invoice::class)->find($id_invoice);

                    $invoicePositionsCollection = $invoice->getInvoicePosition();  //collection of Invoice_position objects associated to this invoice
                    $invoicePositionsArrayDB = $invoicePositionsCollection->toArray();  // change the Collection into Array
                    
                    $this->session->set('sessionInvoicePositionsArray'.$id_invoice, $invoicePositionsArrayDB);
                    
                }
                else {
 
                    $note_sup_recip_saved = 1;  // Flag, that only Sup/Recip were saved to DB

                }
            }
        }

        $contents = $this->renderView('invoice_edit/invoice_edit.html.twig', [
                    
            'form_position' => $form_position->createView(),
            'form' => $form->createView(),
            'note_invoice' => $note_invoice,
            'note_position' => $note_position,
            'integer' => $integer,
            'zero' => $zero,
            'note_positions_not_saved' => $note_positions_not_saved,
            'note_sup_recip_saved' => $note_sup_recip_saved,
            'note_invoice_saved' => $note_invoice_saved,
            'invoice' => $invoice,
            'invoicePositionsArray'=>$invoicePositionsArray,
                    
        ]);
                      
        return new Response ($contents);
    } 
    
    //deleting session variable with array of InvoicePositions objects (that is deleting all the changes in the table):
    public function invoice_edit_clear_all($id_invoice)
    {
        $this->session->set('sessionInvoicePositionsArray'.$id_invoice, null);
        return $this->redirectToRoute( 'invoice_edit', ['id_invoice' => $id_invoice]);
    }

    //delete the whole invoice from DB with all positions and assotiated invoicePositions objects:
    public function invoice_delete ($id_invoice)  
    {
        $this->session->set('sessionInvoicePositionsArray'.$id_invoice, null);

        //removing invoice from DB; all assotiations are removed thanks to "cascade={"remove"}"-annotation in property $invoicePostion in Invoice class:
        $invoiceManager = $this->getDoctrine()->getManager();
        $invoice = $invoiceManager->getRepository(Invoice::class)->find($id_invoice);
        $invoiceManager->remove($invoice);
        $invoiceManager->flush(); 

        return $this->redirectToRoute( 'invoices');
    }

    //saving changes from the table(changed InvoicePositions)  into DB (but befor that we have to delete all previous InvoicePositions):
    public function invoice_edit_save_positions($id_invoice)  
    {
        
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
            // See @TODO in InvoiceAddController where the same problem:
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
    
    //adding +1 item to the position in the table, but not save in DB yet (by pressing '+' in the table):
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

    //deleting -1 item to the position in the table, but not save in DB yet (by pressing '-' in the table):
    public function invoice_edit_position_delete ($quantity, $id_position, $id_invoice)  
    {
        $invoicePositionsArray = $this->session->get('sessionInvoicePositionsArray'.$id_invoice);
       
        $i=0;      
        foreach ($invoicePositionsArray as $invoicePosition) {

            if ($invoicePosition->getPosition()->getId() ==  $id_position) {

                if ($quantity == 1) {
                    array_splice($invoicePositionsArray, $i, 1);//deleting all the InvoicePosition object in the array
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

    //deleting the whole position in the table with all its quantity at once (by pressing 'X' in the table):
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
    
/**
 * @todo for future study!!
 * 
 * 1. make fields for enter the  quantity opposite every item in  the table - mayby customized build-in form??? (the same as in Invoice_Add page)
 * 
 * 2. How to make save inputs in the fields for Supplier and Recipient after pressing "skipp changes in the Table" (that is after refreshing page) , but! if they are not yet Submitted by the Invoice-form  (see the same @todo in Invoice_Add page)
 * 
 * + after choosing new Supplier/Recipient (!!but befor submitting the ivoice_form)- make appear the Note: "Supplier/Recipient were changed.
 *  Save them to DB or skip changes". Mayby JS here or sessions here????:
 *  
 * how it's realised now:  
 * Buttons "SKIP NOT SAVED CHANGES IN THE TABLE", "SKIP ALL CHANGES"  just refresh page and set the session Array to null; and button 
 * "SKIP NOT SAVED CHANGES IN SUPPLIER/ RECIPIENT" also just refresh page. 
 * So, chosen but not saved to DB Supplier/Recipient are skipped  after pressing all these 3 buttons. But I want to make them skipped only after pressing "SKIP ALL CHANGES" or "SKIP NOT SAVED CHANGES IN SUPPLIER/ RECIPIENT" .
 * 
 * 
 * 3. How to make unset session variable for Array with positions after: leaving the page with 'back' or closing the page. 
 * + maybe pop-up message: Are you sure to quit without saving? (the same as in Invoice_Add page)
 * 
 * 
 * 4. Put buttons "SKIP NOT SAVED CHANGES IN SUPPLIER/ RECIPIEN" and "SAVE CHANGES IN SUPPLIER/ RECIPIENT TO DB" in one line,
 * and buttons "SAVE ALL CHANGES" and "SKIP ALL CHANGES" in the next one line - learn customizing forms!!????? 
 * 
 *  
 */
