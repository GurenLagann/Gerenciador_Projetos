<?php

namespace App\Livewire;

use App\Services\ProjectScannerService;
use Livewire\Component;

class ScannerStatus extends Component
{
    public bool $scanning = false;

    public string $message = '';

    public array $results = [];

    public bool $fullWidth = false;

    public function scan(): void
    {
        $this->scanning = true;
        $this->message = 'Scanning...';

        $scanner = app(ProjectScannerService::class);
        $this->results = $scanner->scan();
        $this->scanning = false;
        $this->message = count($this->results).' projects found and imported.';

        $this->dispatch('scan-complete');
    }

    public function render()
    {
        return view('livewire.scanner-status');
    }
}
