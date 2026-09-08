<?php
namespace App\Events;

use App\Models\Report;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SosTriggered implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $report;

    public function __construct(Report $report)
    {
        $this->report = $report;
    }

    public function broadcastOn()
    {
        return new Channel('emergency-alerts');
    }

    public function broadcastAs()
    {
        return 'SosTriggered';
    }
}