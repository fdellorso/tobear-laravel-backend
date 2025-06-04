<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\TaskResource;
use App\Models\Task;
use Illuminate\Http\Request;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return TaskResource::collection(Task::where('user_id', $request->user()->id)->orderBy('order')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTaskRequest $request)
    {
        $all = $request->all();

        $maxOrder = $request->user()->tasks()->max('order') ?? 0;

        $data = [
            'title' => $all['title'],
            'description' => $all['description'],
            'order' => $maxOrder + 1,
            'user_id' => $request->user()->id,
        ];

        $task = Task::create($data);
        return new TaskResource($task);
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'tasks' => 'required|array',
            'tasks.*' => 'integer|exists:tasks,id',
        ]);

        $user = $request->user();
        $taskIds = $request->input('tasks');

        // Controlla che tutte le task appartengano all'utente loggato
        $userTaskIds = $user->tasks()->pluck('id')->toArray();
        $invalidIds = array_diff($taskIds, $userTaskIds);

        if (!empty($invalidIds)) {
            return response()->json([
                'message' => 'Alcune task non appartengono all’utente.'
            ], 403);
        }

        // Aggiorna il campo order in base alla posizione
        foreach ($taskIds as $index => $taskId) {
            Task::where('id', $taskId)->update(['order' => $index]);
        }

        return response()->json([
            'message' => 'Ordine aggiornato con successo.',
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Task $task)
    {
        $this->authorizeTask($request, $task);

        return new TaskResource($task);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTaskRequest $request, Task $task)
    {
        if ($request->user()->id != $task->user_id) {
            abort(403, 'Unauthorized');
        }

        $task->update($request->validated());
        return new TaskResource($task);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Task $task)
    {
        $this->authorizeTask($request, $task);
        $task->delete();
        return response()->json(['message' => 'Task eliminato.'], 200);
    }

    protected function authorizeTask(Request $request, Task $task): void
    {
        if ($request->user()->id != $task->user_id) {
            abort(403, 'Unauthorized');
        }
    }
}
