<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\RatingRequest;
use App\Models\Rating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RatingController extends Controller
{

    public function all(): JsonResponse
    {
        try {
            $ratings = Rating::with('images')->latest()->get();
            return BaseController::sendResponse($ratings, __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.can_not_sent_data'), [], 500);
        }
    }

    public function store(RatingRequest $request): JsonResponse
    {
        try {
            $rating = Rating::create([
                'user_id' => auth()->id(),
                'product_id' => $request->product_id, // تأكد من إرسالها
                'stars' => $request->stars,
                'comment' => $request->comment,
                'show_name' => $request->show_name,
            ]);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = saveImage($image, 'ratings');
                    $rating->images()->create(['image' => $path]);
                }
            }
            return BaseController::sendResponse($rating, __('messages.store_successfully', ['item' => __('messages.rating')]));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.store_failed', ['item' => __('messages.rating')]), [], 500);
        }
    }
}
