<?php

namespace App\Http\Controllers;

use App\Services\MainService;

class MainController extends Controller
{
    public function getMobileMainScreen()
    {
        return MainService::getMobileMainScreen();
    }
    public function getCategoriesWithSubCategories()
    {
        return MainService::getCategoriesWithSubCategories();
    }
    public function getMainContent()
    {
        return MainService::getMainContent();
    }
}
