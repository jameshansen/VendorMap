<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Manage the suggestion list of vendor product categories shown on the sign-up
 * and profile forms.
 */
class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name',
        ]);

        Category::create(['name' => trim($data['name'])]);

        return back()->with('status', 'Category added.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return back()->with('status', 'Category removed.');
    }
}
