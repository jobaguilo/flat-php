<?php

namespace App\Controller\Api;

use App\Entity\Tasks;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/tasks', name: 'api_tasks_')]
class TasksController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $tasks = $this->entityManager->getRepository(Tasks::class)->findAll();
        $data = array_map(fn(Tasks $task) => [
            'id' => $task->getId(),
            'type' => $task->getType(),
            'priority' => $task->getPriority(),
            'status' => $task->getStatus(),
            'result' => $task->getResult(),
            'createdAt' => $task->getCreatedAt()->format('Y-m-d H:i:s'),
            'executedAt' => $task->getExecutedAt() ? $task->getExecutedAt()->format('Y-m-d H:i:s') : null,
        ], $tasks);

        return $this->json($data);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function get(Tasks $task): JsonResponse
    {
        return $this->json([
            'id' => $task->getId(),
            'type' => $task->getType(),
            'priority' => $task->getPriority(),
            'status' => $task->getStatus(),
            'result' => $task->getResult(),
            'createdAt' => $task->getCreatedAt()->format('Y-m-d H:i:s'),
            'executedAt' => $task->getExecutedAt() ? $task->getExecutedAt()->format('Y-m-d H:i:s') : null,
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $task = new Tasks();
        $task->setType($data['type']);
        $task->setPriority($data['priority']);
        $task->setStatus(0);
        $task->setCreatedAt(new \DateTimeImmutable());
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $this->json([
            'id' => $task->getId(),
            'type' => $task->getType(),
            'priority' => $task->getPriority(),
            'status' => $task->getStatus(),
            'createdAt' => $task->getCreatedAt()->format('Y-m-d H:i:s'),
        ], 201);
    }
}