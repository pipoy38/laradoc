<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use App\Support\PathResolver;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        return view('admin.categories.index', ['tree' => self::adminTree()]);
    }

    public function create() { return view('admin.categories.form', ['category' => new Category(), 'parentId' => request()->integer('parent') ?: null]); }

    public function store(CategoryRequest $request)
    {
        $data = $request->validated();
        $data['sort_order'] = (Category::where('parent_id', $data['parent_id'])->max('sort_order') ?? -1) + 1;
        Category::create($data);
        return redirect()->route('admin.categories.index')->with('ok', 'Category created.');
    }

    public function edit(Category $category) { return view('admin.categories.form', ['category' => $category, 'parentId' => $category->parent_id]); }

    public function update(CategoryRequest $request, Category $category, PathResolver $resolver)
    {
        $oldPath = $category->path();
        $category->fill($request->validated());
        if ($category->isDirty('parent_id')) { $category->sort_order = (Category::where('parent_id', $category->parent_id)->max('sort_order') ?? -1) + 1; }
        $category->save();
        $category->refresh()->load('parent');
        $resolver->recordRedirects($oldPath, $category->path(), $category);
        return redirect()->route('admin.categories.index')->with('ok', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        if ($category->children()->exists() || $category->articles()->exists()) {
            return back()->with('err', 'Move or delete its sub-categories and articles first.');
        }
        $category->delete();
        return redirect()->route('admin.categories.index')->with('ok', 'Category deleted.');
    }

    public function move(Request $request, Category $category)
    {
        $dir = $request->input('dir') === 'up' ? -1 : 1;
        $siblings = Category::where('parent_id', $category->parent_id)->ordered()->get()->values();
        $i = $siblings->search(fn ($c) => $c->id === $category->id);
        $j = $i + $dir;
        if (isset($siblings[$j])) {
            foreach ($siblings as $k => $s) { $s->sort_order = $k; }
            [$siblings[$i]->sort_order, $siblings[$j]->sort_order] = [$j, $i];
            $siblings->each->save();
        }
        return back();
    }

    /** All categories nested (drafts included) with article counts, for admin screens. */
    public static function adminTree(): \Illuminate\Support\Collection
    {
        $all = Category::ordered()->withCount('articles')->get();
        $byParent = $all->groupBy(fn ($c) => $c->parent_id ?? 0);
        $build = function ($parentId, $depth = 0) use (&$build, $byParent) {
            return ($byParent[$parentId] ?? collect())->each(function ($c) use (&$build, $depth) { $c->depth = $depth; $c->setRelation('children', $build($c->id, $depth + 1)); })->values();
        };
        return $build(0);
    }

    /** Flatten adminTree() into [id => ['name', 'depth', 'path']] for selects. */
    public static function flatOptions(): array
    {
        $out = [];
        $walk = function ($nodes) use (&$walk, &$out) { foreach ($nodes as $n) { $out[$n->id] = ['name' => $n->name, 'depth' => $n->depth, 'path' => $n->path()]; $walk($n->children); } };
        $walk(self::adminTree());
        return $out;
    }
}
