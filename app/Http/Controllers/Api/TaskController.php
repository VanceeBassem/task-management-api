<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function index(Request $request)
  {
    $user = $request->user();
    $query = Task::with(['assignee', 'creator', 'dependencies']);

    // managers can filter all tasks, users only see their tasks
    if (!$user->hasRole('manager')) {
      $query->where('assignee_id', $user->id);
    } else {
      if ($request->filled('status')) $query->where('status', $request->status);
      if ($request->filled('assignee_id')) $query->where('assignee_id', $request->assignee_id);
      if ($request->filled('due_from')) $query->where('due_date', '>=', $request->due_from);
      if ($request->filled('due_to')) $query->where('due_date', '<=', $request->due_to);
    }

    $perPage = $request->get('per_page', 15);
    $tasks = $query->paginate($perPage);

    return response()->json($tasks);
  }


  /**
   * Store a newly created resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function store(StoreTaskRequest $request)
  {
    $user = $request->user();
    if (!$user->hasRole('manager')) {
      return response()->json(['message' => 'Forbidden'], 403);
    }

    $data = $request->validated();
    $data['creator_id'] = $user->id;

    $task = Task::create($data);

    return response()->json($task, 201);
  }
  /**
   * Display the specified resource.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function show(Request $request, Task $task)
  {
    $user = $request->user();

    if (!$user->hasRole('manager') && $task->assignee_id !== $user->id) {
      return response()->json(['message' => 'Forbidden'], 403);
    }

    $task->load(['assignee', 'creator', 'dependencies', 'dependents']);
    return response()->json($task);
  }


  /**
   * Update the specified resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function update(UpdateTaskRequest $request, Task $task)
  {
    $user = $request->user();

    // manager full update
    if ($user->hasRole('manager')) {
      $task->update($request->validated());
      return response()->json($task);
    }

    // non-manager: must be assignee and can only change status
    if ($task->assignee_id !== $user->id) {
      return response()->json(['message' => 'Forbidden'], 403);
    }

    $validated = $request->validated();

    // when completing task, check deps
    if (isset($validated['status']) && $validated['status'] === 'completed') {
      if (!$task->dependenciesCompleted()) {
        return response()->json(['message' => 'Cannot complete task until all dependencies are completed.'], 422);
      }
    }

    $task->update(['status' => $validated['status']]);
    return response()->json($task);
  }

  public function updateStatus(Request $request, Task $task)
  {
    $request->validate([
      'status' => 'required|in:pending,completed,canceled',
    ]);

    $user = $request->user();

    // 🚫 Block managers from changing status
    if ($user->role === 'manager') {
      return response()->json(['message' => 'Managers cannot update task status'], 403);
    }

    // ✅ Allow only users to update their own tasks
    if ($user->role === 'user' && $task->assignee_id !== $user->id) {
      return response()->json(['message' => 'You can only update status of your own tasks'], 403);
    }

    // ⛔ Prevent completing if dependencies are not done
    if ($request->status === 'completed') {
      $incompleteDeps = $task->dependencies()->where('status', '!=', 'completed')->count();
      if ($incompleteDeps > 0) {
        return response()->json([
          'message' => 'Cannot complete task until all dependencies are completed'
        ], 422);
      }
    }

    $task->status = $request->status;
    $task->save();

    return response()->json($task);
  }


  /**
   * Remove the specified resource from storage.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function destroy($id)
  {
    //
  }
}
