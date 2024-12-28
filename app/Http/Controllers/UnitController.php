<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class UnitController extends Controller
{

    public function getList()
    {
        $Item = Unit::get()->toarray();

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
        }
        else

            DB::beginTransaction();

        try {

            $Item = new Unit();
            $Item->name = $request->name;
            
            $Item->save();

            DB::commit();

            return $this->returnSuccess('ดำเนินการสำเร็จ', $Item);
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e, 404);
        }
    }
}
