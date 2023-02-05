<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Helpers\UsersHelper;
use App\Helpers\SmsHelper;
use App\Helpers\Helper;
use App\Helpers\LogsHelper;


use App\User;
use App\Models\NumberValidation;
use App\Interceptions\Users\UsersCreditorsInterception;

use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use stdClass;
use Str;
use Storage;
use File;

use App\Mail\SendMailToUser;

class UsersController extends Controller
{
    public function update(Request $request)
    {
        $local_user = User::where('id', $request->id)->first();
        if (!$local_user || UsersHelper::userAlreadyExists($request->id, $request->email, $request->nickname)) return abort(409, "User email ($request->email) or nickname ($request->nickname) is already on DB.");
        //dd($request->full_name);
        $local_user->full_name = $request->full_name;
        $local_user->email = $request->email;
        $local_user->nickname = $request->nickname;
        //Passport data
        if($request->birthPlace != "")
            $local_user->birth_place = $request->birthPlace;
        if($request->birthDate != ""){
            try{
                $timestamp = strtotime($request->birthDate);
                $date = date("Y-m-d H:i:s", $timestamp);
                $local_user->birth_date = $date;
            }
            catch(Exception $e){}
        }
        if($request->passportIssueDate != ""){
            try{
                $timestamp = strtotime($request->passportIssueDate);
                $date = date("Y-m-d H:i:s", $timestamp);
                $local_user->passport_issue_date = $date;
            }
            catch(Exception $e){}
        }
        if($request->passportDepartmentСode != "")
            $local_user->passport_department_code = $request->passportDepartmentСode;
        if($request->passportIssuedBy != "")
            $local_user->passport_issued_by = $request->passportIssuedBy;
        if($request->passportSN != "")
            $local_user->passport_sn = $request->passportSN;
        if($request->passportRegistrationAddress != "")
            $local_user->passport_registration_address = $request->passportRegistrationAddress;
        $local_user->addresses_equal = (int)$request->addressesEqual;
        
        $local_user->save();
    }

