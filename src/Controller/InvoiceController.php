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
                                        -> orderBy('i.id', 'DESC');
        $invoices  = $queryBuilder->getQuery()->getResult();

// getting array of positions associated with the invoices with two join query:



        
        $contents = $this->renderView('invoices/invoices.html.twig', [
                
            'form' => $form->createView(),
            'invoices' => $invoices,
                
            ]);
        return new Response ($contents);
        
    }
}