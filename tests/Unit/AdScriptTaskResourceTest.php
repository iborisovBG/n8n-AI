<?php

namespace Tests\Unit;

use App\Http\Resources\AdScriptTaskResource;
use App\Models\AdScriptTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\MissingValue;
use Tests\TestCase;

class AdScriptTaskResourceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test resource transformation for completed task.
     */
    public function test_resource_transforms_completed_task_correctly(): void
    {
        $task = AdScriptTask::factory()->completed()->create([
            'created_at' => now()->subSeconds(120),
            'updated_at' => now(),
        ]);

        $resource = new AdScriptTaskResource($task);
        $array = $resource->toArray(new Request);

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('reference_script', $array);
        $this->assertArrayHasKey('outcome_description', $array);
        $this->assertArrayHasKey('new_script', $array);
        $this->assertArrayHasKey('analysis', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
        $this->assertArrayHasKey('processing_time', $array);
        $this->assertEquals('completed', $array['status']);
        $this->assertEquals(120, $array['processing_time']);
        // error_details should not be present for completed tasks (MissingValue)
        if (array_key_exists('error_details', $array)) {
            $this->assertInstanceOf(MissingValue::class, $array['error_details']);
        }
    }

    /**
     * Test resource transformation for failed task.
     */
    public function test_resource_includes_error_details_for_failed_task(): void
    {
        $task = AdScriptTask::factory()->failed()->create([
            'error_details' => 'Test error message',
        ]);

        $resource = new AdScriptTaskResource($task);
        $array = $resource->toArray(new Request);

        $this->assertArrayHasKey('error_details', $array);
        $this->assertEquals('Test error message', $array['error_details']);
        // processing_time should not be present for failed tasks (MissingValue)
        if (array_key_exists('processing_time', $array)) {
            $this->assertInstanceOf(MissingValue::class, $array['processing_time']);
        }
    }

    /**
     * Test resource transformation for pending task.
     */
    public function test_resource_transforms_pending_task_correctly(): void
    {
        $task = AdScriptTask::factory()->create([
            'status' => 'pending',
        ]);

        $resource = new AdScriptTaskResource($task);
        $array = $resource->toArray(new Request);

        $this->assertEquals('pending', $array['status']);
        $this->assertNull($array['new_script']);
        $this->assertNull($array['analysis']);
        // error_details and processing_time should not be present for pending tasks (MissingValue)
        if (array_key_exists('error_details', $array)) {
            $this->assertInstanceOf(MissingValue::class, $array['error_details']);
        }
        if (array_key_exists('processing_time', $array)) {
            $this->assertInstanceOf(MissingValue::class, $array['processing_time']);
        }
    }

    /**
     * Test resource date formatting.
     */
    public function test_resource_formats_dates_correctly(): void
    {
        $task = AdScriptTask::factory()->create([
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $resource = new AdScriptTaskResource($task);
        $array = $resource->toArray(new Request);

        $this->assertIsString($array['created_at']);
        $this->assertIsString($array['updated_at']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $array['created_at']);
    }

    /**
     * Test resource handles null timestamps.
     */
    public function test_resource_handles_null_timestamps(): void
    {
        $task = AdScriptTask::factory()->create([
            'status' => 'pending',
        ]);

        // Manually set timestamps to null for testing
        $task->created_at = null;
        $task->updated_at = null;

        $resource = new AdScriptTaskResource($task);
        $array = $resource->toArray(new Request);

        $this->assertNull($array['created_at']);
        $this->assertNull($array['updated_at']);
        // processing_time should not be calculated when timestamps are null (MissingValue)
        if (array_key_exists('processing_time', $array)) {
            $this->assertInstanceOf(MissingValue::class, $array['processing_time']);
        }
    }
}
