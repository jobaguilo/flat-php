# Task Processing System

A Symfony-based task processing system that handles different types of tasks with priorities and statuses.

## Setup

1. Clone the repository
```
git clone https://github.com/jobaguilo/flat-php.git
cd flat-php
```

2. Start docker containers
```bash
docker-compose up -d
```

3. Install dependencies
```bash
docker-compose exec php composer install
```

4. Run database migrations
```bash
docker-compose exec php php bin/console doctrine:migrations:migrate
```

## Usage
### Task Management API Create a Task
Create a Task
```bash
curl -X POST http://localhost:8080/api/tasks \
  -H "Content-Type: application/json" \
  -d '{
    "type": "1",
    "priority": 2
  }'
```
Types must be:
- 1: Title fetch (gets webpage title)
- 2: Joke fetch (gets random joke)
- 3: Timestamp generation (with random error and delay)

Priority must be:
- 0: Low priority
- 1: Medium priority
- 2: High priority

 List Tasks
```bash
# All non-deleted tasks
curl http://localhost:8080/api/tasks

# Filter by status (pending, active, executed, deleted)
curl http://localhost:8080/api/tasks?status=pending

# Order by priority (DESC)
curl http://localhost:8080/api/tasks?order=priority

# Combined filters
curl http://localhost:8080/api/tasks?status=pending&order=priority
```
 Get Single Task (id)
```bash
curl http://localhost:8080/api/tasks/1
```
 Update Task
```bash
curl -X PATCH http://localhost:8080/api/tasks/1 \
  -H "Content-Type: application/json" \
  -d '{
    "priority": 1,
    "status": 2
  }'
```
 Delete Task (Logical)
```bash
curl -X DELETE http://localhost:8080/api/tasks/1
```

### Task Generation
Visit http://localhost:8080/publisher to generate 100 random tasks with:

- Random type (1, 2, or 3)
- Random priority (0, 1, or 2)
- Initial status set to 0 (pending)
### Task Processing
Start the task processor/s by visiting http://localhost:8080/subscriber . 
Also can be executed from terminal with the command:
```bash
docker-compose exec php php bin/console app:process-tasks
```
This will:

- Run continuously checking for new tasks
- Process pending tasks in priority order
- Execute different actions based on task type:
  - Type 1: Fetch webpage title from jokeapi documentation
  - Type 2: Fetch random joke from jokeapi
  - Type 3: Generate timestamp with 5-second delay and 25% chance of error
- Handle retries (up to 3 attempts with 30-second delay between retries)
- Update task status and results automatically
## Task Properties
### Statuses
- 0: Pending (newly created tasks)
- 1: Executing (currently being processed)
- 2: Executed (processing completed)
- 3: Deleted (logically deleted)
### Types
- 1: Title fetcher (gets webpage title)
- 2: Joke fetcher (gets random joke)
- 3: Timestamp generator (with random delay)
### Priorities
- 0: Low priority
- 1: Medium priority
- 2: High priority
## Error Handling
- Failed tasks are automatically retried up to 3 times
- 30-second delay between retry attempts
- Error messages are stored in task results
- Entity Manager is automatically reset on errors
## Memory Management
- Processed tasks list is limited to last 10 entries
- Entity Manager state is managed to prevent memory leaks
- Continuous execution with 5-second intervals between checks
## API Response Format
Tasks are returned in JSON format:

```json
{
    "id": 1,
    "type": "2",
    "priority": 1,
    "status": 2,
    "result": "The joke text goes here",
    "createdAt": "2024-01-01 12:00:00",
    "executedAt": "2024-01-01 12:01:00"
}
```