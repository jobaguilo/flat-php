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
        $response = $this->httpClient->request('GET', 'http://localhost:8080/api/tasks');
        $tasks = $response->toArray();
        
        foreach ($tasks as $task) {
            if ($task['status'] === 0) {


                // Update task status as processing
                // If type 1,2 or 3, call the corresponding method
                // process the response and update the result on the database
                $result = match ($task['type']) {
                    '1' => $this->processType1(),
                    '2' => $this->processType2(),
                    '3' => $this->processType3(),
                    default => 'Unknown task type',
                };

                // Update task in database
                $taskEntity = $this->entityManager->getRepository(Tasks::class)->find($task['id']);
                if ($taskEntity) {
                    $taskEntity->setStatus(1);
                    $taskEntity->setResult($result);
                    $taskEntity->setExecutedAt(new \DateTimeImmutable());
                    $this->entityManager->flush();
                }
            }
        }

        return new Response('Tasks processed successfully');
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
        $response = $this->httpClient->request('GET', 'https://v2.jokeapi.dev/joke/Any');
        $data = $response->toArray();
        
        return json_encode($data);
    }

    private function processType3(): string
    {
        return (new \DateTime())->format('Y-m-d H:i:s');
    }
}