<?php

namespace Tests\Feature;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_tasks_index_renders_with_tasks(): void
    {
        Task::factory()->create(['title' => 'Buy milk']);

        $this->get('/tasks')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Tasks/Index')
                ->has('tasks', 1)
                ->where('tasks.0.title', 'Buy milk')
            );
    }

    public function test_a_task_can_be_created(): void
    {
        $this->post('/tasks', [
            'title' => 'Write report',
            'description' => 'Quarterly report',
        ])->assertRedirect('/tasks');

        $this->assertDatabaseHas('tasks', [
            'title' => 'Write report',
            'completed' => false,
        ]);
    }

    public function test_a_task_requires_a_title(): void
    {
        $this->post('/tasks', ['title' => ''])
            ->assertSessionHasErrors('title');
    }

    public function test_a_task_can_be_marked_completed(): void
    {
        $task = Task::factory()->create(['completed' => false]);

        $this->put("/tasks/{$task->id}", ['completed' => true])
            ->assertRedirect('/tasks');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'completed' => true,
        ]);
    }

    public function test_a_task_can_be_deleted(): void
    {
        $task = Task::factory()->create();

        $this->delete("/tasks/{$task->id}")
            ->assertRedirect('/tasks');

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }
}
