<?php

namespace App\Http\Controllers;

use App\Mail\SendMail;
use App\Models\Log;
use App\Models\Orders;
use App\Models\Signature;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Intervention\Image\Facades\Image;
use \Firebase\JWT\JWT;
use Illuminate\Support\Facades\DB;
use Berkayk\OneSignal\OneSignalFacade;
use PhpParser\Node\Stmt\TryCatch;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function returnSuccess($massage, $data)
    {

        return response()->json([
            'code' => strval(200),
            'status' => true,
            'message' => $massage,
            'data' => $data,
        ], 200);
    }

    public function returnUpdate($massage)
    {
        return response()->json([
            'code' => strval(201),
            'status' => true,
            'message' => $massage,
            'data' => [],
        ], 201);
    }

    public function returnUpdateReturnData($massage, $data)
    {
        return response()->json([
            'code' => strval(201),
            'status' => true,
            'message' => $massage,
            'data' => $data,
        ], 201);
    }

    public function returnErrorData($massage, $code)
    {
        return response()->json([
            'code' => strval($code),
            'status' => false,
            'message' => $massage,
            'data' => [],
        ], 404);
    }

    public function returnError($massage)
    {
        return response()->json([
            'code' => strval(401),
            'status' => false,
            'message' => $massage,
            'data' => [],
        ], 401);
    }

    public function Log($userId, $description, $type)
    {
        $Log = new Log();
        $Log->user_id = $userId;
        $Log->description = $description;
        $Log->type = $type;
        $Log->save();
    }

    // public function sendMail($email, $data, $title, $type)
    // {

    //     $mail = new SendMail($email, $data, $title, $type);
    //     Mail::to($email)->send($mail);
    // }

    public function sendLine($line_token, $text)
    {

        $sToken = $line_token;
        $sMessage = $text;

        $chOne = curl_init();
        curl_setopt($chOne, CURLOPT_URL, "https://notify-api.line.me/api/notify");
        curl_setopt($chOne, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($chOne, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($chOne, CURLOPT_POST, 1);
        curl_setopt($chOne, CURLOPT_POSTFIELDS, "message=" . $sMessage);
        $headers = array('Content-type: application/x-www-form-urlencoded', 'Authorization: Bearer ' . $sToken . '');
        curl_setopt($chOne, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($chOne, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($chOne);

        curl_close($chOne);
    }
/// old version
    // public function uploadImages(Request $request)
    // {
    //     try {
    //         $image = $request->image;
    //         $path = $request->path;

    //         $input['imagename'] = md5(rand(0, 999999) . $image->getClientOriginalName()) . '.' . $image->extension();
    //         $destinationPath = public_path('/thumbnail');
    //         if (!File::exists($destinationPath)) {
    //             File::makeDirectory($destinationPath, 0777, true);
    //         }

    //         $img = Image::make($image->path());
    //         $img->save($destinationPath . '/' . $input['imagename']);
    //         $destinationPath = public_path($path);
    //         $image->move($destinationPath, $input['imagename']);

    //         return $this->returnSuccess('ดำเนินการสำเร็จ', $path . '/' . $input['imagename']);
    //     } catch (\Throwable $e) {

    //         return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e, 404);
    //     }
    // }
    public function uploadImages(Request $request)
{
    try {
        $image = $request->image;
        $path = $request->path;

        if ($request->path != "/image_card" && $request->path != "/image_bank"&& $request->path == null) {
            return $this->returnErrorData('เกิดข้อผิดพลาดที่ path ', 404);
        }

        $input['imagename'] = md5(rand(0, 999999) . $image->getClientOriginalName()) . '.' . $image->extension();
        $destinationPath = public_path("/images" . $path);

        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0777, true);
        }

        $image->move($destinationPath, $input['imagename']);

        return $this->returnSuccess('ดำเนินการสำเร็จ', "/images" . $path . '/' . $input['imagename']);
    } catch (\Throwable $e) {
        return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e, 404);
    }
}

    // public function uploadSignature(Request $request)
    // {

    //     $image = $request->image;
    //     $path = $request->path;
    //     $refno = $request->refno;
    //     $action = $request->action;

    //     $input['imagename'] = md5(rand(0, 999999) . $image->getClientOriginalName()) . '.' . $image->extension();
    //     $destinationPath = public_path('/thumbnail');
    //     if (!File::exists($destinationPath)) {
    //         File::makeDirectory($destinationPath, 0777, true);
    //     }

    //     $img = Image::make($image->path());
    //     $img->save($destinationPath . '/' . $input['imagename']);
    //     $destinationPath = public_path($path);
    //     $image->move($destinationPath, $input['imagename']);

    //     DB::beginTransaction();

    //     try {

    //         $Item = new Signature();
    //         $Item->refno = $refno;
    //         $Item->path = $path . $input['imagename'];
    //         $Item->action = $action;
    //         $Item->save();

    //         $ItemOrder = Orders::where('code', $refno)->first();
    //         if ($ItemOrder) {
    //             if ($Item->action == "Receive to Client") {
    //                 $ItemOrder->status = "ToClient";
    //             } else {
    //                 $ItemOrder->status = "Recived";
    //             }

    //             $ItemOrder->save();

    //             OneSignalFacade::sendNotificationToAll("แจ้งเตือนรายการนำเข้าโกดังสำเร็จรายการ : " . $refno);
    //         }

    //         //

    //         //log
    //         $userId = "admin";
    //         $type = 'เพิ่มรายการ';
    //         $description = 'ผู้ใช้งาน ' . $userId . ' ได้ทำการ ' . $type . ' ' . $request->name;
    //         $this->Log($userId, $description, $type);
    //         //

    //         DB::commit();

    //         return $this->returnSuccess('ดำเนินการสำเร็จ', $Item);
    //     } catch (\Throwable $e) {

    //         DB::rollback();

    //         return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e, 404);
    //     }

    //     // return $this->returnSuccess('ดำเนินการสำเร็จ', $path . $input['imagename']);
    // }

    public function uploadImage($image, $path)
    {
        $input['imagename'] = md5(rand(0, 999999) . $image->getClientOriginalName()) . '.' . $image->extension();
        $destinationPath = public_path("/images" . $path);
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0777, true);
        }

        $image->move($destinationPath, $input['imagename']);

        return '/images'.$path .'/'. $input['imagename'];
    }

    public function uploadPostImage(Request $request)
    {
        $image = $request->file;
        $input['imagename'] = md5(rand(0, 999999) . $image->getClientOriginalName()) . '.' . $image->extension();
        $destinationPath = public_path("/presentation/postimage") ;
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0777, true);
        }

        $image->move($destinationPath, $input['imagename']);

        return $this->returnSuccess('ดำเนินการสำเร็จ', "/presentation/postimage" .'/'. $input['imagename']);
    }

    public function uploadProductImage(Request $request)
    {
        $image = $request->file;
        $input['imagename'] = md5(rand(0, 999999) . $image->getClientOriginalName()) . '.' . $image->extension();
        $destinationPath = public_path("/product") ;
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0777, true);
        }

        $image->move($destinationPath, $input['imagename']);

        return $this->returnSuccess('ดำเนินการสำเร็จ', "/product" .'/'. $input['imagename']);
    }

    public function getImage($path)
    {
        $imagePath = $path;
    
        if (File::exists($imagePath)) {
            $files = File::allFiles($imagePath);
            
            if (count($files) > 0) {
                $image = $files[0];
                $imageContent = file_get_contents($image->getPathname());
                $base64 = base64_encode($imageContent);
                $extension = pathinfo($image->getPathname(), PATHINFO_EXTENSION);
                $mime = mime_content_type($image->getPathname());
    
                return [
                    'base64' => $base64,
                    'extension' => $extension,
                    'mime' => $mime
                ];
            }
        }
    
        return null;
    }
    public function uploadFile(Request $request)
    {
        if(!$request->hasFile('file')) {
            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ', 404);
        }
        $file = $request->file;

        $input['filename'] = time() . '.' . $file->extension();

        $destinationPath = public_path('files');
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0777, true);
        }

        $destinationPath = public_path('files');
        $file->move($destinationPath, $input['filename']);

        return $this->returnSuccess('ดำเนินการสำเร็จ','/files' .'/'. $input['filename']);
    }

    public function downloadFile(Request $request)
    {
        $filePath = $request->file_path;
        if (!File::exists(public_path($filePath))) {
            return $this->returnErrorData('File not found.', 404);
        }
    
        // Get the file extension
        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
    
        // Set the appropriate headers based on the file extension
        switch (strtolower($fileExtension)) {
            case 'pdf':
                $contentType = 'application/pdf';
                break;
            case 'xlsx':
            case 'xls':
                $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                break;
            case 'docx':
            case 'doc':
                $contentType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                break;
            case 'pptx':
            case 'ppt':
                $contentType = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
                break;
            case 'jpeg':
            case 'jpg':
                $contentType = 'image/jpeg';
                break;
            case 'png':
                $contentType = 'image/png';
                break;
            default:
                $contentType = File::mimeType(public_path($filePath));
                break;
        }
    
        // Get the file name from the path
        $fileName = pathinfo($filePath, PATHINFO_BASENAME);
    
        // Set the appropriate headers for file download
        $headers = [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Content-Transfer-Encoding' => 'binary',
        ];
    
        // Return the file for download
        return response()->download(public_path($filePath), $fileName, $headers);
    }

    public function getDropDownYear()
    {
        $Year = intval(((date('Y')) + 1) + 543);

        $data = [];

        for ($i = 0; $i < 10; $i++) {

            $Year = $Year - 1;
            $data[$i]['year'] = $Year;
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $data);
    }

    public function getDropDownProvince()
    {

        $province = array("กระบี่", "กรุงเทพมหานคร", "กาญจนบุรี", "กาฬสินธุ์", "กำแพงเพชร", "ขอนแก่น", "จันทบุรี", "ฉะเชิงเทรา", "ชลบุรี", "ชัยนาท", "ชัยภูมิ", "ชุมพร", "เชียงราย", "เชียงใหม่", "ตรัง", "ตราด", "ตาก", "นครนายก", "นครปฐม", "นครพนม", "นครราชสีมา", "นครศรีธรรมราช", "นครสวรรค์", "นนทบุรี", "นราธิวาส", "น่าน", "บุรีรัมย์", "บึงกาฬ", "ปทุมธานี", "ประจวบคีรีขันธ์", "ปราจีนบุรี", "ปัตตานี", "พะเยา", "พังงา", "พัทลุง", "พิจิตร", "พิษณุโลก", "เพชรบุรี", "เพชรบูรณ์", "แพร่", "ภูเก็ต", "มหาสารคาม", "มุกดาหาร", "แม่ฮ่องสอน", "ยโสธร", "ยะลา", "ร้อยเอ็ด", "ระนอง", "ระยอง", "ราชบุรี", "ลพบุรี", "ลำปาง", "ลำพูน", "เลย", "ศรีสะเกษ", "สกลนคร", "สงขลา", "สตูล", "สมุทรปราการ", "สมุทรสงคราม", "สมุทรสาคร", "สระแก้ว", "สระบุรี", "สิงห์บุรี", "สุโขทัย", "สุพรรณบุรี", "สุราษฎร์ธานี", "สุรินทร์", "หนองคาย", "หนองบัวลำภู", "อยุธยา", "อ่างทอง", "อำนาจเจริญ", "อุดรธานี", "อุตรดิตถ์", "อุทัยธานี", "อุบลราชธานี");

        $data = [];

        for ($i = 0; $i < count($province); $i++) {

            $data[$i]['province'] = $province[$i];
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $data);
    }

    public function getDownloadFomatImport($params)
    {

        $file = $params;
        $destinationPath = public_path() . "/fomat_import/";

        return response()->download($destinationPath . $file);
    }

    public function checkDigitMemberId($memberId)
    {

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {

            $sum += (int) ($memberId[$i]) * (13 - $i);
        }

        if ((11 - ($sum % 11)) % 10 == (int) ($memberId[12])) {
            return 'true';
        } else {
            return 'false';
        }
    }

    public function genCode(Model $model, $prefix, $number)
    {

        $countPrefix = strlen($prefix);
        $countRunNumber = strlen($number);

        //get last code
        $Property_type = $model::orderby('code', 'desc')->first();
        if ($Property_type) {
            $lastCode = $Property_type->code;
        } else {
            $lastCode = $prefix . $number;
        }

        $codelast = substr($lastCode, $countPrefix, $countRunNumber);

        $newNumber = intval($codelast) + 1;
        $Number = sprintf('%0' . strval($countRunNumber) . 'd', $newNumber);

        $runNumber = $prefix . $Number;

        return $runNumber;
    }


    // public function dateBetween($dateStart, $dateStop)
    // {
    //     $datediff = strtotime($dateStop) - strtotime($this->dateform($dateStart));
    //     return abs($datediff / (60 * 60 * 24));
    // }

    // public function log_noti($Title, $Description, $Url, $Pic, $Type)
    // {
    //     $log_noti = new Log_noti();
    //     $log_noti->title = $Title;
    //     $log_noti->description = $Description;
    //     $log_noti->url = $Url;
    //     $log_noti->pic = $Pic;
    //     $log_noti->log_noti_type = $Type;

    //     $log_noti->save();
    // }

    /////////////////////////////////////////// seach datatable  ///////////////////////////////////////////

    public function withPermission($query, $search)
    {

        $col = array('id', 'name', 'create_by', 'update_by', 'created_at', 'updated_at');

        $query->orWhereHas('permission', function ($query) use ($search, $col) {

            $query->Where(function ($query) use ($search, $col) {

                //search datatable
                $query->orwhere(function ($query) use ($search, $col) {
                    foreach ($col as &$c) {
                        $query->orWhere($c, 'like', '%' . $search['value'] . '%');
                    }
                });
            });
        });

        return $query;
    }

    public function withMember($query, $search)
    {

        // $col = array('id', 'member_group_id','code', 'name', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        // $query->orWhereHas('member', function ($query) use ($search, $col) {

        //     $query->Where(function ($query) use ($search, $col) {

        //         //search datatable
        //         $query->orwhere(function ($query) use ($search, $col) {
        //             foreach ($col as &$c) {
        //                 $query->orWhere($c, 'like', '%' . $search['value'] . '%');
        //             }
        //         });
        //     });

        // });

        // return $query;
    }


    public function withInquiryType($query, $search)
    {

        $col = array('id', 'code', 'name', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $query->orWhereHas('inquiry_type', function ($query) use ($search, $col) {

            $query->Where(function ($query) use ($search, $col) {

                //search datatable
                $query->orwhere(function ($query) use ($search, $col) {
                    foreach ($col as &$c) {
                        $query->orWhere($c, 'like', '%' . $search['value'] . '%');
                    }
                });
            });
        });

        return $query;
    }

    public function withPropertyType($query, $search)
    {

        $col = array('id', 'code', 'name', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $query->orWhereHas('property_type', function ($query) use ($search, $col) {

            $query->Where(function ($query) use ($search, $col) {

                //search datatable
                $query->orwhere(function ($query) use ($search, $col) {
                    foreach ($col as &$c) {
                        $query->orWhere($c, 'like', '%' . $search['value'] . '%');
                    }
                });
            });
        });

        return $query;
    }

    public function withPropertySubType($query, $search)
    {

        $col = array('id', 'property_type_id', 'code', 'name', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $query->orWhereHas('property_sub_type', function ($query) use ($search, $col) {

            $query->Where(function ($query) use ($search, $col) {

                //search datatable
                $query->orwhere(function ($query) use ($search, $col) {
                    foreach ($col as &$c) {
                        $query->orWhere($c, 'like', '%' . $search['value'] . '%');
                    }
                });
            });
        });

        return $query;
    }

    public function withPropertyAnnouncer($query, $search)
    {

        $col = array('id', 'name', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $query->orWhereHas('property_announcer', function ($query) use ($search, $col) {

            $query->Where(function ($query) use ($search, $col) {

                //search datatable
                $query->orwhere(function ($query) use ($search, $col) {
                    foreach ($col as &$c) {
                        $query->orWhere($c, 'like', '%' . $search['value'] . '%');
                    }
                });
            });
        });

        return $query;
    }

    public function withPropertyColorLand($query, $search)
    {

        $col = array('id', 'name', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $query->orWhereHas('property_color_land', function ($query) use ($search, $col) {

            $query->Where(function ($query) use ($search, $col) {

                //search datatable
                $query->orwhere(function ($query) use ($search, $col) {
                    foreach ($col as &$c) {
                        $query->orWhere($c, 'like', '%' . $search['value'] . '%');
                    }
                });
            });
        });

        return $query;
    }

    public function withPropertyOwnership($query, $search)
    {

        $col = array('id', 'name', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $query->orWhereHas('property_ownership', function ($query) use ($search, $col) {

            $query->Where(function ($query) use ($search, $col) {

                //search datatable
                $query->orwhere(function ($query) use ($search, $col) {
                    foreach ($col as &$c) {
                        $query->orWhere($c, 'like', '%' . $search['value'] . '%');
                    }
                });
            });
        });

        return $query;
    }

    public function withPropertyFacility($query, $search)
    {

        $col = array('id', 'name', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $query->orWhereHas('property_facility', function ($query) use ($search, $col) {

            $query->Where(function ($query) use ($search, $col) {

                //search datatable
                $query->orwhere(function ($query) use ($search, $col) {
                    foreach ($col as &$c) {
                        $query->orWhere($c, 'like', '%' . $search['value'] . '%');
                    }
                });
            });
        });

        return $query;
    }

    public function withPropertySubFacility($query, $search)
    {

        $col = array('id', 'property_facility_id', 'name', 'icon', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $query->orWhereHas('property_sub_facility', function ($query) use ($search, $col) {

            $query->Where(function ($query) use ($search, $col) {

                //search datatable
                $query->orwhere(function ($query) use ($search, $col) {
                    foreach ($col as &$c) {
                        $query->orWhere($c, 'like', '%' . $search['value'] . '%');
                    }
                });

                $query = $this->withPropertyFacility($query, $search);
            });
        });

        return $query;
    }

    public function withPropertySubFacilityExplend($query, $search)
    {

        $col = array('id', 'property_sub_facility_id', 'name', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $query->orWhereHas('property_sub_facility_explend', function ($query) use ($search, $col) {

            $query->Where(function ($query) use ($search, $col) {

                //search datatable
                $query->orwhere(function ($query) use ($search, $col) {
                    foreach ($col as &$c) {
                        $query->orWhere($c, 'like', '%' . $search['value'] . '%');
                    }
                });

                $query = $this->withPropertySubFacility($query, $search);
            });
        });

        return $query;
    }

    // public function decodername($data)
    // {
    //     $token = str_replace('Bearer ', '', $data);

    //         if ($token == "") {
    //             return $this->returnError('Token Not Found', 401);
    //         }
    //         $name = JWT::decode($token, "key", array('HS256'))->lun;

    //         return $name;
    // }

    // public function decoderid($data)
    // {
    //     $token = str_replace('Bearer ', '', $data);

    //         if ($token == "") {
    //             return $this->returnError('Token Not Found', 401);
    //         }
    //         $name = JWT::decode($token, "key", array('HS256'))->aud;

    //         return $name;
    // }
    /////////////////////////////////////////// seach datatable  ///////////////////////////////////////////

}
