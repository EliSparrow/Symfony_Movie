<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/user")
 * @IsGranted("IS_AUTHENTICATED_FULLY")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/", name="user_index", methods={"GET"})
     */
    public function index(UserRepository $userRepository): Response
    {
        if($this->getUser()->getAdmin()){
            return $this->render('user/index.html.twig', [
                'users' => $userRepository->findAll(),
            ]);
        }
        else{
            return $this->redirectToRoute('main');
        }
        
    }

    /**
     * @Route("/new", name="user_new", methods={"GET","POST"})
     */
    public function new(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        if($this->getUser()->getAdmin()){
            $user = new User();
            $form = $this->createForm(RegistrationFormType::class, $user);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                // encode the plain password
                $user->setPassword(
                    $passwordEncoder->encodePassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($user);
                $entityManager->flush();

                return $this->redirectToRoute('user_index');
            }

            return $this->render('user/new.html.twig', [
                'user' => $user,
                'form' => $form->createView(),
                'title' => 'Create new User'
            ]);
        }
        else{
            return $this->redirectToRoute('main');
        }
    }

    /**
     * @Route("/{id}", name="user_show", methods={"GET"})
     */
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="user_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, User $user, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        if ($this->getUser()->getAdmin() == true || $user->getId() == $this->getUser()->getId()) {
            $form = $this->createForm(UserType::class, $user);
            $form->handleRequest($request);

            // echo($this->getUser()->getId());
            // echo($user->getId());

            if ($form->isSubmitted() && $form->isValid()) {
                // encode the plain password
                $user->updateModifiedDatetime();
                if($form->get('plainPassword')->getData() != ''){
                    $user->setPassword(
                    $passwordEncoder->encodePassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    ));
                }

                $this->getDoctrine()->getManager()->flush();

                return $this->redirectToRoute('user_index');
            }

            return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'title' => 'Edit User'
            ]);
        }
        else{
            return $this->redirectToRoute('main');
        }
    }

    /**
     * @Route("/{id}", name="user_delete", methods={"DELETE"})
     */
    public function delete(Request $request, User $user): Response
    {
        if($this->getUser()->getAdmin() == true || $user->getId() == $this->getUser()->getId()){
            if($user->getId() == $this->getUser()->getId()){
                if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
                    $session = $this->get('session');
                    $session = new Session();
                    $session->invalidate();
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->remove($user);
                    $entityManager->flush();
                }
                return $this->redirectToRoute('app_register');
            }
            elseif($this->getUser()->getAdmin() == true){
                if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->remove($user);
                    $entityManager->flush();
                }
                return $this->redirectToRoute('user_index');
            }
        }
    }
}
