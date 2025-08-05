<?php

namespace App\Http\Controllers;

use App\Services\MainService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MainController extends Controller
{
    public function getMobileMainScreen(): JsonResponse
    {
        return MainService::getMobileMainScreen();
    }
    public function getCategoriesWithSubCategories(): JsonResponse
    {
        return MainService::getCategoriesWithSubCategories();
    }
    public function getMainContent(): JsonResponse
    {
        return MainService::getMainContent();
    }


    public function getCategory($slug): JsonResponse
    {
        return MainService::getCategory($slug);
    }
    public function getSubCategory($slug): JsonResponse
    {
        return MainService::getSubCategory($slug);
    }
    public function getProduct($slug): JsonResponse
    {
        return MainService::getProduct($slug);
    }

    public function getFAQS(): JsonResponse
    {
        return MainService::getFAQS();
    }

    public function getOrders(): JsonResponse
    {
        return MainService::getOrders();
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->input('query');
        return MainService::search($query);
    }
}
