<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use App\Traits\ApiResponse;

class OrderManagementController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'admin']);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,paid,failed,cancelled'],
        ]);

        if ($order->status === 'paid') {
            return $this->error('Order already completed.', 400);
        }

        try {

            $order->update([
                'status' => $validated['status'],
                'paid_at' => $validated['status'] === 'paid' ? now() : null,
                'failed_at' => $validated['status'] === 'failed' ? now() : null,
            ]);

        } catch (QueryException $e) {

            return $this->error('Duplicate paid order detected.', 400);
        }

        return $this->success($order, 'Order status updated.');
    }
}
