<?php

namespace App\Controller;

use App\Entity\Tasks;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SubscriberController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/subscriber', name: 'subscriber_tasks')]
    public function listTasks(): Response
    {
        set_time_limit(0); // Prevent PHP timeout
        $processedTasks = [];

        while (true) {
            $response = $this->httpClient->request('GET', 'http://nginx:80/api/tasks?status=pending&order=priority');
            $tasks = $response->toArray();
            
            foreach ($tasks as $task) {
                if (!in_array($task['id'], $processedTasks)) {
                    try {
                        $taskEntity = $this->entityManager->getRepository(Tasks::class)->find($task['id']);
                        if (!$taskEntity) {
                            throw new \RuntimeException("Task {$task['id']} not found");
                        }
                        
                        // Set as executing
                        $taskEntity->setStatus(1);
                        $taskEntity->setExecutedAt(new \DateTimeImmutable());
                        $this->entityManager->flush();
                        
                        $result = match ($task['type']) {
                            '1' => $this->executeWithRetry(fn() => $this->processTitleTask()),
                            '2' => $this->executeWithRetry(fn() => $this->processJokeTask()),
                            '3' => $this->executeWithRetry(fn() => $this->processDateTask()),
                            default => 'Unknown task type',
                        };
                        
                        // Update with result
                        $taskEntity->setStatus(2);
                        $taskEntity->setResult($result);
                        $taskEntity->setExecutedAt(new \DateTimeImmutable());
                        $this->entityManager->flush();
                        $processedTasks[] = $task['id'];
                    } catch (\Exception $e) {
                        // Handle errors
                        if (!$this->entityManager->isOpen()) {
                            $this->entityManager = $this->entityManager->create(
                                $this->entityManager->getConnection(),
                                $this->entityManager->getConfiguration()
                            );
                        }
                        
                        $taskEntity = $this->entityManager->getRepository(Tasks::class)->find($task['id']);
                        if ($taskEntity) {
                            $taskEntity->setStatus(2);
                            $taskEntity->setResult('Error: ' . $e->getMessage());
                            $taskEntity->setExecutedAt(new \DateTimeImmutable());
                            $this->entityManager->flush();
                            $processedTasks[] = $task['id'];
                        }
                    }
                }
            }

            // Prevent memory issues by limiting the processed tasks array
            if (count($processedTasks) > 10) {
                $processedTasks = array_slice($processedTasks, -10);
            }
            
            // Wait 5 seconds before next check
            sleep(5); 
        }
    }

    private function executeWithRetry(callable $process): string
    {
        $tries = 0;
        while ($tries < 3) {
            try {
                return $process();
            } catch (\Exception $e) {
                $tries++;
                if ($tries === 3) throw $e;
                sleep(30);
            }
        }
    }

    private function processTitleTask(): string
    {
        $response = $this->httpClient->request('GET', 'https://sv443.net/jokeapi/v2/');
        $content = $response->getContent();
        
        if (preg_match('/<title>(.*?)<\/title>/i', $content, $matches)) {
            return $matches[1] ?? 'No title found';
        }
        
        return 'No title found';
    }

    private function processJokeTask(): string
    {
        $response = $this->httpClient->request('GET', 'https://v2.jokeapi.dev/joke/Any?type=single');
        $data = $response->toArray();
        
        return $data['joke'] ?? 'No joke found';
    }

    private function processDateTask(): string
    {
        sleep(5);
        
        if (rand(1, 100) <= 25) {
            throw new \RuntimeException('Error returning date');
        }

        return (new \DateTime())->format('Y-m-d H:i:s');
    }
}