<?php

namespace App\Enums;

enum MediationStatus: string
{
    case Scheduled = 'Scheduled';
    case Resolved = 'Resolved';
    case Failed = 'Failed';
}