    public function updateProfilePicture(Request $request)
    {
        $local_user = User::where('id', $request->id)->first();
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $image = $request->file('image');
            if ($local_user->image) {
                File::delete('images/users/' . $local_user->image);
            }
            $name = time() . Str::random(5) . '_' . date("Y-m-d") . '_' . $local_user->id . '.' . $image->getClientOriginalExtension();
            Storage::disk('public')->put('images/users/' . $name, File::get($image));
            $local_user->image =  $name;
            $local_user->save();
        }else{
            if ($request->image_deleted && $local_user->image) {
                File::delete('images/users/' . $local_user->image);
                $local_user->image =  null;
                $local_user->save();
            }
        }
    }

    public function updateNewsTags(Request $request)
    {
        UsersHelper::fillInterceptions($request->tags, 'news_tags', null, $request->userId);
    }

    public function resetCodeAndSendSms(Request $request)
    {
        $phone = Helper::cleanPhoneNumber($request->phone);
        $validation = NumberValidation::where('phone', $phone)->orderBy('id', 'desc')->first();
        $generated_code = Helper::generateRandomNumber(5);
        $validation->code = $generated_code;
        $validation->attempts = 0;
        $validation->save();
        SmsHelper::sendSMS("Code: {$generated_code}", $phone);
        return response()->json(['success' => true, 'code' => 'CODE_RESENT'], 200);
    }


    /* METHODS TO VALIDATE PHONE NUMBER AND RESET PASSWORD */
    public function validatePhoneAndSendSms(Request $request)
    {
        $phone = Helper::cleanPhoneNumber($request->phone);
        if (!UsersHelper::phoneAlreadyExists($phone)) return response()->json(['success' => false, 'code' => 'PHONE_DOESNT_EXISTS'], 404);
        $phone_is_blocked = NumberValidation::where('phone', $phone)->where('blocked_till', '>', Carbon::now())->first();
        if ($phone_is_blocked) return response()->json(['success' => false, 'code' => 'STILL_BLOCKED'], 403);
        $new_validation =  new NumberValidation();
        $new_validation->phone = $phone;
        $generated_code = Helper::generateRandomNumber(5);
        $new_validation->code = $generated_code;
        $new_validation->save();
        SmsHelper::sendSMS("Code: {$generated_code}", $phone);
        return response()->json(['success' => true, 'code' => 'CODE_SENT'], 200);
    }

    public function resetPassword(Request $request)
    {
        $phone = Helper::cleanPhoneNumber($request->phone);
        $code = $request->code;
        if (!UsersHelper::phoneAlreadyExists($phone)) return response()->json(['success' => false, 'code' => 'PHONE_DOESNT_EXISTS'], 404);

        $phone_is_blocked = NumberValidation::where('phone', $phone)->where('blocked_till', '>', Carbon::now())->first();
        if ($phone_is_blocked) return response()->json(['success' => false, 'code' => 'STILL_BLOCKED'], 403);
        $validation = NumberValidation::where('phone', $phone)->orderBy('id', 'desc')->first();
        $validation->attempts += 1;

        if ($validation->attempts > 4) {
            $validation->blocked_till = Carbon::now()->addHours(2);
            $validation->save();
            return response()->json(['success' => false, 'code' => 'BLOCKED'], 403);
        }
        $validation->save();
        if ($validation->code != $code) return response()->json(['success' => false, 'code' => 'INCORRECT_CODE'], 404);

        $user = User::where("phone", $phone)->first();
        $password = Str::random(10);
        $user->password = Hash::make($password);
        $user->save();
        // $data_obj = (object) ["email"=> $user->email, "password"=> $password];
        $data_obj = new stdClass();
        $data_obj->email = $user->email;
        $data_obj->password = $password;
        Mail::to($user->email)->send(new SendMailToUser($data_obj, 'PASSWORD_RESETED'));
        return response()->json(['success' => true, 'code' => 'PASSWORD_RESETED'], 200);
    }

    /* ----------------------METHODS TO VALIDATE AND CREATE USER -------------------- */

    public function validateDataAndSendSms(Request $request)
    {
        $phone = Helper::cleanPhoneNumber($request->phone);
        if (UsersHelper::emailAlreadyExists($request->email)) return response()->json(['success' => false, 'code' => 'EMAIL_CONFLICT'], 409);
        if (UsersHelper::nicknameAlreadyExists($request->nickname)) return response()->json(['success' => false, 'code' => 'NICKNAME_CONFLICT'], 409);
        if (UsersHelper::phoneAlreadyExists($phone)) return response()->json(['success' => false, 'code' => 'PHONE_CONFLICT'], 409);
        $phone_is_blocked = NumberValidation::where('phone', $phone)->where('blocked_till', '>', Carbon::now())->first();
        if ($phone_is_blocked) return response()->json(['success' => false, 'code' => 'STILL_BLOCKED'], 403);
        $new_validation =  new NumberValidation();
        $new_validation->phone = $phone;
        $generated_code = Helper::generateRandomNumber(5);
        $new_validation->code = $generated_code;
        $new_validation->save();
        SmsHelper::sendSMS("Code: {$generated_code}", $phone);
        return response()->json(['success' => true, 'code' => 'CODE_SENT'], 200);
    }

    public function createUser(Request $request)
    {
        $code = $request->code;
        $phone = Helper::cleanPhoneNumber($request->phone);
        $is_blocked = !!NumberValidation::where('phone', $phone)->where('blocked_till', '>', Carbon::now())->first();
        if ($is_blocked) return response()->json(['success' => false, 'code' => 'STILL_BLOCKED'], 403);
        $validation = NumberValidation::where('phone', $phone)->orderBy('id', 'desc')->first();
        $validation->attempts += 1;

        if ($validation->attempts > 4) {
            $validation->blocked_till = Carbon::now()->addHours(2);
            $validation->save();
            return response()->json(['success' => false, 'code' => 'BLOCKED'], 403);
        }

        $validation->save();

        if ($validation->code != $code) return response()->json(['success' => false, 'code' => 'INCORRECT_CODE'], 404);

        if (UsersHelper::emailAlreadyExists($request->email)) return abort(409, "EMAIL_CONFLICT");
        if (UsersHelper::nicknameAlreadyExists($request->nickname)) return abort(409, "NICKNAME_CONFLICT");
        if (UsersHelper::phoneAlreadyExists($phone)) return abort(409, "PHONE_CONFLICT");
        $new_user = new User();
        $new_user->nickname = $request->nickname;
        $new_user->email = $request->email;
        $new_user->phone = $phone;
        if($request->birthPlace != "")
            $new_user->birth_place = $request->birthPlace;
        if($request->birthDate != ""){
            try{
                $timestamp = strtotime($request->birthDate);
                $date = date("Y-m-d H:i:s", $timestamp);
                $new_user->birth_date = $date;
            }
            catch(Exception $e){}
        }
        if($request->passportIssueDate != ""){
            try{
                $timestamp = strtotime($request->passportIssueDate);
                $date = date("Y-m-d H:i:s", $timestamp);
                $new_user->passport_issue_date = $date;
            }
            catch(Exception $e){}
        }
        if($request->passportDepartmentСode != "")
            $new_user->passport_department_code = $request->passportDepartmentСode;
        if($request->passportIssuedBy != "")
            $new_user->passport_issued_by = $request->passportIssuedBy;
        if($request->passportSN != "")
            $new_user->passport_sn = $request->passportSN;
        if($request->passportRegistrationAddress != "")
            $new_user->passport_registration_address = $request->passportRegistrationAddress;
        if($request->addressesEqual != "")
            $new_user->addresses_equal = (int)$request->addressesEqual;
        $password = Str::random(10);
        $new_user->password = Hash::make($password);
        $new_user->save();
        //$data_obj = (object) ["email"=> $request->email, "password"=> $password];
        $data_obj = new stdClass();
        $data_obj->email = $request->email;
        $data_obj->password = $password;
        Mail::to($request->email)->send(new SendMailToUser($data_obj, 'REGISTRATION_SUCCESFULLY'));

        return response()->json(['success' => true, 'password' => $password], 200);
    }



    public function updateMyCreditors(Request $request)
    {
        $new_creditors_list = $request->myCreditorsList;
        $user_id = $request->user_id;
        UsersCreditorsInterception::where('user_id', $user_id)->delete();
        foreach ($new_creditors_list as $key => $creditor) {
            $obj = [
                "user_id" => $user_id,
                "creditor_id" => $creditor['value'],
            ];
            UsersCreditorsInterception::create($obj);
        };
        return response()->json(['success' => 'success'], 200);
    }

    public function getUsers(Request $request, $xnumber = null)
    {
        return User::selectFields($request->isKeyValue)->paginateOrGet($request->page, $xnumber);
    }


    /*----------------------------------------------------------------------------------------------------------------------------------------------- */
    /* Functions for ADMIN, protected by JWT and double verification with email, the verification happens on the middleware EnsureHasPermissions */
    /*----------------------------------------------------------------------------------------------------------------------------------------------- */
    public function addAllRelationships($user)
    {
        $user->role;
    }

    public function getAll(Request $request, $xnumber = 10)
    {
        $users = User::matchEmailLike($request->email ?? null)
            ->matchPhoneLike($request->phone ?? null)
            ->orderByDate()
            ->paginateOrGet($request->page, $xnumber);
        UsersHelper::addRoleMeaning($users);
        return $users;
    }

    public function getByIdFull(Request $request, $id)
    {
        $user = User::findOrFail($id);
        UsersHelper::addRoleMeaningToOneElement($user);
        return $user;
    }

    public function add(Request $request)
    {
        $user = new User();
        $user->role_id  = $request->role_id ?? 2;
        $user->nickname  = $request->nickname;
        $user->email   = $request->email;
        $user->password   = Hash::make($request->password);
        $user->phone = $request->phone;
        $user->full_name   = $request->full_name;
        $user->save();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($user);
        LogsHelper::addLogEntry($request, "users", "create", $user);
    }

    public function updateById(Request $request, $id)
    {
        $old_user= User::findOrFail($id);
        $this->addAllRelationships($old_user);

        $user = User::findOrFail($id);
        $user->role_id  = $request->role_id ?? 2;
        $user->nickname  = $request->nickname;
        $user->email   = $request->email;
        if ($request->password) {
            $user->password   = Hash::make($request->password);
        }
        $user->phone = $request->phone;
        $user->full_name   = $request->full_name;
        $user->save();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($user);
        LogsHelper::addLogEntry($request, "users", "update", $user, $old_user);
    }

    public function deleteById(Request $request, $id)
    {
        $old_user = User::findOrFail($id);
        $this->addAllRelationships($old_user);

        User::findOrFail($id)->delete();
        
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        LogsHelper::addLogEntry($request, "users", "delete", null, $old_user);
    }
}
