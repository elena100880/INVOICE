<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
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
    // filter for invoices
        $invoice = new Invoice();
        $form = $this->createForm (InvoiceType::class, $invoice,['method' => 'GET'])
                ->add('send', SubmitType::class, ['label'=>'Show the chosen']);
       
        $form->handleRequest($request);

        if ($form->isSubmitted() ) {
            
            $supplier=$form->get('supplier')->getData();
            $recipient=$form->get('recipient')->getData();
            
            $entityManager = $this->getDoctrine()->getManager();
            $queryBuilder = $entityManager->createQueryBuilder()
                                            -> select('inv')
                                            -> from ('App\Entity\Invoice', 'inv');
            if (isset($supplier)) {
                $queryBuilder=$queryBuilder->setParameter('supplier', strtolower($supplier))
                                            ->andwhere ($queryBuilder->expr()->eq(
                                                       $queryBuilder-> expr()->lower('inv.supplier'), ':supplier') ) ;
            }
            if (isset($recipient)) {
                $queryBuilder=$queryBuilder->setParameter('recipient', strtolower($recipient))
                                            ->andwhere ($queryBuilder->expr()->eq(
                                                       $queryBuilder-> expr()->lower('inv.recipient'), ':recipient') ) ;
            }
            $invoices = $queryBuilder->getQuery()->getResult();

        }

        else {

           $invoices = $this->getDoctrine()
                            ->getRepository(Invoice::class)
                            ->findAll();
        }                
        
        $contents = $this->renderView('invoices/invoices.html.twig', [
                
            'form' => $form->createView(),
            'invoices' => $invoices,
                
            ]);
        return new Response ($contents);
        
    }
}