<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

enum InstallSessionStatus: string
{
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Failed = 'failed';
}
