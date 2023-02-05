<?php

namespace App\Http\Controllers;

use App\Helpers\SubDomainHelper;
use App\Helpers\RequestsHelper;
use App\Helpers\ProductsHelper;
use App\Helpers\LogsHelper;

use Illuminate\Http\Request;

use App\Models\Creditors\CreditorsBlacklist;

use App\Models\Requests\SupportRequest;
use App\Models\Requests\CheckHistoryRequest;
use App\Models\Requests\CallRequest;
use App\Models\Requests\CreditSelectionRequest;
use App\Models\Requests\AdvertisementRequest;

use App\Models\Products\Credits\CreditsRequest;
use App\Models\Products\Cards\CardsRequest;
use App\Models\Products\Deposits\DepositsRequest;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;

use App\Mail\SendMailToStaff;
use App\Mail\SendMailToUser;

use App\User;
use stdClass;

class RequestsController extends Controller
{
    public function addSupportRequest(Request $request)
    {
        $user_id = $request->id;
        $user_email = $request->email;

        $support_request = new SupportRequest();
        $support_request->text = $request->text;
        if ($user_id) {
            $support_request->user_id = $user_id;
        } else {
            $support_request->email = $user_email;
        }
        $support_request->save();
        // support@bankiroff.ru
        // $data_obj = (object) ["email" => $user_email, "text"=> $request->text];
        $data_obj = new stdClass();
        $data_obj->email = $user_email;
        $data_obj->text = $request->text;
        Mail::to(env('MAIL_STAFF_SUPPORT'))->send(new SendMailToStaff($data_obj, 'NEW_HELP_REQUEST'));
        return Mail::to($user_email)->send(new SendMailToUser($data_obj, 'NEW_HELP_REQUEST'));
    }


    public function addAdvertisementRequest(Request $request)
    {
        $user_id = $request->user_id;
        $items = json_decode($request->adType, true);
        $advertisement_request = new AdvertisementRequest();
        if ($user_id) {
            $advertisement_request->user_id = $user_id;
        }
        $advertisement_request->text = $request->text;
        $advertisement_request->phone = $request->phone;
        $advertisement_request->full_name = $request->full_name;
        $advertisement_request->email = $request->email;
        $advertisement_request->save();
        RequestsHelper::fillAdvertisementsInterceptions($items, $advertisement_request->id);
        // info@bankiroff.ru
        $data_obj = new stdClass();
        $ad_type = "";
        foreach ($items as $key => $item) {
            $ad_type .= ", ".$item["title"];
        }
        $ad_type = trim($ad_type, ", ");
        $data_obj->full_name = $request->full_name;
        $data_obj->email = $request->email;
        $data_obj->text = $request->text;
        $data_obj->phone = $request->phone;
        $data_obj->ad_type = $ad_type;
        Mail::to(env('MAIL_STAFF_INFO'))->send(new SendMailToStaff($data_obj, 'NEW_ADVERTISEMENT_REQUEST'));
        Mail::to($request->email)->send(new SendMailToUser($data_obj, 'NEW_ADVERTISEMENT_REQUEST'));
    }

    public function addCheckCreditHistoryRequest(Request $request)
    {
        $default_region = 604;
        $history_request = new CheckHistoryRequest();
        $history_request->email = $request->email;
        $history_request->full_name = $request->full_name;
        $history_request->birthday = $request->birthday;
        $history_request->phone = $request->phone;
        $history_request->region_id     = $request->region_id && $request->region_id != 'default' ? $request->region_id : $default_region;
        $history_request->save();
        // $data_obj = (object) ["email" => $request->email, "full_name"=> $request->full_name, "phone"=> $request->phone,];
        $data_obj = new stdClass();
        $data_obj->email = $request->email;
        $data_obj->full_name = $request->full_name;
        $data_obj->phone = $request->phone;
        Mail::to(env('MAIL_STAFF_REQUESTS'))->send(new SendMailToStaff($data_obj, 'NEW_CREDIT_HISTORY_REQUEST'));
        return Mail::to($request->email)->send(new SendMailToUser($data_obj, 'NEW_CREDIT_HISTORY_REQUEST'));
    }

