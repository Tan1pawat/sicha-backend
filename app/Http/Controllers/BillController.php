<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bill;
use App\Models\BillOrder;
use App\Models\Product;
use App\Models\Prison;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

class BillController extends Controller
{

    public function getPage(Request $request)
    {
        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;


        $col = array('id', 'prison_id', 'company_id','code','bill_type', 'date', 'sum_income', 'sum_expense', 'sum_total');

        $orderby = array('id', 'prison_id', 'company_id','code','bill_type', 'date', 'sum_income', 'sum_expense', 'sum_total');

        $D = Bill::select($col);

        if ($orderby[$order[0]['column']]) {
            $D->orderby($orderby[$order[0]['column']], $order[0]['dir']);
        }

        if ($search['value'] != '' && $search['value'] != null) {

            $D->Where(function ($query) use ($search, $col) {

                //search datatable
                $query->orWhere(function ($query) use ($search, $col) {
                    foreach ($col as &$c) {
                        $query->orWhere($c, 'like', '%' . $search['value'] . '%');
                    }
                });

            });
        }

        $d = $D->paginate($length, ['*'], 'page', $page);

        if ($d->isNotEmpty()) {

            $No = (($page - 1) * $length);

            $prisonIds = $d->pluck('prison_id')->filter()->unique();
            $companyIds = $d->pluck('company_id')->filter()->unique();
    
            $prisons = Prison::whereIn('id', $prisonIds)->pluck('name', 'id');
            $companys = Company::whereIn('id', $companyIds)->pluck('name', 'id');

            foreach ($d as $item) {
                $No++;
                $item->No = $No;

                $item->prison_name = $item->prison_id && isset($prisons[$item->prison_id]) ? $prisons[$item->prison_id] : 'Unknown Prison';
                $item->company_name = $item->company_id && isset($companys[$item->company_id]) ? $companys[$item->company_id] : 'Unknown Company';
            }

        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $d);
    }
    public function store(Request $request)
    {
        // Validate request inputs
        $validatedData = $request->validate([
            'prison_id' => 'required|integer',
            'company_id' => 'required|integer',
            'bill_type' => 'required|integer',
            'bill_order' => 'required|array',
            'date' => 'required|string',
        ], [
            'required' => 'กรุณาระบุ :attribute ให้เรียบร้อย',
        ]);

        DB::beginTransaction();

        try {
            // Create a new bill
            $lastId = Bill::max('id') ?? 0;
            $bill = Bill::create([
                'prison_id' => $validatedData['prison_id'],
                'company_id' => $validatedData['company_id'],
                'bill_type' => $validatedData['bill_type'],
                'code' => 'BILL-' . ($lastId + 1),
                'date' => $validatedData['date'],
                'sum_income' => 0,
                'sum_expense' => 0,
                'sum_total' => 0,
            ]);

            $totals = $this->createBillOrder($validatedData['bill_order'], $bill->id);

            $bill->update($totals);

            DB::commit();

            return $this->returnSuccess('ดำเนินการสำเร็จ', $bill);
        } catch (\Exception $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง '.$e, 500);
        }
    }

    private function createBillOrder(array $billOrders, int $billId): array
    {
        $sumIncome = 0;
        $sumExpense = 0;

        foreach ($billOrders as $item) {
            $product = Product::find($item['product_id']);

            BillOrder::create([
                'bill_id' => $billId,
                'product_id' => $item['product_id'],
                'price' => $item['price'],
                'value' => $item['value'],
            ]);

            $sumIncome += $item['price'] * $item['value'];
            $sumExpense += $product->price * $item['value'];
        }

        return [
            'sum_income' => $sumIncome,
            'sum_expense' => $sumExpense,
            'sum_total' => $sumIncome - $sumExpense,
        ];
    }
}
