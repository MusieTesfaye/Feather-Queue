<?php

/**
 * Basic usage example for FeatherQueue
 */

require_once __DIR__ . '/../vendor/autoload.php';

use FeatherQueue\JobQueue;
use FeatherQueue\Worker;

// Create a queue instance with file storage
$queue = new JobQueue(__DIR__ . '/queue_storage');

// Create a worker
$worker = new Worker($queue);

// Register handlers for different job types
$worker->registerHandler('email', function($payload, $job) {
    echo "Sending email to: {$payload['to']}\n";
    echo "Subject: {$payload['subject']}\n";
    echo "Message: {$payload['message']}\n";
    
    // Simulate email sending (in a real app, you'd use a mail library)
    sleep(1);
    
    return [
        'sent' => true,
        'time' => date('Y-m-d H:i:s')
    ];
});

$worker->registerHandler('log', function($payload, $job) {
    echo "Logging message: {$payload['message']}\n";
    
    // Simulate logging
    $logEntry = "[" . date('Y-m-d H:i:s') . "] " . $payload['message'] . "\n";
    echo $logEntry;
    
    return true;
});

// Add some jobs to the queue
echo "Adding jobs to queue...\n";

$emailJob = $queue->add('email', [
    'to' => 'user@example.com',
    'subject' => 'Welcome to FeatherQueue',
    'message' => 'This is a test email from FeatherQueue'
]);

echo "Email job added with ID: {$emailJob->getId()}\n";

$logJob = $queue->add('log', [
    'message' => 'Application started successfully',
    'level' => 'info'
]);

echo "Log job added with ID: {$logJob->getId()}\n";

// Add a delayed job (will run after 5 seconds)
$delayedJob = $queue->later('email', [
    'to' => 'admin@example.com',
    'subject' => 'Delayed notification',
    'message' => 'This email was delayed by 5 seconds'
], 5);

echo "Delayed job added with ID: {$delayedJob->getId()}\n";

// Process the jobs
echo "\nProcessing jobs...\n";
echo "==================\n";

// Process regular jobs first
$result = $queue->processJobs(function($payload, $job) use ($worker) {
    return $worker->processJob($job);
}, 2); // Process only 2 jobs

echo "\nProcessed " . count($result['processed']) . " jobs\n";

if (!empty($result['errors'])) {
    echo "Encountered " . count($result['errors']) . " errors\n";
    
    foreach ($result['errors'] as $error) {
        echo "Error: " . $error->getMessage() . "\n";
    }
}

// Wait for the delayed job
echo "\nWaiting for delayed job...\n";
sleep(6);

echo "\nProcessing delayed job...\n";
echo "=====================\n";

try {
    $job = $queue->processNext(function($payload, $job) use ($worker) {
        return $worker->processJob($job);
    });
    
    if ($job) {
        echo "\nDelayed job processed successfully with ID: {$job->getId()}\n";
    } else {
        echo "\nNo delayed job found (it might have been processed already)\n";
    }
} catch (\Exception $e) {
    echo "\nError processing delayed job: " . $e->getMessage() . "\n";
}

// Get queue statistics
$stats = $queue->getStorage()->getStats();

echo "\nQueue Statistics:\n";
echo "-----------------\n";
echo "Pending jobs: " . ($stats['jobs_by_status']['pending'] ?? 0) . "\n";
echo "Processing jobs: " . ($stats['jobs_by_status']['processing'] ?? 0) . "\n";
echo "Completed jobs: " . ($stats['jobs_by_status']['completed'] ?? 0) . "\n";
echo "Failed jobs: " . ($stats['jobs_by_status']['failed'] ?? 0) . "\n";
echo "Jobs in memory cache: " . $stats['cache_size'] . "\n";

// Clean up completed jobs (normally you'd do this periodically)
$cleaned = $queue->cleanup();
echo "\nCleaned up {$cleaned} completed jobs\n";