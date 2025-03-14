<?php

/**
 * Delayed jobs example for FeatherQueue
 */

require_once __DIR__ . '/../vendor/autoload.php';

use FeatherQueue\JobQueue;
use FeatherQueue\Worker;
use FeatherQueue\Scheduler;

// Create a queue instance
$queue = new JobQueue(__DIR__ . '/queue_storage');

// Create a worker to process jobs
$worker = new Worker($queue);

// Register handlers for different job types
$worker->registerHandler('email', function($payload, $job) {
    echo "[" . date('Y-m-d H:i:s') . "] Sending email '{$payload['subject']}' to {$payload['to']}\n";
    sleep(1); // Simulate sending
    return true;
});

$worker->registerHandler('notification', function($payload, $job) {
    echo "[" . date('Y-m-d H:i:s') . "] Sending {$payload['type']} notification to {$payload['user']}: {$payload['message']}\n";
    sleep(1); // Simulate sending
    return true;
});

$worker->registerHandler('report', function($payload, $job) {
    echo "[" . date('Y-m-d H:i:s') . "] Generating {$payload['name']} report...\n";
    sleep(2); // Simulate report generation
    echo "  - Report generated successfully\n";
    return ['file' => 'report_' . time() . '.pdf'];
});

// Create a scheduler for more complex scheduling
$scheduler = new Scheduler($queue);

// Add jobs with different delays

// 1. Job with a 10 second delay
$job1 = $queue->later('email', [
    'to' => 'user@example.com',
    'subject' => 'Welcome to our service',
    'body' => 'Thank you for signing up...'
], 10);

echo "Email job scheduled with 10 second delay. ID: {$job1->getId()}\n";

// 2. Job with a 20 second delay
$job2 = $queue->later('notification', [
    'user' => 'admin',
    'type' => 'system',
    'message' => 'New user registered'
], 20);

echo "Notification job scheduled with 20 second delay. ID: {$job2->getId()}\n";

// 3. Job with a 30 second delay
$job3 = $queue->later('report', [
    'name' => 'Monthly Analytics',
    'period' => 'March 2025',
    'format' => 'PDF'
], 30);

echo "Report generation job scheduled with 30 second delay. ID: {$job3->getId()}\n";

// Using the scheduler for a specific time
$timestamp = strtotime('+2 minutes');
$job4 = $scheduler->at($timestamp, 'email', [
    'to' => 'team@example.com',
    'subject' => 'Scheduled Meeting Reminder',
    'body' => 'Team meeting in 15 minutes...'
]);

echo "Meeting reminder email scheduled for " . date('Y-m-d H:i:s', $timestamp) . ". ID: {$job4->getId()}\n";

// Process jobs as they become ready
echo "\nProcessing delayed jobs as they become ready...\n";
echo "-------------------------------------------\n";

// Process for the next 3 minutes (or until all jobs processed)
$endTime = time() + 180; // 3 minutes
$allJobIds = [$job1->getId(), $job2->getId(), $job3->getId(), $job4->getId()];
$processedJobs = [];

while (time() < $endTime && count($processedJobs) < count($allJobIds)) {
    // Get all pending jobs
    $pendingJobs = $queue->getStorage()->getPendingJobs();
    
    if (!empty($pendingJobs)) {
        foreach ($pendingJobs as $job) {
            // Only process our specific jobs from this example
            if (in_array($job->getId(), $allJobIds) && !in_array($job->getId(), $processedJobs)) {
                if ($job->isReadyToExecute()) {
                    echo "\nJob '{$job->getName()}' (ID: {$job->getId()}) is ready to execute\n";
                    
                    // Process the job
                    $queue->processNext(function($payload, $job) use ($worker) {
                        return $worker->processJob($job);
                    });
                    
                    // Mark as processed
                    $processedJobs[] = $job->getId();
                    
                    // Get the job again to see its updated status
                    $job = $queue->getJob($job->getId());
                    echo "Job '{$job->getName()}' status: {$job->getStatus()}\n";
                    
                    // If all jobs are processed, we can exit early
                    if (count($processedJobs) >= count($allJobIds)) {
                        break;
                    }
                } else {
                    $now = time();
                    $executeAt = $job->getExecuteAt();
                    $delay = $executeAt - $now;
                    
                    echo "."; // Show progress
                }
            }
        }
    }
    
    // Wait before checking again
    sleep(1);
}

echo "\n\nAll delayed jobs processed!\n";

// Get statistics
$stats = $queue->getStorage()->getStats();
echo "\nJob Statistics:\n";
echo "--------------\n";
foreach ($stats['jobs_by_status'] as $status => $count) {
    echo "{$status}: {$count} jobs\n";
}

// Clean up completed jobs
$cleaned = $queue->cleanup();
echo "\nCleaned up {$cleaned} completed jobs\n";