<?php

namespace App\Controller;
use App\Entity\Student;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class StudentController extends AbstractController
{
    #[Route('/student', name: 'student')]
    public function index(): Response
    {
        $student=$this->getDoctrine()
        ->getRepository(Student::class)
        ->findAll();

        return $this->render('student/index.html.twig', ['student' => $student]);
    }
    /**
     * @Route("/student/new", name="new_student")
     */
    public function newStudent(Request $request):Response{
        $student=new Student();
        $form=$this->createFormBuilder($student)
        ->add('lastname',TextType::class, [
            'attr' => [
                'placeholder' => 'last name',
                'class' => 'form-control input-lg'
            ]])
        ->add('firstname',TextType::class)
        ->add('age',TextType::class)
        ->add('course',TextType::class)
        ->add('save', SubmitType::class, array('label' => 'Submit')) 
        ->getForm();

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            //fethc the entity manager
            $entitManager=$this->getDoctrine()->getManager();
            //get the form values and save in a variable
            $ln=$form['lastname']->getData();
            $fn=$form['firstname']->getData();
            $a=$form['age']->getData();
            $c=$form['course']->getData();
            //utilize the setter of your entity/object
            $student->setLastname($ln);
            $student->setFirstname($fn);
            $student->setAge($a);
            $student->setCourse($c);
            //inform doctrine to save the data
            $entitManager->persist($student);
            $entitManager->flush();
            //if successful in saving, redirect to student page
            return $this->redirectToRoute("student");
        }
        return $this->render('student/new.html.twig', array('form'=>$form->createView(),));
    }
}
