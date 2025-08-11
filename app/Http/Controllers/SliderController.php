<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\SliderRequest;
use App\Http\Resources\SliderResource;
use App\Models\Slider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SliderController extends Controller
{
    private string $image_folder = 'sliders';

    public function index(): JsonResponse
    {
        $sliders = Slider::latest()->get();
        return BaseController::sendResponse($sliders, __('messages.sent_data'));
    }

    public function store(SliderRequest $request): JsonResponse
    {
        try {
            $image_ar = saveImage($request->image_ar, $this->image_folder);
            $image_en = saveImage($request->image_en, $this->image_folder);

            $slider = Slider::create([
                "image" => [
                    "ar" => $image_ar,
                    "en" => $image_en,
                ]
            ]);

            return BaseController::sendResponse($slider, __('messages.store_successfully', ['item' => __('messages.slider')]));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.store_failed', ['item' => __('messages.slider')]), [], 500);
        }
    }

    public function delete($id): JsonResponse
    {
        try {
            $slider = Slider::find($id);

            if (!$slider || $slider == null) {
                return BaseController::sendError(__('messages.item_not_found', ['item' => __('messages.slider'), [], 404]));
            }
            $slider->delete();
            return BaseController::sendResponse(SliderResource::make($slider), __('messages.delete_successfully', ['item' => __('messages.slider')]));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.delete_failed', ['item' => __('messages.slider')]), [], 500);
        }
    }
}
