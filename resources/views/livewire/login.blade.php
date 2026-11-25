<div class="flex min-h-screen items-center justify-center px-4 py-12">
    <div class="w-full max-w-md rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="mb-6 text-center">
            <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-2xl bg-neutral-900 text-white">
                <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2ZM8 11V7a4 4 0 0 1 8 0v4"/></svg>
            </div>
            <h1 class="text-lg font-semibold">Admin Login</h1>
            <p class="mt-1 text-xs text-neutral-500">Masukkan kata sandi untuk mengelola folder dan file</p>
        </div>
        <form wire:submit="login" class="space-y-4">
            <div>
                <label for="username" class="mb-1.5 block text-xs font-medium text-neutral-600">Username</label>
                <input id="username" type="text" wire:model="username" autocomplete="username" required class="w-full rounded-xl border border-neutral-200 bg-neutral-50 px-3.5 py-2.5 text-sm outline-none focus:border-neutral-400 focus:bg-white">
                @error('username') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password" class="mb-1.5 block text-xs font-medium text-neutral-600">Password Admin</label>
                <div x-data="{ show: false }" class="relative">
                    <input id="password" :type="show ? 'text' : 'password'" wire:model="password" autocomplete="current-password" required class="w-full rounded-xl border border-neutral-200 bg-neutral-50 px-3.5 py-2.5 pr-10 text-sm outline-none transition focus:border-neutral-400 focus:bg-white">
                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3 text-neutral-400 hover:text-neutral-700" :title="show ? 'Sembunyikan password' : 'Tampilkan password'" tabindex="-1">
                        <svg x-show="!show" class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg x-show="show" x-cloak class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                    </button>
                </div>
                @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="w-full rounded-xl bg-neutral-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-neutral-800" wire:loading.attr="disabled" wire:target="login">
                Masuk sebagai Admin
            </button>
        </form>
        <div class="mt-6 flex items-center justify-between border-t border-neutral-100 pt-5 text-xs text-neutral-500">
            <a href="{{ route('home') }}" wire:navigate class="hover:text-neutral-900">← Kembali ke Workspace</a>
            <span class="text-neutral-400">TRPL FINPRO</span>
        </div>
    </div>
</div>
