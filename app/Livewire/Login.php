<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Login extends Component
{
    public string $username = 'admin';

    public string $password = '';

    public function login(): void
    {
        $this->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $configuredUser = (string) config('audit.admin_username');
        $configuredHash = (string) config('audit.admin_password_hash');

        if ($configuredHash === '' || ! hash_equals($configuredUser, $this->username) || ! password_verify($this->password, $configuredHash)) {
            $this->addError('password', 'Username atau password salah.');
            $this->password = '';

            return;
        }

        session()->regenerate();
        session(['admin_auth' => true]);
        $this->redirect(route('home'), navigate: true);
    }

    public function render()
    {
        return view('livewire.login');
    }
}
