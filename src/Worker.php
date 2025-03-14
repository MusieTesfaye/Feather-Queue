<?php

namespace FeatherQueue;

use FeatherQueue\Exceptions\QueueException;

/**
 * Worker class for FeatherQueue
 * 
 * Processes jobs from the queue
 */
class Worker
{
    /**
     * The job queue to process
     * 
     * @var JobQueue
     */
    private JobQueue $queue;
    
    /**
     * Registered job handlers
     * 
     * @var array
     */
    private array $handlers = [];
    
    /**
     * Default handler for unregistered job types
     * 
     * @var callable|null
     */
    private $defaultHandler = null;
    
    /**
     * Event handlers
     * 
     * @var array
     */
    private array $events = [];
    
    /**
     * Worker statistics
     * 
     * @var array
     */
    private array $stats = [
        'jobs_processed' => 0,
        'jobs_successful' => 0,
        'jobs_failed' => 0,
        'start_time' => 0,
        'end_time' => 0
    ];
    
    /**
     * Sleep time between polling (microseconds)
     * 
     * @var int
     */
    private int $sleepTime = 1000000; // 1 second
    
    /**
     * Whether adaptive polling is enabled
     * 
     * @var bool
     */
    private bool $adaptivePolling = false;
    
    /**
     * Maximum sleep time for adaptive polling
     * 
     * @var int
     */
    private int $maxSleepTime = 10000000; // 10 seconds
    
    /**
     * Constructor
     * 
     * @param JobQueue $queue
     */
    public function __construct(JobQueue $queue)
    {
        $this->queue = $queue;
        
        // Initialize event handlers
        $this->events = [
            'beforeProcessing' => [],
            'onSuccess' => [],
            'onFailure' => [],
            'onShutdown' => []
        ];
    }
    
    /**
     * Register a handler for a specific job type
     * 
     * @param string $jobName
     * @param callable $handler
     * @return self
     */
    public function registerHandler(string $jobName, callable $handler): self
    {
        $this->handlers[$jobName] = $handler;
        return $this;
    }
    
    /**
     * Register a default handler for unregistered job types
     * 
     * @param callable $handler
     * @return self
     */
    public function registerDefaultHandler(callable $handler): self
    {
        $this->defaultHandler = $handler;
        return $this;
    }
    
    /**
     * Process a job
     * 
     * @param Job $job
     * @return mixed
     * @throws QueueException
     */
    public function processJob(Job $job)
    {
        // Trigger beforeProcessing event
        $this->triggerEvent('beforeProcessing', $job);
        
        try {
            $handler = $this->getHandlerForJob($job);
            
            // Process the job
            $result = $handler($job->getPayload(), $job);
            
            // Trigger success event
            $this->triggerEvent('onSuccess', $job, $result);
            
            $this->stats['jobs_processed']++;
            $this->stats['jobs_successful']++;
            
            return $result;
        } catch (\Exception $e) {
            // Trigger failure event
            $this->triggerEvent('onFailure', $job, $e);
            
            $this->stats['jobs_processed']++;
            $this->stats['jobs_failed']++;
            
            throw $e;
        }
    }
    
    /**
     * Get the appropriate handler for a job
     * 
     * @param Job $job
     * @return callable
     * @throws QueueException
     */
    private function getHandlerForJob(Job $job): callable
    {
        $jobName = $job->getName();
        
        if (isset($this->handlers[$jobName])) {
            return $this->handlers[$jobName];
        }
        
        if ($this->defaultHandler !== null) {
            return $this->defaultHandler;
        }
        
        throw new QueueException(
            "No handler registered for job type '{$jobName}'",
            QueueException::HANDLER_NOT_FOUND
        );
    }
    
    /**
     * Set the sleep time between polling
     * 
     * @param int $microseconds
     * @return self
     */
    public function setSleepTime(int $microseconds): self
    {
        $this->sleepTime = $microseconds;
        return $this;
    }
    
    /**
     * Set adaptive polling settings
     * 
     * @param bool $enabled
     * @param int $maxSleepTime Maximum sleep time in microseconds
     * @return self
     */
    public function setAdaptivePolling(bool $enabled = true, int $maxSleepTime = 10000000): self
    {
        $this->adaptivePolling = $enabled;
        $this->maxSleepTime = $maxSleepTime;
        return $this;
    }
    
    /**
     * Register an event handler
     * 
     * @param string $event Event name
     * @param callable $callback
     * @return self
     */
    public function on(string $event, callable $callback): self
    {
        if (isset($this->events[$event])) {
            $this->events[$event][] = $callback;
        }
        
        return $this;
    }
    
    /**
     * Trigger an event
     * 
     * @param string $event
     * @param mixed ...$args
     * @return void
     */
    private function triggerEvent(string $event, ...$args): void
    {
        if (isset($this->events[$event])) {
            foreach ($this->events[$event] as $callback) {
                $callback(...$args);
            }
        }
    }
    
    /**
     * Run the worker
     * 
     * @param int $jobLimit Maximum number of jobs to process (0 = unlimited)
     * @param int $timeout Maximum runtime in seconds (0 = unlimited)
     * @return array Worker statistics
     */
    public function run(int $jobLimit = 0, int $timeout = 0): array
    {
        $this->stats['start_time'] = time();
        $endTime = $timeout > 0 ? time() + $timeout : 0;
        $jobsProcessed = 0;
        $currentSleepTime = $this->sleepTime;
        $consecutiveEmpty = 0;
        
        while (true) {
            // Check if we've reached the job limit
            if ($jobLimit > 0 && $jobsProcessed >= $jobLimit) {
                break;
            }
            
            // Check if we've reached the timeout
            if ($endTime > 0 && time() >= $endTime) {
                break;
            }
            
            try {
                $job = $this->queue->processNext(function($payload, $job) {
                    return $this->processJob($job);
                });
                
                if ($job) {
                    $jobsProcessed++;
                    $consecutiveEmpty = 0;
                    
                    // Reset sleep time when jobs are found
                    if ($this->adaptivePolling) {
                        $currentSleepTime = $this->sleepTime;
                    }
                } else {
                    $consecutiveEmpty++;
                    
                    // Increase sleep time when no jobs are found (adaptive polling)
                    if ($this->adaptivePolling && $consecutiveEmpty > 2) {
                        $currentSleepTime = min(
                            $currentSleepTime * 1.5,
                            $this->maxSleepTime
                        );
                    }
                    
                    usleep($currentSleepTime);
                }
            } catch (\Exception $e) {
                // Just continue to the next job
                continue;
            }
            
            // Handle signals if supported
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }
        }
        
        $this->stats['end_time'] = time();
        
        // Trigger shutdown event
        $this->triggerEvent('onShutdown', $this->stats);
        
        return $this->stats;
    }
}