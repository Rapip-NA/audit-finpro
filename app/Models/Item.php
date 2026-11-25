<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

class Item extends Model
{
    protected $table = 'items';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['id', 'parent_id', 'type', 'name', 'color', 'password', 'content'];

    protected $hidden = ['password'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function passwordMatches(string $candidate): bool
    {
        if (! $this->password) {
            return true;
        }

        $info = password_get_info($this->password);

        return $info['algo'] !== null
            ? Hash::check($candidate, $this->password)
            : hash_equals($this->password, $candidate); // Existing rows used plain text.
    }

    public static function lockedAncestor(?self $item): ?self
    {
        if (session('admin_auth') === true) {
            return null;
        }

        $seen = [];
        $unlocked = session('unlocked_folders', []);
        $locked = null;

        while ($item !== null && ! isset($seen[$item->id])) {
            $seen[$item->id] = true;

            if ($item->type === 'folder' && $item->password && ! in_array($item->id, $unlocked, true)) {
                $locked = $item;
            }

            $item = $item->parent_id ? self::find($item->parent_id) : null;
        }

        return $locked;
    }
}
