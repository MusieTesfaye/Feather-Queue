<?php

namespace FeatherQueue\Contracts;

use FeatherQueue\Job;

/**
 * StorageInterface for FeatherQueue
 * 
 * Interface for queue storage implementations
 */
interface StorageInterface
{
    /**
     * Save a job to the storage
     * 
     * @param Job $job
     * @return bool
     */
    public function saveJob(Job $job): bool;
    
    /**
     * Get a job by its ID
     * 
     * @param string $id
     * @return Job|null
     */
    public function getJob(string $id): ?Job;
    
    /**
     * Delete a job from the storage
     * 
     * @param Job $job
     * @return bool
     */
    public function deleteJob(Job $job): bool;
    
    /**
     * Get the next pending job
     * 
     * @return Job|null
     */
    public function getNextPendingJob(): ?Job;
    
    /**
     * Get all pending jobs
     * 
     * @return array
     */
    public function getPendingJobs(): array;
    
    /**
     * Update a job's status
     * 
     * @param Job $job
     * @param string $status
     * @return bool
     */
    public function updateJobStatus(Job $job, string $status): bool;
    
    /**
     * Get jobs by status
     * 
     * @param string $status
     * @param int|null $limit
     * @return array
     */
    public function getJobsByStatus(string $status, ?int $limit = null): array;
    
    /**
     * Get queue statistics
     * 
     * @return array
     */
    public function getStats(): array;
    
    /**
     * Clean up old completed jobs
     * 
     * @param int $olderThan Timestamp or seconds
     * @return int Number of jobs removed
     */
    public function cleanup(int $olderThan = 86400): int;
}