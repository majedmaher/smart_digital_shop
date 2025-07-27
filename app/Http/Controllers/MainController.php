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


    public function getSubCategory($slug)
    {
        return MainService::getSubCategory($slug);
    }
    public function getProduct($slug)
    {
        return MainService::getProduct($slug);
    }
}
