<?php

namespace Tests\Feature;

use App\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Facades\Tests\Setup\ProjectFactory;
use Tests\TestCase;

class TriggerActivityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function creating_a_project()
    {
        $project = ProjectFactory::create();

        $this->assertCount(1, $project->activity);
        $this->assertEquals('created', $project->activity->first()->description);
    }

    /** @test */
    public function updating_a_project()
    {
        $project = ProjectFactory::create();

        $project->update(['title' => 'changed']);

        $this->assertCount(2, $project->activity);
        $this->assertEquals('updated', $project->activity->last()->description);
    }

    /** @test */
    public function creating_a_new_task()
    {
        $project = ProjectFactory::create();
        $project->addTask('Task');

        $this->assertCount(2, $project->activity);

        tap($project->activity->last(), function ($activity) {
            $this->assertEquals('created_task', $activity->description);
            $this->assertInstanceOf(Task::class, $activity->subject);
            $this->assertEquals('Task', $activity->subject->body);
        });
    }

    /** @test */
    public function completing_task()
    {
        $project = ProjectFactory::withTasks(1)->create();

        $this->actingAs($project->user)
            ->patch($project->tasks->first()->path(), [
            'body' => 'text',
            'completed' => true,
        ]);

        $this->assertCount(3, $project->activity);
        tap($project->activity->last(), function ($activity) {
            $this->assertEquals('completed_task', $activity->description);
//            $this->assertInstanceOf(Task::class, $activity->subject);
        });
    }

    /** @test */
    public function incompleting_task()
    {
        $project = ProjectFactory::withTasks(1)->create();

        $this->actingAs($project->user)
            ->patch($project->tasks->first()->path(), [
            'body' => 'text',
            'completed' => true,
        ]);
        $this->assertCount(3, $project->activity);

        $this->actingAs($project->user)
            ->patch($project->tasks->first()->path(), [
                'body' => 'text',
                'completed' => false,
            ]);
        $this->assertCount(4, $project->refresh()->activity);

        $this->assertEquals('incompleted_task', $project->activity->last()->description);
    }

    /** @test */
    public function deleting_task()
    {
        $project = ProjectFactory::withTasks(1)->create();

        $project->tasks->first()->delete();
        $this->assertCount(3, $project->refresh()->activity);
        $this->assertEquals('deleted_task', $project->activity->last()->description);

    }

}
