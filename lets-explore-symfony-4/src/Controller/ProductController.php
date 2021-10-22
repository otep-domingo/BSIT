<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Product;
//use Symfony\Component\BrowserKit\Request;
//use Doctrine\DBAL\Types\TextType;
//use Doctrine\DBAL\Types\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class ProductController extends AbstractController
{
    #[Route('/product', name: 'product')]
    public function index(): Response
    {
        $product=$this->getDoctrine()
        ->getRepository(Product::class)
        ->findAll();

        //return new Response('Check out this greate product: '.$product->getName());
        return $this->render('product/index.html.twig', ['product' => $product]);
    }
    /**
     * @Route("/product/new", name="new_product")
     */
    public function newProduct(Request $request):Response{
        $product=new Product();
        $form=$this->createFormBuilder($product)
        ->add('name',TextType::class)
        ->add('description', TextType::class)
        ->add('price',TextType::class)
        ->add('save', SubmitType::class, array('label' => 'Submit')) 
        ->getForm();

       $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
           //you can fetch the EntityManage via $this->getDoctrine()
        $entityManager=$this->getDoctrine()->getManager();
        //get the values into variables
        $name=$form['name']->getData();
        $description=$form['description']->getData();
        $price=$form['price']->getData();
       
        $product->setName($name);
        $product->setPrice($price);
        $product->setDescription($description);

        //tell doctrine you want to save the product (no queries yet)

        $entityManager->persist($product);
        $entityManager->flush();

        //return new Response('Saved new product with id '.$product->getId());
            return $this->redirectToRoute("product");
        }

        return $this->render('product/new.html.twig', array('form'=>$form->createView(),));
    }

    /**
     * @Route("/product/create", name="create_product")
     */
    public function createProduct(): Response{
        //you can fetch the EntityManage via $this->getDoctrine()
        $entityManager=$this->getDoctrine()->getManager();

        $product=new Product();
        $product->setName('Mouse');
        $product->setPrice(1999.50);
        $product->setDescription("Ergonomic and stylish mouseeeee!");

        //tell doctrine you want to save the product (no queries yet)
        $entityManager->persist($product);
        $entityManager->flush();

        return new Response('Saved new product with id '.$product->getId());
    }
     /**
     * @Route("/product/{id}", name="product_show")
     */

    public function show(int $id): Response{
        $product=$this->getDoctrine()
        ->getRepository(Product::class)
        ->find($id);

        if(!$product){
           // throw $this->createNotFoundException(
           //     'No product found for id '.$id
           // );
           $this->addFlash(
                'success',
                'Record not found!'
           );
        }

        //return new Response('Check out this greate product: '.$product->getName());
        return $this->render('product/detail.html.twig', ['product' => $product]);
    }
}
