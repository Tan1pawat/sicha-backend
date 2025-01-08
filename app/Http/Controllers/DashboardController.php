<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bill;
use App\Models\BillOrder;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function getDashboard(Request $request)
    {
        // Base query for bills with related data
        $query = Bill::with('billOrders.product.productType');
        
        // Filter by years if provided
        if ($request->has('years')) {
            $years = $request->years;
            $query->where(function($q) use ($years) {
                foreach ($years as $year) {
                    // Convert the date string to match the database format
                    $q->orWhere(function($query) use ($year) {
                        $query->whereRaw('DATE_FORMAT(date, "%Y") = ?', [$year]);
                    });
                }
            });
        }

        $bills = $query->get();

        // Initialize arrays for all months (1-12)
        $monthlyTemplate = array_fill(0, 12, 0);

        // Group data by year
        $yearlyData = $bills->groupBy(function ($bill) {
            // Parse date from dd/mm/yyyy format
            $date = Carbon::createFromFormat('d/m/Y', $bill->date);
            return $date->format('Y'); // This will be in BE
        })->map(function ($billsByYear) use ($monthlyTemplate) {
            // Group by month within the year
            $monthlyIncomes = $monthlyTemplate;
            $monthlyExpenses = $monthlyTemplate;
            
            // Fill in actual data
            foreach ($billsByYear->groupBy(function ($bill) {
                $date = Carbon::createFromFormat('d/m/Y', $bill->date);
                return $date->format('n'); // 1-12
            }) as $month => $monthBills) {
                $monthIndex = (int)$month - 1; // Convert to 0-11 index
                $monthlyIncomes[$monthIndex] = number_format($monthBills->sum('sum_income'), 2, '.', '');
                $monthlyExpenses[$monthIndex] = number_format($monthBills->sum('sum_expense'), 2, '.', '');
            }

            return [
                'income' => array_values($monthlyIncomes),
                'expenses' => array_values($monthlyExpenses),
                'expenseCategories' => $this->calculateExpenseCategories($billsByYear),
            ];
        });

        // Group data by year-month
        $monthlyData = $bills->groupBy(function ($bill) {
            $date = Carbon::createFromFormat('d/m/Y', $bill->date);
            return $date->format('Y') . '-' . $date->format('m');
        })->map(function ($billsByMonth) {
            return [
                'income' => [number_format($billsByMonth->sum('sum_income'), 2, '.', '')],
                'expenses' => [number_format($billsByMonth->sum('sum_expense'), 2, '.', '')],
                'expenseCategories' => $this->calculateExpenseCategories($billsByMonth),
            ];
        });

        $response = [
            'yearlyData' => $yearlyData,
            'monthlyData' => $monthlyData
        ];

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $response);
    }

    private function calculateExpenseCategories($bills)
    {
        $categories = [];

        foreach ($bills as $bill) {
            foreach ($bill->billOrders as $order) {
                $productType = $order->product->productType->name;
                $totalExpense = $order->value * $order->product->price;

                if (!isset($categories[$productType])) {
                    $categories[$productType] = 0;
                }

                $categories[$productType] += $totalExpense;
            }
        }

        return $categories;
    }
}