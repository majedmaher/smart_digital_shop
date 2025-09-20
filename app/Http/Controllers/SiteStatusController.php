<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\UpdateSiteStatusRequest;
use App\Services\SiteStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteStatusController extends Controller
{
    /**
     * Get current site status
     */
    public function getCurrentStatus(): JsonResponse
    {
        return SiteStatusService::getCurrentStatus();
    }

    /**
     * Update site status
     */
    public function updateStatus(UpdateSiteStatusRequest $request): JsonResponse
    {
        return SiteStatusService::updateStatus($request->validated()['status']);
    }

    /**
     * Get available statuses
     */
    public function getAvailableStatuses(): JsonResponse
    {
        return SiteStatusService::getAvailableStatuses();
    }

    /**
     * Toggle between demo and live mode
     */
    public function toggleStatus(): JsonResponse
    {
        try {
            $currentStatus = SiteStatusService::isDemoMode() ? 'demo' : 'live';
            $newStatus = $currentStatus === 'demo' ? 'live' : 'demo';

            return SiteStatusService::updateStatus($newStatus);
        } catch (\Exception $e) {
            return BaseController::sendError(__('messages.error_occurred'), [], 500);
        }
    }
}
