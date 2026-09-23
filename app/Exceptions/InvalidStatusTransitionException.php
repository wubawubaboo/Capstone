<?php

namespace App\Exceptions;

/**
 * Thrown by a model's transitionStatus()/reopen() method when the requested
 * status change isn't allowed from the record's current status. Rendered
 * globally in bootstrap/app.php into a back()->withErrors() redirect, so
 * callers don't need to catch this themselves.
 */
class InvalidStatusTransitionException extends \DomainException
{
}
