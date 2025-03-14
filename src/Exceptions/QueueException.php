<?php

namespace FeatherQueue\Exceptions;

/**
 * QueueException for FeatherQueue
 * 
 * Used for queue-specific exceptions
 */
class QueueException extends \Exception
{
    // Queue-specific exception codes
    const STORAGE_ERROR = 100;
    const JOB_NOT_FOUND = 101;
    const INVALID_JOB = 102;
    const HANDLER_NOT_FOUND = 200;
    const JOB_FAILED = 300;
    const MAX_ATTEMPTS_EXCEEDED = 301;
}