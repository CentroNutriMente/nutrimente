<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(): Response
    {
        $professionals = User::whereHas('roles')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($u) => [
                'id'   => $u->id,
                'name' => $u->name,
            ]);

        $tasks = Task::with('createdBy')
            ->whereIn('user_id', $professionals->pluck('id'))
            ->orderByRaw("completed_at IS NOT NULL, priority DESC, due_date ASC NULLS LAST, created_at ASC")
            ->get()
            ->map(fn ($t) => [
                'id'           => $t->id,
                'user_id'      => $t->user_id,
                'title'        => $t->title,
                'notes'        => $t->notes,
                'due_date'     => $t->due_date?->format('Y-m-d'),
                'priority'     => $t->priority,
                'completed'    => ! is_null($t->completed_at),
                'created_by_name' => $t->createdBy->name,
                'created_at'   => $t->created_at->format('Y-m-d'),
            ]);

        return Inertia::render('Workspace/Index', [
            'professionals' => $professionals,
            'tasks'         => $tasks,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id'  => 'required|exists:users,id',
            'title'    => 'required|string|max:500',
            'notes'    => 'nullable|string',
            'due_date' => 'nullable|date',
            'priority' => 'in:low,medium,high',
        ]);

        Task::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        return back();
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'title'     => 'sometimes|required|string|max:500',
            'notes'     => 'nullable|string',
            'due_date'  => 'nullable|date',
            'priority'  => 'sometimes|in:low,medium,high',
            'completed' => 'sometimes|boolean',
        ]);

        if (array_key_exists('completed', $validated)) {
            $validated['completed_at'] = $validated['completed'] ? now() : null;
            unset($validated['completed']);
        }

        $task->update($validated);

        return back();
    }

    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();
        return back();
    }
}
