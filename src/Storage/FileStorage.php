<?php

namespace FeatherQueue\Storage;

use FeatherQueue\Contracts\StorageInterface;
use FeatherQueue\Job;
use FeatherQueue\Exceptions\QueueException;

/**
 * FileStorage implementation for FeatherQueue
 * 
 * Stores jobs as JSON files in a directory structure
 */
class FileStorage implements StorageInterface
{
    /**
     * Base storage directory
     * 
     * @var string
     */
    private string $storageDir;
    
    /**
     * In-memory job cache
     * 
     * @var array
     */
    private array $jobCache = [];
    
    /**
     * Maximum size of the job cache
     * 
     * @var int
     */
    private int $cacheSize = 50;
    
    /**
     * Whether the cache is enabled
     * 
     * @var bool
     */
    private bool $cacheEnabled = true;
    
    /**
     * Constructor
     * 
     * @param string $storageDir Base directory for job storage
     * @param array $options Additional options
     */
    public function __construct(string $storageDir, array $options = [])
    {
        $this->storageDir = rtrim($storageDir, '/');
        
        // Set options if provided
        if (isset($options['cache_size'])) {
            $this->cacheSize = (int)$options['cache_size'];
        }
        
        if (isset($options['cache_enabled'])) {
            $this->cacheEnabled = (bool)$options['cache_enabled'];
        }
        
        // Create directory structure if it doesn't exist
        $this->initializeStorage();
    }
    
