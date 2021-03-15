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
        $note_positions_not_saved = 0;


        $invoiceManager = $this->getDoctrine()->getManager();
        $invoice = $invoiceManager->getRepository(Invoice::class)->find($id_invoice);
        $invoicePositionsCollection = $invoice->getInvoicePosition();  //collection of Invoice_position objects associated to this invoice
        $invoicePositionsArrayDB = $invoicePositionsCollection->toArray();  // change the Collection into Array

        // if invoice was deleted - no way to get to rhis page:
        if ( $invoice==null ) {
            return $this->redirectToRoute('invoices');
        }
     
        // form for adding positions:
        $invoicePosition = new InvoicePosition;
        $form_position = $this  -> createForm(InvoicePositionType::class, $invoicePosition) //, ['method' => 'GET'])
                               
                                -> add('invoice_position_add', HiddenType::class, ['mapped' => false])
                                -> add ('send', SubmitType::class, ['label' => 'Add chosen position']);

        $form_position->handleRequest($request);

       // getting Array of InvoicePositions objects for the Invoice from session if it exists:
        if ( $this->session->get('sessionInvoicePositionsArray'.$id_invoice) != null)  {
            $invoicePositionsArray = $this->session->get('sessionInvoicePositionsArray'.$id_invoice);
           
        }
        //if not in session - get Collection from DB and change the Collection into Array and write it to the session:
        else {
            $invoicePositionsArray = $invoicePositionsArrayDB;
            $this->session->set('sessionInvoicePositionsArray'.$id_invoice, $invoicePositionsArrayDB);
        }

        //if Array  from DB is not equal to the Array from session - stage NOTE:
        if ($invoicePositionsArrayDB != $invoicePositionsArray) {
            $note_positions_not_saved = 1;
        }
                       
        $contents = $this->renderView('invoice_edit/invoice_edit.html.twig', [
                    
            'form_position' => $form_position->createView(),
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
    
    public function invoice_edit_position_add ($quantity, $id_position, $id_invoice)
    {
        $invoicePositionsArray = $this->session->get('sessionInvoicePositionsArray'.$id_invoice);
            
        foreach ($invoicePositionsArray as $invoicePosition) {

            if ($invoicePosition->getPosition()->getId() ==  $id_position) {

                $invoicePosition->setQuantity($quantity + 1);
                //break;
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
                //break;
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
        

