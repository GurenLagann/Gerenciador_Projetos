<?php

namespace Tests\Feature\Models;

use App\Models\Project;
use App\Models\ProjectStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnotationSearchableContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_searchable_content_falls_back_to_a_generic_title_when_the_parent_is_soft_deleted_and_the_annotation_has_no_title(): void
    {
        $project = Project::create([
            'name' => 'Alpha',
            'slug' => 'alpha',
            'path' => '/tmp/alpha',
            'status_id' => ProjectStatus::where('code', 'planning')->value('id'),
        ]);

        $annotation = $project->annotations()->create(['content' => 'Some note']);

        $project->delete();

        [$title, $text] = $annotation->fresh()->searchableContent();

        $this->assertSame('Annotation', $title);
        $this->assertSame('Some note', $text);
    }
}