    /**
     * Initialize the storage directory structure
     * 
     * @return void
     */
    private function initializeStorage(): void
    {
        $dirs = [
            $this->storageDir,
            $this->getStatusDir('pending'),
            $this->getStatusDir('processing'),
            $this->getStatusDir('completed'),
            $this->getStatusDir('failed')
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * Get the directory path for a specific job status
     * 
     * @param string $status
     * @return string
     */
    private function getStatusDir(string $status): string
    {
        return $this->storageDir . '/' . $status;
    }
    
    /**
     * Get the full path for a job file
     * 
     * @param Job $job
     * @return string
     */
    private function getJobPath(Job $job): string
    {
        return $this->getStatusDir($job->getStatus()) . '/' . $job->getId() . '.json';
    }
    
    /**
     * Save a job to the storage
     * 
     * @param Job $job
     * @return bool
     */
    public function saveJob(Job $job): bool
    {
        $path = $this->getJobPath($job);
        $data = json_encode($job->toArray(), JSON_PRETTY_PRINT);
        
        $result = file_put_contents($path, $data, LOCK_EX);
        
        if ($result !== false && $this->cacheEnabled) {
            $this->addToCache($job);
        }
        
        return $result !== false;
    }
    
    /**
     * Get a job by its ID
     * 
     * @param string $id
     * @return Job|null
     */
    public function getJob(string $id): ?Job
    {
        // Check cache first
        if ($this->cacheEnabled && isset($this->jobCache[$id])) {
            return $this->jobCache[$id];
        }
        
        // Search in all status directories
        $statuses = ['pending', 'processing', 'completed', 'failed'];
        
        foreach ($statuses as $status) {
            $path = $this->getStatusDir($status) . '/' . $id . '.json';
            
            if (file_exists($path)) {
                $data = json_decode(file_get_contents($path), true);
                
                if ($data) {
                    $job = Job::fromArray($data);
                    
                    if ($this->cacheEnabled) {
                        $this->addToCache($job);
                    }
                    
                    return $job;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Delete a job from the storage
     * 
     * @param Job $job
     * @return bool
     */
    public function deleteJob(Job $job): bool
    {
        $path = $this->getJobPath($job);
        
        if (file_exists($path)) {
            $result = unlink($path);
            
            if ($result && $this->cacheEnabled) {
                unset($this->jobCache[$job->getId()]);
            }
            
            return $result;
        }
        
        return false;
    }
    
    /**
     * Get the next pending job
     * 
     * @return Job|null
     */
    public function getNextPendingJob(): ?Job
    {
        $pendingJobs = $this->getPendingJobs();
        
        if (empty($pendingJobs)) {
            return null;
        }
        
        // Find the next job that's ready to execute
        foreach ($pendingJobs as $job) {
            if ($job->isReadyToExecute()) {
                return $job;
            }
        }
        
        return null;
    }
    
    /**
     * Get all pending jobs
     * 
     * @return array
     */
    public function getPendingJobs(): array
    {
        return $this->getJobsByStatus('pending');
    }
    
    /**
     * Update a job's status
     * 
     * @param Job $job
     * @param string $status
     * @return bool
     */
    public function updateJobStatus(Job $job, string $status): bool
    {
        $oldPath = $this->getJobPath($job);
        
        // Update job status
        $job->setStatus($status);
        
        // Save to new location
        $newPath = $this->getJobPath($job);
        
        // If old file exists, remove it
        if (file_exists($oldPath)) {
            unlink($oldPath);
        }
        
        // Save to new location
        $result = $this->saveJob($job);
        
        return $result;
    }
    
    /**
     * Get jobs by status
     * 
     * @param string $status
     * @param int|null $limit
     * @return array
     */
    public function getJobsByStatus(string $status, ?int $limit = null): array
    {
        $dir = $this->getStatusDir($status);
        $jobs = [];
        
        if (!is_dir($dir)) {
            return $jobs;
        }
        
        $files = glob($dir . '/*.json');
        
        if ($limit !== null && count($files) > $limit) {
            $files = array_slice($files, 0, $limit);
        }
        
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            
            if ($data) {
                $job = Job::fromArray($data);
                $jobs[] = $job;
                
                if ($this->cacheEnabled) {
                    $this->addToCache($job);
                }
            }
        }
        
        return $jobs;
    }
    
    /**
     * Get queue statistics
     * 
     * @return array
     */
    public function getStats(): array
    {
        $stats = [
            'jobs_by_status' => [
                'pending' => 0,
                'processing' => 0,
                'completed' => 0,
                'failed' => 0
            ],
            'cache_size' => count($this->jobCache),
            'storage_size' => 0
        ];
        
        // Count jobs by status
        foreach (array_keys($stats['jobs_by_status']) as $status) {
            $dir = $this->getStatusDir($status);
            
            if (is_dir($dir)) {
                $files = glob($dir . '/*.json');
                $stats['jobs_by_status'][$status] = count($files);
                $stats['storage_size'] += count($files);
            }
        }
        
        return $stats;
    }
    
    /**
     * Clean up old completed jobs
     * 
     * @param int $olderThan Seconds or timestamp
     * @return int Number of jobs removed
     */
    public function cleanup(int $olderThan = 86400): int
    {
        $count = 0;
        $cutoffTime = $olderThan > 1000000000 ? $olderThan : time() - $olderThan;
        
        // Clean up completed jobs
        $completedJobs = $this->getJobsByStatus('completed');
        
        foreach ($completedJobs as $job) {
            $completedAt = $job->getMetadata('completed_at', 0);
            
            if ($completedAt < $cutoffTime) {
                if ($this->deleteJob($job)) {
                    $count++;
                }
            }
        }
        
        return $count;
    }
    
    /**
     * Add a job to the in-memory cache
     * 
     * @param Job $job
     * @return void
     */
    private function addToCache(Job $job): void
    {
        if (!$this->cacheEnabled) {
            return;
        }
        
        // Add to cache
        $this->jobCache[$job->getId()] = $job;
        
        // If cache exceeds limit, remove oldest entries
        if (count($this->jobCache) > $this->cacheSize) {
            // Remove oldest entries (first ones in the array)
            array_shift($this->jobCache);
        }
    }
    
    /**
     * Clear the job cache
     * 
     * @return void
     */
    public function clearCache(): void
    {
        $this->jobCache = [];
    }
}