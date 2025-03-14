<?php

/**
 * Recurring jobs example for FeatherQueue
 */

require_once __DIR__ . '/../vendor/autoload.php';

use FeatherQueue\JobQueue;
use FeatherQueue\Worker;
use FeatherQueue\Scheduler;

// Create a queue instance
$queue = new JobQueue(__DIR__ . '/queue_storage');

// Create a worker to process jobs
$worker = new Worker($queue);

// Register handlers for job types
$worker->registerHandler('stats', function($payload, $job) {
    echo "[" . date('Y-m-d H:i:s') . "] Collecting system stats...\n";
    
    // Simulate collecting system metrics
    $stats = [
        'memory_usage' => rand(100, 300) . 'MB',
        'cpu_load' => rand(10, 90) . '%',
        'disk_usage' => rand(50, 85) . '%',
        'active_users' => rand(5, 100),
        'timestamp' => time()
    ];
    
    echo "  Memory: {$stats['memory_usage']}, CPU: {$stats['cpu_load']}, Disk: {$stats['disk_usage']}, Users: {$stats['active_users']}\n";
    
    return $stats;
});

$worker->registerHandler('backup', function($payload, $job) {
    echo "[" . date('Y-m-d H:i:s') . "] Running {$payload['type']} backup...\n";
    
    // Simulate backup process
    $sleepTime = ($payload['type'] === 'full') ? 3 : 1;
    sleep($sleepTime);
    
    $backupSize = ($payload['type'] === 'full') ? rand(500, 1000) : rand(50, 200);
    echo "  Backup completed: {$backupSize}MB\n";
    
    return [
        'type' => $payload['type'],
        'size' => $backupSize . 'MB',
        'duration' => $sleepTime . 's',
        'timestamp' => time()
    ];
});

$worker->registerHandler('notification', function($payload, $job) {
    echo "[" . date('Y-m-d H:i:s') . "] Sending {$payload['type']} notification\n";
    echo "  Message: {$payload['message']}\n";
    
    // Simulate sending
    sleep(1);
    
    return true;
});

// Create a scheduler
$scheduler = new Scheduler($queue);

// Schedule recurring jobs
echo "Setting up recurring jobs...\n";

// 1. Collect system stats every minute
$statsSchedule = $scheduler->everyMinutes(1, 'stats', [
    'detailed' => true,
    'service' => 'system_monitor'
]);

echo "System stats job scheduled every minute. ID: {$statsSchedule}\n";

// 2. Run incremental backup every 5 minutes
$incrementalBackupSchedule = $scheduler->everyMinutes(5, 'backup', [
    'type' => 'incremental',
    'destination' => 'cloud_storage'
]);

echo "Incremental backup scheduled every 5 minutes. ID: {$incrementalBackupSchedule}\n";

// 3. Run full backup daily at midnight
$fullBackupSchedule = $scheduler->daily('00:00', 'backup', [
    'type' => 'full',
    'destination' => 'off_site_storage'
]);

echo "Full backup scheduled daily at midnight. ID: {$fullBackupSchedule}\n";

// 4. Send daily summary at 8:00 AM
$dailySummarySchedule = $scheduler->daily('08:00', 'notification', [
    'type' => 'daily_summary',
    'message' => 'Daily system summary report is available',
    'recipients' => ['admin@example.com']
]);

echo "Daily summary notification scheduled at 8:00 AM. ID: {$dailySummarySchedule}\n";

// 5. Send weekly report every Monday at 9:00 AM
$weeklyReportSchedule = $scheduler->weekly(1, '09:00', 'notification', [
    'type' => 'weekly_report',
    'message' => 'Weekly performance report is available',
    'recipients' => ['management@example.com']
]);

echo "Weekly report notification scheduled on Mondays at 9:00 AM. ID: {$weeklyReportSchedule}\n";

// Show all scheduled jobs
echo "\nListing all scheduled jobs:\n";
echo "=========================\n";

$schedules = $scheduler->getSchedules();

foreach ($schedules as $id => $schedule) {
    $nextRun = date('Y-m-d H:i:s', $schedule['next_run']);
    echo "ID: {$id}\n";
    echo "  Type: {$schedule['type']}\n";
    echo "  Job: {$schedule['job_name']}\n";
    echo "  Next run: {$nextRun}\n";
    echo "\n";
}

// Simulate the scheduler running and creating jobs (in a real scenario, this would be a separate process)
echo "\nRunning scheduler (simulating a few runs)...\n";
echo "======================================\n";

// Run the scheduler multiple times to demonstrate job creation
for ($i = 0; $i < 3; $i++) {
    echo "\nScheduler run #" . ($i + 1) . ":\n";
    
    // Force next_run to be now for demonstration purposes
    foreach ($schedules as $id => &$schedule) {
        if ($id === $statsSchedule || ($i >= 2 && $id === $incrementalBackupSchedule)) {
            $schedule['next_run'] = time() - 1; // Make it ready to run
        }
    }
    
    // Run the scheduler
    $jobs = $scheduler->run();
    
    if (count($jobs) > 0) {
        echo "  Created " . count($jobs) . " jobs:\n";
        
        foreach ($jobs as $item) {
            $job = $item['job'];
            echo "  - Job ID: {$job->getId()}, Type: {$job->getName()}\n";
            
            // Process the job right away for demonstration
            try {
                $result = $worker->processJob($job);
                $queue->getStorage()->updateJobStatus($job, 'completed');
            } catch (\Exception $e) {
                echo "    Error: {$e->getMessage()}\n";
            }
        }
    } else {
        echo "  No jobs created in this run.\n";
    }
    
    // Wait between runs
    if ($i < 2) {
        echo "  Waiting 2 seconds before next run...\n";
        sleep(2);
    }
}

// Show queue statistics
$stats = $queue->getStorage()->getStats();

echo "\nQueue Statistics:\n";
echo "-----------------\n";
foreach ($stats['jobs_by_status'] as $status => $count) {
    echo "{$status}: {$count} jobs\n";
}

echo "\nFinished demonstrating recurring jobs.\n";