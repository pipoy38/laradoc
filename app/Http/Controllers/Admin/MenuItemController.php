<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Http\Request;

class MenuItemController extends Controller
{
    private function rules(): array
    {
        return [
            'label' => 'required|string|max:60',
            // `/(?!/)` requires the path to start with a single slash, not a double slash —
            // "//evil.example" is a protocol-relative URL (browsers resolve it against the
            // current scheme), not a safe same-site path, so it must not match here.
            'url' => ['required', 'string', 'max:500', 'regex:#^(https?://|/(?!/)|\#|mailto:)#i'],
            'new_tab' => 'nullable|boolean',
        ];
    }

    private function messages(): array
    {
        return ['url.regex' => 'Use a full http(s) URL, a path starting with /, an anchor, or a mailto: link.'];
    }

    public function index()
    {
        return view('admin.menu.index', ['items' => MenuItem::orderBy('sort_order')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(), $this->messages());
        MenuItem::create($data + ['new_tab' => $request->boolean('new_tab'), 'sort_order' => (MenuItem::max('sort_order') ?? -1) + 1]);

        return redirect()->route('admin.menu.index')->with('ok', 'Menu item added.');
    }

    public function update(Request $request, MenuItem $menu)
    {
        $menu->update($request->validate($this->rules(), $this->messages()) + ['new_tab' => $request->boolean('new_tab')]);

        return redirect()->route('admin.menu.index')->with('ok', 'Menu item saved.');
    }

    public function destroy(MenuItem $menu)
    {
        $menu->delete();

        return back()->with('ok', 'Menu item removed.');
    }

    public function move(Request $request, MenuItem $menu)
    {
        $items = MenuItem::orderBy('sort_order')->get()->values();
        $i = $items->search(fn ($m) => $m->id === $menu->id);
        $j = $i + ($request->input('dir') === 'up' ? -1 : 1);
        if (isset($items[$j])) {
            foreach ($items as $k => $m) {
                $m->sort_order = $k;
            } [$items[$i]->sort_order, $items[$j]->sort_order] = [$j, $i];
            $items->each->save();
        }

        return back();
    }
}
