<?php

namespace App\Livewire;

use App\Models\Item;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.app')]
class Workspace extends Component
{
    #[Locked]
    public ?string $folderId = null;

    #[Locked]
    public ?string $fileId = null;

    #[Locked]
    public ?string $editingId = null;

    #[Locked]
    public ?string $deleteId = null;

    public string $search = '';

    public ?string $modal = null;

    public string $name = '';

    public string $color = 'black';

    public bool $passwordEnabled = false;

    public string $newPassword = '';

    public string $content = '';

    public string $unlockPassword = '';

    public string $device = 'desktop';

    public bool $previewDraft = false;

    public function mount(?string $folder = null, ?string $file = null): void
    {
        $this->folderId = $folder;
        $this->fileId = $file;
    }

    public function openCreate(string $type): void
    {
        $this->requireAdmin();
        abort_unless(in_array($type, ['folder', 'file'], true), 404);
        abort_if($this->fileId !== null, 404);
        $this->reset(['editingId', 'name', 'newPassword', 'content', 'passwordEnabled', 'previewDraft']);
        $this->color = 'black';
        $this->modal = $type;
        $this->resetValidation();
    }

    public function openEdit(string $id): void
    {
        $this->requireAdmin();
        $item = $this->editableItem($id);
        $this->editingId = $item->id;
        $this->name = $item->name;
        $this->color = $item->color ?: 'black';
        $this->passwordEnabled = (bool) $item->password;
        $this->newPassword = '';
        $this->content = $item->content ?? '';
        $this->previewDraft = false;
        $this->modal = $item->type === 'folder' ? 'folder' : 'file';
        $this->resetValidation();
    }

    public function saveFolder(): void
    {
        $this->requireAdmin();
        abort_unless($this->modal === 'folder', 403);
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['required', Rule::in(['black', 'stone', 'blue', 'amber', 'emerald', 'rose'])],
            'passwordEnabled' => ['boolean'],
            'newPassword' => ['nullable', 'string', 'max:255'],
        ]);

        $item = $this->editingId ? $this->editableItem($this->editingId) : new Item([
            'id' => 'f_'.Str::random(12),
            'parent_id' => $this->folderId,
            'type' => 'folder',
        ]);
        abort_unless($item->type === 'folder', 404);

        if ($this->passwordEnabled && $this->newPassword === '' && ! $item->password) {
            $this->addError('newPassword', 'Masukkan password folder.');

            return;
        }

        $item->name = trim($this->name);
        $item->color = $this->color;
        $item->password = ! $this->passwordEnabled
            ? null
            : ($this->newPassword !== '' ? Hash::make($this->newPassword) : $item->password);
        $item->save();
        $this->closeModal();
    }

    public function saveFile(): void
    {
        $this->requireAdmin();
        abort_unless($this->modal === 'file', 403);
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
        ]);

        $item = $this->editingId ? $this->editableItem($this->editingId) : new Item([
            'id' => 'n_'.Str::random(12),
            'parent_id' => $this->folderId,
        ]);
        abort_if($item->exists && $item->type === 'folder', 404);
        $item->name = trim($this->name);
        $item->type = preg_match('/\.html?$/i', $item->name) ? 'html' : 'note';
        $item->content = $this->content;
        $item->save();
        $this->closeModal();
    }

    public function confirmDelete(string $id): void
    {
        $this->requireAdmin();
        $this->editableItem($id);
        $this->deleteId = $id;
        $this->modal = 'delete';
    }

    public function deleteItem(): void
    {
        $this->requireAdmin();
        abort_unless($this->modal === 'delete' && $this->deleteId, 403);
        $item = $this->editableItem($this->deleteId);
        $parentId = $item->parent_id;

        DB::transaction(function () use ($item) {
            $ids = [$item->id];
            $seen = [$item->id => true];
            for ($position = 0; $position < count($ids); $position++) {
                foreach (Item::where('parent_id', $ids[$position])->pluck('id') as $id) {
                    if (! isset($seen[$id])) {
                        $seen[$id] = true;
                        $ids[] = $id;
                    }
                }
            }
            Item::whereIn('id', $ids)->delete();
        });

        $this->closeModal();
        if ($this->fileId === $item->id) {
            $this->redirect($parentId ? route('folders.show', $parentId) : route('home'), navigate: true);
        }
    }

    public function unlock(): void
    {
        $this->validate(['unlockPassword' => ['required', 'string']]);
        $locked = Item::lockedAncestor($this->currentItem());
        abort_unless($locked, 404);
        if (! $locked->passwordMatches($this->unlockPassword)) {
            $this->addError('unlockPassword', 'Password folder salah.');
            $this->unlockPassword = '';

            return;
        }
        $unlocked = session('unlocked_folders', []);
        $unlocked[] = $locked->id;
        session(['unlocked_folders' => array_values(array_unique($unlocked))]);
        $this->unlockPassword = '';
        $this->resetValidation();
    }

    public function closeModal(): void
    {
        $this->modal = null;
        $this->editingId = null;
        $this->deleteId = null;
        $this->newPassword = '';
        $this->previewDraft = false;
        $this->resetValidation();
    }

    public function logout(): void
    {
        session()->forget(['admin_auth', 'unlocked_folders']);
        session()->invalidate();
        session()->regenerateToken();
        $this->redirect(route('home'), navigate: true);
    }

    private function requireAdmin(): void
    {
        abort_unless(session('admin_auth') === true, 403);
    }

    private function editableItem(string $id): Item
    {
        $item = Item::findOrFail($id);
        abort_unless(
            ($this->fileId === $id) || ($this->fileId === null && $item->parent_id === $this->folderId),
            404
        );

        return $item;
    }

    private function currentItem(): ?Item
    {
        if ($this->fileId !== null) {
            $item = Item::findOrFail($this->fileId);
            abort_if($item->type === 'folder', 404);

            return $item;
        }
        if ($this->folderId !== null) {
            $item = Item::findOrFail($this->folderId);
            abort_unless($item->type === 'folder', 404);

            return $item;
        }

        return null;
    }

    public function render()
    {
        $current = $this->currentItem();
        $locked = Item::lockedAncestor($current);
        $breadcrumbs = [];
        $cursor = $locked ?? $current;
        $seen = [];
        while ($cursor !== null && ! isset($seen[$cursor->id])) {
            $seen[$cursor->id] = true;
            array_unshift($breadcrumbs, $cursor);
            $cursor = $cursor->parent_id ? Item::find($cursor->parent_id) : null;
        }

        $items = collect();
        if ($this->fileId === null && $locked === null) {
            $items = Item::query()
                ->withCount('children')
                ->where('parent_id', $this->folderId)
                ->when(trim($this->search) !== '', fn ($query) => $query->where('name', 'like', '%'.trim($this->search).'%'))
                ->orderByRaw("CASE WHEN type = 'folder' THEN 0 ELSE 1 END")
                ->orderBy('created_at')
                ->get();
        }

        return view('livewire.workspace', compact('current', 'locked', 'breadcrumbs', 'items'));
    }
}
