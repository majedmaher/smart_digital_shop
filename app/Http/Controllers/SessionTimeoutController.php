<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\UpdateSessionTimeoutRequest;
use App\Services\SessionTimeoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionTimeoutController extends Controller
{
    /**
     * Get current session timeout settings
     */
    public function getTimeoutSettings(): JsonResponse
    {
        return SessionTimeoutService::getTimeoutSettings();
    }

    /**
     * Update session timeout for specific site status
     */
    public function updateTimeout(UpdateSessionTimeoutRequest $request): JsonResponse
    {
        $data = $request->validated();
        return SessionTimeoutService::updateTimeout($data['site_status'], $data['timeout_minutes']);
    }

    /**
     * Get user session information
     */
    public function getUserSessionInfo(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        return SessionTimeoutService::getUserSessionInfo($userId);
    }

    /**
     * Extend user session
     */
    public function extendUserSession(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        return SessionTimeoutService::extendUserSession($userId);
    }

    /**
     * Clear user session
     */
    public function clearUserSession(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        return SessionTimeoutService::clearUserSession($userId);
    }

    /**
     * Set session timeout for current user (called after login)
     */
    public function setUserSession(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            SessionTimeoutService::setUserSessionTimeout($userId);

            return BaseController::sendResponse([
                'user_id' => $userId,
                'timeout_minutes' => SessionTimeoutService::getCurrentTimeout(),
                'expires_at' => now()->addMinutes(SessionTimeoutService::getCurrentTimeout())->toDateTimeString(),
                'message' => __('messages.session_timeout_set')
            ], __('messages.success'));
        } catch (\Exception $e) {
            return BaseController::sendError(__('messages.error_occurred'), [], 500);
        }
    }
}
