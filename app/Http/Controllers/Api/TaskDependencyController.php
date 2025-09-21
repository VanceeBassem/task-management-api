<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskDependencyController extends Controller
{
    // add one or multiple dependencies: expects 'depends_on_ids' => [1,2,3] or 'depends_on_id' => 5
    public function store(Request $request, Task $task)
    {
        $user = $request->user();
        if (!$user->hasRole('manager')) {
            return response()->json(['message'=>'Forbidden'], 403);
        }

        $ids = $request->input('depends_on_ids') ?? [$request->input('depends_on_id')];
        $ids = array_filter(array_unique($ids));

        foreach ($ids as $dependsOnId) {
            if (!$dependsOnId || $dependsOnId == $task->id) {
                return response()->json(['message'=>'Invalid dependency'], 422);
            }

            $dependsOn = Task::find($dependsOnId);
            if (!$dependsOn) {
                return response()->json(['message'=>"Dependency task {$dependsOnId} not found"], 404);
            }

            // prevent circular dependency: ensure dependsOn does NOT (transitively) depend on $task
            if ($this->hasDependencyPath($dependsOn, $task)) {
                return response()->json(['message'=>'Adding this dependency would create a cycle'], 422);
            }

            $task->dependencies()->syncWithoutDetaching([$dependsOnId]);
        }

        $task->load('dependencies');
        return response()->json($task);
    }

    public function destroy(Request $request, Task $task, $dependsOnId)
    {
        $user = $request->user();
        if (!$user->hasRole('manager')) {
            return response()->json(['message'=>'Forbidden'], 403);
        }

        $task->dependencies()->detach($dependsOnId);
        $task->load('dependencies');

        return response()->json($task);
    }

    // helper: check if $start transitively depends on $target (i.e., start -> ... -> target)
    protected function hasDependencyPath(Task $start, Task $target): bool
    {
        $visited = [];
        $stack = [$start->id];

        while (!empty($stack)) {
            $currentId = array_pop($stack);
            if (isset($visited[$currentId])) continue;
            $visited[$currentId] = true;

            if ($currentId == $target->id) return true;

            $current = Task::with('dependencies')->find($currentId);
            if (!$current) continue;

            foreach ($current->dependencies as $dep) {
                if (!isset($visited[$dep->id])) {
                    $stack[] = $dep->id;
                }
            }
        }

        return false;
    }
}
