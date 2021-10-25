<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Product;
use Exception;
use PHPUnit\Util\Filesystem as UtilFilesystem;
//use Symfony\Component\BrowserKit\Request;
//use Doctrine\DBAL\Types\TextType;
//use Doctrine\DBAL\Types\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
//for File uploading
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
//Slugger for making sure your filename is unique
use Symfony\Component\String\Slugger\SluggerInterface;
//headers for downloading file
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Filesystem as FilesystemFilesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\KernelInterface;
class ProductController extends AbstractController
{
     /**
     * @Route("/", name="app_index")
     */
    public function index()
    {
        return new JsonResponse([
            'name' => "Carlos",
            'age' => 22,
            'location' => [
                'city' => 'Bucaramanga',
                'state' => 'Santander',
                'country' => 'Colombia'
            ]
        ]);
    }
    #[Route('/product', name: 'product')]
    public function index2(): Response
    {
        $product = $this->getDoctrine()
            ->getRepository(Product::class)
            ->findAll();

        //return new Response('Check out this greate product: '.$product->getName());
        return $this->render('product/index.html.twig', ['product' => $product]);
    }
    /**
     * @Route("/product/new", name="new_product")
     */
    public function newProduct(Request $request, SluggerInterface $slugger): Response
    {
        $product = new Product();
        $form = $this->createFormBuilder($product)
            ->add('name', TextType::class)
            ->add('description', TextType::class,array('attr' => array('class'=>'form-control')))
            ->add('price', TextType::class,array('attr' => array('class'=>'form-control')))
            ->add('brochure', FileType::class, [
                'label' => 'Brochure (PDF file)',

                // unmapped means that this field is not associated to any entity property
                'mapped' => false,

                // make it optional so you don't have to re-upload the PDF file
                // every time you edit the Product details
                'required' => false,

                // unmapped fields can't define their validation using annotations
                // in the associated entity, so you can use the PHP constraint classes
                'constraints' => [
                    new File([
                        'maxSize' => '1024k', //specify the file size limit, remove if you o not want adding limit
                        'mimeTypes' => [ //specify the acceptable files to be uploaded
                            'application/pdf',
                            'application/x-pdf',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid PDF document',
                    ])
                ],
            ])
            ->add('save', SubmitType::class, array('label' => 'Submit',
            'attr'=>array('class'=>'btn btn-primary mt-3')))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //you can fetch the EntityManage via $this->getDoctrine()
            $entityManager = $this->getDoctrine()->getManager();
            //get the values into variables
            $name = $form['name']->getData();
            $description = $form['description']->getData();
            $price = $form['price']->getData();
            //uploading
            $brochureFile = $form->get('brochure')->getData();
            //check if brochurefield not required
            $newFilename="defaultfilename.pdf";
            if($brochureFile){
                $originalFilename = pathinfo($brochureFile->getClientOriginalName(),PATHINFO_FILENAME);
                //include filename as part of URL
                $safeFilename=$slugger->slug($originalFilename);
                $newFilename=$safeFilename.'-'.uniqid().'.'.$brochureFile->guessExtension();
                //move the file to the directory
                try{
                    $brochureFile->move(
                        //$this->getParameter('brochures_directory'),
                        '../public/uploads/brochures',//specify here the folder where you want the file to be uploaded
                        $newFilename
                    );
                }catch(FileException $e){
                    throw new Exception($e);
                }
            }
            
            //setting the values of the object
            $product->setName($name);
            $product->setPrice($price);
            $product->setDescription($description);
            $product->setBrochureFilename($newFilename);

            //tell doctrine you want to save the product (no queries yet)
            $entityManager->persist($product);
            $entityManager->flush();

            //return to the main page to display the new inserted record
            return $this->redirectToRoute("product");
        }

        return $this->render('product/new.html.twig', array('form' => $form->createView(),));
    }
    /**
     * @Route("/product/edit/{id}", name="edit_product")
     */
    public function updtateProduct(int $id,Request $request): Response
    {
        $entityManager = $this->getDoctrine()->getManager();

        $product = $entityManager->getRepository(Product::class)->find($id);
        $form=$this->createFormBuilder($product)
        ->add('name', TextType::class,['data'=>$product->getName()])
        ->add('description', TextType::class,['data'=>$product->getDescription()])
        ->add('price', TextType::class,['data'=>$product->getPrice()])
        ->add('save', SubmitType::class, array('label' => 'Save changes',
        'attr'=>array('class'=>'btn btn-primary mt-3')))
        ->getForm();
        $form->handleRequest($request);

        
        if (!$product) {
            throw $this->createNotFoundException(
                'No product found for id ' . $id
            );
        }
        if ($form->isSubmitted() && $form->isValid()) {
             //you can fetch the EntityManage via $this->getDoctrine()
             $entityManager = $this->getDoctrine()->getManager();
             //get the values into variables
             $name = $form['name']->getData();
             $description = $form['description']->getData();
             $price = $form['price']->getData();
 
             $product->setName($name);
             $product->setPrice($price);
             $product->setDescription($description);
 
             //tell doctrine you want to save the product (no queries yet)
 
             //$entityManager->persist($product);
             $entityManager->flush();
 
             //return new Response('Saved new product with id '.$product->getId());
             return $this->redirectToRoute("product");
        }
        //option 1: read the form content as submitted via POST
        //https://symfony.com/doc/current/introduction/http_fundamentals.html#step-1-the-client-sends-a-request
        //$request=Request::createFromGlobals();
        //$name = $request->request->get('name');
        //$description=$request->request->get('description');

        //option 2: using the symfony way
        
        
        /*$name=$form['name']->getData();
        $description=$form['description']->getData();


        $product->setName(strval($name));
        $product->setDescription(strval($description));
        $entityManager->flush();*/
        return $this->render('product/edit.html.twig',array('form' => $form->createView(),));
        /*return $this->redirectToRoute('product', [
            'id' => $product->getId()
        ]);*/
    }

