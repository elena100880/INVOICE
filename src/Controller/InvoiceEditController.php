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
                
    //saving info about chosen/not chosen products (by saving GET 'form' parameters) 
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
    
    public function position_add ($id_position, $id_invoice)
    {
        $requestForm=$this->session->get('sessionFormPerson'); 
        
    // extracting array of PersonLikeProduct objects for the chosen product and person        
        $productLikePersonManager = $this->getDoctrine()->getManager();
        $productLikePersonArray = $productLikePersonManager->getRepository(PersonLikeProduct::class)
                                                        ->findBy ([
                                                            'person' => $id_person, 
                                                            'product' => $id_product 
                                                        ]);
    //checking if this lover is already exist for the person  and adding new like for the person if not exist
        if (empty($productLikePersonArray) ) { 
            
            $productManager = $this->getDoctrine()->getManager();
            $product = $productManager->getRepository(Product::class)->find($id_product);

            $personManager = $this->getDoctrine()->getManager();
            $person = $personManager->getRepository(Person::class)->find($id_person);
            
            $personLikeProduct = new PersonLikeProduct();
            $personLikeProduct->setPerson($person);
            $personLikeProduct->setProduct($product);
            
            $productLikePersonManager ->persist($personLikeProduct);
            $productLikePersonManager ->flush();

        //reconstraction of chosen/not chosen products (by getting saved GET form parameters) 
            $request= Request::createFromGlobals();
            $requestForm=$this->session->get('sessionFormPerson'); 
        }
        
        return $this->redirectToRoute( 'product_like_person_edit', ['id_product'=> $id_product,
                                                                    'form'=>$requestForm]);
    }

    public function position_delete ($id_position, $id_invoice)
    {
    // extracting array of PersonLikeProduct objects for the chosen product and person    
        $productLikePersonManager = $this->getDoctrine()->getManager();
        $productLikePersonArray = $this->getDoctrine()->getRepository(PersonLikeProduct::class)
                                                ->findBy ([
                                                    'person' => $id_person, 
                                                    'product' => $id_product 
                                                ]);
    //delete lover for the product    
        foreach ($productLikePersonArray as $prodpers) {
            $productLikePersonManager->remove($prodpers);
            $productLikePersonManager->flush();
        }   

    //reconstraction of chosen/not chosen products (by getting saved GET form parameters) 
        $requestForm=$this->session->get('sessionFormPerson'); 
        
        return $this->redirectToRoute( 'product_like_person_edit', ['id_product'=> $id_product,
                                                                    'form'=>$requestForm]);
    }

        
}
