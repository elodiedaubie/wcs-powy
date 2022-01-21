<?php

namespace App\Controller;

use App\Entity\Message;
use App\Form\MessageType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChatController extends AbstractController
{
    /**
     * @Route("/chat/{id}", name="chat", requirements={"id"="\d+"})
     * @IsGranted("ROLE_USER")
     */
    public function index(int $id, UserRepository $userRepository, Request $request): Response
    {
        //add both situations if user is a student or a mentor (because you can only fetch the mentoring this way)
        $user = $userRepository->find($id);
        if ($user !== null) {
            $roles = $user->getRoles();
            if ($user->getMentor() !== null && in_array('ROLE_MENTOR', $roles)) {
                $mentoring = $user->getMentor()->getMentoring();
            } elseif ($user->getStudent() !== null && in_array('ROLE_STUDENT', $roles)) {
                $mentoring = $user->getStudent()->getMentoring();
            }
        } else {
            //Had to initialized $mentoring as an empty array if $user is null to make phpstan happy
            $mentoring = [];
        }

        //load the form
        $message = new Message();
        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);
        //handle the request, etc.

        //check if this user has a mentoring, if not redirect to profile
        if (empty($mentoring)) {
            $this->addFlash('message', "Vous n'avez pas encore de mentorat.");
            return $this->redirectToRoute("profile_index");
        }

        return $this->render('chat/index.html.twig', [
            'mentoring' => $mentoring,
            'messageForm' => $form->createView(),
        ]);
    }
}
