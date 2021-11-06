<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'login')]
    public function index(): Response
    {
        return $this->render('login/index.html.twig', [
            'controller_name' => 'LoginController',
        ]);
    }
    #[Route('/loginvalidate', name: 'validate')]
    public function loginvalidate(Request $request): Response
    {
        //you can set this in the env file as variables
        $dbuser = 'phpdemo';
        $dbpass = '';
        $dbhost = '127.0.0.1';
        $dbname = 'symfony';
        //get evironment variables
        $databaseURL = $_ENV['DATABASE_URL'];

        //get the user input from the index 
        $request = Request::createFromGlobals();
        $name = stripslashes($request->request->get('username'));
        $password = stripslashes($request->request->get('password'));

        //connect to db
        $conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
        if (!$conn) {
            echo "Connection failed " . mysqli_connect_error();
            //or you can redirect to an error page
        }
        //create a flat query wihtout using the entity framework
        $sql = "SELECT * FROM user WHERE email='$name' and password=sha2('$password',256)";
        $result = mysqli_query($conn, $sql) or die("Error connecting to db");

        $rows  = mysqli_num_rows($result);
        $result = mysqli_fetch_array($result); //use the mysqli_fetch_array to convert the $result to array
        if ($rows == 1) {
            
            //TWIG-Controller style
            $session = $this->get('session');
            $session->set('usernametwig', $name);
            //$session->set('role',$role)

            //php style
            $role = $result['roles'];   //extract the roles based from the database query

            if (!isset($_SESSION)) {    //check if session is not yet started. If not, start the session
                session_start();
            }
            $_SESSION['username'] = $name;  //save the data to the session you might be needing them from other pages
            $_SESSION['role'] = $role;      //include the role if you want to know the role of the person in other pages
            if ($role == 'administrator') { //heck for the user roles and redirect them based on their pages
                return $this->redirectToRoute('product');
            } elseif ($role == 'regular') {
                return $this->redirectToRoute('student');
            }
        }
    }


    #[Route('/logout', name: 'logout')]
    public function logout(Request $request) : Response
    {
        if (!isset($_SESSION)) {    //check if session is not yet started. If not, start the session
            session_start();
            session_destroy();
        }
        return $this->redirectToRoute('login');
    }
}