    public function addBlackListRequest(Request $request)
    {
        $creditor_blacklist_request = new CreditorsBlacklist();
        $creditor_blacklist_request->creditor_id = $request->creditor_id;
        $creditor_blacklist_request->user_id = $request->user_id;
        $creditor_blacklist_request->text = $request->text;
        $creditor_blacklist_request->level = $request->level;
        $creditor_blacklist_request->save();
        $user = User::where('id', $request->user_id)->first();
        // $data_obj = (object) ["text" => $request->text,"level" => $request->level, "full_name" => $user->full_name, "email" => $user->email, "phone" => $user->phone];
        $data_obj = new stdClass();
        $data_obj->email = $user->email;
        $data_obj->full_name = $request->full_name;
        $data_obj->phone = $request->phone;
        $data_obj->text = $request->text;
        $data_obj->level = $request->level;
        Mail::to(env('MAIL_STAFF_INFO'))->send(new SendMailToStaff($data_obj, 'NEW_BLACKLIST_REQUEST'));
        return Mail::to($user->email)->send(new SendMailToUser($data_obj, 'NEW_BLACKLIST_REQUEST'));
    }


    public function addCallRequest(Request $request)
    {
        $region_id = SubDomainHelper::getSubdomainId($request->header('Subdomain')) ?? null;
        $call_request = new CallRequest();
        $call_request->user_id = $request->user_id;
        $call_request->type_slug  = $request->type_slug;
        $call_request->item_id = $request->item_id;
        $call_request->phone  = $request->phone;
        $call_request->text  = $request->text;
        $call_request->full_name  = $request->full_name;
        $call_request->region_id = $region_id;
        $call_request->save();
        $user = User::where('id', $request->user_id)->first();
        // $data_obj = (object) ["full_name" => $request->full_name, "phone" => $request->phone, "text" => $request->text];
        $data_obj = new stdClass();
        $data_obj->text = $request->text;
        $data_obj->full_name = $request->full_name;
        $data_obj->phone = $request->phone;
        if ($user) {
            Mail::to($user->email)->send(new SendMailToUser($data_obj, 'NEW_CALL_REQUEST'));
        }
        return Mail::to(env('MAIL_STAFF_REQUESTS'))->send(new SendMailToStaff($data_obj, 'NEW_CALL_REQUEST'));
    }

    public function addProductRequest(Request $request)
    {
        $default_region = 604;
        $type_slug = $request->type_slug;
        if ($type_slug == 'deposits') {
            $product_request = new DepositsRequest();
            $product_request->amount  = $request->amount;
            $product_request->days_period  = $request->days_period;
            $type_request = 'deposits';
        } else if ($type_slug == 'cards_credit' || $type_slug == 'cards_debit') {
            $product_request = new CardsRequest();
            $product_request->grace_period = $request->grace_period;
            $product_request->credit_limit = $request->credit_limit;

            if ($request->card_category_id==null) { $product_request->card_category_id = 1; }
            else { $product_request->card_category_id = $request->card_category_id; }

            if ($request->currency_id==null) { $product_request->currency_id = 35; }
            else { $product_request->currency_id = $request->currency_id; }

            $product_request->type = $request->card_type;
            $type_request = 'cards';
        } else {
            $product_request = new CreditsRequest();
            $product_request->amount  = $request->amount;
            $product_request->type_slug  = $request->type_slug;
            $product_request->pledge_slug  = $request->pledge_slug;
            if ($type_slug == 'microloans') {
                $product_request->days_period  = $request->days_period;
            } else {
                $product_request->months_period  =  $request->months_period;
            }
            $type_request = 'credits';
        }

        if (isset($request->from_url)) $product_request->from_url = $request->from_url;
        $product_request->user_id = $request->user_id;
        $product_request->creditor_id = $request->creditor_id;
        $product_request->full_name  = $request->full_name;
        // $product_request->email = $request->email;
        // $product_request->birthday  = $request->birthday;
        $product_request->phone  = $request->phone;
        $product_request->user_comment  = $request->text;
        $product_request->item_id = $request->item_id;
        $product_request->region_id =  $request->region_id && $request->region_id != 'default' ? $request->region_id : $default_region;
        $product_request->title = $request->title_request;
        $product_request->save();
        $data_obj = new stdClass();
        // $data_obj->email = $request->email;
        $data_obj->full_name = $request->full_name;
        $data_obj->phone = $request->phone;
        $data_obj->text = $request->text;
        $data_obj->title_request = $request->title_request;
        // Mail::to($request->email)->send(new SendMailToUser($data_obj, 'NEW_PRODUCT_REQUEST'));
        Mail::to(env('MAIL_STAFF_REQUESTS'))->send(new SendMailToStaff($data_obj, 'NEW_PRODUCT_REQUEST'));
        if (env('APP_ENV') == 'production') Http::post('https://mosinvestfinans.ru/api/bankiroff/lead', ["requestType" => $type_request, "data" => $product_request, "urlParams" => $request->url_params]);
    }

