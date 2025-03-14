<?php

/**
 * FeatherQueue Dashboard
 * 
 * A web interface to monitor and manage the queue.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use FeatherQueue\JobQueue;
use FeatherQueue\Job;
use FeatherQueue\Worker;
use FeatherQueue\Scheduler;

// Create queue instance
$queueDir = __DIR__ . '/queue_storage';
$queue = new JobQueue($queueDir);
$storage = $queue->getStorage();

// Process actions
$action = $_GET['action'] ?? '';
$jobId = $_GET['job'] ?? '';
$message = '';
$messageType = 'success';

// Current view/page
$currentView = $_GET['view'] ?? 'overview';
$validViews = ['overview', 'pending', 'processing', 'completed', 'failed', 'add', 'scheduled'];

if (!in_array($currentView, $validViews)) {
    $currentView = 'overview';
}

if ($action === 'delete' && !empty($jobId)) {
    $job = $queue->getJob($jobId);
    if ($job) {
        $queue->delete($job);
        $message = "Job {$jobId} deleted successfully";
        $messageType = 'success';
    } else {
        $message = "Job {$jobId} not found";
        $messageType = 'error';
    }
}

if ($action === 'process' && !empty($jobId)) {
    $job = $queue->getJob($jobId);
    if ($job) {
        $worker = new Worker($queue);
        
        // Register a simple handler for demonstration
        $worker->registerDefaultHandler(function($payload, $job) {
            return ['processed_at' => date('Y-m-d H:i:s')];
        });
        
        // Process the job
        $storage->updateJobStatus($job, 'pending');
        $queue->processNext(function($payload, $job) use ($worker) {
            return $worker->processJob($job);
        });
        
        $message = "Job {$jobId} processed";
        $messageType = 'success';
    } else {
        $message = "Job {$jobId} not found";
        $messageType = 'error';
    }
}

if ($action === 'cleanup') {
    $age = $_GET['age'] ?? 86400; // Default 1 day
    $count = $queue->cleanup((int)$age);
    $message = "Cleaned up {$count} jobs";
    $messageType = 'success';
}

// Add a test job if requested
if ($action === 'addjob') {
    $jobType = $_POST['jobtype'] ?? '';
    $delay = (int)($_POST['delay'] ?? 0);
    
    if (!empty($jobType)) {
        $payload = [
            'message' => $_POST['message'] ?? 'Test job',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        if ($delay > 0) {
            $job = $queue->later($jobType, $payload, $delay);
            $message = "Delayed job added with ID: {$job->getId()}";
        } else {
            $job = $queue->add($jobType, $payload);
            $message = "Job added with ID: {$job->getId()}";
        }
        $messageType = 'success';
    } else {
        $message = "Job type is required";
        $messageType = 'error';
    }
}

// Get statistics
$stats = $storage->getStats();
$pendingJobs = $storage->getJobsByStatus('pending');
$processingJobs = $storage->getJobsByStatus('processing');
$completedJobs = $storage->getJobsByStatus('completed', 25); // Limit to 25
$failedJobs = $storage->getJobsByStatus('failed');

// Get total counts
$pendingCount = $stats['jobs_by_status']['pending'] ?? 0;
$processingCount = $stats['jobs_by_status']['processing'] ?? 0;
$completedCount = $stats['jobs_by_status']['completed'] ?? 0;
$failedCount = $stats['jobs_by_status']['failed'] ?? 0;
$totalCount = $pendingCount + $processingCount + $completedCount + $failedCount;

// HTML output
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>FeatherQueue Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --primary-dark: #2980b9;
            --secondary-color: #2c3e50;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --light-bg: #f8f9fa;
            --dark-bg: #2c3e50;
            --border-radius: 8px;
            --sidebar-width: 260px;
            --header-height: 60px;
            --font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: var(--font-family);
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f5f7fa;
        }
        
        a {
            text-decoration: none;
            color: var(--primary-color);
        }
        
        .layout {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--dark-bg);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 20px;
            background-color: rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .sidebar-header h2 {
            margin: 0;
            font-size: 1.5em;
        }
        
        .sidebar-menu {
            padding: 0;
            list-style: none;
            margin: 20px 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            color: #ecf0f1;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(255,255,255,0.1);
            border-left-color: var(--primary-color);
        }
        
        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .sidebar-menu .badge {
            margin-left: auto;
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px;
            padding: 2px 8px;
            font-size: 12px;
        }
        
        .content-wrapper {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: all 0.3s;
        }
        
        .content-header {
            background-color: white;
            padding: 0 30px;
            height: var(--header-height);
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .toggle-sidebar {
            background: none;
            border: none;
            font-size: 1.5em;
            cursor: pointer;
            color: var(--secondary-color);
            display: none;
        }
        
        @media (max-width: 992px) {
            .toggle-sidebar {
                display: block;
            }
            .sidebar {
                margin-left: calc(var(--sidebar-width) * -1);
            }
            .content-wrapper {
                margin-left: 0;
            }
            .sidebar.active {
                margin-left: 0;
            }
            .content-wrapper.active {
                margin-left: var(--sidebar-width);
            }
        }
        
        .content {
            padding: 30px;
        }
        
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card .icon {
            font-size: 2em;
            margin-bottom: 10px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .stat-card .pending .icon {
            background-color: var(--warning-color);
        }
        
        .stat-card .processing .icon {
            background-color: var(--primary-color);
        }
        
        .stat-card .completed .icon {
            background-color: var(--success-color);
        }
        
        .stat-card .failed .icon {
            background-color: var(--danger-color);
        }
        
        .stat-card .number {
            font-size: 2em;
            font-weight: bold;
            margin: 5px 0;
            color: var(--secondary-color);
        }
        
        .stat-card .label {
            color: #7f8c8d;
            font-size: 0.9em;
        }
        
        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .card-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h2 {
            margin: 0;
            font-size: 1.25em;
            color: var(--secondary-color);
        }
        
        .card-body {
            padding: 20px;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--secondary-color);
        }
        
        tr:hover {
            background-color: #f9f9f9;
        }
        
        .job-actions {
            display: flex;
            gap: 5px;
        }
        
        .button {
            display: inline-block;
            padding: 8px 16px;
            background-color: var(--primary-color);
            color: white;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
            text-align: center;
        }
        
        .button:hover {
            background-color: var(--primary-dark);
            color: white;
        }
        
        .button.danger {
            background-color: var(--danger-color);
        }
        
        .button.danger:hover {
            background-color: #c0392b;
        }
        
        .button.success {
            background-color: var(--success-color);
        }
        
        .button.success:hover {
            background-color: #27ae60;
        }
        
        .button.sm {
            padding: 4px 10px;
            font-size: 12px;
        }
        
        .message {
            padding: 15px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid var(--success-color);
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid var(--danger-color);
        }
        
        .message i {
            margin-right: 10px;
            font-size: 1.2em;
        }
        
        form {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--secondary-color);
        }
        
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-family: var(--font-family);
            font-size: 14px;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .form-actions {
            margin-top: 20px;
            text-align: right;
        }
        
        .empty-state {
            text-align: center;
            padding: 30px;
            color: #7f8c8d;
        }
        
        .empty-state i {
            font-size: 3em;
            margin-bottom: 15px;
            color: #bdc3c7;
        }
        
        footer {
            margin-top: 30px;
            text-align: center;
            color: #7f8c8d;
            font-size: 14px;
            padding: 20px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .dashboard-stats {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            
            .content {
                padding: 15px;
            }
        }
        
        /* Add this for job details modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: var(--border-radius);
            width: 80%;
            max-width: 800px;
            animation: modalFadeIn 0.3s;
        }
        
        @keyframes modalFadeIn {
            from {opacity: 0; transform: translateY(-30px);}
            to {opacity: 1; transform: translateY(0);}
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #333;
        }
        
        /* Code display for payload */
        pre {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: var(--border-radius);
            overflow-x: auto;
            font-family: monospace;
            margin: 0;
        }
        
        code {
            font-family: monospace;
        }
        
        /* Progress bar for job attempts */
        .progress-container {
            width: 100%;
            height: 8px;
            background-color: #f0f0f0;
            border-radius: 4px;
            margin-top: 5px;
        }
        
        .progress-bar {
            height: 8px;
            border-radius: 4px;
            background-color: var(--primary-color);
        }
        
        .progress-bar.warning {
            background-color: var(--warning-color);
        }
        
        .progress-bar.danger {
            background-color: var(--danger-color);
        }
    </style>
