<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use App\Models\Category;
use App\Services\MainService;
use Illuminate\Http\Request;

class MainController extends Controller
{
    public function getCategoriesWithSubCategories()
    {
        return MainService::getCategoriesWithSubCategories();
    }
}