    public function addCreditSelectionRequest(Request $request)
    {
        $default_region = 604;
        $product_request = new CreditSelectionRequest();
        $product_request->credit_type  = $request->credit_type;
        $product_request->employment_type_id  = $request->employment_type_id;
        $product_request->amount  = $request->amount;
        $product_request->salary  = $request->salary;
        $product_request->days_period  = $request->days_period;
        $product_request->months_period  =  $request->months_period;
        $product_request->years_period  =  $request->years_period;
        $product_request->user_id = $request->user_id;
        $product_request->email = $request->email;
        $product_request->full_name  = $request->full_name;
        $product_request->phone  = $request->phone;
        $product_request->region_id =  $request->region_id && $request->region_id != 'default' ? $request->region_id : $default_region;
        $product_request->title = $request->title_request;
        $product_request->save();
        $data_obj = new stdClass();
        $data_obj->email = $request->email;
        $data_obj->full_name = $request->full_name;
        $data_obj->phone = $request->phone;
        $data_obj->title_request = $request->title_request;
        Mail::to($request->email)->send(new SendMailToUser($data_obj, 'NEW_CREDIT_SELECTION_REQUEST'));
        return Mail::to(env('MAIL_STAFF_REQUESTS'))->send(new SendMailToStaff($data_obj, 'NEW_CREDIT_SELECTION_REQUEST'));
    }

    /*----------------------------------------------------------------------------------------------------------------------------------------------- */
    /* Methods for authorized users, protected by JWT */
    /*----------------------------------------------------------------------------------------------------------------------------------------------- */


    public function changeStatusById(Request $request, $id)
    {
        $request_type = $request->type;
        switch ($request_type) {
            case 'credits':
            case 'consumers':
            case 'microloans':
            case 'pledge':
            case 'refinancing':
            case 'mortgage':
                $base_model = CreditsRequest::findOrFail($id);
                break;
            case 'deposits':
                $base_model = DepositsRequest::findOrFail($id);
                break;
            case 'cards':
                $base_model = CardsRequest::findOrFail($id);
                break;
        }
        $base_model->status_id = 0;
        $base_model->save();
    }


    /*----------------------------------------------------------------------------------------------------------------------------------------------- */
    /* Methods for ADMIN, protected by JWT and double verification with email, the verification happens on the middleware EnsureHasPermissions */
    /*----------------------------------------------------------------------------------------------------------------------------------------------- */

    public function addAllRelationships($request_item)
    {
        $request_item->region;
    }

    /*-------------------------- */
    /* Credit requests */
    /*------------------------- */
    public function getAllCreditRequests(Request $request, $xnumber = 10)
    {
        $requests = CreditsRequest::orderByDate()
            ->paginateOrGet($request->page, $request->paginatedBy ?? $xnumber);
        RequestsHelper::addUser($requests);
        RequestsHelper::addRegion($requests);
        return $requests;
    }

    public function getCreditRequestByIdFull(Request $request, $id)
    {
        $request_item = CreditsRequest::findOrFail($id);
        $this->addAllRelationships($request_item);
        return $request_item;
    }

