// Keep links shared by the previous hash-routed version working.
function redirectLegacyHash() {
    const hash = window.location.hash;

    if (hash === '#/login' || hash === '#login') {
        window.location.replace(new URL('login', window.location.href));
        return;
    }

    const match = hash.match(/^#\/?(folder|preview|file)\/([^/?#]+)$/);
    if (!match) return;

    const type = match[1] === 'folder' ? 'folder' : 'file';
    const id = decodeURIComponent(match[2]);
    window.location.replace(new URL(type + '/' + encodeURIComponent(id), window.location.href));
}

document.addEventListener('DOMContentLoaded', redirectLegacyHash);
document.addEventListener('livewire:navigated', redirectLegacyHash);
