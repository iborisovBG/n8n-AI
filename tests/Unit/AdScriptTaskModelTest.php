<?php

namespace Tests\Unit;

use App\Models\AdScriptTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdScriptTaskModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test model fillable attributes.
     */
    public function test_model_has_correct_fillable_attributes(): void
    {
        $task = new AdScriptTask;

        $expectedFillable = [
            'reference_script',
            'outcome_description',
            'new_script',
            'analysis',
            'status',
            'error_details',
        ];

        $this->assertEquals($expectedFillable, $task->getFillable());
    }

    /**
     * Test model can be created with fillable attributes.
     */
    public function test_model_can_be_created_with_fillable_attributes(): void
    {
        $data = [
            'reference_script' => 'Test script',
            'outcome_description' => 'Test outcome',
            'new_script' => 'New script',
            'analysis' => 'Analysis',
            'status' => 'completed',
            'error_details' => null,
        ];

        $task = AdScriptTask::create($data);

        $this->assertDatabaseHas('ad_script_tasks', [
            'id' => $task->id,
            'reference_script' => 'Test script',
            'outcome_description' => 'Test outcome',
            'status' => 'completed',
        ]);
    }

    /**
     * Test model casts timestamps correctly.
     */
    public function test_model_casts_timestamps_to_datetime(): void
    {
        $task = AdScriptTask::factory()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $task->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $task->updated_at);
    }

    /**
     * Test model factory works correctly.
     */
    public function test_model_factory_creates_valid_instances(): void
    {
        $task = AdScriptTask::factory()->create();

        $this->assertInstanceOf(AdScriptTask::class, $task);
        $this->assertNotNull($task->reference_script);
        $this->assertNotNull($task->outcome_description);
        $this->assertNotNull($task->status);
    }

    /**
     * Test model factory states work correctly.
     */
    public function test_model_factory_states_work(): void
    {
        $completedTask = AdScriptTask::factory()->completed()->create();
        $failedTask = AdScriptTask::factory()->failed()->create();

        $this->assertEquals('completed', $completedTask->status);
        $this->assertNotNull($completedTask->new_script);
        $this->assertNotNull($completedTask->analysis);

        $this->assertEquals('failed', $failedTask->status);
        $this->assertNotNull($failedTask->error_details);
    }

    /**
     * Test model can be updated.
     */
    public function test_model_can_be_updated(): void
    {
        $task = AdScriptTask::factory()->create([
            'status' => 'pending',
        ]);

        $task->update([
            'status' => 'completed',
            'new_script' => 'Updated script',
            'analysis' => 'Updated analysis',
        ]);

        $this->assertEquals('completed', $task->status);
        $this->assertEquals('Updated script', $task->new_script);
        $this->assertEquals('Updated analysis', $task->analysis);
    }
}
