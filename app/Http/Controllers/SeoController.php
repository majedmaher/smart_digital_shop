<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\SeoRequest;
use App\Http\Resources\SeoResource;
use App\Models\Seo;
use Illuminate\Http\Request;

class SeoController extends Controller
{
    public function get()
    {
        try {
            return BaseController::sendResponse(SeoResource::make(Seo::first()), __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.something_went_wrong'), [], 500);
        }
    }

    public function update(SeoRequest $request)
    {
        try {
            $seo = Seo::first();

            if ($seo) {
                $seo->update($request->validated());
            } else {
                $seo = Seo::create($request->validated());
            }
            return BaseController::sendResponse(SeoResource::make($seo), __('messages.update_successfully', ['item' => 'SEO']));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.something_went_wrong'), [$th->getMessage()], 500);
        }
    }
}
