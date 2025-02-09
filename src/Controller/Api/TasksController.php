<?php

namespace App\Controller\Api;

use App\Entity\Tasks;
use App\Enum\TaskStatus;
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
    public function list(Request $request): JsonResponse
    {
        $statusParam = $request->query->get('status');
        $repository = $this->entityManager->getRepository(Tasks::class);

        if ($statusParam !== null) {
            $status = TaskStatus::fromString($statusParam);
            if ($status !== null) {
                $tasks = $repository->findBy(['status' => $status->value]);
            } else {
                return $this->json(['error' => 'Invalid status parameter. Valid values are: pending, active, executed, deleted'], 400);
            }
        } else {
            $tasks = $repository->findBy(['status' => [
                TaskStatus::PENDING->value,
                TaskStatus::ACTIVE->value,
                TaskStatus::EXECUTED->value
            ]]);
        }

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

    #[Route('/{id}', name: 'update', methods: ['PATCH'])]
    public function update(Tasks $task, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['priority'])) {
            $task->setPriority($data['priority']);
        }
        if (isset($data['status'])) {
            $task->setStatus($data['status']);
        }
        if (isset($data['result'])) {
            $task->setResult($data['result']);
        }
        if (isset($data['executedAt'])) {
            $task->setExecutedAt(new \DateTimeImmutable($data['executedAt']));
        }

        $this->entityManager->flush();

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

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Tasks $task): JsonResponse
    {
        $task->setStatus(3);
        $this->entityManager->flush();

        return $this->json(null, 204);
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