<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\TicketMessageRequest;
use App\Http\Requests\TicketRequest;
use App\Models\Ticket;
use App\PermissionEnum;
use App\Services\TicketService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TicketController extends Controller
{
    use AuthorizesRequests;
    public function index(): JsonResponse
    {
        return TicketService::index();
    }
    public function adminIndex(): JsonResponse
    {
        return TicketService::adminIndex();
    }

    public function getAllReplies(Ticket $ticket)
    {
        if (Gate::denies('update', $ticket)) {
            return BaseController::sendError(__('messages.do_not_have_permission'), [], 403);
        }

        return TicketService::getAllReplies($ticket);
    }

    public function store(TicketRequest $request): JsonResponse
    {
        return TicketService::store($request->validated());
    }

    public function show(Ticket $ticket)
    {
        if (Gate::denies('view', $ticket)) {
            return BaseController::sendError(__('messages.do_not_have_permission'), [], 403);
        }
        return TicketService::show($ticket);
    }

    public function updateStatus(Request $request, Ticket $ticket): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|in:open,pending,resolved,closed'
        ]);
        return TicketService::updateStatus($data, $ticket);
    }

    public function reply(TicketMessageRequest $request, Ticket $ticket): JsonResponse
    {
        // $this->authorize('update', $ticket);
        if (Gate::denies('update', $ticket)) {
            return BaseController::sendError(__('messages.do_not_have_permission'), [], 403);
        }

        return TicketService::reply($request->validated(), $ticket);
    }
}
