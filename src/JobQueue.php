<?php

namespace FeatherQueue;

use FeatherQueue\Storage\FileStorage;
use FeatherQueue\Contracts\StorageInterface;
use FeatherQueue\Exceptions\QueueException;

/**
 * JobQueue class for FeatherQueue
 * 
 * Core class for queue operations
 */
class JobQueue
{
    /**
     * Storage implementation
     * 
     * @var StorageInterface
     */
    private StorageInterface $storage;
    
    /**
     * Queue configuration options
     * 
     * @var array
     */
    private array $options = [];
    
    /**
     * Constructor
     * 
     * @param string|StorageInterface $storage Storage path or implementation
     * @param array $options Queue options
     */
    public function __construct($storage, array $options = [])
    {
        $this->options = array_merge([
            'default_max_attempts' => 3,
            'encryption_key' => null,
            'cache_size' => 50,
            'cache_enabled' => true
        ], $options);
        
        // Set up storage
        if (is_string($storage)) {
            $this->storage = new FileStorage($storage, [
                'cache_size' => $this->options['cache_size'],
                'cache_enabled' => $this->options['cache_enabled']
            ]);
        } elseif ($storage instanceof StorageInterface) {
            $this->storage = $storage;
        } else {
            throw new QueueException('Invalid storage type', QueueException::STORAGE_ERROR);
        }
    }
    
    /**
     * Get the storage implementation
     * 
     * @return StorageInterface
     */
    public function getStorage(): StorageInterface
    {
        return $this->storage;
    }
    
    /**
     * Add a job to the queue
     * 
     * @param string $name The job name/type
     * @param array $payload The job data
     * @param array $options Additional job options
     * @return Job
     */
    public function add(string $name, array $payload = [], array $options = []): Job
    {
        // Set default max attempts if not specified
        if (!isset($options['maxAttempts'])) {
            $options['maxAttempts'] = $this->options['default_max_attempts'];
        }
        
        // Create the job
        $job = new Job($name, $payload, $options);
        
        // Save the job
        if (!$this->storage->saveJob($job)) {
            throw new QueueException('Failed to save job', QueueException::STORAGE_ERROR);
        }
        
        return $job;
    }
    
    /**
     * Add a delayed job to the queue
     * 
     * @param string $name The job name/type
     * @param array $payload The job data
     * @param int $delay Delay in seconds
     * @param array $options Additional job options
     * @return Job
     */
    public function later(string $name, array $payload = [], int $delay = 0, array $options = []): Job
    {
        $options['executeAt'] = time() + $delay;
        return $this->add($name, $payload, $options);
    }
    
    /**
     * Get a job by ID
     * 
     * @param string $id
     * @return Job|null
     */
    public function getJob(string $id): ?Job
    {
        return $this->storage->getJob($id);
    }
    
    /**
     * Delete a job
     * 
     * @param Job $job
     * @return bool
     */
    public function delete(Job $job): bool
    {
        return $this->storage->deleteJob($job);
    }
    
    /**
     * Process the next available job
     * 
     * @param callable $processor Function to process the job
     * @return Job|null The processed job, or null if no job was available
     */
    public function processNext(callable $processor): ?Job
    {
        $job = $this->storage->getNextPendingJob();
        
        if ($job === null) {
            return null;
        }
        
        // Mark job as processing
        $job->incrementAttempts();
        $this->storage->updateJobStatus($job, Job::STATUS_PROCESSING);
        
        try {
            // Process the job
            $result = $processor($job->getPayload(), $job);
            
            // Mark as completed
            $job->setMetadata('result', $result);
            $this->storage->updateJobStatus($job, Job::STATUS_COMPLETED);
            
            return $job;
        } catch (\Exception $e) {
            // Handle failure
            $job->setMetadata('error', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // Check if max attempts reached
            if ($job->hasReachedMaxAttempts()) {
                $this->storage->updateJobStatus($job, Job::STATUS_FAILED);
            } else {
                // Put back in pending for retry
                $this->storage->updateJobStatus($job, Job::STATUS_PENDING);
            }
            
            // Re-throw the exception
            throw new QueueException(
                'Job processing failed: ' . $e->getMessage(),
                QueueException::JOB_FAILED,
                $e
            );
        }
    }
    
    /**
     * Process multiple jobs
     * 
     * @param callable $processor Function to process jobs
     * @param int $count Maximum number of jobs to process (0 = all available)
     * @return array Processed jobs
     */
    public function processJobs(callable $processor, int $count = 0): array
    {
        $processed = [];
        $errors = [];
        $limit = $count > 0 ? $count : PHP_INT_MAX;
        
        for ($i = 0; $i < $limit; $i++) {
            try {
                $job = $this->processNext($processor);
                
                if ($job === null) {
                    break; // No more jobs
                }
                
                $processed[] = $job;
            } catch (\Exception $e) {
                $errors[] = $e;
                
                if ($count === 0) {
                    // If processing all jobs, continue despite errors
                    continue;
                } else {
                    // If processing a specific count, stop on error
                    break;
                }
            }
        }
        
        return [
            'processed' => $processed,
            'errors' => $errors
        ];
    }
    
    /**
     * Clean up completed jobs
     * 
     * @param int $olderThan Jobs completed this many seconds ago (or timestamp)
     * @return int Number of jobs removed
     */
    public function cleanup(int $olderThan = 86400): int
    {
        return $this->storage->cleanup($olderThan);
    }
}