<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

class AdScriptTaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $array = [
            'id' => $this->id,
            'reference_script' => $this->reference_script,
            'outcome_description' => $this->outcome_description,
            'new_script' => $this->new_script,
            'analysis' => $this->analysis,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];

        // Only include error_details for failed tasks
        if ($this->status === 'failed') {
            $array['error_details'] = $this->error_details;
        } else {
            $array['error_details'] = new MissingValue;
        }

        // Only include processing_time for completed tasks
        if ($this->status === 'completed' && $this->created_at && $this->updated_at) {
            $array['processing_time'] = $this->created_at->diffInSeconds($this->updated_at);
        } else {
            $array['processing_time'] = new MissingValue;
        }

        return $array;
    }
}

