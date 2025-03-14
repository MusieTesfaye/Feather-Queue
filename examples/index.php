<?php

/**
 * FeatherQueue - Landing Page and Documentation
 */

require_once __DIR__ . '/../vendor/autoload.php';

use FeatherQueue\JobQueue;

// Optional: Display queue stats if you want to show them on the landing page
$queueDir = __DIR__ . '/queue_storage';
if (is_dir($queueDir)) {
    $queue = new JobQueue($queueDir);
    $stats = $queue->getStorage()->getStats();
} else {
    $stats = [
        'jobs_by_status' => [
            'pending' => 0,
            'processing' => 0,
            'completed' => 0,
            'failed' => 0
        ],
        'cache_size' => 0
    ];
}

// Active section
$currentSection = $_GET['section'] ?? 'home';
$validSections = ['home', 'documentation', 'examples', 'api'];

if (!in_array($currentSection, $validSections)) {
    $currentSection = 'home';
}

// HTML output
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>FeatherQueue - Lightweight PHP Job Queue</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/atom-one-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/languages/php.min.js"></script>
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
            --font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: var(--font-family);
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            scroll-behavior: smooth;
        }
        
        a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        a:hover {
            color: var(--primary-dark);
        }
        
        /* Navigation */
        nav {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            height: 70px;
        }
        
        .nav-logo {
            font-size: 1.5em;
            font-weight: bold;
            color: var(--secondary-color);
            display: flex;
            align-items: center;
        }
        
        .nav-logo i {
            margin-right: 10px;
            color: var(--primary-color);
        }
        
        .nav-menu {
            display: flex;
            list-style: none;
        }
        
        .nav-menu li {
            margin-left: 30px;
        }
        
        .nav-menu a {
            color: var(--secondary-color);
            font-weight: 500;
            padding: 8px 0;
            position: relative;
        }
        
        .nav-menu a:after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            display: block;
            margin-top: 5px;
            background-color: var(--primary-color);
            transition: width 0.3s;
        }
        
        .nav-menu a:hover:after, .nav-menu a.active:after {
            width: 100%;
        }
        
        .nav-menu a.active {
            color: var(--primary-color);
        }
        
        .nav-button {
            display: inline-block;
            padding: 8px 16px;
            background-color: var(--primary-color);
            color: white;
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .nav-button:hover {
            background-color: var(--primary-dark);
            color: white;
        }
        
        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5em;
            cursor: pointer;
            color: var(--secondary-color);
        }
        
        /* Hero Section */
        .hero {
            background-color: var(--dark-bg);
            background-image: linear-gradient(135deg, #2c3e50 0%, #4ca1af 100%);
            color: white;
            padding: 100px 0;
            text-align: center;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .hero h1 {
            font-size: 3.5em;
            margin-bottom: 20px;
            animation: fadeInUp 1s ease;
        }
        
        .hero p {
            font-size: 1.3em;
            margin-bottom: 30px;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            animation: fadeInUp 1s ease 0.2s;
            animation-fill-mode: both;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .hero-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            animation: fadeInUp 1s ease 0.4s;
            animation-fill-mode: both;
        }
        
        .button {
            display: inline-block;
            padding: 12px 24px;
            border-radius: var(--border-radius);
            font-weight: 600;
            transition: all 0.3s;
            text-align: center;
        }
        
        .button-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .button-primary:hover {
            background-color: var(--primary-dark);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 7px 14px rgba(0,0,0,0.1);
        }
        
        .button-secondary {
            background-color: rgba(255,255,255,0.1);
            color: white;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .button-secondary:hover {
            background-color: rgba(255,255,255,0.2);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 7px 14px rgba(0,0,0,0.1);
        }
        
        /* Features Section */
        .section {
            padding: 80px 0;
        }
        
        .section-alt {
            background-color: var(--light-bg);
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .section-header h2 {
            font-size: 2.5em;
            color: var(--secondary-color);
            margin-bottom: 15px;
        }
        
        .section-header p {
            color: #666;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .feature-card {
            background-color: white;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .feature-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--primary-color);
            font-size: 1.5em;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .feature-card h3 {
            font-size: 1.5em;
            margin-bottom: 15px;
            color: var(--secondary-color);
        }
        
        /* Documentation Section */
        .documentation {
            display: flex;
            gap: 40px;
        }
        
        .doc-sidebar {
            width: 250px;
            position: sticky;
            top: 100px;
            align-self: flex-start;
        }
        
        .doc-menu {
            list-style: none;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .doc-menu li a {
            display: block;
            padding: 12px 15px;
            color: var(--secondary-color);
            border-bottom: 1px solid #eee;
            transition: all 0.3s;
        }
        
        .doc-menu li a:hover, .doc-menu li a.active {
            background-color: rgba(52, 152, 219, 0.05);
            color: var(--primary-color);
            padding-left: 20px;
        }
        
        .doc-menu li:last-child a {
            border-bottom: none;
        }
        
        .doc-content {
            flex: 1;
        }
        
        .doc-section {
            margin-bottom: 60px;
        }
        
        .doc-section h3 {
            font-size: 1.8em;
            color: var(--secondary-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .doc-section p {
            margin-bottom: 15px;
        }
        
        /* Code Blocks */
        .code-block {
            position: relative;
            margin: 20px 0;
        }
        
        pre {
            background-color: #282c34;
            color: #abb2bf;
            padding: 20px;
            border-radius: var(--border-radius);
            overflow-x: auto;
            font-family: Consolas, Monaco, 'Andale Mono', monospace;
            line-height: 1.5;
            tab-size: 4;
            margin: 0;
        }
        
        .copy-button {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(255,255,255,0.1);
            color: #abb2bf;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .copy-button:hover {
            background-color: rgba(255,255,255,0.2);
        }
        
        .copy-feedback {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: var(--success-color);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            display: none;
        }
        
        .code-label {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background-color: rgba(0,0,0,0.3);
            color: #abb2bf;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 40px;
        }
        
        .stat-card {
            background-color: white;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--primary-color);
            font-size: 1.3em;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: var(--secondary-color);
            margin-bottom: 5px;
        }
        
        /* Footer */
        footer {
            background-color: var(--dark-bg);
            color: white;
            padding: 60px 0 30px;
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .footer-column h3 {
            color: white;
            font-size: 1.2em;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }
        
        .footer-column h3:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 30px;
            height: 2px;
            background-color: var(--primary-color);
        }
        
        .footer-column ul {
            list-style: none;
        }
        
        .footer-column ul li {
            margin-bottom: 10px;
        }
        
        .footer-column ul li a {
            color: rgba(255,255,255,0.7);
            transition: color 0.3s;
        }
        
        .footer-column ul li a:hover {
            color: white;
        }
        
        .footer-social {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }
        
        .social-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255,255,255,0.1);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .social-icon:hover {
            background-color: var(--primary-color);
            transform: translateY(-3px);
        }
        
        .footer-bottom {
            text-align: center;
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 30px;
            color: rgba(255,255,255,0.7);
            font-size: 0.9em;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .nav-menu {
                position: fixed;
                top: 70px;
                left: 0;
                background-color: white;
                width: 100%;
                flex-direction: column;
                align-items: center;
                padding: 20px 0;
                box-shadow: 0 10px 10px rgba(0,0,0,0.1);
                transform: translateY(-100%);
                opacity: 0;
                pointer-events: none;
                transition: all 0.3s;
            }
            
            .nav-menu.active {
                transform: translateY(0);
                opacity: 1;
                pointer-events: all;
            }
            
            .nav-menu li {
                margin: 15px 0;
            }
            
            .mobile-toggle {
                display: block;
            }
            
            .documentation {
                flex-direction: column;
            }
            
            .doc-sidebar {
                width: 100%;
                position: static;
                margin-bottom: 30px;
            }
            
            .hero h1 {
                font-size: 2.5em;
            }
            
            .hero p {
                font-size: 1.1em;
            }
            
            .hero-buttons {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav>
        <div class="nav-container">
            <a href="index.php" class="nav-logo">
                <i class="fas fa-layer-group"></i> FeatherQueue
            </a>
            
            <button class="mobile-toggle" id="mobile-toggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <ul class="nav-menu" id="nav-menu">
                <li><a href="?section=home" class="<?php echo $currentSection === 'home' ? 'active' : ''; ?>">Home</a></li>
                <li><a href="?section=documentation" class="<?php echo $currentSection === 'documentation' ? 'active' : ''; ?>">Documentation</a></li>
                <li><a href="?section=examples" class="<?php echo $currentSection === 'examples' ? 'active' : ''; ?>">Examples</a></li>
                <li><a href="dashboard.php" class="nav-button">Dashboard</a></li>
            </ul>
        </div>
    </nav>
    
    <?php if ($currentSection === 'home'): ?>
    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>FeatherQueue</h1>
            <p>A lightweight PHP job queue library with filesystem storage. No Redis, no MySQL, just PHP and simplicity.</p>
            <div class="hero-buttons">
                <a href="?section=documentation" class="button button-primary">Get Started</a>
                <a href="dashboard.php" class="button button-secondary">Dashboard</a>
            </div>
        </div>
    </section>
    
    <!-- Features Section -->
    <section class="section">
        <div class="container">
            <div class="section-header">
                <h2>Why Choose FeatherQueue?</h2>
                <p>A modern, efficient queue system with zero external dependencies.</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-plug-circle-xmark"></i>
                    </div>
                    <h3>No Dependencies</h3>
                    <p>Works without Redis, MySQL, or other databases. Just PHP and the filesystem, making it ideal for simple projects and rapid development.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3>Lightning Fast</h3>
                    <p>Optimized with in-memory caching for superior performance, even with filesystem storage. Fast enough for most workloads.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3>Delayed Jobs</h3>
                    <p>Schedule jobs to run at a later time with precise timing control. Perfect for notifications, reminders, and scheduled tasks.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-rotate"></i>
                    </div>
                    <h3>Recurring Jobs</h3>
                    <p>Set up jobs to run on a schedule—every few minutes, daily, weekly, or monthly with flexible configuration options.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-battery-three-quarters"></i>
                    </div>
                    <h3>Efficient Processing</h3>
                    <p>Smart polling with adaptive intervals reduces CPU usage during idle periods, optimizing resource utilization.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-gauge-high"></i>
                    </div>
                    <h3>Web Dashboard</h3>
                    <p>Monitor and manage jobs through a clean and intuitive web interface. View job statuses, process or retry jobs, and maintain your queue.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Stats Section -->
    <section class="section section-alt">
        <div class="container">
            <div class="section-header">
                <h2>Current Queue Status</h2>
                <p>Real-time metrics from your job queue</p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['jobs_by_status']['pending'] ?? 0; ?></div>
                    <div>Pending Jobs</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['jobs_by_status']['processing'] ?? 0; ?></div>
                    <div>Processing Jobs</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['jobs_by_status']['completed'] ?? 0; ?></div>
                    <div>Completed Jobs</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['jobs_by_status']['failed'] ?? 0; ?></div>
                    <div>Failed Jobs</div>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 40px;">
                <a href="dashboard.php" class="button button-primary">Open Dashboard</a>
            </div>
        </div>
    </section>
    
    <?php elseif ($currentSection === 'documentation'): ?>
    <!-- Documentation Section -->
    <section class="section">
        <div class="container">
            <div class="section-header">
                <h2>Documentation</h2>
                <p>Learn how to integrate and utilize FeatherQueue in your applications</p>
            </div>
            
            <div class="documentation">
                <div class="doc-sidebar">
                    <ul class="doc-menu">
                        <li><a href="#installation" class="active">Installation</a></li>
                        <li><a href="#basic-usage">Basic Usage</a></li>
                        <li><a href="#processing-jobs">Processing Jobs</a></li>
                        <li><a href="#delayed-jobs">Delayed Jobs</a></li>
                        <li><a href="#recurring-jobs">Recurring Jobs</a></li>
                        <li><a href="#worker-processes">Worker Processes</a></li>
                        <li><a href="#handling-failures">Handling Failures</a></li>
                        <li><a href="#dashboard">Using the Dashboard</a></li>
                    </ul>
                </div>
                
                <div class="doc-content">
                    <div id="installation" class="doc-section">
                        <h3>Installation</h3>
                        <p>The recommended way to install FeatherQueue is through Composer:</p>
                        
                        <div class="code-block">
                            <button class="copy-button"><i class="fas fa-copy"></i> Copy</button>
                            <div class="copy-feedback">Copied!</div>
                            <pre><code class="language-bash">composer require featherqueue/featherqueue</code></pre>
                            <div class="code-label">bash</div>
                        </div>
                        
                        <p>Alternatively, you can download the source files directly from GitHub:</p>
                        
                        <div class="code-block">
                            <button class="copy-button"><i class="fas fa-copy"></i> Copy</button>
                            <div class="copy-feedback">Copied!</div>
                            <pre><code class="language-bash">git clone https://github.com/MusieTesfaye/Feather-Queue.git</code></pre>
                            <div class="code-label">bash</div>
                        </div>
                        
                        <p>Once installed, make sure to set up a directory for queue storage with appropriate write permissions.</p>
                    </div>
                    
                    <div id="basic-usage" class="doc-section">
                        <h3>Basic Usage</h3>
                        <p>Create a new queue instance and add jobs to it:</p>
                        
                        <div class="code-block">
                            <button class="copy-button"><i class="fas fa-copy"></i> Copy</button>
                            <div class="copy-feedback">Copied!</div>
                            <pre><code class="language-php"><?php echo htmlspecialchars('// Create a queue instance
$queue = new FeatherQueue\JobQueue(__DIR__ . \'/queue_storage\');

// Add a job to the queue
$job = $queue->add(\'email\', [
    \'to\' => \'user@example.com\',
    \'subject\' => \'Welcome!\',
    \'body\' => \'Thanks for signing up!\'
]);

// Add a delayed job (runs after 5 minutes)
$delayedJob = $queue->later(\'notification\', [
    \'message\' => \'Your report is ready\'
], 300);'); ?></code></pre>
                            <div class="code-label">php</div>
                        </div>
                        
                        <p>Each job requires a type (e.g., 'email', 'notification') and a payload array containing the data needed to process the job.</p>
                    </div>
                    
                    <div id="processing-jobs" class="doc-section">
                        <h3>Processing Jobs</h3>
                        <p>Create a worker to process jobs from the queue:</p>
                        
                        <div class="code-block">
                            <button class="copy-button"><i class="fas fa-copy"></i> Copy</button>
                            <div class="copy-feedback">Copied!</div>
                            <pre><code class="language-php"><?php echo htmlspecialchars('// Create a worker
$worker = new FeatherQueue\Worker($queue);

// Register handlers for different job types
$worker->registerHandler(\'email\', function($payload, $job) {
    // Send an email...
    mail($payload[\'to\'], $payload[\'subject\'], $payload[\'body\']);
    return [\'sent\' => true, \'time\' => date(\'Y-m-d H:i:s\')];
});

$worker->registerHandler(\'notification\', function($payload, $job) {
    // Send a notification...
    // Your notification logic here
    return true;
});

// Process a single job
$result = $queue->processNext(function($payload, $job) use ($worker) {
    return $worker->processJob($job);
});

// Or process multiple jobs
$worker->run(10); // Process up to 10 jobs'); ?></code></pre>
                            <div class="code-label">php</div>
                        </div>
                        
                        <p>The worker processes jobs and executes the handler that corresponds to each job's type. Handlers receive the job payload and the job object itself.</p>
                    </div>
                    
                    <div id="delayed-jobs" class="doc-section">
                        <h3>Delayed Jobs</h3>
                        <p>Schedule jobs to be executed at a later time:</p>
                        
                        <div class="code-block">
                            <button class="copy-button"><i class="fas fa-copy"></i> Copy</button>
                            <div class="copy-feedback">Copied!</div>
                            <pre><code class="language-php"><?php echo htmlspecialchars('// Add a job to be executed after 30 seconds
$job = $queue->later(\'email\', [
    \'to\' => \'user@example.com\',
    \'subject\' => \'Reminder\',
    \'body\' => \'Don\'t forget your appointment tomorrow\'
], 30);

// Add a job to be executed at a specific timestamp
$timestamp = strtotime(\'tomorrow 9:00 AM\');
$job = $queue->laterAt(\'reminder\', [
    \'message\' => \'Meeting with the team\'
], $timestamp);'); ?></code></pre>
                            <div class="code-label">php</div>
                        </div>
                        
                        <p>Delayed jobs will remain in the queue but won't be processed until their execution time has been reached.</p>
                    </div>
                    
                    <div id="recurring-jobs" class="doc-section">
                        <h3>Recurring Jobs</h3>
                        <p>Set up jobs to run on a regular schedule:</p>
                        
                        <div class="code-block">
                            <button class="copy-button"><i class="fas fa-copy"></i> Copy</button>
                            <div class="copy-feedback">Copied!</div>
                            <pre><code class="language-php"><?php echo htmlspecialchars('// Create a scheduler
$scheduler = new FeatherQueue\Scheduler($queue);

// Run a job every 5 minutes
$scheduler->everyMinutes(5, \'stats\', [
    \'service\' => \'system_monitor\'
]);

// Run a job daily at midnight
$scheduler->daily(\'00:00\', \'backup\', [
    \'type\' => \'full\',
    \'destination\' => \'cloud_storage\'
]);

// Run a job weekly on Monday at 9am
$scheduler->weekly(1, \'09:00\', \'report\', [
    \'type\' => \'weekly_summary\'
]);

// Execute the scheduler (typically in a cron job)
$scheduler->run();'); ?></code></pre>
                            <div class="code-label">php</div>
                        </div>
                        
                        <p>The scheduler should be executed regularly (e.g., every minute via cron) to check for and create scheduled jobs at the appropriate times.</p>
                    </div>
                    
                    <div id="worker-processes" class="doc-section">
                        <h3>Worker Processes</h3>
                        <p>Run a long-running worker process to continuously process jobs:</p>
                        
                        <div class="code-block">
                            <button class="copy-button"><i class="fas fa-copy"></i> Copy</button>
                            <div class="copy-feedback">Copied!</div>
                            <pre><code class="language-php"><?php echo htmlspecialchars('// Create a worker
$worker = new FeatherQueue\Worker($queue);

// Register job handlers
$worker->registerHandler(\'email\', function($payload, $job) {
    // Process email job
});

// Set worker options
$options = [
    \'sleep\' => 5,         // Sleep 5 seconds when no jobs are available
    \'maxJobs\' => 0,       // Process unlimited jobs (0 = no limit)
    \'maxTime\' => 3600,    // Run for a maximum of 1 hour
];

// Start the worker loop
$worker->run($options);'); ?></code></pre>
                            <div class="code-label">php</div>
                        </div>
                        
                        <p>This creates a long-running process that will continuously check for and process jobs. Ideally, this should be run as a daemon or service.</p>
                    </div>
                    
                    <div id="handling-failures" class="doc-section">
                        <h3>Handling Failures</h3>
                        <p>FeatherQueue automatically handles failed jobs and allows for retries:</p>
                        
                        <div class="code-block">
                            <button class="copy-button"><i class="fas fa-copy"></i> Copy</button>
                            <div class="copy-feedback">Copied!</div>
                            <pre><code class="language-php"><?php echo htmlspecialchars('// Create a job with custom retry settings
$options = [
    \'maxAttempts\' => 5  // Try up to 5 times before marking as failed
];

$job = $queue->add(\'api_request\', [
    \'endpoint\' => \'https://api.example.com/data\',
    \'method\' => \'POST\'
], $options);

// In your handler, throw an exception to mark a job as failed
$worker->registerHandler(\'api_request\', function($payload, $job) {
    try {
        // Make API request...
        if ($response->status !== 200) {
            throw new Exception("API returned error: " . $response->message);
        }
        return [\'success\' => true];
    } catch (Exception $e) {
        // The job will be marked as failed and retried later
        throw $e;
    }
});

// Manually retry failed jobs
$failedJobs = $queue->getFailedJobs();
foreach ($failedJobs as $job) {
    $queue->retry($job);
}'); ?></code></pre>
                            <div class="code-label">php</div>
                        </div>
                        
                        <p>Failed jobs are stored in the queue for inspection and can be retried manually or automatically according to the retry settings.</p>
                    </div>
                    
                    <div id="dashboard" class="doc-section">
                        <h3>Using the Dashboard</h3>
                        <p>FeatherQueue comes with a web dashboard for monitoring and managing your queue:</p>
                        
                        <div class="code-block">
                            <button class="copy-button"><i class="fas fa-copy"></i> Copy</button>
                            <div class="copy-feedback">Copied!</div>
                            <pre><code class="language-php"><?php echo htmlspecialchars('// To use the dashboard, simply include the dashboard.php file in your web server
// or copy it to a location accessible by your web server

// The dashboard allows you to:
// - View queue statistics
// - Inspect pending, processing, completed, and failed jobs
// - Manually process or retry jobs
// - Delete jobs
// - Add test jobs

// Security note: In production, make sure to properly secure access to the dashboard
// using authentication and HTTPS'); ?></code></pre>
                            <div class="code-label">php</div>
                        </div>
                        
                        <p>The dashboard provides a user-friendly interface for monitoring your queue and performing common maintenance tasks.</p>
                        
                        <p style="margin-top: 30px;">
                            <a href="dashboard.php" class="button button-primary">Open Dashboard</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <?php elseif ($currentSection === 'examples'): ?>
    <!-- Examples Section -->
    <section class="section">
        <div class="container">
            <div class="section-header">
                <h2>Examples</h2>
                <p>Practical code examples to help you implement FeatherQueue in your projects</p>
            </div>
            
            <div class="documentation">
                <div class="doc-sidebar">
                    <ul class="doc-menu">
                        <li><a href="#basic-example" class="active">Basic Usage</a></li>
                        <li><a href="#email-queue">Email Queue</a></li>
                        <li><a href="#api-processing">API Request Processing</a></li>
                        <li><a href="#data-export">Data Export</a></li>
                        <li><a href="#image-processing">Image Processing</a></li>
                        <li><a href="#cron-setup">Cron Setup</a></li>
                    </ul>
                </div>
                
                <div class="doc-content">
                    <div id="basic-example" class="doc-section">
                        <h3>Basic Usage Example</h3>
                        <p>A simple example of setting up and using FeatherQueue:</p>
                        
                        <div class="code-block">
                            <button class="copy-button"><i class="fas fa-copy"></i> Copy</button>
                            <div class="copy-feedback">Copied!</div>
                            <pre><code class="language-php"><?php echo htmlspecialchars('<?php
// basic_usage.php
require_once __DIR__ . \'/vendor/autoload.php\';

use FeatherQueue\JobQueue;
use FeatherQueue\Worker;

// Create a queue instance
$queue = new JobQueue(__DIR__ . \'/queue_storage\');

// Add a few test jobs
$job1 = $queue->add(\'log\', [\'message\' => \'This is test job 1\']);
$job2 = $queue->add(\'log\', [\'message\' => \'This is test job 2\']);
$job3 = $queue->later(\'log\', [\'message\' => \'This is a delayed job\'], 10);

echo "Added jobs: {$job1->getId()}, {$job2->getId()}, {$job3->getId()}\n";

// Create a worker
$worker = new Worker($queue);

// Register a handler for \'log\' jobs
$worker->registerHandler(\'log\', function($payload, $job) {
    echo "Processing job {$job->getId()}: {$payload[\'message\']}\n";
    return [\'processed_at\' => date(\'Y-m-d H:i:s\')];
});

// Process jobs
echo "Processing jobs...\n";
$worker->run([\'maxJobs\' => 3]); // Process up to 3 jobs

echo "Done!\n";'); ?></code></pre>
                            <div class="code-label">php</div>
                        </div>
                    </div>
                    
                    <div id="email-queue" class="doc-section">
                        <h3>Email Queue Example</h3>
                        <p>Using FeatherQueue to handle email sending in the background:</p>
                        
                        <div class="code-block">
                            <button class="copy-button"><i class="fas fa-copy"></i> Copy</button>
                            <div class="copy-feedback">Copied!</div>
                            <pre><code class="language-php"><?php echo htmlspecialchars('<?php
// email_queue.php
require_once __DIR__ . \'/vendor/autoload.php\';

use FeatherQueue\JobQueue;
use FeatherQueue\Worker;

// In your application code, when you need to send an email
function queueEmail($to, $subject, $body) {
    $queue = new JobQueue(__DIR__ . \'/queue_storage\');
    
    $job = $queue->add(\'email\', [
        \'to\' => $to,
        \'subject\' => $subject,
        \'body\' => $body,
        \'queued_at\' => date(\'Y-m-d H:i:s\')
    ]);
    
    return $job->getId();
}

// Queue some test emails
$id1 = queueEmail(\'user1@example.com\', \'Welcome to our site\', \'Thank you for registering!\');
$id2 = queueEmail(\'user2@example.com\', \'Your order #12345\', \'Your order has shipped.\');

echo "Queued emails with IDs: {$id1}, {$id2}\n";

// In a separate worker process or script:
function processEmails() {
    $queue = new JobQueue(__DIR__ . \'/queue_storage\');
    $worker = new Worker($queue);
    
    $worker->registerHandler(\'email\', function($payload, $job) {
        echo "Sending email to {$payload[\'to\']}...\n";
        
        // Here you would use your preferred email library
        // For example, with PHPMailer:
        /*
        $mail = new PHPMailer(true);
        $mail->setFrom(\'noreply@example.com\', \'Your App\');
        $mail->addAddress($payload[\'to\']);
        $mail->Subject = $payload[\'subject\'];
        $mail->Body = $payload[\'body\'];
        $mail->send();
        */
        
        // For this example, we\'ll just pretend to send
        sleep(1); // Simulate sending time
        
        return [
            \'sent_at\' => date(\'Y-m-d H:i:s\'),
            \'success\' => true
        ];
    });
    
    // Process all pending email jobs
    $worker->run([\'maxJobs\' => 0, \'maxTime\' => 60]);
}

// Uncomment to process the emails
// processEmails();'); ?></code></pre>
                            <div class="code-label">php</div>
                        </div>
                    </div>
                    
                    <div id="api-processing" class="doc-section">
                        <h3>API Request Processing</h3>
                        <p>Using FeatherQueue to handle API requests in the background:</p>
                        
                        <div class="code-block">
                            <button class="copy-button"><i class="fas fa-copy"></i> Copy</button>
                            <div class="copy-feedback">Copied!</div>
                            <pre><code class="language-php"><?php echo htmlspecialchars('<?php
// api_processing.php
require_once __DIR__ . \'/vendor/autoload.php\';

use FeatherQueue\JobQueue;
use FeatherQueue\Worker;

// In your application code
function queueApiRequest($endpoint, $method, $data) {
    $queue = new JobQueue(__DIR__ . \'/queue_storage\');
    
    $job = $queue->add(\'api_request\', [
        \'endpoint\' => $endpoint,
        \'method\' => $method,
        \'data\' => $data,
        \'queued_at\' => time()
    ], [\'maxAttempts\' => 5]); // Retry up to 5 times
    
    return $job->getId();
}

// Queue some API requests
$id1 = queueApiRequest(\'https://api.example.com/users\', \'POST\', [\'name\' => \'John Doe\', \'email\' => \'john@example.com\']);
$id2 = queueApiRequest(\'https://api.example.com/orders\', \'GET\', [\'order_id\' => 12345]);

echo "Queued API requests with IDs: {$id1}, {$id2}\n";

// In a separate worker process or script:
function processApiRequests() {
    $queue = new JobQueue(__DIR__ . \'/queue_storage\');
    $worker = new Worker($queue);
    
    $worker->registerHandler(\'api_request\', function($payload, $job) {
        echo "Making {$payload[\'method\']} request to {$payload[\'endpoint\']}...\n";
        
        try {
            // Here you would use your preferred HTTP client
            // For example, with Guzzle:
            /*
            $client = new GuzzleHttp\\Client();
            $response = $client->request($payload[\'method\'], $payload[\'endpoint\'], [
                \'json\' => $payload[\'data\']
            ]);
            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();
            */
            
            // For this example, we\'ll simulate a response
            $statusCode = 200;
            $responseBody = json_encode([\'success\' => true]);
            
            // If the API returned an error, throw an exception to trigger a retry
            if ($statusCode >= 400) {
                throw new Exception("API error: Status {$statusCode}");
            }
            
            return [
                \'processed_at\' => date(\'Y-m-d H:i:s\'),
                \'status_code\' => $statusCode,
                \'response\' => $responseBody
            ];
        } catch (Exception $e) {
            // This will mark the job as failed and retry later if attempts remain
            throw new Exception("Failed to process API request: " . $e->getMessage());
        }
    });
    
    // Process API requests with a 5 second sleep when the queue is empty
    $worker->run([\'sleep\' => 5, \'maxTime\' => 3600]); // Run for an hour
}

// Uncomment to process the API requests
// processApiRequests();'); ?></code></pre>
                            <div class="code-label">php</div>
                        </div>
                    </div>
                    
                    <div id="data-export" class="doc-section">
                        <h3>Data Export Example</h3>
                        <p>Using FeatherQueue to handle large data exports:</p>
                        
                        <div class="code-block">
                            <button class="copy-button"><i class="fas fa-copy"></i> Copy</button>
                            <div class="copy-feedback">Copied!</div>
                            <pre><code class="language-php"><?php echo htmlspecialchars('<?php
// data_export.php
require_once __DIR__ . \'/vendor/autoload.php\';

use FeatherQueue\JobQueue;
use FeatherQueue\Worker;

// In your application code
function queueExport($userId, $format, $filters = []) {
    $queue = new JobQueue(__DIR__ . \'/queue_storage\');
    
    $job = $queue->add(\'data_export\', [
        \'user_id\' => $userId,
        \'format\' => $format, // csv, xlsx, json
        \'filters\' => $filters,
        \'queued_at\' => date(\'Y-m-d H:i:s\')
    ]);
    
    return $job->getId();
}

// Queue an export
$exportId = queueExport(123, \'csv\', [\'date_range\' => [\'start\' => \'2022-01-01\', \'end\' => \'2022-12-31\']]);
echo "Queued export with ID: {$exportId}\n";

// In a separate worker process or script:
function processExports() {
    $queue = new JobQueue(__DIR__ . \'/queue_storage\');
    $worker = new Worker($queue);
    
    $worker->registerHandler(\'data_export\', function($payload, $job) {
        echo "Processing export for user {$payload[\'user_id\']} in format {$payload[\'format\']}...\n";
        
        try {
            // 1. Fetch the data (simulate with a delay)
            echo "Fetching data...\n";
            sleep(2); // Simulate database query time
            
            // 2. Format the data
            echo "Formatting data as {$payload[\'format\']}...\n";
            sleep(1); // Simulate formatting time
            
            // 3. Save the file
            $filename = "export_{$payload[\'user_id\']}_" . time() . ".{$payload[\'format\']}";
            $filepath = __DIR__ . \'/exports/\' . $filename;
            
            // Create exports directory if it doesn\'t exist
            if (!is_dir(__DIR__ . \'/exports\')) {
                mkdir(__DIR__ . \'/exports\', 0755, true);
            }
            
            // Simulate file creation
            file_put_contents($filepath, "This is a simulated export file.");
            
            // 4. Notify the user (in a real app, you might email them)
            echo "Export complete. File saved at: {$filepath}\n";
            
            return [
                \'completed_at\' => date(\'Y-m-d H:i:s\'),
                \'file_path\' => $filepath,
                \'file_size\' => filesize($filepath)
            ];
        } catch (Exception $e) {
            // Log the error and rethrow
            error_log("Export failed: " . $e->getMessage());
            throw $e;
        }
    });
    
    // Process exports
    $worker->run([\'maxJobs\' => 10]);
}

// Uncomment to process the exports
// processExports();'); ?></code></pre>
                            <div class="code-label">php</div>
                        </div>
                    </div>
                    
                    <div id="image-processing" class="doc-section">
                        <h3>Image Processing Example</h3>
                        <p>Using FeatherQueue to handle image processing tasks:</p>
                        
                        <div class="code-block">
                            <button class="copy-button"><i class="fas fa-copy"></i> Copy</button>
                            <div class="copy-feedback">Copied!</div>
                            <pre><code class="language-php"><?php echo htmlspecialchars('<?php
// image_processing.php
require_once __DIR__ . \'/vendor/autoload.php\';

use FeatherQueue\JobQueue;
use FeatherQueue\Worker;

// In your application code
function queueImageProcessing($imagePath, $operations = []) {
    $queue = new JobQueue(__DIR__ . \'/queue_storage\');
    
    $job = $queue->add(\'image_processing\', [
        \'image_path\' => $imagePath,
        \'operations\' => $operations,
        \'queued_at\' => date(\'Y-m-d H:i:s\')
    ]);
    
    return $job->getId();
}

// Queue some image processing jobs
$id1 = queueImageProcessing(\'uploads/image1.jpg\', [
    [\'resize\', 800, 600],
    [\'watermark\', \'logo.png\', \'bottom-right\']
]);

$id2 = queueImageProcessing(\'uploads/image2.jpg\', [
    [\'crop\', 100, 100, 500, 500],
    [\'filter\', \'grayscale\']
]);

echo "Queued image processing jobs with IDs: {$id1}, {$id2}\n";

// In a separate worker process or script:
function processImages() {
    $queue = new JobQueue(__DIR__ . \'/queue_storage\');
    $worker = new Worker($queue);
    
    $worker->registerHandler(\'image_processing\', function($payload, $job) {
        $imagePath = $payload[\'image_path\'];
        $operations = $payload[\'operations\'];
        
        echo "Processing image: {$imagePath}\n";
        
        try {
            // Load the image (in a real app, you\'d use GD, Imagick, etc.)
            echo "Loading image...\n";
            // $image = new Imagick($imagePath);
            
            // Apply each operation
            foreach ($operations as $operation) {
                $type = $operation[0];
                
                echo "Applying {$type}...\n";
                
                switch ($type) {
                    case \'resize\':
                        $width = $operation[1];
                        $height = $operation[2];
                        echo "  Resizing to {$width}x{$height}\n";
                        // $image->resizeImage($width, $height, Imagick::FILTER_LANCZOS, 1);
                        break;
                        
                    case \'crop\':
                        $x = $operation[1];
                        $y = $operation[2];
                        $width = $operation[3];
                        $height = $operation[4];
                        echo "  Cropping to {$width}x{$height} from position {$x},{$y}\n";
                        // $image->cropImage($width, $height, $x, $y);
                        break;
                        
                    case \'watermark\':
                        $logoPath = $operation[1];
                        $position = $operation[2];
                        echo "  Adding watermark from {$logoPath} at {$position}\n";
                        // Add watermark code here
                        break;
                        
                    case \'filter\':
                        $filterType = $operation[1];
                        echo "  Applying filter: {$filterType}\n";
                        // Apply filter code here
                        break;
                }
                
                // Simulate processing time
                sleep(1);
            }
            
            // Save the processed image
            $outputPath = preg_replace(\'/(\.[^.]+)$/\', \'_processed$1\', $imagePath);
            echo "Saving processed image to {$outputPath}\n";
            // $image->writeImage($outputPath);
            
            // In this example, just touch the file
            touch($outputPath);
            
            return [
                \'completed_at\' => date(\'Y-m-d H:i:s\'),
                \'output_path\' => $outputPath
            ];
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            throw $e;
        }
    });
    
    // Process images
    $worker->run([\'maxJobs\' => 5]);
}

// Uncomment to process the images
// processImages();'); ?></code></pre>
                            <div class="code-label">php</div>
                        </div>
                    </div>
                    
                    <div id="cron-setup" class="doc-section">
                        <h3>Cron Setup Example</h3>
                        <p>Setting up cron jobs to run workers and schedulers:</p>
                        
                        <div class="code-block">
                            <button class="copy-button"><i class="fas fa-copy"></i> Copy</button>
                            <div class="copy-feedback">Copied!</div>
                            <pre><code class="language-bash"># Crontab entries for FeatherQueue

# Run scheduler every minute to check for recurring jobs
* * * * * php /path/to/your/app/scheduler.php >> /path/to/logs/scheduler.log 2>&1

# Run worker every 5 minutes to process queued jobs
*/5 * * * * php /path/to/your/app/worker.php >> /path/to/logs/worker.log 2>&1

# Run cleanup script once a day to remove old completed jobs
0 0 * * * php /path/to/your/app/cleanup.php >> /path/to/logs/cleanup.log 2>&1</code></pre>
                            <div class="code-label">bash</div>
                        </div>
                        
                        <p>Example of <code>scheduler.php</code>:</p>
                        
                        <div class="code-block">
                            <button class="copy-button"><i class="fas fa-copy"></i> Copy</button>
                            <div class="copy-feedback">Copied!</div>
                            <pre><code class="language-php"><?php echo htmlspecialchars('<?php
// scheduler.php
require_once __DIR__ . \'/vendor/autoload.php\';

use FeatherQueue\JobQueue;
use FeatherQueue\Scheduler;

$queue = new JobQueue(__DIR__ . \'/queue_storage\');
$scheduler = new Scheduler($queue);

// Define recurring jobs
$scheduler->everyMinutes(5, \'system_stats\', [\'type\' => \'server_monitoring\']);
$scheduler->hourly(\'aggregator\', [\'type\' => \'hourly_metrics\']);
$scheduler->daily(\'00:00\', \'backup\', [\'type\' => \'daily_backup\']);
$scheduler->weekly(1, \'09:00\', \'report\', [\'type\' => \'weekly_report\']);
$scheduler->monthly(1, \'01:00\', \'invoice\', [\'type\' => \'monthly_billing\']);

// Run the scheduler
$scheduler->run();

echo date(\'Y-m-d H:i:s\') . " - Scheduler run completed\n";'); ?></code></pre>
                            <div class="code-label">php</div>
                        </div>
                        
                        <p>Example of <code>worker.php</code>:</p>
                        
                        <div class="code-block">
                            <button class="copy-button"><i class="fas fa-copy"></i> Copy</button>
                            <div class="copy-feedback">Copied!</div>
                            <pre><code class="language-php"><?php echo htmlspecialchars('<?php
// worker.php
require_once __DIR__ . \'/vendor/autoload.php\';

use FeatherQueue\JobQueue;
use FeatherQueue\Worker;

$queue = new JobQueue(__DIR__ . \'/queue_storage\');
$worker = new Worker($queue);

// Register handlers
$worker->registerHandler(\'system_stats\', function($payload) {
    // Collect system stats...
    return [\'collected\' => true];
});

$worker->registerHandler(\'aggregator\', function($payload) {
    // Aggregate metrics...
    return [\'aggregated\' => true];
});

$worker->registerHandler(\'backup\', function($payload) {
    // Perform backup...
    return [\'backed_up\' => true];
});

$worker->registerHandler(\'report\', function($payload) {
    // Generate report...
    return [\'generated\' => true];
});

$worker->registerHandler(\'invoice\', function($payload) {
    // Generate invoices...
    return [\'billed\' => true];
});

// Process up to 50 jobs per run
echo date(\'Y-m-d H:i:s\') . " - Starting worker\n";
$processed = $worker->run([\'maxJobs\' => 50, \'maxTime\' => 290]); // Run for max 290 seconds (under 5 min)
echo date(\'Y-m-d H:i:s\') . " - Worker completed - processed {$processed} jobs\n";'); ?></code></pre>
                            <div class="code-label">php</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-column">
                    <h3>FeatherQueue</h3>
                    <p>A lightweight PHP job queue library with filesystem storage. Simple, yet powerful background processing for your PHP applications.</p>
                    <div class="footer-social">
                        <a href="https://github.com/MusieTesfaye/Feather-Queue.git" class="social-icon"><i class="fab fa-github"></i></a>
                        
                    </div>
                </div>
                
                <div class="footer-column">
                    <h3>Documentation</h3>
                    <ul>
                        <li><a href="?section=documentation#installation">Installation</a></li>
                        <li><a href="?section=documentation#basic-usage">Basic Usage</a></li>
                        <li><a href="?section=documentation#processing-jobs">Processing Jobs</a></li>
                        <li><a href="?section=documentation#recurring-jobs">Recurring Jobs</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Examples</h3>
                    <ul>
                        <li><a href="?section=examples#basic-example">Basic Example</a></li>
                        <li><a href="?section=examples#email-queue">Email Queue</a></li>
                        <li><a href="?section=examples#api-processing">API Processing</a></li>
                        <li><a href="?section=examples#image-processing">Image Processing</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Resources</h3>
                    <ul>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="https://github.com/MusieTesfaye/Feather-Queue.git" target="_blank">GitHub</a></li>
                        <li><a href="https://packagist.org/packages/featherqueue/featherqueue" target="_blank">Packagist</a></li>
                        <li><a href="https://github.com/MusieTesfaye/Feather-Queue/issues" target="_blank">Report Issues</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> FeatherQueue | Version 1.0.0</p>
            </div>
        </div>
    </footer>
    
    <script>
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileToggle = document.getElementById('mobile-toggle');
            const navMenu = document.getElementById('nav-menu');
            
            if (mobileToggle && navMenu) {
                mobileToggle.addEventListener('click', function() {
                    navMenu.classList.toggle('active');
                    
                    const icon = mobileToggle.querySelector('i');
                    if (icon) {
                        if (navMenu.classList.contains('active')) {
                            icon.classList.remove('fa-bars');
                            icon.classList.add('fa-times');
                        } else {
                            icon.classList.remove('fa-times');
                            icon.classList.add('fa-bars');
                        }
                    }
                });
            }
            
            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    
                    const targetId = this.getAttribute('href');
                    if (targetId === '#') return;
                    
                    const targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        window.scrollTo({
                            top: targetElement.offsetTop - 100,
                            behavior: 'smooth'
                        });
                    }
                });
            });
            
            // Code copy functionality
            document.querySelectorAll('.copy-button').forEach(button => {
                button.addEventListener('click', function() {
                    const codeBlock = this.nextElementSibling.nextElementSibling;
                    const text = codeBlock.textContent;
                    
                    navigator.clipboard.writeText(text).then(() => {
                        const feedback = this.parentNode.querySelector('.copy-feedback');
                        feedback.style.display = 'block';
                        
                        setTimeout(() => {
                            feedback.style.display = 'none';
                        }, 2000);
                    });
                });
            });
            
            // Initialize syntax highlighting
            hljs.highlightAll();
        });
    </script>
</body>
</html>