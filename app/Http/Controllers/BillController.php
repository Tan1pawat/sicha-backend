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


        $col = array('id', 'prison_id', 'company_id', 'code', 'date', 'sum_income', 'sum_expense', 'sum_total');

        $orderby = array('id', 'prison_id', 'company_id', 'code', 'date', 'sum_income', 'sum_expense', 'sum_total');

        $D = Bill::select($col);

        if ($request->has('prison_id') && $request->prison_id != null) {
            $D->where('prison_id', $request->prison_id);
        }

        if ($request->has('company_id') && $request->company_id != null) {
            $D->where('company_id', $request->company_id);
        }

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
            'bill_order' => 'required|array',
            'date' => 'required|string',
        ], [
            'required' => 'กรุณาระบุ :attribute ให้เรียบร้อย',
        ]);

        DB::beginTransaction();

        try {
            $dateParts = explode('/', $validatedData['date']);
            $thaiYear = $dateParts[2];
            $lastTwoDigits = substr($thaiYear, -2);

            // Calculate the current count based on the year and company
            $currentCount = Bill::where('company_id', $validatedData['company_id'])
                ->whereYear(DB::raw("STR_TO_DATE(date, '%d/%m/%Y')"), $thaiYear)
                ->max('count') ?? 0;

            // Create a new bill
            $company = Company::find($validatedData['company_id']);
            $bill = Bill::create([
                'prison_id' => $validatedData['prison_id'],
                'company_id' => $validatedData['company_id'],
                'code' => $company->code . $lastTwoDigits . '-' . ($currentCount + 1),
                'date' => $validatedData['date'],
                'sum_income' => 0,
                'sum_expense' => 0,
                'sum_total' => 0,
                'count' => $currentCount + 1
            ]);

            $totals = $this->createBillOrder($validatedData['bill_order'], $bill->id);

            $bill->update($totals);

            DB::commit();

            return $this->returnSuccess('ดำเนินการสำเร็จ', $bill);
        } catch (\Exception $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e->getMessage(), 500);
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

            $product->value -= $item['value'];
            $product->save();

            $sumIncome += $item['price'] * $item['value'];
            $sumExpense += $product->price * $item['value'];
        }

        return [
            'sum_income' => $sumIncome,
            'sum_expense' => $sumExpense,
            'sum_total' => $sumIncome - $sumExpense,
        ];
    }

    public function updatebill(Request $request, $id)
    {
        // Validate request inputs
        $validatedData = $request->validate([
            'prison_id' => 'required|integer',
            'company_id' => 'required|integer',
            'bill_order' => 'required|array',
            'date' => 'required|string',
        ], [
            'required' => 'กรุณาระบุ :attribute ให้เรียบร้อย',
        ]);

        DB::beginTransaction();

        try {
            // Find the existing bill
            $bill = Bill::findOrFail($id);

            // Update bill fields
            $bill->update([
                'prison_id' => $validatedData['prison_id'],
                'company_id' => $validatedData['company_id'],
                'date' => $validatedData['date'],
            ]);

            // Revert previous orders
            $this->revertBillOrder($bill->id);

            // Create new or update orders
            $totals = $this->createBillOrder($validatedData['bill_order'], $bill->id);

            // Update totals in the bill
            $bill->update($totals);

            DB::commit();

            return $this->returnSuccess('อัปเดตสำเร็จ', $bill);
        } catch (\Exception $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e->getMessage(), 500);
        }
    }


    private function revertBillOrder(int $billId)
    {
        $billOrders = BillOrder::where('bill_id', $billId)->get();

        foreach ($billOrders as $order) {
            $product = Product::find($order->product_id);
            if ($product) {
                $product->value += $order->value;
                $product->save();
            }

            // Delete the order
            $order->delete();
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {

            $Item = Bill::find($id);
            $Item->delete();

            DB::commit();

            return $this->returnUpdate('ดำเนินการสำเร็จ');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e, 404);
        }
    }

    public function gettAll($id)
    {
        $data = Bill::with('billOrders.product')->find($id);
        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $data);
    }
}
