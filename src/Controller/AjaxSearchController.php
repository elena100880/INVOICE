<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\AbstractType;
use App\Form\Type\InvoiceType;
use App\Form\Type\PositionType;

use App\Entity\Supplier;
use App\Entity\Recipient;
use App\Entity\Position;
use App\Entity\InvoicePosition;
use App\Entity\Invoice;

use App\Repository\SupplierRepository;
use App\Repository\RecipientRepository;
use App\Repository\InvoiceRepository;
use App\Repository\PositionRepository;
use App\Repository\InvoicePositionRepository;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityManagerInterface;

use App\Controller\JsonResponse;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class AjaxSearchController extends AbstractController
{
    public function ajax_search_invoice_supplier(Request $request) : Response {
        $key = $request->query->get('q'); 

        $entityManager = $this->getDoctrine()->getManager();
        $queryBuilder = $entityManager->createQueryBuilder()
                                                -> select('s')
                                                -> from ('App\Entity\Supplier', 's')
                                                -> setParameter('key1', '%'.addcslashes($key, '%_').'%') 
                                                -> setParameter('key2', addcslashes($key, '%_').'%') 
                                                -> orWhere ('s.name LIKE :key1')
                                                -> orWhere ('s.nip LIKE :key2');
        $suppliers = $queryBuilder->getQuery()->getResult();     // 500 risk?????

        $returnArray=array();
        foreach($suppliers as $supplier) {                  // 500 risk?????
            $name = $supplier->getName();
            $nip = $supplier->getNip();
            $id = $supplier->getId();
            $elem = [ 'id' => $id, 'text' => $name.', nip: '.$nip];
            array_push ($returnArray, $elem );
        };
        return $this->json($returnArray);
    }

    public function ajax_search_invoice_recipient(Request $request) : Response {
        $key = $request->query->get('q'); 

        $entityManager = $this->getDoctrine()->getManager();
        $queryBuilder = $entityManager->createQueryBuilder()
                                                -> select('r')
                                                -> from ('App\Entity\Recipient', 'r')
                                                -> setParameter('key', '%'.addcslashes($key, '%_').'%') 
                                                -> orWhere ('r.name LIKE :key')
                                                -> orWhere ('r.family LIKE :key')
                                                -> orWhere ('r.address LIKE :key');
        $recipients = $queryBuilder->getQuery()->getResult();     // 500 risk?????

        $returnArray=array();
        foreach($recipients as $recipient) {                  // 500 risk?????
            $name = $recipient->getName();
            $family = $recipient->getFamily();
            $address = $recipient->getAddress();
            
            $id = $recipient->getId();
            $elem = [ 'id' => $id, 'text' => $name.' '.$family.', '.$address];
            array_push ($returnArray, $elem );
        };
        return $this->json($returnArray);
    }


}
    