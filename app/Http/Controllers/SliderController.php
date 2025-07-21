<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\SliderRequest;
use App\Models\Slider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SliderController extends Controller
{
    private static string $image_folder = 'sliders';

    public function index(): JsonResponse
    {
        $sliders = Slider::latest()->get();
        return BaseController::sendResponse($sliders, __('messages.sent_data'));
    }

    public function store(SliderRequest $request): JsonResponse
    {
        try {
            $image_ar = saveImage($request->image_ar, self::$image_folder);
            $image_en = saveImage($request->image_en, self::$image_folder);

            $slider = Slider::create([
                "image" => [
                    "ar" => $image_ar,
                    "en" => $image_en,
                ]
            ]);

            return BaseController::sendResponse($slider, __('messages.store_successfully'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.store_failed', ['item' => __('messages.slider')]), [], 500);
        }
    }
}
