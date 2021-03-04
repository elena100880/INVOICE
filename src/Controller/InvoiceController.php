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

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityManagerInterface;


class InvoiceController extends AbstractController
{
    public function invoices(Request $request) : Response
    {     
    
        $invoice = new Invoice();
        $form = $this->createForm (InvoiceType::class, $invoice)
                        ->add('send', SubmitType::class, ['label'=>'Add new invoice']);

        $form->handleRequest($request);
       
        if ($form->isSubmitted()) {
            $invoiceManager = $this->getDoctrine()->getManager();
            $invoiceManager->persist($invoice);
            $invoiceManager->flush();
            $id=$invoice->getId();
                    
            return $this->redirectToRoute('invoice_edit', ['id_invoice'=> $id] );
        }

        $entityManager = $this->getDoctrine()->getManager();
        $queryBuilder = $entityManager->createQueryBuilder()
                                        -> select('i')
                                        -> from ('App\Entity\Invoice', 'i')
                                        -> orderBy('i.id', 'ASC');
        $invoices  = $queryBuilder->getQuery()->getResult();    //TODO: pagination????

//STUDY !!:
// getting array of positions associated with the invoices with two join query: - so were chosen only the Positions-objects, which are present in invoices (for all invoices in this case)
        $queryBuilder = $entityManager->createQueryBuilder()
                                        -> select('p', 'pi', 'i')
                                        -> from ('App\Entity\Position', 'p')
                                        -> join ('p.positionInvoice', 'pi')
                                        -> join ('pi.invoice', 'i')
                                        -> orderBy('i.id', 'ASC');
        $positions  = $queryBuilder->getQuery()->getResult();   

// getting array of positionInvoices associated with the invoices (WHERE for invoices must be added here): (instead of getting Collection with getInvoicePosition()-method)
        $queryBuilder = $entityManager->createQueryBuilder()
                                        -> select('pi', 'i')
                                        -> from ('App\Entity\InvoicePosition', 'pi')
                                        -> join ('pi.invoice', 'i');
                                        //-> orderBy('i.id', 'ASC');
        $positionInvoices  = $queryBuilder->getQuery()->getResult();   

// getting array of positionInvoices withot join-to-invoice as here is no WHERE-condition for the invoice, we use ALL invoices, so - ALL posInvoices-objects: (instead of getting Collection with getInvoicePosition()-method)
        $queryBuilder = $entityManager->createQueryBuilder()
                                        -> select('pi')
                                        -> from ('App\Entity\InvoicePosition', 'pi');
        $positionInvoices  = $queryBuilder->getQuery()->getResult();   //TODO: pagination???? 


        $contents = $this->renderView('invoices/invoices.html.twig', [
                
            'form' => $form->createView(),
            'invoices' => $invoices,
            'positions' => $positions,
            'positionInvoices' => $positionInvoices,
                
            ]);
        return new Response ($contents);
        
    }
}