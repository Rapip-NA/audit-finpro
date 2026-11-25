<div class="min-h-screen">
    @if(session('admin_auth') !== true)
        <a href="{{ route('login') }}" wire:navigate title="Admin Login" aria-label="Admin Login" class="fixed bottom-3 left-3 z-30 flex size-9 items-center justify-center rounded-xl bg-neutral-900/10 text-neutral-500 opacity-0 transition hover:bg-neutral-900 hover:text-white hover:opacity-100 focus:opacity-100">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2ZM8 11V7a4 4 0 0 1 8 0v4"/></svg>
        </a>
    @endif

    <div class="mx-auto flex min-h-screen w-full max-w-5xl flex-col px-4 py-6 sm:px-8 sm:py-10">
        <header class="mb-8 flex flex-col justify-between gap-4 border-b border-neutral-200 pb-6 sm:flex-row sm:items-center">
            <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-1.5 text-sm font-medium text-neutral-500">
                <a href="{{ route('home') }}" wire:navigate class="hover:text-neutral-900 {{ $current ? '' : 'text-neutral-900' }}">Workspace</a>
                @foreach($breadcrumbs as $crumb)
                    <span class="text-neutral-300">/</span>
                    @if($loop->last)
                        <span class="max-w-48 truncate text-neutral-900" title="{{ $crumb->name }}">{{ $crumb->name }}</span>
                    @else
                        <a href="{{ route('folders.show', $crumb->id) }}" wire:navigate class="max-w-40 truncate hover:text-neutral-900">{{ $crumb->name }}</a>
                    @endif
                @endforeach
            </nav>

            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                @if($this->fileId === null && $locked === null)
                    <div class="relative flex-1 sm:flex-initial">
                        <svg class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7" stroke-width="2"/><path stroke-width="2" d="m16 16 5 5"/></svg>
                        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari..." aria-label="Cari item" class="w-full rounded-xl border border-neutral-200 bg-white py-2 pr-3 pl-9 text-sm outline-none transition focus:border-neutral-400 sm:w-40 sm:focus:w-52">
                    </div>
                @endif
                <button type="button" x-data="{ copied: false }" x-on:click="navigator.clipboard.writeText(window.location.href).then(() => { copied = true; setTimeout(() => copied = false, 1800) })" class="rounded-xl border border-neutral-200 bg-white px-3 py-2 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50">
                    <span x-text="copied ? 'Disalin!' : 'Bagikan'">Bagikan</span>
                </button>
                @if(session('admin_auth') === true)
                    @if($this->fileId === null && $locked === null)
                        <button type="button" wire:click="openCreate('file')" class="rounded-xl border border-neutral-200 bg-white px-3.5 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-100">File / HTML</button>
                        <button type="button" wire:click="openCreate('folder')" class="rounded-xl bg-neutral-900 px-4 py-2 text-sm font-medium text-white hover:bg-neutral-800">+ Folder</button>
                    @endif
                    <span class="rounded-full border border-neutral-300 bg-neutral-200/70 px-2.5 py-1 text-xs font-medium">● Admin</span>
                    <button type="button" wire:click="logout" class="rounded-xl border border-neutral-200 px-3 py-2 text-xs font-medium text-neutral-600 hover:bg-red-50 hover:text-red-600">Keluar</button>
                @endif
            </div>
        </header>

        <main class="flex min-h-[550px] flex-1 flex-col rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm sm:p-8" wire:poll.5s>
            @if($locked)
                <div class="m-auto w-full max-w-sm text-center">
                    <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">🔒</div>
                    <h1 class="text-base font-semibold">Folder Dilindungi</h1>
                    <p class="mt-1 text-xs text-neutral-500">Masukkan password untuk membuka {{ $locked->name }}.</p>
                    <form wire:submit="unlock" class="mt-5 space-y-3">
                        <input type="password" wire:model="unlockPassword" placeholder="Password folder" aria-label="Password folder" class="w-full rounded-xl border border-neutral-200 bg-neutral-50 px-3.5 py-2.5 text-sm outline-none focus:border-neutral-400" required>
                        @error('unlockPassword') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        <button type="submit" class="w-full rounded-xl bg-neutral-900 px-4 py-2.5 text-sm font-medium text-white">Buka Folder</button>
                    </form>
                </div>
            @elseif($this->fileId !== null)
                <div class="flex flex-1 flex-col">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 border-b border-neutral-200 pb-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <a href="{{ $current->parent_id ? route('folders.show', $current->parent_id) : route('home') }}" wire:navigate class="rounded-xl bg-neutral-100 px-3 py-1.5 text-xs font-medium text-neutral-700 hover:bg-neutral-200">← Kembali</a>
                            <h1 class="max-w-64 truncate text-sm font-semibold" title="{{ $current->name }}">{{ $current->name }}</h1>
                            @if($current->type === 'html') <span class="rounded-full border border-orange-200 bg-orange-100 px-2 py-0.5 text-[10px] font-semibold text-orange-700">Live Preview</span> @endif
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            @if($current->type === 'html')
                                <div class="flex rounded-xl bg-neutral-100 p-1 text-xs">
                                    @foreach(['desktop' => 'Desktop', 'tablet' => 'Tablet', 'mobile' => 'Mobile'] as $size => $label)
                                        <button type="button" wire:click="$set('device', '{{ $size }}')" class="rounded-lg px-2.5 py-1 {{ $device === $size ? 'bg-white text-neutral-900 shadow-sm' : 'text-neutral-600 hover:text-neutral-900' }}">{{ $label }}</button>
                                    @endforeach
                                </div>
                                <a href="{{ route('files.preview', $current->id) }}" target="_blank" rel="noopener noreferrer" class="rounded-xl border border-neutral-200 px-3 py-1.5 text-xs font-medium hover:bg-neutral-50">Buka Tab Baru</a>
                            @endif
                            @if(session('admin_auth') === true)
                                <button type="button" wire:click="openEdit('{{ $current->id }}')" class="rounded-xl bg-neutral-900 px-3 py-1.5 text-xs font-medium text-white">Edit</button>
                                <button type="button" wire:click="confirmDelete('{{ $current->id }}')" class="rounded-xl border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600">Hapus</button>
                            @endif
                        </div>
                    </div>
                    @if($current->type === 'html')
                        <div class="flex min-h-[560px] flex-1 items-stretch justify-center overflow-auto rounded-xl border border-neutral-200 bg-neutral-100/70 p-2 sm:p-4">
                            <iframe wire:key="preview-{{ $current->id }}-{{ $current->updated_at?->timestamp }}" src="{{ route('files.preview', $current->id) }}?v={{ $current->updated_at?->timestamp }}" title="Pratinjau {{ $current->name }}" sandbox="allow-scripts allow-forms allow-modals allow-popups" class="min-h-[580px] rounded-xl border border-neutral-200 bg-white shadow-sm transition-all {{ $device === 'mobile' ? 'w-[375px] shrink-0' : ($device === 'tablet' ? 'w-[768px] shrink-0' : 'w-full') }}"></iframe>
                        </div>
                    @else
                        <pre class="flex-1 overflow-auto whitespace-pre-wrap break-words rounded-xl bg-neutral-50 p-5 font-sans text-sm leading-relaxed">{{ $current->content }}</pre>
                    @endif
                </div>
            @else
                @if($items->isEmpty())
                    <div class="m-auto py-16 text-center">
                        <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-2xl border border-neutral-200 bg-neutral-100 text-neutral-400">
                            <svg class="size-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/></svg>
                        </div>
                        <h1 class="text-sm font-medium text-neutral-700">{{ $search ? 'Tidak ditemukan "'.$search.'"' : 'Folder ini masih kosong' }}</h1>
                        <p class="mt-1 text-xs text-neutral-400">{{ $search ? 'Coba kata kunci lain.' : 'Belum ada item di dalam folder ini.' }}</p>
                        @if(session('admin_auth') === true && ! $search)
                            <button type="button" wire:click="openCreate('folder')" class="mt-5 rounded-xl bg-neutral-100 px-4 py-2 text-xs font-medium hover:bg-neutral-200">+ Buat Folder Baru</button>
                        @endif
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-x-6 gap-y-8 sm:grid-cols-3 sm:gap-x-10 sm:gap-y-10 md:grid-cols-4">
                        @foreach($items as $item)
                            @php
                                $folderColor = match($item->color) {
                                    'stone' => 'text-neutral-500',
                                    'blue' => 'text-blue-600',
                                    'amber' => 'text-amber-500',
                                    'emerald' => 'text-emerald-600',
                                    'rose' => 'text-rose-500',
                                    default => 'text-neutral-950',
                                };
                                $itemUrl = $item->type === 'folder' ? route('folders.show', $item->id) : route('files.show', $item->id);
                            @endphp
                            <div wire:key="item-{{ $item->id }}" class="group relative min-w-0">
                                <a href="{{ $itemUrl }}" wire:navigate class="block rounded-xl p-2 text-center outline-none transition hover:bg-neutral-50 focus:ring-2 focus:ring-neutral-300">
                                    <div class="relative mx-auto mb-2 flex size-24 items-center justify-center">
                                        @if($item->type === 'folder')
                                            <svg class="size-20 {{ $folderColor }} transition-transform group-hover:-translate-y-1" fill="currentColor" viewBox="0 0 24 24"><path d="M3 5a2 2 0 0 1 2-2h4.17a2 2 0 0 1 1.42.59L12 5h7a2 2 0 0 1 2 2v11a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V5Z"/></svg>
                                            @if($item->password)<span class="absolute right-1 bottom-1 rounded-full bg-white px-1.5 py-0.5 text-xs shadow-sm" title="Dilindungi password">🔒</span>@endif
                                        @else
                                            <div class="flex size-17 items-center justify-center rounded-xl border {{ $item->type === 'html' ? 'border-orange-200 bg-orange-50 text-orange-600' : 'border-neutral-200 bg-neutral-100 text-neutral-600' }}">
                                                <svg class="size-9" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 3h6l5 5v12a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h2Zm6 0v5h5M8 13h8M8 17h6"/></svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="truncate text-xs font-medium text-neutral-800" title="{{ $item->name }}">{{ $item->name }}</div>
                                    <div class="mt-1 text-[11px] text-neutral-400">{{ $item->type === 'folder' ? $item->children_count.' item' : ($item->type === 'html' ? 'HTML' : 'Catatan') }}</div>
                                </a>
                                <div class="absolute top-1 right-1 flex gap-1 opacity-0 transition group-hover:opacity-100 group-focus-within:opacity-100">
                                    <button type="button" x-data="{ copied: false }" x-on:click="navigator.clipboard.writeText(@js($itemUrl)).then(() => { copied = true; setTimeout(() => copied = false, 1800) })" x-bind:title="copied ? 'Disalin' : 'Bagikan'" aria-label="Bagikan {{ $item->name }}" class="rounded-lg border border-neutral-200 bg-white px-1.5 py-1 text-xs shadow-sm">↗</button>
                                    @if(session('admin_auth') === true)
                                        <button type="button" wire:click="openEdit('{{ $item->id }}')" aria-label="Edit {{ $item->name }}" title="Edit" class="rounded-lg border border-neutral-200 bg-white px-1.5 py-1 text-xs shadow-sm">✎</button>
                                        <button type="button" wire:click="confirmDelete('{{ $item->id }}')" aria-label="Hapus {{ $item->name }}" title="Hapus" class="rounded-lg border border-red-200 bg-white px-1.5 py-1 text-xs text-red-600 shadow-sm">×</button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif
        </main>

        <footer class="mt-4 flex items-center justify-between px-1 text-xs text-neutral-400">
            <span>{{ $this->fileId === null && ! $locked ? $items->count().' item' : '' }}</span>
            <span>{{ $this->fileId === null ? 'Klik kartu untuk membuka' : 'Pratinjau file' }}</span>
        </footer>
    </div>

    @if($modal === 'folder' && session('admin_auth') === true)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-neutral-900/30 p-4 backdrop-blur-[2px]">
            <div class="w-full max-w-sm rounded-2xl border border-neutral-200 bg-white p-6 shadow-xl">
                <h2 class="mb-4 text-base font-semibold">{{ $editingId ? 'Edit Folder' : 'Folder Baru' }}</h2>
                <form wire:submit="saveFolder" class="space-y-4">
                    <div>
                        <label for="folder-name" class="mb-1.5 block text-xs font-medium text-neutral-500">Nama Folder</label>
                        <input id="folder-name" type="text" wire:model="name" maxlength="255" required class="w-full rounded-xl border border-neutral-200 bg-neutral-50 px-3.5 py-2.5 text-sm outline-none focus:border-neutral-400">
                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <fieldset>
                        <legend class="mb-2 text-xs font-medium text-neutral-500">Warna Folder</legend>
                        <div class="flex gap-2.5">
                            @foreach(['black' => 'bg-neutral-950', 'stone' => 'bg-neutral-500', 'blue' => 'bg-blue-600', 'amber' => 'bg-amber-500', 'emerald' => 'bg-emerald-600', 'rose' => 'bg-rose-500'] as $option => $swatch)
                                <label class="cursor-pointer">
                                    <input type="radio" wire:model="color" value="{{ $option }}" class="peer sr-only">
                                    <span class="block size-7 rounded-full {{ $swatch }} ring-offset-2 peer-checked:ring-2 peer-checked:ring-neutral-600" title="{{ $option }}"></span>
                                    <span class="sr-only">{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('color') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </fieldset>
                    <div class="border-t border-neutral-100 pt-3">
                        <label class="flex items-start justify-between gap-3 text-xs font-medium text-neutral-800">
                            <span>Kunci dengan Password <small class="mt-1 block font-normal text-neutral-400">Pengunjung wajib membuka kunci untuk melihat isinya</small></span>
                            <input type="checkbox" wire:model.live="passwordEnabled" class="mt-1 size-4 rounded border-neutral-300">
                        </label>
                        @if($passwordEnabled)
                            <input type="password" wire:model="newPassword" maxlength="255" placeholder="{{ $editingId ? 'Kosongkan untuk mempertahankan password' : 'Password folder' }}" class="mt-3 w-full rounded-xl border border-neutral-200 bg-neutral-50 px-3.5 py-2.5 text-sm outline-none focus:border-neutral-400">
                            @error('newPassword') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        @endif
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeModal" class="rounded-xl px-4 py-2 text-sm text-neutral-600 hover:bg-neutral-100">Batal</button>
                        <button type="submit" class="rounded-xl bg-neutral-900 px-4 py-2 text-sm font-medium text-white">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @elseif($modal === 'file' && session('admin_auth') === true)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-neutral-900/30 p-4 backdrop-blur-[2px]">
            <div class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-2xl border border-neutral-200 bg-white p-6 shadow-xl">
                <h2 class="mb-3 text-base font-semibold">{{ $editingId ? 'Edit File' : 'File Baru' }}</h2>
                <p class="mb-4 rounded-xl border border-orange-200 bg-orange-50 p-2.5 text-[11px] text-orange-800">Gunakan nama berakhiran <strong>.html</strong> agar file menampilkan halaman web saat dibuka.</p>
                <form wire:submit="saveFile" class="space-y-4">
                    <div>
                        <label for="file-name" class="mb-1.5 block text-xs font-medium text-neutral-500">Nama File</label>
                        <input id="file-name" type="text" wire:model="name" maxlength="255" required placeholder="landing-page.html atau catatan.txt" class="w-full rounded-xl border border-neutral-200 bg-neutral-50 px-3.5 py-2.5 font-mono text-xs outline-none focus:border-neutral-400">
                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <div class="mb-1.5 flex items-center justify-between">
                            <label for="file-content" class="text-xs font-medium text-neutral-500">Kode HTML / Isi Catatan</label>
                            <button type="button" wire:click="$toggle('previewDraft')" class="text-xs font-medium text-orange-600 hover:underline">{{ $previewDraft ? 'Tutup Pratinjau' : '▶ Pratinjau Hasil' }}</button>
                        </div>
                        <textarea id="file-content" wire:model.live.debounce.600ms="content" rows="9" class="w-full resize-y rounded-xl border border-neutral-200 bg-neutral-50 px-3.5 py-2.5 font-mono text-xs leading-relaxed outline-none focus:border-neutral-400"></textarea>
                        @error('content') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        @if($previewDraft)
                            <iframe srcdoc="{{ $content }}" sandbox="allow-scripts allow-forms allow-modals allow-popups" title="Pratinjau draf" class="mt-3 h-64 w-full rounded-xl border border-neutral-200 bg-white"></iframe>
                        @endif
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeModal" class="rounded-xl px-4 py-2 text-sm text-neutral-600 hover:bg-neutral-100">Tutup</button>
                        <button type="submit" class="rounded-xl bg-neutral-900 px-4 py-2 text-sm font-medium text-white">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @elseif($modal === 'delete' && session('admin_auth') === true)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-neutral-900/30 p-4 backdrop-blur-[2px]">
            <div class="w-full max-w-xs rounded-2xl border border-neutral-200 bg-white p-6 text-center shadow-xl">
                <div class="mx-auto mb-3 flex size-11 items-center justify-center rounded-xl bg-red-50 text-xl text-red-600">×</div>
                <h2 class="text-sm font-semibold">Hapus item ini?</h2>
                <p class="mt-1 mb-5 text-xs text-neutral-500">Semua isi di dalam folder ini juga akan terhapus permanen.</p>
                <div class="flex gap-2">
                    <button type="button" wire:click="closeModal" class="flex-1 rounded-xl bg-neutral-100 px-3 py-2 text-sm text-neutral-600">Batal</button>
                    <button type="button" wire:click="deleteItem" class="flex-1 rounded-xl bg-red-600 px-3 py-2 text-sm font-medium text-white">Hapus</button>
                </div>
            </div>
        </div>
    @endif
</div>
