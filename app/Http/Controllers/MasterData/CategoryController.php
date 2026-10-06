<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Category;

class CategoryController extends Controller
{
    /**
     * Kategori hanya bisa dilihat (data dibuat lewat CategorySeeder).
     */
    public function index()
    {
        $categories = Category::withCount('sales')->orderBy('name')->get();

        return view('master-data.categories.index', compact('categories'));
    }
}