    public function addCreditRequest(Request $request)
    {
        $request_item = new CreditsRequest();
        $request_item->title = $request->title;
        $request_item->user_id = $request->user_id;
        $request_item->creditor_id = $request->creditor_id;
        $request_item->email = $request->email;
        $request_item->full_name = $request->full_name;
        $request_item->birthday = $request->birthday;
        $request_item->phone = $request->phone;
        $request_item->user_comment = $request->user_comment;
        $request_item->amount = $request->amount;
        $request_item->months_period = $request->months_period;
        $request_item->region_id = $request->region_id;
        $request_item->save();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($request_item);
        LogsHelper::addLogEntry($request, "requests.credits", "create", $request_item);
    }

    public function updateCreditRequestById(Request $request, $id)
    {
        $old_request_item = CreditsRequest::findOrFail($id);
        $this->addAllRelationships($old_request_item);

        $request_item = CreditsRequest::findOrFail($id);
        $request_item->title = $request->title;
        $request_item->user_id = $request->user_id;
        $request_item->creditor_id = $request->creditor_id;
        $request_item->email = $request->email;
        $request_item->full_name = $request->full_name;
        $request_item->birthday = $request->birthday;
        $request_item->phone = $request->phone;
        $request_item->user_comment = $request->user_comment;
        $request_item->amount = $request->amount;
        $request_item->months_period = $request->months_period;
        $request_item->region_id = $request->region_id;
        $request_item->save();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($request_item);
        LogsHelper::addLogEntry($request, "requests.credits", "update", $request_item, $old_request_item);
    }

    public function deleteCreditRequestById(Request $request, $id)
    {
        $old_request_item = CreditsRequest::findOrFail($id);
        $this->addAllRelationships($old_request_item);

        $request_item = CreditsRequest::findOrFail($id);
        $request_item->delete();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        LogsHelper::addLogEntry($request, "requests.credits", "delete", null, $old_request_item);
    }

    /*-------------------------- */
    /* Card requests */
    /*------------------------- */

    public function getAllCardRequests(Request $request, $xnumber = 10)
    {
        $requests = CardsRequest::orderByDate()
            ->paginateOrGet($request->page, $request->paginatedBy ?? $xnumber);
        RequestsHelper::addUser($requests);
        RequestsHelper::addRegion($requests);
        ProductsHelper::addCardCategoryMeaning($requests);
        ProductsHelper::addCurrencyMeaning($requests);
        return $requests;
    }

    public function getCardRequestByIdFull(Request $request, $id)
    {
        $request_item = CardsRequest::findOrFail($id);
        $this->addAllRelationships($request_item);
        return $request_item;
    }

    public function addCardRequest(Request $request)
    {
        $request_item = new CardsRequest();
        $request_item->title = $request->title;
        $request_item->user_id = $request->user_id;
        $request_item->creditor_id = $request->creditor_id;
        $request_item->email = $request->email;
        $request_item->full_name = $request->full_name;
        $request_item->birthday = $request->birthday;
        $request_item->phone = $request->phone;
        $request_item->user_comment = $request->user_comment;
        $request_item->card_category_id = $request->category_id;
        $request_item->currency_id = $request->currency_id;
        $request_item->region_id = $request->region_id;
        $request_item->save();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($request_item);
        LogsHelper::addLogEntry($request, "requests.cards", "create", $request_item);
    }

    public function updateCardRequestById(Request $request, $id)
    {

        $old_request_item = CardsRequest::findOrFail($id);
        $this->addAllRelationships($old_request_item);

        $request_item = CardsRequest::findOrFail($id);
        $request_item->title = $request->title;
        $request_item->user_id = $request->user_id;
        $request_item->creditor_id = $request->creditor_id;
        $request_item->email = $request->email;
        $request_item->full_name = $request->full_name;
        $request_item->birthday = $request->birthday;
        $request_item->phone = $request->phone;
        $request_item->user_comment = $request->user_comment;
        $request_item->card_category_id = $request->category_id;
        $request_item->currency_id = $request->currency_id;
        $request_item->region_id = $request->region_id;
        $request_item->save();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($request_item);
        LogsHelper::addLogEntry($request, "requests.cards", "update", $request_item, $old_request_item);
    }

    public function deleteCardRequestById(Request $request, $id)
    {
        $old_request_item = CardsRequest::findOrFail($id);
        $this->addAllRelationships($old_request_item);

        $request_item = CardsRequest::findOrFail($id);
        $request_item->delete();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        LogsHelper::addLogEntry($request, "requests.cards", "delete", null, $old_request_item);
    }

