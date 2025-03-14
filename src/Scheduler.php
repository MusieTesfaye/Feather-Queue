<?php

namespace FeatherQueue;

/**
 * Scheduler class for FeatherQueue
 * 
 * Handles scheduling recurring jobs
 */
class Scheduler
{
    /**
     * The job queue
     * 
     * @var JobQueue
     */
    private JobQueue $queue;
    
    /**
     * Scheduled jobs
     * 
     * @var array
     */
    private array $schedules = [];
    
    /**
     * Constructor
     * 
     * @param JobQueue $queue
     */
    public function __construct(JobQueue $queue)
    {
        $this->queue = $queue;
    }
    
    /**
     * Schedule a job to run at a specific time
     * 
     * @param int $timestamp Unix timestamp
     * @param string $jobName
     * @param array $payload
     * @param array $options
     * @return Job
     */
    public function at(int $timestamp, string $jobName, array $payload = [], array $options = []): Job
    {
        $options['executeAt'] = $timestamp;
        return $this->queue->add($jobName, $payload, $options);
    }
    
    /**
     * Schedule a job to run daily at a specific time
     * 
     * @param string $timeString Time string (e.g., "09:00")
     * @param string $jobName
     * @param array $payload
     * @param array $options
     * @return string Schedule ID
     */
    public function daily(string $timeString, string $jobName, array $payload = [], array $options = []): string
    {
        $scheduleId = $this->generateScheduleId();
        
        $this->schedules[$scheduleId] = [
            'type' => 'daily',
            'time' => $timeString,
            'job_name' => $jobName,
            'payload' => $payload,
            'options' => $options,
            'created_at' => time(),
            'next_run' => $this->getNextDailyRunTime($timeString)
        ];
        
        return $scheduleId;
    }
    
    /**
     * Schedule a job to run weekly on a specific day and time
     * 
     * @param int $dayOfWeek Day of week (0 = Sunday, 6 = Saturday)
     * @param string $timeString Time string (e.g., "09:00")
     * @param string $jobName
     * @param array $payload
     * @param array $options
     * @return string Schedule ID
     */
    public function weekly(int $dayOfWeek, string $timeString, string $jobName, array $payload = [], array $options = []): string
    {
        $scheduleId = $this->generateScheduleId();
        
        $this->schedules[$scheduleId] = [
            'type' => 'weekly',
            'day' => $dayOfWeek,
            'time' => $timeString,
            'job_name' => $jobName,
            'payload' => $payload,
            'options' => $options,
            'created_at' => time(),
            'next_run' => $this->getNextWeeklyRunTime($dayOfWeek, $timeString)
        ];
        
        return $scheduleId;
    }
    
    /**
     * Schedule a job to run monthly on a specific day and time
     * 
     * @param int $dayOfMonth Day of month (1-31)
     * @param string $timeString Time string (e.g., "09:00")
     * @param string $jobName
     * @param array $payload
     * @param array $options
     * @return string Schedule ID
     */
    public function monthly(int $dayOfMonth, string $timeString, string $jobName, array $payload = [], array $options = []): string
    {
        $scheduleId = $this->generateScheduleId();
        
        $this->schedules[$scheduleId] = [
            'type' => 'monthly',
            'day' => $dayOfMonth,
            'time' => $timeString,
            'job_name' => $jobName,
            'payload' => $payload,
            'options' => $options,
            'created_at' => time(),
            'next_run' => $this->getNextMonthlyRunTime($dayOfMonth, $timeString)
        ];
        
        return $scheduleId;
    }
    
    /**
     * Schedule a job to run every N minutes
     * 
     * @param int $minutes
     * @param string $jobName
     * @param array $payload
     * @param array $options
     * @return string Schedule ID
     */
    public function everyMinutes(int $minutes, string $jobName, array $payload = [], array $options = []): string
    {
        $scheduleId = $this->generateScheduleId();
        
        $this->schedules[$scheduleId] = [
            'type' => 'minutes',
            'interval' => $minutes,
            'job_name' => $jobName,
            'payload' => $payload,
            'options' => $options,
            'created_at' => time(),
            'next_run' => time() + ($minutes * 60)
        ];
        
        return $scheduleId;
    }
    
    /**
     * Schedule a job to run every N hours
     * 
     * @param int $hours
     * @param string $jobName
     * @param array $payload
     * @param array $options
     * @return string Schedule ID
     */
    public function everyHours(int $hours, string $jobName, array $payload = [], array $options = []): string
    {
        return $this->everyMinutes($hours * 60, $jobName, $payload, $options);
    }
    
