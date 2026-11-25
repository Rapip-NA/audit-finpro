<?php

namespace Tests\Feature;

use App\Livewire\Login;
use App\Livewire\Workspace;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuditWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_recursively_delete_a_folder(): void
    {
        $this->withSession(['admin_auth' => true]);

        Livewire::test(Workspace::class)
            ->call('openCreate', 'folder')
            ->set('name', 'Tim A')
            ->set('color', 'blue')
            ->call('saveFolder')
            ->assertSee('Tim A');

        $folder = Item::where('name', 'Tim A')->firstOrFail();
        $this->assertSame('folder', $folder->type);

        Livewire::test(Workspace::class, ['folder' => $folder->id])
            ->call('openCreate', 'file')
            ->set('name', 'audit.html')
            ->set('content', '<h1>Audit</h1>')
            ->call('saveFile');

        $this->assertDatabaseHas('items', ['parent_id' => $folder->id, 'name' => 'audit.html']);
        $file = Item::where('name', 'audit.html')->firstOrFail();
        $this->get(route('files.show', $file->id))
            ->assertOk()
            ->assertSee('Live Preview')
            ->assertSee('wire:navigate', false);

        Livewire::test(Workspace::class)
            ->call('confirmDelete', $folder->id)
            ->call('deleteItem');

        $this->assertDatabaseMissing('items', ['id' => $folder->id]);
        $this->assertDatabaseMissing('items', ['name' => 'audit.html']);
    }

    public function test_protected_folder_blocks_nested_file_until_unlocked(): void
    {
        $folder = Item::create([
            'id' => 'f_legacy',
            'type' => 'folder',
            'name' => 'Rahasia',
            'password' => 'old-password',
        ]);
        Item::create([
            'id' => 'n_private',
            'parent_id' => $folder->id,
            'type' => 'html',
            'name' => 'private.html',
            'content' => '<h1>Private</h1>',
        ]);

        $this->get(route('files.preview', 'n_private'))->assertForbidden();
        $this->get(route('folders.show', $folder->id))->assertSee('Folder Dilindungi')->assertDontSee('private.html');

        Livewire::test(Workspace::class, ['folder' => $folder->id])
            ->set('unlockPassword', 'old-password')
            ->call('unlock')
            ->assertSee('private.html');

        $this->get(route('files.preview', 'n_private'))->assertOk()->assertSee('Private');
    }

    public function test_workspace_uses_livewire_navigation_and_does_not_expose_admin_controls_to_guests(): void
    {
        Item::create(['id' => 'f_public', 'type' => 'folder', 'name' => 'Publik']);

        $this->get('/index.html')->assertRedirect(route('home'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('wire:navigate', false)
            ->assertSee('Publik')
            ->assertDontSee('wire:click="openCreate', false);
    }

    public function test_admin_login_uses_the_configured_password_hash(): void
    {
        config()->set('audit.admin_username', 'admin');
        config()->set('audit.admin_password_hash', password_hash('test-secret', PASSWORD_BCRYPT));

        Livewire::test(Login::class)
            ->set('username', 'admin')
            ->set('password', 'wrong')
            ->call('login')
            ->assertHasErrors('password');

        Livewire::test(Login::class)
            ->set('username', 'admin')
            ->set('password', 'test-secret')
            ->call('login')
            ->assertRedirect(route('home'));

        $this->assertTrue(session('admin_auth'));
    }
}
