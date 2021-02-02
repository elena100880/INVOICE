<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\AbstractType;

use App\Entity\Invoice;
use App\Form\Type\InvoiceType;
use App\Repository\InvoiceRepository;

use App\Entity\Position;
use App\Form\Type\PositionType;
use App\Repository\PositionRepository;

use App\Entity\InvoicePosition;
use App\Repository\InvoicePositionRepository;


use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityManagerInterface;

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
        $invoiceManager = $this->getDoctrine()->getManager();
        $invoice = $invoiceManager->getRepository(Invoice::class)->find($id_invoice);

        if ( $invoice==null ) {
            return $this->redirectToRoute('invoices');
        }

    //making array of Positions objects in Invoice    
        $invoiceHavePositions = $invoice->getInvoicePosition();
        $invoicePositions = array();
        foreach ($invoiceHavePositions as $invoiceHavePosition) {
            array_push($invoicePositions, $invoiceHavePosition->getPosition());
        }

    //form for choose positions to add 
        $form = $this->createFormBuilder()
                        ->setMethod('GET')
                        ->add('name', EntityType::class, ['label'=>'Filter with Name:',
                                                            'class'=> Position::class,
                                                            'choice_label' => 'name',
                                                            'required' => false,
                                                            'placeholder'=>"all"
                                                            ]) 
                        ->add('value', EntityType::class, ['label'=>'Filter with Value:',
                                                            'class'=> Position::class,
                                                            'choice_label' => 'value',
                                                            'required' => false,
                                                            'placeholder'=>"all"
                                                            ] )
                        ->add('send', SubmitType::class, ['label'=>'Show positions'])
                        ->getForm();

        $form->handleRequest($request);
                
    //saving info about chosen/not chosen positions (by saving GET 'form' parameters) 
        $request= Request::createFromGlobals();
        $requestForm=$request->query->get('form');
        $this->session->set('sessionForm', $requestForm  );
                
    //filtering positions  
        $positions=array();

        if ($form->isSubmitted() ) {
            
            $data = $form->getData();
            $name=$data['name'];
            $value=$data['value'];
            
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
                $queryBuilder=$queryBuilder->setParameter('name', strtolower($name))
                                            ->andwhere ($queryBuilder->expr()->eq(
                                                       $queryBuilder-> expr()->lower('pos.name'), ':name') ) ;
            }
            $positions = $queryBuilder->getQuery()->getResult();

        //saving info about chosen/not chosen products (by saving GET form parameters) 
            $request= Request::createFromGlobals();
            $requestForm=$request->query->get('form');
            $this->session->set('sessionForm', $requestForm  );
              
        } 
        
        $contents = $this->renderView('invoice_edit/invoice_edit.html.twig', [
                    
            'form' => $form->createView(),
            'positions' => $positions,
            'invoice' => $invoice,
            'invoicePositions'=>$invoicePositions,
                    
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