</head>
<body>
    <div class="layout">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>FeatherQueue</h2>
            </div>
            
            <ul class="sidebar-menu">
                <li>
                    <a href="?view=overview" class="<?php echo $currentView === 'overview' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i> Overview
                    </a>
                </li>
                <li>
                    <a href="?view=pending" class="<?php echo $currentView === 'pending' ? 'active' : ''; ?>">
                        <i class="fas fa-clock"></i> Pending Jobs
                        <?php if($pendingCount > 0): ?>
                        <span class="badge"><?php echo $pendingCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="?view=processing" class="<?php echo $currentView === 'processing' ? 'active' : ''; ?>">
                        <i class="fas fa-spinner fa-spin"></i> Processing
                        <?php if($processingCount > 0): ?>
                        <span class="badge"><?php echo $processingCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="?view=completed" class="<?php echo $currentView === 'completed' ? 'active' : ''; ?>">
                        <i class="fas fa-check-circle"></i> Completed
                        <?php if($completedCount > 0): ?>
                        <span class="badge"><?php echo $completedCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="?view=failed" class="<?php echo $currentView === 'failed' ? 'active' : ''; ?>">
                        <i class="fas fa-exclamation-triangle"></i> Failed
                        <?php if($failedCount > 0): ?>
                        <span class="badge"><?php echo $failedCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="?view=add" class="<?php echo $currentView === 'add' ? 'active' : ''; ?>">
                        <i class="fas fa-plus-circle"></i> Add Job
                    </a>
                </li>
                <li>
                    <a href="?action=cleanup" onclick="return confirm('Are you sure you want to clean up old jobs?')">
                        <i class="fas fa-broom"></i> Cleanup Jobs
                    </a>
                </li>
                <li>
                    <a href="index.php">
                        <i class="fas fa-book"></i> Documentation
                    </a>
                </li>
            </ul>
            
            <div style="padding: 20px; font-size: 0.8em; opacity: 0.7; text-align: center;">
                FeatherQueue v1.0.0<br>
                <a href="https://github.com/yourusername/featherqueue" target="_blank" style="color: white; text-decoration: underline;">GitHub</a>
            </div>
        </div>
        
        <!-- Content -->
        <div class="content-wrapper" id="content-wrapper">
            <div class="content-header">
                <button class="toggle-sidebar" id="toggle-sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <h1><?php echo ucfirst($currentView); ?> Dashboard</h1>
                <div>
                    <?php if($currentView !== 'add'): ?>
                    <a href="?view=add" class="button success">
                        <i class="fas fa-plus"></i> Add Job
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="content">
                <?php if (!empty($message)): ?>
                <div class="message <?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($currentView === 'overview'): ?>
                <!-- Overview Dashboard -->
                <div class="dashboard-stats">
                    <div class="stat-card">
                        <div class="pending">
                            <div class="icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="number"><?php echo $pendingCount; ?></div>
                            <div class="label">Pending Jobs</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="processing">
                            <div class="icon">
                                <i class="fas fa-spinner"></i>
                            </div>
                            <div class="number"><?php echo $processingCount; ?></div>
                            <div class="label">Processing Jobs</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="completed">
                            <div class="icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="number"><?php echo $completedCount; ?></div>
                            <div class="label">Completed Jobs</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="failed">
                            <div class="icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="number"><?php echo $failedCount; ?></div>
                            <div class="label">Failed Jobs</div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Jobs -->
                <div class="card">
                    <div class="card-header">
                        <h2>Recent Pending Jobs</h2>
                        <a href="?view=pending" class="button sm">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (count($pendingJobs) > 0): ?>
                        <div class="table-responsive">
                            <table>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Execute At</th>
                                    <th>Actions</th>
                                </tr>
                                <?php foreach (array_slice($pendingJobs, 0, 5) as $job): ?>
                                <tr>
                                    <td><?php echo $job->getId(); ?></td>
                                    <td><?php echo $job->getName(); ?></td>
                                    <td><?php echo $job->getExecuteAt() ? date('Y-m-d H:i:s', $job->getExecuteAt()) : 'Now'; ?></td>
                                    <td class="job-actions">
                                        <a href="?action=process&job=<?php echo $job->getId(); ?>" class="button success sm">Process</a>
                                        <a href="?action=delete&job=<?php echo $job->getId(); ?>" class="button danger sm" onclick="return confirm('Are you sure?')">Delete</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-clock"></i>
                            <p>No pending jobs available.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Cache Stats -->
                <div class="card">
                    <div class="card-header">
                        <h2>Cache Information</h2>
                    </div>
                    <div class="card-body">
                        <p>Jobs in memory cache: <strong><?php echo $stats['cache_size']; ?></strong></p>
                    </div>
                </div>
                
                <?php elseif ($currentView === 'pending'): ?>
                <!-- Pending Jobs View -->
                <div class="card">
                    <div class="card-header">
                        <h2>Pending Jobs</h2>
                    </div>
                    <div class="card-body">
                        <?php if (count($pendingJobs) > 0): ?>
                        <div class="table-responsive">
                            <table>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Execute At</th>
                                    <th>Attempts</th>
                                    <th>Actions</th>
                                </tr>
                                <?php foreach ($pendingJobs as $job): ?>
                                <tr>
                                    <td><?php echo $job->getId(); ?></td>
                                    <td><?php echo $job->getName(); ?></td>
                                    <td><?php echo $job->getExecuteAt() ? date('Y-m-d H:i:s', $job->getExecuteAt()) : 'Now'; ?></td>
                                    <td>
                                        <?php echo $job->getAttempts(); ?> / <?php echo $job->getMaxAttempts(); ?>
                                        <div class="progress-container">
                                            <?php 
                                            $progress = ($job->getAttempts() / $job->getMaxAttempts()) * 100;
                                            $progressClass = $progress < 50 ? '' : ($progress < 80 ? 'warning' : 'danger');
                                            ?>
                                            <div class="progress-bar <?php echo $progressClass; ?>" style="width: <?php echo $progress; ?>%"></div>
                                        </div>
                                    </td>
                                    <td class="job-actions">
                                        <a href="?action=process&job=<?php echo $job->getId(); ?>" class="button success sm">Process</a>
                                        <a href="?action=delete&job=<?php echo $job->getId(); ?>" class="button danger sm" onclick="return confirm('Are you sure?')">Delete</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-clock"></i>
                            <p>No pending jobs available.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php elseif ($currentView === 'processing'): ?>
                <!-- Processing Jobs View -->
                <div class="card">
                    <div class="card-header">
                        <h2>Processing Jobs</h2>
                    </div>
                    <div class="card-body">
                        <?php if (count($processingJobs) > 0): ?>
                        <div class="table-responsive">
                            <table>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Started At</th>
                                    <th>Attempts</th>
                                    <th>Actions</th>
                                </tr>
                                <?php foreach ($processingJobs as $job): ?>
                                <tr>
                                    <td><?php echo $job->getId(); ?></td>
                                    <td><?php echo $job->getName(); ?></td>
                                    <td><?php echo $job->getMetadata('processing_started_at') ? date('Y-m-d H:i:s', $job->getMetadata('processing_started_at')) : 'Unknown'; ?></td>
                                    <td>
                                        <?php echo $job->getAttempts(); ?> / <?php echo $job->getMaxAttempts(); ?>
                                        <div class="progress-container">
                                            <?php 
                                            $progress = ($job->getAttempts() / $job->getMaxAttempts()) * 100;
                                            $progressClass = $progress < 50 ? '' : ($progress < 80 ? 'warning' : 'danger');
                                            ?>
                                            <div class="progress-bar <?php echo $progressClass; ?>" style="width: <?php echo $progress; ?>%"></div>
                                        </div>
                                    </td>
                                    <td class="job-actions">
                                        <a href="?action=delete&job=<?php echo $job->getId(); ?>" class="button danger sm" onclick="return confirm('Are you sure? This job is still processing.')">Force Delete</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-spinner"></i>
                            <p>No jobs are currently processing.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php elseif ($currentView === 'completed'): ?>
                <!-- Completed Jobs View -->
                <div class="card">
                    <div class="card-header">
                        <h2>Completed Jobs</h2>
                    </div>
                    <div class="card-body">
                        <?php if (count($completedJobs) > 0): ?>
                        <div class="table-responsive">
                            <table>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Completed At</th>
                                    <th>Duration</th>
                                    <th>Actions</th>
                                </tr>
                                <?php foreach ($completedJobs as $job): ?>
                                <tr>
                                    <td><?php echo $job->getId(); ?></td>
                                    <td><?php echo $job->getName(); ?></td>
                                    <td><?php echo $job->getMetadata('completed_at') ? date('Y-m-d H:i:s', $job->getMetadata('completed_at')) : 'Unknown'; ?></td>
                                    <td>
                                        <?php 
                                        $startTime = $job->getMetadata('processing_started_at');
                                        $endTime = $job->getMetadata('completed_at');
                                        if ($startTime && $endTime) {
                                            $duration = $endTime - $startTime;
                                            echo $duration . ' seconds';
                                        } else {
                                            echo 'Unknown';
                                        }
                                        ?>
                                    </td>
                                    <td class="job-actions">
                                        <a href="?action=delete&job=<?php echo $job->getId(); ?>" class="button danger sm" onclick="return confirm('Are you sure?')">Delete</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-check-circle"></i>
                            <p>No completed jobs available.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php elseif ($currentView === 'failed'): ?>
                <!-- Failed Jobs View -->
                <div class="card">
                    <div class="card-header">
                        <h2>Failed Jobs</h2>
                    </div>
                    <div class="card-body">
                        <?php if (count($failedJobs) > 0): ?>
                        <div class="table-responsive">
                            <table>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Failed At</th>
                                    <th>Attempts</th>
                                    <th>Actions</th>
                                </tr>
                                <?php foreach ($failedJobs as $job): ?>
                                <tr>
                                    <td><?php echo $job->getId(); ?></td>
                                    <td><?php echo $job->getName(); ?></td>
                                    <td><?php echo $job->getMetadata('failed_at') ? date('Y-m-d H:i:s', $job->getMetadata('failed_at')) : 'Unknown'; ?></td>
                                    <td><?php echo $job->getAttempts(); ?> / <?php echo $job->getMaxAttempts(); ?></td>
                                    <td class="job-actions">
                                        <a href="?action=process&job=<?php echo $job->getId(); ?>" class="button success sm">Retry</a>
                                        <a href="?action=delete&job=<?php echo $job->getId(); ?>" class="button danger sm" onclick="return confirm('Are you sure?')">Delete</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>No failed jobs available.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php elseif ($currentView === 'add'): ?>
                <!-- Add Job Form -->
                <div class="card">
                    <div class="card-header">
                        <h2>Add New Job</h2>
                    </div>
                    <div class="card-body">
                        <form method="post" action="?action=addjob">
                            <div class="form-group">
                                <label for="jobtype">Job Type:</label>
                                <input type="text" id="jobtype" name="jobtype" placeholder="e.g., email, notification, report" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="message">Message or Payload Description:</label>
                                <input type="text" id="message" name="message" placeholder="Brief description of job payload">
                            </div>
                            
                            <div class="form-group">
                                <label for="delay">Delay (seconds):</label>
                                <input type="number" id="delay" name="delay" value="0" min="0">
                                <small style="color: #7f8c8d;">Set to 0 for immediate execution, or specify delay in seconds</small>
                            </div>
                            
                            <div class="form-actions">
                                <button type="reset" class="button">Clear</button>
                                <button type="submit" class="button success">Add Job</button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <footer>
                <p>FeatherQueue Dashboard | Version 1.0.0 | <?php echo date('Y-m-d H:i:s'); ?></p>
            </footer>
        </div>
    </div>
    
    <script>
        // Toggle sidebar on mobile
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('toggle-sidebar');
            const sidebar = document.getElementById('sidebar');
            const contentWrapper = document.getElementById('content-wrapper');
            
            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                contentWrapper.classList.toggle('active');
            });
        });
    </script>
</body>
</html>