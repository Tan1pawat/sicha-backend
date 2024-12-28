<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Unit;
use App\Models\ProductType;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function getList()
    {
        $Item = Product::get()->toarray();

        if (!empty($Item)) {

            for ($i = 0; $i < count($Item); $i++) {
                $Item[$i]['No'] = $i + 1;
            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $Item);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getPage(Request $request)
    {
        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;


        $col = array('id', 'name', 'code','price','value', 'image', 'unit_id', 'product_type_id', 'created_at', 'updated_at');

        $orderby = array('id', 'name', 'code','price' ,'value', 'image', 'unit_id', 'product_type_id', 'created_at', 'updated_at');

        $D = Product::select($col);

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

            // Get all related units and product types in one query
            $unitIds = $d->pluck('unit_id')->filter()->unique(); // Filter out null or missing unit_id
            $productTypeIds = $d->pluck('product_type_id')->filter()->unique(); // Filter out null or missing product_type_id
    
            $units = Unit::whereIn('id', $unitIds)->pluck('name', 'id');
            $productTypes = ProductType::whereIn('id', $productTypeIds)->pluck('name', 'id');
    
            foreach ($d as $item) {
                $No++;
                $item->No = $No;
    
                // Handle null or missing unit_id and product_type_id
                $item->unit_name = $item->unit_id && isset($units[$item->unit_id]) ? $units[$item->unit_id] : 'Unknown Unit';
                $item->product_type_name = $item->product_type_id && isset($productTypes[$item->product_type_id]) 
                    ? $productTypes[$item->product_type_id] 
                    : 'Unknown Product Type';
            }

        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $d);
    }

    public function searchData(Request $request)
    {
        try{
            $key = $request->input('key');
            $Item = Product::where('name','like',"%{$key}%")
            ->limit(20)
            ->get()->toarray();
    
            if (!empty($Item)) {
    
                for ($i = 0; $i < count($Item); $i++) {
                    $Item[$i]['No'] = $i + 1;
                }
            }
    
            return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $Item);
        }catch(\Exception $e){
            return $this->returnErrorData($e->getMessage(), 404);
        }
    }
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        if (!isset($request->name)) {
            return $this->returnErrorData('กรุณาระบุ ชื่อ ให้เรียบร้อย', 404);
        }else if(!isset($request->unit_id)){
            return $this->returnErrorData('กรุณาระบุ หน่วย ให้เรียบร้อย', 404);
        }else if (!isset($request->product_type_id)) {
            return $this->returnErrorData('กรุณาระบุ ชนิดสินค้า ให้เรียบร้อย', 404);
        }
        else

            DB::beginTransaction();

        try {
            $lastProduct = Product::latest('id')->first();
            $lastId = $lastProduct ? $lastProduct->id : 0;

            $Item = new Product();
            $Item->name = $request->name;
            $Item->price = $request->price;
            $Item->value = $request->value;
            $Item->code = "SI-". ($lastId + 1);
            $Item->image = $request->image ?? "/public/files/default.jpg";
            $Item->unit_id= $request->unit_id;
            $Item->product_type_id= $request->product_type_id;
            
            $Item->save();

            DB::commit();

            return $this->returnSuccess('ดำเนินการสำเร็จ', $Item);
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e, 404);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\InfluSocial $influSocial
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $checkId = Product::find($id);
        if (!$checkId) {
            return $this->returnErrorData('ไม่พบข้อมูลที่ท่านต้องการ', 404);
        }
        $Item = Product::where('id', $id)
            ->first();
        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $Item);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Product $Product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $Product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product $Product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        if (!isset($request->name)) {
            return $this->returnErrorData('กรุณาระบุ ชื่อ ให้เรียบร้อย', 404);
        }else if(!isset($request->unit_id)){
            return $this->returnErrorData('กรุณาระบุ หน่วย ให้เรียบร้อย', 404);
        }else if (!isset($request->product_type_id)) {
            return $this->returnErrorData('กรุณาระบุ ชนิดสินค้า ให้เรียบร้อย', 404);
        }
        else

            DB::beginTransaction();

        try {
            $Item = Product::find($id);
            $Item->name = $request->name;
            $Item->price = $request->price;
            $Item->unit_id= $request->unit_id;
            $Item->product_type_id = $request->product_type_id;
            $Item->value = $request->value;
            
            $Item->save();
            
            DB::commit();

            return $this->returnSuccess('ดำเนินการสำเร็จ', $Item);
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e, 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product $Product
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {

            $Item = Product::find($id);
            $Item->delete();

            DB::commit();

            return $this->returnUpdate('ดำเนินการสำเร็จ');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e, 404);
        }
    
    }
}
