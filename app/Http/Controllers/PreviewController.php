<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Response;

class PreviewController extends Controller
{
    public function __invoke(string $file): Response
    {
        $item = Item::findOrFail($file);
        abort_unless($item->type === 'html', 404);
        abort_if(Item::lockedAncestor($item), 403);

        return response($item->content ?? '', 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Security-Policy' => 'sandbox allow-scripts allow-forms allow-modals allow-popups',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
