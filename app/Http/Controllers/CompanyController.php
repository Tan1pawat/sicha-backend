<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{
    public function getPage(Request $request)
    {
        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;


        $col = array('id', 'name', 'code', 'sender_address', 'sender_tax_number');

        $orderby = array('id', 'name', 'code', 'sender_address', 'sender_tax_number');

        $D = Company::select($col);


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


            foreach ($d as $item) {
                $No++;
                $item->No = $No;
            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $d);
    }
    public function getList()
    {
        $Item = Company::get()->toarray();

        if (!empty($Item)) {

            for ($i = 0; $i < count($Item); $i++) {
                $Item[$i]['No'] = $i + 1;
            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $Item);
    }
    public function store(Request $request)
    {

        if (!isset($request->name)) {
            return $this->returnErrorData('กรุณาระบุ ชื่อ ให้เรียบร้อย', 404);
        } else

            DB::beginTransaction();

        try {

            $Item = new Company();
            $Item->name = $request->name;
            $Item->code = $request->code;
            $Item->sender_address = $request->sender_address;
            $Item->sender_tax_number = $request->sender_tax_number;

            $Item->save();

            DB::commit();

            return $this->returnSuccess('ดำเนินการสำเร็จ', $Item);
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e, 404);
        }
    }

    public function show($id)
    {
        $checkId = Company::find($id);
        if (!$checkId) {
            return $this->returnErrorData('ไม่พบข้อมูลที่ท่านต้องการ', 404);
        }
        $Item = Company::where('id', $id)
            ->first();
        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $Item);
    }

    public function update(Request $request, $id)
    {

        DB::beginTransaction();

        try {
            $Item = Company::find($id);

            $Item->name = $request->name;
            $Item->code = $request->code;
            $Item->sender_address = $request->sender_address;
            $Item->sender_tax_number = $request->sender_tax_number;

            $Item->save();

            DB::commit();

            return $this->returnSuccess('ดำเนินการสำเร็จ', $Item);
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e, 404);
        }
    }
}
