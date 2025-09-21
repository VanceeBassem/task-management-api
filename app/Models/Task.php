<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'description',
        'assignee_id',
        'creator_id',
        'due_date',
        'status',
    ];
     protected $casts = [
        'due_date' => 'datetime',
    ];

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function dependencies()
    {
        // tasks this task depends on
        return $this->belongsToMany(Task::class, 'task_dependencies', 'task_id', 'depends_on_task_id');
    }

    public function dependents()
    {
        // tasks that depend on this task
        return $this->belongsToMany(Task::class, 'task_dependencies', 'depends_on_task_id', 'task_id');
    }

    public function dependenciesCompleted(): bool
    {
        return $this->dependencies()->where('status', '!=', 'completed')->count() === 0;
    }
}
