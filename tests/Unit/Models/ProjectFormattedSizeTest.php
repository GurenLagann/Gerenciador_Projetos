<?php

namespace Tests\Unit\Models;

use App\Models\Project;
use Tests\TestCase;

class ProjectFormattedSizeTest extends TestCase
{
    public function test_it_formats_bytes(): void
    {
        $project = new Project(['size_bytes' => 500]);

        $this->assertSame('500 B', $project->formatted_size);
    }

    public function test_it_formats_kilobytes(): void
    {
        $project = new Project(['size_bytes' => 2048]);

        $this->assertSame('2 KB', $project->formatted_size);
    }

    public function test_it_formats_megabytes_with_one_decimal(): void
    {
        $project = new Project(['size_bytes' => (int) (1.5 * 1024 * 1024)]);

        $this->assertSame('1.5 MB', $project->formatted_size);
    }

    public function test_it_formats_gigabytes_with_one_decimal(): void
    {
        $project = new Project(['size_bytes' => (int) (2.25 * 1024 * 1024 * 1024)]);

        $this->assertSame('2.3 GB', $project->formatted_size);
    }

    public function test_it_returns_null_when_size_is_unknown(): void
    {
        $project = new Project(['size_bytes' => null]);

        $this->assertNull($project->formatted_size);
    }
}