    /*-------------------------- */
    /* Deposits requests */
    /*------------------------- */

    public function getAllDepositRequests(Request $request, $xnumber = 10)
    {
        $requests = DepositsRequest::orderByDate()
            ->paginateOrGet($request->page, $request->paginatedBy ?? $xnumber);
        RequestsHelper::addUser($requests);
        RequestsHelper::addRegion($requests);
        return $requests;
    }

    public function getDepositRequestByIdFull(Request $request, $id)
    {
        $request_item = DepositsRequest::findOrFail($id);
        $this->addAllRelationships($request_item);
        return $request_item;
    }

    public function addDepositRequest(Request $request)
    {
        $request_item = new DepositsRequest();
        $request_item->title = $request->title;
        $request_item->user_id = $request->user_id;
        $request_item->creditor_id = $request->creditor_id;
        $request_item->email = $request->email;
        $request_item->full_name = $request->full_name;
        $request_item->birthday = $request->birthday;
        $request_item->phone = $request->phone;
        $request_item->user_comment = $request->user_comment;
        $request_item->amount = $request->amount;
        $request_item->days_period = $request->days_period;
        $request_item->region_id = $request->region_id;
        $request_item->save();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($request_item);
        LogsHelper::addLogEntry($request, "requests.deposits", "create", $request_item);
    }

    public function updateDepositRequestById(Request $request, $id)
    {
        $old_request_item = DepositsRequest::findOrFail($id);
        $this->addAllRelationships($old_request_item);

        $request_item = DepositsRequest::findOrFail($id);
        $request_item->title = $request->title;
        $request_item->user_id = $request->user_id;
        $request_item->creditor_id = $request->creditor_id;
        $request_item->email = $request->email;
        $request_item->full_name = $request->full_name;
        $request_item->birthday = $request->birthday;
        $request_item->phone = $request->phone;
        $request_item->user_comment = $request->user_comment;
        $request_item->amount = $request->amount;
        $request_item->days_period = $request->days_period;
        $request_item->region_id = $request->region_id;
        $request_item->save();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($request_item);
        LogsHelper::addLogEntry($request, "requests.deposits", "update", $request_item, $old_request_item);
    }

    public function deleteDepositRequestById(Request $request, $id)
    {
        $old_request_item = DepositsRequest::findOrFail($id);
        $this->addAllRelationships($old_request_item);

        $request_item = DepositsRequest::findOrFail($id);
        $request_item->delete();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        LogsHelper::addLogEntry($request, "requests.deposits", "delete", null, $old_request_item);
    }


    /*-------------------------- */
    /* Call requests */
    /*------------------------- */

    public function getAllCallRequests(Request $request, $xnumber = 10)
    {
        $requests = CallRequest::orderByDate()
            ->paginateOrGet($request->page, $request->paginatedBy ?? $xnumber);
        return $requests;
    }

    public function getCallRequestByIdFull(Request $request, $id)
    {
        return CallRequest::findOrFail($id);;
    }

    public function addCallRequestAdmin(Request $request)
    {
        $request_item = new CallRequest();
        $request_item->full_name = $request->full_name;
        $request_item->phone = $request->phone;
        $request_item->text = $request->text;
        $request_item->save();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($request_item);
        LogsHelper::addLogEntry($request, "requests.calls", "create", $request_item);
    }

    public function updateCallRequestById(Request $request, $id)
    {
        $old_request_item = CallRequest::findOrFail($id);
        $this->addAllRelationships($old_request_item);

        $request_item = CallRequest::findOrFail($id);
        $request_item->full_name = $request->full_name;
        $request_item->phone = $request->phone;
        $request_item->text = $request->text;
        $request_item->save();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($request_item);
        LogsHelper::addLogEntry($request, "requests.calls", "update", $request_item, $old_request_item);
    }

    public function deleteCallRequestById(Request $request, $id)
    {
        $old_request_item = CallRequest::findOrFail($id);
        $this->addAllRelationships($old_request_item);

        $request_item = CallRequest::findOrFail($id);
        $request_item->delete();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        LogsHelper::addLogEntry($request, "requests.calls", "delete", null, $old_request_item);
    }
}