    /**
     * @Route("/product/deleted/{id}", name="delete_product")
     */
    public function deleteProduct(int $id): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $product = $entityManager->getRepository(Product::class)->find($id);
        if (!$product) {
            throw $this->createNotFoundException(
                'No product found for id ' . $id
            );
        }
        $entityManager->remove($product);
        $entityManager->flush();

        return $this->redirectToRoute('product');
    }


    /**
     * @Route("/product/create", name="create_product")
     */
    public function createProduct(): Response
    {
        //you can fetch the EntityManage via $this->getDoctrine()
        $entityManager = $this->getDoctrine()->getManager();

        $product = new Product();
        $product->setName('Mouse');
        $product->setPrice(1999.50);
        $product->setDescription("Ergonomic and stylish mouseeeee!");

        //tell doctrine you want to save the product (no queries yet)
        $entityManager->persist($product);
        $entityManager->flush();

        return new Response('Saved new product with id ' . $product->getId());
    }
    /**
     * @Route("/product/{id}", name="product_show")
     */

    public function show(int $id): Response
    {
        $product = $this->getDoctrine()
            ->getRepository(Product::class)
            ->find($id);

        if (!$product) {
            // throw $this->createNotFoundException(
            //     'No product found for id '.$id
            // );
            $this->addFlash(
                'notice',
                'Record not found!'
            );
        }

        //return new Response('Check out this greate product: '.$product->getName());
        return $this->render('product/detail2.html.twig', ['product' => $product]);
    }

    /**
     * @Route("/product/download/{file}", name="product_brochure_download")
     */
    public function downloadAction($file):BinaryFileResponse{
        //try{
            //https://mobikul.com/controller-upload-download-files-symfony/
        //}
        $tmpFilename=(new Filesystem())->tempnam(sys_get_temp_dir(),'sb_');
        $tmpFile=fopen($tmpFilename,'wb+');
        $data = [
            ['name', 'firstname', 'age'],
            ['COil', 'Doo', random_int(30, 42)],
            ['Fab', 'Pot', random_int(30, 42)],
            ['Glas', 'Dun', random_int(30, 42)],
        ];
        foreach ($data as $line) {
            fputcsv($tmpFile, $line, ';');
        }
        $response = $this->file($tmpFilename, 'dynamic-csv-file.csv');
        $response->headers->set('Content-type', 'application/csv');
//https://symfony.com/doc/current/components/filesystem.html
//https://symfony.com/doc/current/controller/upload_file.html
//https://www.strangebuzz.com/en/snippets/downloading-of-a-dynamically-generated-file-from-a-symfony-controller

        fclose($tmpFile);

        return $response; 
    }
}
