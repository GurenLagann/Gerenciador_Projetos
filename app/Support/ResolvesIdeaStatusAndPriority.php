<?php

namespace App\Support;

use App\Models\IdeaPriority;
use App\Models\IdeaStatus;

trait ResolvesIdeaStatusAndPriority
{
    /**
     * Swap `status`/`priority` codes for their `status_id`/`priority_id`
     * foreign keys, ready to pass to Idea::create()/update().
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function resolveStatusAndPriority(array $validated): array
    {
        if (array_key_exists('status', $validated)) {
            $validated['status_id'] = IdeaStatus::where('code', $validated['status'])->value('id');
            unset($validated['status']);
        }

        if (array_key_exists('priority', $validated)) {
            $validated['priority_id'] = IdeaPriority::where('code', $validated['priority'])->value('id');
            unset($validated['priority']);
        }

        return $validated;
    }
}
