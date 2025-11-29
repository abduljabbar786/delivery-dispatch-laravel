<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\AssignOrderRequest;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {
    }

    public function index(Request $request)
    {
        $query = Order::query()->with(['rider', 'branch']);

        // Filter by branch if provided
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search filter (by order ID, customer name, or phone)
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        // Date filter
        if ($request->has('date_filter') && $request->date_filter !== 'all') {
            $dateFilter = $request->date_filter;
            $now = now();

            switch ($dateFilter) {
                case 'today':
                    // Today's orders
                    $query->whereDate('created_at', $now->toDateString());
                    break;
                case 'week':
                    // Last 7 days
                    $query->where('created_at', '>=', $now->subDays(7));
                    break;
                case 'month':
                    // Last 30 days
                    $query->where('created_at', '>=', $now->subDays(30));
                    break;
            }
        }

        // Pagination
        $perPage = $request->input('per_page', 10);
        $perPage = min(max((int) $perPage, 1), 100); // Limit between 1 and 100

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function store(StoreOrderRequest $request)
    {
        $order = $this->orderService->create($request->validated());

        return response()->json($order->fresh(), 201);
    }

    public function assign(AssignOrderRequest $request, Order $order)
    {
        $order = $this->orderService->assign($order, $request->rider_id);

        return response()->json($order);
    }

    public function reassign(AssignOrderRequest $request, Order $order)
    {
        $order = $this->orderService->reassign($order, $request->rider_id);

        return response()->json($order);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order)
    {
        $status = OrderStatus::from($request->status);
        $order = $this->orderService->updateStatus($order, $status, $request->reason);

        return response()->json($order);
    }
}
