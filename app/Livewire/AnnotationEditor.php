<?php

namespace App\Livewire;

use App\Models\Annotation;
use App\Models\Idea;
use App\Models\Project;
use League\CommonMark\CommonMarkConverter;
use Livewire\Component;

class AnnotationEditor extends Component
{
    public string $annotatableType;
    public int $annotatableId;
    public string $title = '';
    public string $content = '';
    public string $color = '';
    public bool $showForm = false;
    public ?int $editingId = null;

    public function mount(string $annotatableType, int $annotatableId): void
    {
        $this->annotatableType = $annotatableType;
        $this->annotatableId = $annotatableId;
    }

    public function save(): void
    {
        $this->validate([
            'content' => 'required|string|min:1',
            'title' => 'nullable|string|max:255',
        ]);

        $model = $this->annotatableType === 'project'
            ? Project::findOrFail($this->annotatableId)
            : Idea::findOrFail($this->annotatableId);

        if ($this->editingId) {
            Annotation::findOrFail($this->editingId)->update([
                'title' => $this->title ?: null,
                'content' => $this->content,
                'color' => $this->color ?: null,
            ]);
        } else {
            $model->annotations()->create([
                'title' => $this->title ?: null,
                'content' => $this->content,
                'color' => $this->color ?: null,
            ]);
        }

        $this->reset(['title', 'content', 'color', 'showForm', 'editingId']);
        $this->dispatch('annotation-saved');
    }

    public function edit(int $id): void
    {
        $annotation = Annotation::findOrFail($id);
        $this->editingId = $id;
        $this->title = $annotation->title ?? '';
        $this->content = $annotation->content;
        $this->color = $annotation->color ?? '';
        $this->showForm = true;
    }

    public function delete(int $id): void
    {
        Annotation::findOrFail($id)->delete();
        $this->dispatch('annotation-saved');
    }

    public function pin(int $id): void
    {
        $annotation = Annotation::findOrFail($id);
        $annotation->update(['pinned' => !$annotation->pinned]);
        $this->dispatch('annotation-saved');
    }

    public function renderMarkdown(string $content): string
    {
        $converter = new CommonMarkConverter(['html_input' => 'strip', 'allow_unsafe_links' => false]);
        return $converter->convert($content)->getContent();
    }

    public function render()
    {
        $model = $this->annotatableType === 'project'
            ? Project::findOrFail($this->annotatableId)
            : Idea::findOrFail($this->annotatableId);

        $annotations = $model->annotations()->get();
        $converter = new CommonMarkConverter(['html_input' => 'strip', 'allow_unsafe_links' => false]);

        return view('livewire.annotation-editor', [
            'annotations' => $annotations,
            'converter' => $converter,
        ]);
    }
}
