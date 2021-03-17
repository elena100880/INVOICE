<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class InvoiceController extends AbstractController
{
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function invoices(Request $request) : Response
    {  
        $invoice = new Invoice();
        $form = $this->createForm (InvoiceType::class, $invoice,['method' => 'GET'])

                        ->add ('empty_positions', ChoiceType::class, ['mapped' => false,
                                                                        'label' => ' ',
                                                                        'expanded' =>true,
                                                                        'choices' => [  'envoices with no positions' => 1,
                                                                                        'envoices with positions' => 2,
                                                                                        'all envoices' => 3],
                                                                        'data' => 3,
                                                                        'mapped' =>false ])
                        ->add('invoice_filter', HiddenType::class, ['mapped' => false])
                        ->add('send', SubmitType::class, ['label'=>'Show chosen invoices']);

        $form->handleRequest($request);
       
        $empty_positions = 0;
        if ($form->isSubmitted()) {
            
            $suppliersCollection = $form->get('supplier')->getData();
            $recipientsCollection = $form->get('recipient')->getData();
            $positionsCollection = $form->get('invoicePosition')->getData();
            $empty_positions = $form->get('empty_positions')->getData();

            $suppliersId=array();
            foreach ($suppliersCollection as $supplier) {
                array_push($suppliersId, $supplier->getId());
            }

            $recipientsId=array();
            foreach ($recipientsCollection as $recipient) {
                array_push($recipientsId, $recipient->getId());
            }

            $positionsId=array();
            foreach ($positionsCollection as $position) {
                array_push($positionsId, $position->getId());
            }
            
            $empty = new arrayCollection();
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
        //filtering invoices with no positions when empty_positions==1:           
                    /** 
                     * This filtering is not here but in twig, where empty_positions FLAG is checked, and if FLAG=1 - then only empty envoices are shown
                     * @todo: mayby to think out some code in queryBuilder here to filter empty invoices???? not in twig
                     * 
                     */

        //filtering invoices when some poitions were added to the Field-position:
                    if (  !empty($positionsId) and ($empty_positions == 3 or $empty_positions == 2 or $empty_positions == 1)  ) {
                                    $queryBuilder=$queryBuilder -> join ('i.invoicePosition', 'ip')
                                                                -> join ('ip.position', 'p')
                                                                -> andWhere ('p.id in (:positionsId)')
                                                                -> setParameter('positionsId', $positionsId);
                    }

        //filtering invoices with existing positions:           
                    if ( $empty_positions == 2 and empty($positionsId) ) {
                                    $queryBuilder=$queryBuilder -> join ('i.invoicePosition', 'ip');
                                                                
                    }
                    $invoices  = $queryBuilder->getQuery()->getResult();    
                /**
                 * @todo
                 * !! this invoices have only associated InvoicePosition-objects in them;
                 * that is: filtered invoices have only InvoicePosition-objects with 
                 * positions-id, which was chosen in Position filter.
                 * 
                 * Below - adding missing InvoicePosition-objects to filtered above $invoices (if Position field is not empty).
                 * It is needed for printing all positions in the table for particular invoice.
                 * !!!! - Mayby there is another way to do this - more complicated above query??? or sth else??
                 * 
                 * also - 500 risk here ??? @todo pagination????
                 */
                if (!empty($positionsId)) {

                    $invoices2 = array();
                    foreach ($invoices as $invoice) {               
                        $invoiceId = $invoice->getId();

                        $entityManager = $this->getDoctrine()->getManager();
                        $queryBuilder = $entityManager->createQueryBuilder()
                                                                        -> select('ip', 'i')
                                                                        -> from ('App\Entity\InvoicePosition', 'ip')
                                                                        -> join ('ip.invoice', 'i')
                                                                        -> andWhere ('i.id = :invoiceId')
                                                                        -> setParameter('invoiceId', $invoiceId);
                            
                        $invoicePositions = $queryBuilder->getQuery()->getResult(); 

                        foreach ($invoicePositions as $invoicePosition) {
                            $invoice->addInvoicePosition($invoicePosition);    
                        };

                            array_push($invoices2, $invoice);
                        }
                    $invoices = $invoices2;
                }   
        }  
        else {

            $entityManager = $this->getDoctrine()->getManager();
            $queryBuilder = $entityManager->createQueryBuilder()
                                            -> select('i')
                                            -> from ('App\Entity\Invoice', 'i')
                                            -> orderBy('i.id', 'DESC');
            $invoices  = $queryBuilder->getQuery()->getResult();                    //TODO: pagination????

        }

/** 
 * STUDY !!:
 * /// getting array of positions associated with the invoices with two join query: - so were chosen only the Positions-objects, 
 * which are present in invoices (for all invoices in this case)
 *      $queryBuilder = $entityManager->createQueryBuilder()
 *                                         -> select('p', 'pi', 'i')
 *                                         -> from ('App\Entity\Position', 'p')
 *                                         -> join ('p.positionInvoice', 'pi')
 *                                         -> join ('pi.invoice', 'i')
 *                                          -> orderBy('i.id', 'ASC');
 *         $positions  = $queryBuilder->getQuery()->getResult();   
 * 
 * /// getting array of positionInvoices associated with the invoices (WHERE for invoices must be added here): 
 * (instead of getting Collection with getInvoicePosition()-method)
 *        $queryBuilder = $entityManager->createQueryBuilder()
 *                                         -> select('pi', 'i')
 *                                         -> from ('App\Entity\InvoicePosition', 'pi')
 *                                         -> join ('pi.invoice', 'i');
 *                                         -> orderBy('i.id', 'ASC');
 *         $positionInvoices  = $queryBuilder->getQuery()->getResult();   
 * 
 * /// getting array of positionInvoices withot join-to-invoice as here is no WHERE-condition for the invoice, 
 * we use ALL invoices, so - ALL posInvoices-objects: (instead of getting Collection with getInvoicePosition()-method)
 *  *            $queryBuilder = $entityManager->createQueryBuilder()
 *                                         -> select('pi')
 *                                         -> from ('App\Entity\InvoicePosition', 'pi');
 *         $positionInvoices  = $queryBuilder->getQuery()->getResult();   //TODO: pagination???? 
 */
        $contents = $this->renderView('invoices/invoices.html.twig', [
                
            'form' => $form->createView(),
            'invoices' => $invoices,
            'empty_positions' => $empty_positions,
                        
            ]);
        return new Response ($contents);
    }
}