<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
//use Symfony\Component\Form\Extension\Core\Type\EntityType;
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


class InvoiceController extends AbstractController
{
    public function invoices(Request $request) : Response
    {  
        $invoice = new Invoice();
        $form = $this->createForm (InvoiceType::class, $invoice,['method' => 'GET'])
           
                        ->add('invoice', HiddenType::class, ['mapped' => false])
                        ->add('send', SubmitType::class, ['label'=>'Show chosen invoices'
                                                            ]);

        $form->handleRequest($request);
       
        if ($form->isSubmitted()) {
            
            $suppliersCollection = $form->get('supplier')->getData();
            $recipientsCollection = $form->get('recipient')->getData();

            $suppliersId=array();
            foreach ($suppliersCollection as $supplier) {
                array_push($suppliersId, $supplier->getId());
            }

            $recipientsId=array();
            foreach ($recipientsCollection as $recipient) {
                array_push($recipientsId, $recipient->getId());
            }
            

            $entityManager = $this->getDoctrine()->getManager();
            $queryBuilder = $entityManager->createQueryBuilder()
                                                        -> select('i', 's', 'r')
                                                        -> from ('App\Entity\Invoice', 'i')
                                                        -> join ('i.supplier', 's')
                                                        -> join ('i.recipient', 'r')
                                                        -> orderBy('i.id', 'DESC');
                        if (!empty($suppliersId)) {
                            $queryBuilder=$queryBuilder -> andWhere ('s.id in (:suppliersId)')
                                                        -> setParameter('suppliersId', $suppliersId);
                                            }
                        if (!empty($recipientsId)) {
                            $queryBuilder=$queryBuilder -> andWhere ('r.id in (:recipientsId)')
                                                        -> setParameter('recipientsId', $recipientsId);
                                            }

            $invoices  = $queryBuilder->getQuery()->getResult();       //TODO: pagination????
        
        }  

        else {

            $entityManager = $this->getDoctrine()->getManager();
            $queryBuilder = $entityManager->createQueryBuilder()
                                            -> select('i')
                                            -> from ('App\Entity\Invoice', 'i')
                                            -> orderBy('i.id', 'DESC');
            $invoices  = $queryBuilder->getQuery()->getResult();                    //TODO: pagination????

        }

//STUDY !!:
// getting array of positions associated with the invoices with two join query: - so were chosen only the Positions-objects, which are present in invoices (for all invoices in this case)
       /* $queryBuilder = $entityManager->createQueryBuilder()
                                        -> select('p', 'pi', 'i')
                                        -> from ('App\Entity\Position', 'p')
                                        -> join ('p.positionInvoice', 'pi')
                                        -> join ('pi.invoice', 'i')
                                        -> orderBy('i.id', 'ASC');
        $positions  = $queryBuilder->getQuery()->getResult();   */

// getting array of positionInvoices associated with the invoices (WHERE for invoices must be added here): (instead of getting Collection with getInvoicePosition()-method)
      /*  $queryBuilder = $entityManager->createQueryBuilder()
                                        -> select('pi', 'i')
                                        -> from ('App\Entity\InvoicePosition', 'pi')
                                        -> join ('pi.invoice', 'i');
                                        //-> orderBy('i.id', 'ASC');
        $positionInvoices  = $queryBuilder->getQuery()->getResult();   */

// getting array of positionInvoices withot join-to-invoice as here is no WHERE-condition for the invoice, we use ALL invoices, so - ALL posInvoices-objects: (instead of getting Collection with getInvoicePosition()-method)
      /*  $queryBuilder = $entityManager->createQueryBuilder()
                                        -> select('pi')
                                        -> from ('App\Entity\InvoicePosition', 'pi');
        $positionInvoices  = $queryBuilder->getQuery()->getResult();   //TODO: pagination???? */


        $contents = $this->renderView('invoices/invoices.html.twig', [
                
            'form' => $form->createView(),
            'invoices' => $invoices,
            
            ]);
        return new Response ($contents);
    }
}