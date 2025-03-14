# FeatherQueue - A Lightweight PHP Job Queue Library

FeatherQueue is a simple, lightweight job queue library for PHP applications that need background processing without the overhead of Redis, databases, or other external dependencies. It uses the filesystem for storage and PHP's PCNTL extension for background process management.

## Features

- ✅ File-based job storage using JSON files
- ✅ Background job processing with PCNTL
- ✅ Support for delayed job execution
- ✅ Support for recurring jobs (minutely, hourly, daily, weekly, monthly)
- ✅ Job status tracking and monitoring
- ✅ Error handling and automatic retries
- ✅ CLI and web application compatibility
- ✅ No Redis or database dependencies
- ✅ Minimal configuration required

## Requirements

- PHP 8.0 or higher
- PHP JSON extension
- PHP FileInfo extension
- PHP PCNTL extension (for background processing)
- PHP POSIX extension (recommended for proper process management)

## Installation

```bash
composer require featherqueue/featherqueue
