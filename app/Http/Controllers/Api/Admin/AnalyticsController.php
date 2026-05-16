<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(): JsonResponse
    {
        $totalRevenue = Order::whereIn('status', ['payment_verified', 'processing', 'shipped', 'delivered', 'completed'])
            ->sum('total');

        $stats = [
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'total_revenue' => $totalRevenue,
            'total_products' => Product::count(),
            'total_users' => User::role('user')->count(),
            'low_stock_products' => Product::where('stock', '<', 10)->count(),
        ];

        $monthlyRevenue = Order::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('SUM(total) as revenue')
        )
            ->whereYear('created_at', now()->year)
            ->whereIn('status', ['payment_verified', 'processing', 'shipped', 'delivered', 'completed'])
            ->groupBy('month')
            ->get();

        $ordersByStatus = Order::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        return response()->json([
            'stats' => $stats,
            'monthly_revenue' => $monthlyRevenue,
            'orders_by_status' => $ordersByStatus,
        ]);
    }
}
