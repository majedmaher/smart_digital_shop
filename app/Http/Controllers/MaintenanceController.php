<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use App\Services\MaintenanceService;
use Illuminate\Http\JsonResponse;

class MaintenanceController extends Controller
{
    /**
     * Get current maintenance mode status
     */
    public function getStatus(): JsonResponse
    {
        return MaintenanceService::getStatus();
    }

    /**
     * Enable maintenance mode
     */
    public function enable(): JsonResponse
    {
        return MaintenanceService::enable();
    }

    /**
     * Disable maintenance mode
     */
    public function disable(): JsonResponse
    {
        return MaintenanceService::disable();
    }

    /**
     * Toggle maintenance mode (GET request for dashboard toggle)
     */
    public function toggle(): JsonResponse
    {
        return MaintenanceService::toggle();
    }
}

