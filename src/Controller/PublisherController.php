<?php

namespace App\Controller;

use App\Entity\Tasks;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PublisherController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {}

    #[Route('/publisher', name: 'publish_tasks')]
    public function generateTasks(): Response
    {
        $types = [1, 2, 3];
        $priorities = [0, 1, 2];

        for ($i = 0; $i < 100; $i++) {
            $task = new Tasks();
            $task->setType((string)$types[array_rand($types)]);
            $task->setPriority($priorities[array_rand($priorities)]);
            $task->setStatus(0);
            $task->setCreatedAt(new \DateTimeImmutable());
            
            $this->entityManager->persist($task);
        }
        
        $this->entityManager->flush();

        return new Response('100 random tasks have been generated successfully!');
    }
}