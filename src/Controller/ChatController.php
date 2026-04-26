<?php

namespace App\Controller;

use App\Entity\ChatMessage;
use App\Entity\Observation;
use App\Repository\ChatMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/observation/{id}/chat')]
class ChatController extends AbstractController
{
    #[Route('/messages', name: 'app_chat_messages', methods: ['GET'])]
    public function getMessages(Observation $observation): JsonResponse
    {
        $messages = $observation->getChatMessages();
        
        $data = [];
        foreach ($messages as $msg) {
            $data[] = [
                'id' => $msg->getId(),
                'content' => $msg->getContent(),
                'createdAt' => $msg->getCreatedAt()->format('Y-m-d H:i:s'),
                'author' => $msg->getAuthor()->getNom() . ' ' . $msg->getAuthor()->getPrenom()
            ];
        }

        return $this->json($data);
    }

    #[Route('/send', name: 'app_chat_send', methods: ['POST'])]
    public function sendMessage(
        Request $request,
        Observation $observation,
        EntityManagerInterface $em,
        HubInterface $hub
    ): JsonResponse {
        $content = $request->request->get('content');

        if (empty($content)) {
            return $this->json(['error' => 'Message cannot be empty'], 400);
        }

        $user = $this->getUser();
        if (!$user) {
            // For testing purposes, if no user is logged in, you might want to fetch a default user
            // or return an error. Returning an error is safer:
            return $this->json(['error' => 'You must be logged in to send a message'], 403);
        }

        $message = new ChatMessage();
        $message->setContent($content);
        $message->setObservation($observation);
        $message->setAuthor($user); // user must be an instance of Utilisateur

        $em->persist($message);
        $em->flush();

        // Broadcast the new message via Mercure
        $topic = 'chat/observation/' . $observation->getId_observation();
        $payload = [
            'id' => $message->getId(),
            'content' => $message->getContent(),
            'createdAt' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
            'author' => $message->getAuthor()->getNom() . ' ' . $message->getAuthor()->getPrenom()
        ];

        $update = new Update($topic, json_encode($payload));
        $hub->publish($update);

        return $this->json(['status' => 'success', 'message' => $payload]);
    }
}
