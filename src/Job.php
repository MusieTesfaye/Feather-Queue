<?php

namespace FeatherQueue;

/**
 * Job class for FeatherQueue
 * 
 * Represents a task in the queue system
 */
class Job
{
    /**
     * Job status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    
    /**
     * The unique identifier for the job
     * 
     * @var string
     */
    private string $id;
    
    /**
     * The name/type of the job
     * 
     * @var string
     */
    private string $name;
    
    /**
     * The payload data for the job
     * 
     * @var array
     */
    private array $payload;
    
    /**
     * The current status of the job
     * 
     * @var string
     */
    private string $status = self::STATUS_PENDING;
    
    /**
     * The timestamp when the job should be executed
     * 
     * @var int|null
     */
    private ?int $executeAt = null;
    
    /**
     * The number of times the job has been attempted
     * 
     * @var int
     */
    private int $attempts = 0;
    
    /**
     * The maximum number of attempts for this job
     * 
     * @var int
     */
    private int $maxAttempts = 3;
    
    /**
     * Metadata for the job (timestamps, results, etc.)
     * 
     * @var array
     */
    private array $metadata = [];
    
    /**
     * Constructor
     * 
     * @param string $name    The job name/type
     * @param array  $payload The job payload data
     * @param array  $options Additional options for the job
     */
    public function __construct(string $name, array $payload = [], array $options = [])
    {
        $this->id = uniqid('job_', true);
        $this->name = $name;
        $this->payload = $payload;
        
        // Set options if provided
        if (isset($options['executeAt'])) {
            $this->executeAt = (int)$options['executeAt'];
        }
        
        if (isset($options['maxAttempts'])) {
            $this->maxAttempts = (int)$options['maxAttempts'];
        }
        
        if (isset($options['status'])) {
            $this->status = $options['status'];
        }
        
        if (isset($options['id'])) {
            $this->id = $options['id'];
        }
        
        if (isset($options['attempts'])) {
            $this->attempts = (int)$options['attempts'];
        }
        
        if (isset($options['metadata']) && is_array($options['metadata'])) {
            $this->metadata = $options['metadata'];
        }
        
        // Set created timestamp if not already set
        if (!isset($this->metadata['created_at'])) {
            $this->metadata['created_at'] = time();
        }
    }
    
    /**
     * Get the job ID
     * 
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
    
    /**
     * Get the job name/type
     * 
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * Get the job payload
     * 
     * @return array
     */
    public function getPayload(): array
    {
        return $this->payload;
    }
    
    /**
     * Get the job status
     * 
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }
    
    /**
     * Set the job status
     * 
     * @param string $status
     * @return self
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;
        
        // Add timestamps based on status change
        switch ($status) {
            case self::STATUS_PROCESSING:
                $this->metadata['processing_started_at'] = time();
                break;
                
            case self::STATUS_COMPLETED:
                $this->metadata['completed_at'] = time();
                break;
                
            case self::STATUS_FAILED:
                $this->metadata['failed_at'] = time();
                break;
        }
        
        return $this;
    }
    
    /**
     * Get the execute at timestamp
     * 
     * @return int|null
     */
    public function getExecuteAt(): ?int
    {
        return $this->executeAt;
    }
    
    /**
     * Set the execute at timestamp
     * 
     * @param int|null $timestamp
     * @return self
     */
    public function setExecuteAt(?int $timestamp): self
    {
        $this->executeAt = $timestamp;
        return $this;
    }
    
    /**
     * Get the number of attempts
     * 
     * @return int
     */
    public function getAttempts(): int
    {
        return $this->attempts;
    }
    
    /**
     * Increment the attempts counter
     * 
     * @return self
     */
    public function incrementAttempts(): self
    {
        $this->attempts++;
        return $this;
    }
    
    /**
     * Get the maximum number of attempts
     * 
     * @return int
     */
    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }
    
    /**
     * Set the maximum number of attempts
     * 
     * @param int $maxAttempts
     * @return self
     */
    public function setMaxAttempts(int $maxAttempts): self
    {
        $this->maxAttempts = $maxAttempts;
        return $this;
    }
    
    /**
     * Check if the job has reached maximum attempts
     * 
     * @return bool
     */
    public function hasReachedMaxAttempts(): bool
    {
        return $this->attempts >= $this->maxAttempts;
    }
    
    /**
     * Get a specific metadata value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getMetadata(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }
    
    /**
     * Set a metadata value
     * 
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function setMetadata(string $key, $value): self
    {
        $this->metadata[$key] = $value;
        return $this;
    }
    
    /**
     * Get all metadata
     * 
     * @return array
     */
    public function getAllMetadata(): array
    {
        return $this->metadata;
    }
    
    /**
     * Check if the job is ready to execute
     * 
     * @return bool
     */
    public function isReadyToExecute(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }
        
        if ($this->executeAt === null) {
            return true;
        }
        
        return time() >= $this->executeAt;
    }
    
    /**
     * Convert the job to an array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'payload' => $this->payload,
            'status' => $this->status,
            'executeAt' => $this->executeAt,
            'attempts' => $this->attempts,
            'maxAttempts' => $this->maxAttempts,
            'metadata' => $this->metadata
        ];
    }
    
    /**
     * Create a job from an array
     * 
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $options = [
            'id' => $data['id'] ?? null,
            'status' => $data['status'] ?? self::STATUS_PENDING,
            'executeAt' => $data['executeAt'] ?? null,
            'attempts' => $data['attempts'] ?? 0,
            'maxAttempts' => $data['maxAttempts'] ?? 3
        ];
        
        // Only add metadata if it exists
        if (isset($data['metadata']) && is_array($data['metadata'])) {
            $options['metadata'] = $data['metadata'];
        }
        
        return new self($data['name'], $data['payload'] ?? [], $options);
    }
}