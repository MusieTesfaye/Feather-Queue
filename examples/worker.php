<?php

/**
 * Worker example for FeatherQueue
 * 
 * This script demonstrates running a worker to process jobs continuously.
 * It can be run in the background or as a daemon process.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use FeatherQueue\JobQueue;
use FeatherQueue\Worker;

// Parse command line arguments
$options = getopt('d:l:t:', ['directory:', 'limit:', 'timeout:', 'help']);

if (isset($options['help'])) {
    echo "FeatherQueue Worker\n";
    echo "Usage: php worker.php [options]\n\n";
    echo "Options:\n";
    echo "  -d, --directory=DIR   Set the queue storage directory (default: ./queue_storage)\n";
    echo "  -l, --limit=NUM       Set maximum number of jobs to process (default: 0 = unlimited)\n";
    echo "  -t, --timeout=NUM     Set maximum runtime in seconds (default: 0 = unlimited)\n";
    echo "  --help                Display this help message\n";
    exit(0);
}

// Set options from command line arguments
$storageDir = $options['d'] ?? $options['directory'] ?? __DIR__ . '/queue_storage';
$jobLimit = (int)($options['l'] ?? $options['limit'] ?? 0);
$timeout = (int)($options['t'] ?? $options['timeout'] ?? 0);

// Create queue and worker
$queue = new JobQueue($storageDir, [
    'cache_size' => 100 // Increase cache size for long-running workers
]);

$worker = new Worker($queue);

// Register job handlers
registerJobHandlers($worker);

// Add worker event callbacks
$worker->on('beforeProcessing', function($job) {
    echo "[" . date('Y-m-d H:i:s') . "] Processing job: {$job->getName()} (ID: {$job->getId()})\n";
});

$worker->on('onSuccess', function($job, $result) {
    echo "[" . date('Y-m-d H:i:s') . "] Job completed: {$job->getName()}\n";
    
    // Log additional info if available
    if (is_array($result)) {
        echo "  Result: " . json_encode($result) . "\n";
    }
});

$worker->on('onFailure', function($job, $exception) {
    echo "[" . date('Y-m-d H:i:s') . "] Job failed: {$job->getName()}\n";
    echo "  Error: " . $exception->getMessage() . "\n";
});

$worker->on('onShutdown', function($stats) {
    echo "\nWorker shutting down\n";
    echo "Jobs processed: {$stats['jobs_processed']}\n";
    echo "Jobs successful: {$stats['jobs_successful']}\n";
    echo "Jobs failed: {$stats['jobs_failed']}\n";
    echo "Runtime: " . ($stats['end_time'] - $stats['start_time']) . " seconds\n";
});

// Set up adaptive polling for better performance
$worker->setAdaptivePolling(true, 10000000); // max 10 seconds between polls
$worker->setSleepTime(500000); // 0.5 seconds initial poll time

// Print startup message
echo "FeatherQueue Worker\n";
echo "==================\n";
echo "Storage directory: {$storageDir}\n";
if ($jobLimit > 0) {
    echo "Job limit: {$jobLimit}\n";
} else {
    echo "Job limit: unlimited\n";
}
if ($timeout > 0) {
    echo "Timeout: {$timeout} seconds\n";
} else {
    echo "Timeout: unlimited\n";
}
echo "\nStarting worker at " . date('Y-m-d H:i:s') . "\n";
echo "Press Ctrl+C to stop\n";
echo "==================\n\n";

// Start the worker
$stats = $worker->run($jobLimit, $timeout);

// Exit with success
exit(0);

/**
 * Register handlers for different job types
 */
function registerJobHandlers(Worker $worker): void
{
    // Email jobs
    $worker->registerHandler('email', function($payload, $job) {
        echo "Sending email to: {$payload['to']}\n";
        echo "Subject: {$payload['subject']}\n";
        
        // Simulate sending
        sleep(1);
        
        return [
            'sent' => true,
            'time' => date('Y-m-d H:i:s')
        ];
    });
    
    // Notification jobs
    $worker->registerHandler('notification', function($payload, $job) {
        echo "Sending {$payload['type']} notification to {$payload['user']}\n";
        echo "Message: {$payload['message']}\n";
        
        // Simulate sending
        sleep(1);
        
        return true;
    });
    
    // Report generation jobs
    $worker->registerHandler('report', function($payload, $job) {
        echo "Generating {$payload['type']} report\n";
        
        // Simulate complex processing
        sleep(3);
        
        // Simulate failure sometimes for testing retry logic
        if (rand(1, 10) === 1) {
            throw new Exception("Random report generation failure");
        }
        
        return [
            'file' => 'report_' . uniqid() . '.pdf',
            'size' => rand(100, 1000) . 'KB',
            'pages' => rand(5, 50)
        ];
    });
    
    // Default handler for any unregistered job types
    $worker->registerDefaultHandler(function($payload, $job) {
        echo "Processing generic job: {$job->getName()}\n";
        print_r($payload);
        
        // Just return success
        return true;
    });
}