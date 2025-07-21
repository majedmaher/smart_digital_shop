<?php

namespace App\Http\Controllers;

use App\Services\MainService;

class MainController extends Controller
{
    public function getCategoriesWithSubCategories()
    {
        return MainService::getCategoriesWithSubCategories();
    }
}