    /**
     * Cancel a scheduled job
     * 
     * @param string $scheduleId
     * @return bool
     */
    public function cancel(string $scheduleId): bool
    {
        if (isset($this->schedules[$scheduleId])) {
            unset($this->schedules[$scheduleId]);
            return true;
        }
        
        return false;
    }
    
    /**
     * Run the scheduler
     * 
     * @return array Jobs that were queued
     */
    public function run(): array
    {
        $jobsQueued = [];
        $now = time();
        
        foreach ($this->schedules as $scheduleId => &$schedule) {
            if ($schedule['next_run'] <= $now) {
                // Schedule the job
                $job = $this->queue->add(
                    $schedule['job_name'],
                    $schedule['payload'],
                    $schedule['options']
                );
                
                $jobsQueued[] = [
                    'schedule_id' => $scheduleId,
                    'job' => $job
                ];
                
                // Update next run time
                $schedule['next_run'] = $this->calculateNextRunTime($schedule);
                $schedule['last_run'] = $now;
            }
        }
        
        return $jobsQueued;
    }
    
    /**
     * Get all scheduled jobs
     * 
     * @return array
     */
    public function getSchedules(): array
    {
        return $this->schedules;
    }
    
    /**
     * Generate a unique schedule ID
     * 
     * @return string
     */
    private function generateScheduleId(): string
    {
        return 'schedule_' . uniqid('', true);
    }
    
    /**
     * Calculate the next run time for a schedule
     * 
     * @param array $schedule
     * @return int
     */
    private function calculateNextRunTime(array $schedule): int
    {
        switch ($schedule['type']) {
            case 'minutes':
                return time() + ($schedule['interval'] * 60);
                
            case 'daily':
                return $this->getNextDailyRunTime($schedule['time']);
                
            case 'weekly':
                return $this->getNextWeeklyRunTime($schedule['day'], $schedule['time']);
                
            case 'monthly':
                return $this->getNextMonthlyRunTime($schedule['day'], $schedule['time']);
                
            default:
                return time() + 3600; // Default to 1 hour
        }
    }
    
    /**
     * Get the next daily run time
     * 
     * @param string $timeString
     * @return int
     */
    private function getNextDailyRunTime(string $timeString): int
    {
        list($hour, $minute) = explode(':', $timeString);
        $hour = (int)$hour;
        $minute = (int)$minute;
        
        $date = new \DateTime('today');
        $date->setTime($hour, $minute);
        
        // If the time has already passed today, move to tomorrow
        if ($date->getTimestamp() < time()) {
            $date->modify('+1 day');
        }
        
        return $date->getTimestamp();
    }
    
    /**
     * Get the next weekly run time
     * 
     * @param int $dayOfWeek
     * @param string $timeString
     * @return int
     */
    private function getNextWeeklyRunTime(int $dayOfWeek, string $timeString): int
    {
        list($hour, $minute) = explode(':', $timeString);
        $hour = (int)$hour;
        $minute = (int)$minute;
        
        $date = new \DateTime();
        $currentDayOfWeek = (int)$date->format('w');
        
        // Calculate days to add
        $daysToAdd = $dayOfWeek - $currentDayOfWeek;
        if ($daysToAdd < 0 || ($daysToAdd === 0 && $date->getTimestamp() > $this->getTimeOfDay($hour, $minute))) {
            $daysToAdd += 7;
        }
        
        $date->modify("+{$daysToAdd} days");
        $date->setTime($hour, $minute);
        
        return $date->getTimestamp();
    }
    
    /**
     * Get the next monthly run time
     * 
     * @param int $dayOfMonth
     * @param string $timeString
     * @return int
     */
    private function getNextMonthlyRunTime(int $dayOfMonth, string $timeString): int
    {
        list($hour, $minute) = explode(':', $timeString);
        $hour = (int)$hour;
        $minute = (int)$minute;
        
        $date = new \DateTime('first day of this month');
        $date->setTime($hour, $minute);
        
        // Set to the specified day of month
        $date->setDate(
            (int)$date->format('Y'),
            (int)$date->format('m'),
            min($dayOfMonth, (int)$date->format('t')) // Ensure day is valid for month
        );
        
        // If the date has already passed this month, move to next month
        if ($date->getTimestamp() < time()) {
            $date->modify('first day of next month');
            $date->setDate(
                (int)$date->format('Y'),
                (int)$date->format('m'),
                min($dayOfMonth, (int)$date->format('t'))
            );
        }
        
        return $date->getTimestamp();
    }
    
    /**
     * Get timestamp for a specific time of day
     * 
     * @param int $hour
     * @param int $minute
     * @return int
     */
    private function getTimeOfDay(int $hour, int $minute): int
    {
        $date = new \DateTime('today');
        $date->setTime($hour, $minute);
        return $date->getTimestamp();
    }
}