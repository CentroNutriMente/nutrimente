<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Task;
use Illuminate\Console\Command;

class NotifyUpcomingTasks extends Command
{
    protected $signature   = 'notifications:upcoming-tasks';
    protected $description = 'Send notifications for tasks due tomorrow';

    public function handle(): void
    {
        $tomorrow = now()->addDay()->toDateString();

        Task::whereDate('due_date', $tomorrow)
            ->whereNull('completed_at')
            ->get()
            ->each(function (Task $task) {
                Notification::send(
                    $task->user_id,
                    'task_due',
                    'Attività in scadenza domani',
                    $task->title,
                    ['task_user_id' => $task->user_id]
                );
            });

        $this->info('Done.');
    }
}
