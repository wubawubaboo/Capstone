<?php

namespace App\Exceptions;

/**
 * Thrown by PhilSmsService when a message could not be delivered for a
 * reason that may clear up on its own (network failure, provider outage),
 * so the queued SendSmsJob retries it.
 */
class SmsDeliveryException extends \RuntimeException
{
}
