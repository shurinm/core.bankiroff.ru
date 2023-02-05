<?php

namespace App\Http\Controllers;

use App\Helpers\CreditorsHelper;
use App\Helpers\YandexHelper;

use App\Models\Creditors\Creditor;

use App\Models\Creditors\CreditorsRegistrationsRequest;
use App\Models\Creditors\CreditorsBlacklist;

use Illuminate\Http\Request;
use App\Helpers\SubDomainHelper;
use App\Helpers\ReviewsHelper;
use App\Helpers\AddressesHelper;
use App\Helpers\LogsHelper;
use App\Helpers\RedirectsHelper;
use App\Helpers\SeoHelper;

use DB;
use Str;
use Storage;
use File;

use Illuminate\Support\Facades\Mail;
use App\Mail\SendMailToStaff;
use App\Mail\SendMailToUser;
use stdClass;

class CreditorsController extends Controller
{

    public function getTopXnumber(Request $request, $xnumber = null)
    {
        $subdomain = SubDomainHelper::getSubdomainId($request->header('Subdomain'));
        $creditors = Creditor::active()
            ->matchSubdomain($subdomain)
            ->matchFiltersTop5($request->sort)
            ->paginateOrGet($request->page, $xnumber);
        return $creditors;
    }

    /* Getting Creditors by rating slug (official | unofficial)*/
    public function getByRatingSlugByXnumber(Request $request, $rating_slug, $xnumber = null)
    {
        $subdomain = SubDomainHelper::getSubdomainId($request->header('Subdomain'));
        $creditors = Creditor::active()
            ->matchSubdomain($subdomain)
            ->selectFields($request->isKeyValue)
            ->matchRatingFilters($rating_slug, $request->sort, $request->order)
            ->paginateOrGet($request->page, $xnumber);
        return response()->json($creditors);
    }


    /* Getting ALL Creditors*/
    public function getAllByXnumber(Request $request, $xnumber = null)
    {
        return $this->getXnumberBySlug($request, null, $xnumber);
    }

    /* Getting ALL Creditors*/
    public function getAllWithProductsByProductSlug(Request $request, $slug, $xnumber = null)
    {

        return Creditor::active()
                    ->matchHasProducts($slug)
                    ->matchSubdomain(SubDomainHelper::getSubdomainId($request->header('Subdomain')))
                    ->selectFields($request->isKeyValue)
                    ->paginateOrGet($request->page, $xnumber);
    }

    /* Getting ALL Creditors with regions as KeyValuePair (used in admin for products)*/
    public function getAllActiveWithRegions(Request $request, $xnumber = null)
    {
        $creditors = Creditor::active()->get();
        $creditors_keyvalue = [];
        foreach ($creditors as $key => &$creditor) {
            $object = new stdClass();
            $object->value =  strval($creditor->id);
            $object->title =  $creditor->name;
            $object->regions_as_key_value =  AddressesHelper::addRegionMeaningAsKeyValue($creditor->regionsAsKeyValue);
            $settlements =  $creditor->settlementsAsKeyValue;
            $settlements = AddressesHelper::addTitleByAoId($settlements);
            $object->settlements_as_key_value = $settlements;
            array_push($creditors_keyvalue, $object);
        }
        return $creditors_keyvalue;
    }

    /*Getting creditors by Slug (banks, mfo, etc) */
    public function getXnumberBySlug(Request $request, $slug, $xnumber = null)
    {
        $subdomain = SubDomainHelper::getSubdomainId($request->header('Subdomain'));
        $creditors = Creditor::active()
            ->whereSlug($slug)
            ->matchSubdomain($subdomain)
            ->selectFields($request->isKeyValue)
            ->matchFilters($request->sort, $request->isKeyValue)
            ->matchCreditorsWithLicense()
            ->matchSearch($request->search)
            ->paginateOrGet($request->page, $xnumber);

        $creditors = SeoHelper::appendSeoDataToCollection("creditors", $creditors);
        return response()->json($creditors);
    }

    public function getById(Request $request, $id)
    {
        /* IMPORTANT!! Make logic to count the credits that are just in the subdomain */
        $subdomain = SubDomainHelper::getSubdomainId($request->header('Subdomain'));
        $creditor = Creditor::activeOrAccessibleByDirectLink()->matchFilters('reviews_count&ratings', $request->isKeyValue)
        ->appendRating($id, $request)->countProducts($subdomain)->findOrFail($id);
        $reviews = $creditor->preview_reviews;
        $reviews = ReviewsHelper::addTimestampsPublishedAt($reviews);
        foreach ($reviews as $key => $review) {
            $review->user;
            $review->creditor;
            $review->credit_types;
            $review->card_type;
        }
        return $creditor;
    }


    public function requestRegistration(Request $request)
    {
        $registration_request = new CreditorsRegistrationsRequest();
        $registration_request->name =  $request->name;
        $registration_request->email =  $request->email;
        $registration_request->phone =  $request->phone;
        $registration_request->save();

        // $data_obj = (object) ["name" =>$request->name, "email" => $request->email, "phone"=> $request->phone];
        $data_obj = new stdClass();
        $data_obj->email = $request->email;
        $data_obj->name = $request->name;
        $data_obj->phone = $request->phone;

        Mail::to($request->email)->send(new SendMailToUser($data_obj, 'NEW_REGISTRATION_REQUEST'));
        return Mail::to(env('MAIL_STAFF_INFO'))->send(new SendMailToStaff($data_obj, 'NEW_CREDITOR_REQUEST'));
    }

    public function getCreditorsInBlackList(Request $request, $xnumber)
    {
        $creditors_blacklists = CreditorsBlacklist::active()
            ->matchSearch($request->search)
            ->paginateOrGet($request->page, $xnumber);

        CreditorsHelper::addCreditor($creditors_blacklists);
        return $creditors_blacklists;
    }

    /*----------------------------------------------------------------------------------------------------------------------------------------------- */
    /* Methods for ADMIN, protected by JWT and double verification with email, the verification happens on the middleware EnsureHasPermissions */
    /*----------------------------------------------------------------------------------------------------------------------------------------------- */

    public function addAllRelationships($creditor)
    {
        AddressesHelper::addRegionMeaningAsKeyValue($creditor->regionsAsKeyValue);
        AddressesHelper::addTitleByAoId($creditor->settlementsAsKeyValue);
        $creditor->main_settlement = AddressesHelper::addOneTitleByAoId($creditor->main_settlement_aoid);
        $creditor->extraPhones;
    }

    public function getAll(Request $request, $xnumber = 10)
    {
        $creditors = Creditor::matchNameLike($request->name ?? null)
            ->matchId($request->id ?? null)
            ->matchCreditorsIds($request->creditors ?? null)
            ->matchTypes($request->types ?? null)
            ->matchActiveState($request->moderated ?? null)
            ->orderByDate(null)
            ->paginateOrGet($request->page, $request->paginatedBy ?? $xnumber);
        // CreditorsHelper::addRegions($creditors);
        // CreditorsHelper::addSettlements($creditors);
        CreditorsHelper::addSettlementsCount($creditors);
        return  $creditors;
    }

    public function getByIdFull(Request $request, $id)
    {
        $creditor = Creditor::findOrFail($id);
        $this->addAllRelationships($creditor);
        return $creditor;
    }

    public function add(Request $request)
    {
        $creditor = new Creditor();
        $creditor->name = $request->name;
        $creditor->site = $request->site;
        $creditor->main_region_id = $request->main_region_id;
        $creditor->main_settlement_aoid = $request->main_settlement_aoid;
        $creditor->address = $request->address;
        $creditor->phone = $request->phone;
        $creditor->email = $request->email;
        $creditor->schedule = $request->schedule;
        $creditor->schedule_full = $request->schedule_full;
        $creditor->description = $request->description;
        $creditor->work_days = $request->work_days;
        $creditor->dative = $request->dative;
        $creditor->genitive = $request->genitive;
        $creditor->prepositional = $request->prepositional;
        $creditor->alternative = $request->alternative;
        $creditor->type_slug = $request->type_slug;
        $creditor->license_number = $request->license_number;
        $creditor->ogrn = $request->ogrn;
        $creditor->cbr_link = $request->cbr_link;
        $creditor->active = $request->active ? 1 : 0;
        $creditor->direct_access = $request->direct_access ? 1 : 0;
        $creditor->license_revoked = $request->license_revoked ? 1 : 0;
        $creditor->save();
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $image = $request->file('image');
            $name = time() . Str::random(5) . '_' . date("Y-m-d") . '_' . $creditor->id . '.' . $image->getClientOriginalExtension();
            Storage::disk('public')->put('images/creditors/' . $name, File::get($image));
            $creditor->image =  $name;
            $creditor->save();
        }
        if ($request->hasFile('thumbnail') && $request->file('thumbnail')->isValid()) {
            $thumbnail = $request->file('thumbnail');
            $name = time() . Str::random(5) . '_thumbnail' . '_' . date("Y-m-d") . '_' . $creditor->id . '.' . $thumbnail->getClientOriginalExtension();
            Storage::disk('public')->put('images/creditors/' . $name, File::get($thumbnail));
            $creditor->thumbnail =  $name;
            $creditor->save();
        }
        CreditorsHelper::fillInterceptions($request->creditorRegions, 'creditors_regions',  $creditor->id);
        CreditorsHelper::fillInterceptions($request->creditorSettlements, 'creditors_settlements',  $creditor->id);
        CreditorsHelper::fillInterceptions($request->creditorPhones, 'creditors_phones',  $creditor->id);

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($creditor);
        LogsHelper::addLogEntry($request, "creditors", "create", $creditor);
        YandexHelper::reportChanges("creditors", $creditor );
    }

    public function updateById(Request $request, $id)
    {
        
        $old_creditor = Creditor::findOrFail($id);
        $this->addAllRelationships($old_creditor);

        $creditor = Creditor::findOrFail($id);
        $creditor_active_copy = $creditor->active;
        $creditor_direct_access_copy = $creditor->direct_access;
        $creditor->name = $request->name;
        $creditor->site = $request->site;
        $creditor->main_region_id = $request->main_region_id;
        $creditor->main_settlement_aoid = $request->main_settlement_aoid;
        $creditor->address = $request->address;
        $creditor->phone = $request->phone;
        $creditor->email = $request->email;
        $creditor->schedule = $request->schedule;
        $creditor->schedule_full = $request->schedule_full;
        $creditor->description = $request->description;
        $creditor->work_days = $request->work_days;
        $creditor->dative = $request->dative;
        $creditor->genitive = $request->genitive;
        $creditor->prepositional = $request->prepositional;
        $creditor->alternative = $request->alternative;
        $creditor->type_slug = $request->type_slug;
        $creditor->active = $request->active ? 1 : 0;
        $creditor->direct_access = $request->direct_access ? 1 : 0;
        $creditor->license_revoked = $request->license_revoked ? 1 : 0;
        $creditor->direct_access = $creditor->active ? 0 : $creditor->direct_access;
        $creditor->license_number = $request->license_number;
        $creditor->ogrn = $request->ogrn;
        $creditor->cbr_link = $request->cbr_link;

        if ($request->image_deleted && $creditor->image) {
            File::delete('images/creditors/' . $creditor->image);
            $creditor->image = null;
        }

        if ($request->thumbnail_deleted && $creditor->thumbnail) {
            File::delete('images/creditors/' . $creditor->thumbnail);
            $creditor->thumbnail = null;
        }

        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $image = $request->file('image');
            if ($creditor->image) {
                File::delete('images/creditors/' . $creditor->image);
            }
            $name = time() . Str::random(5) . '_' . date("Y-m-d") . '_' . $creditor->id . '.' . $image->getClientOriginalExtension();
            Storage::disk('public')->put('images/creditors/' . $name, File::get($image));
            $creditor->image =  $name;
        }

        if ($request->hasFile('thumbnail') && $request->file('thumbnail')->isValid()) {
            $thumbnail = $request->file('thumbnail');
            if ($creditor->thumbnail) {
                File::delete('images/creditors/' . $creditor->thumbnail);
            }
            $name = time() . Str::random(5) . '_thumbnail' . '_' . date("Y-m-d") . '_' . $creditor->id . '.' . $thumbnail->getClientOriginalExtension();
            Storage::disk('public')->put('images/creditors/' . $name, File::get($thumbnail));
            $creditor->thumbnail =  $name;
        }

        $creditor->save();
        CreditorsHelper::fillInterceptions($request->creditorRegions, 'creditors_regions', null, $creditor->id);
        CreditorsHelper::fillInterceptions($request->creditorSettlements, 'creditors_settlements', null, $creditor->id);
        CreditorsHelper::fillInterceptions($request->creditorPhones, 'creditors_phones', null, $creditor->id);

        CreditorsHelper::replicateActionToAllProducts($creditor->id, 'active', $creditor->active, $creditor_active_copy);
        CreditorsHelper::replicateActionToAllProducts($creditor->id, 'direct_access', $creditor->direct_access, $creditor_direct_access_copy);

        
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($creditor);
        LogsHelper::addLogEntry($request, "creditors", "update", $creditor, $old_creditor);
        YandexHelper::reportChanges("creditors", $creditor, $old_creditor );
        /* 
            We validate if the new URL is the same as it was before. 
            In case the new URL is different, we create an automatic redirect.
        */
        RedirectsHelper::validateUrlAndAddRedirect("creditors", $creditor, $old_creditor);
    }

    public function toggleActiveById(Request $request, $id)
    {
        $old_creditor = Creditor::findOrFail($id);
        $creditor = Creditor::findOrFail($id);
        $creditor_active_copy = $creditor->active;
        // $creditor_direct_access_copy = $creditor->direct_access;
        $creditor->active =  $creditor->active ? 0 : 1;
        $creditor->direct_access = $creditor->active ? 0 : $creditor->direct_access;
        $creditor->save();
        CreditorsHelper::replicateActionToAllProducts($creditor->id, 'active', $creditor->active, $creditor_active_copy);
        // CreditorsHelper::replicateActionToAllProducts($creditor->id, 'direct_access', $creditor->direct_access, $creditor_direct_access_copy);
        
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        $this->addAllRelationships($creditor);
        $this->addAllRelationships($old_creditor);
        LogsHelper::addLogEntry($request, "creditors", "update", $creditor, $old_creditor);
        YandexHelper::reportChanges("creditors", $creditor, $old_creditor );
    }

    public function deleteById(Request $request, $id)
    {
        $old_creditor = Creditor::findOrFail($id);
        $this->addAllRelationships($old_creditor);
        Creditor::findOrFail($id)->delete();
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        LogsHelper::addLogEntry($request, "creditors", "delete", null, $old_creditor);
        YandexHelper::reportChanges("creditors", null, $old_creditor );
    }

    public function duplicateById(Request $request, $id)
    {
        /* This function is not being used */
        $creditor_to_copy = Creditor::findOrFail($id);
        $new_creditor = $creditor_to_copy->replicate();
        $new_creditor->name = $new_creditor->name . ' КОПИЯ';
        $new_creditor->active = false;
        $new_creditor->save();
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        $this->addAllRelationships($new_creditor);
        LogsHelper::addLogEntry($request, "creditors", "duplicate", $new_creditor);
        YandexHelper::reportChanges("creditors", $new_creditor );
    }

    /*-------------------------------------------- */
    /* Methods for Creditors Blacklists */
    /*-------------------------------------------- */

    public function addAllRelationshipsBlacklist($blacklist_element)
    {
        $blacklist_element->creditor;
        $blacklist_element->user;
    }

    public function getAllInBlackList(Request $request, $xnumber = 10)
    {
        $blacklist_elements =  CreditorsBlacklist::orderByDate(null)->paginateOrGet($request->page, $xnumber);
        CreditorsHelper::addCreditor($blacklist_elements);
        CreditorsHelper::addUser($blacklist_elements);
        return $blacklist_elements;
    }

    public function getBlackListByIdFull(Request $request, $id)
    {
        $blacklist_element = CreditorsBlacklist::findOrFail($id);
        $this->addAllRelationshipsBlacklist($blacklist_element);
        return $blacklist_element;
    }


    public function updateBlackListById(Request $request, $id)
    {
        $old_blacklist_element = CreditorsBlacklist::findOrFail($id);
        $this->addAllRelationshipsBlacklist($old_blacklist_element);

        $blacklist_element = CreditorsBlacklist::findOrFail($id);
        $blacklist_element->creditor_id = $request->creditor_id;
        $blacklist_element->user_id = $request->user_id;
        $blacklist_element->text = $request->text;
        $blacklist_element->level = $request->level;
        $blacklist_element->active =  $request->active ?? 0;
        $blacklist_element->save();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationshipsBlacklist($blacklist_element);
        LogsHelper::addLogEntry($request, "blacklists", "update", $blacklist_element, $old_blacklist_element);

    }

    public function toggleActiveBlackListById(Request $request, $id)
    {
        $old_blacklist_element = CreditorsBlacklist::findOrFail($id);

        $blacklist_element = CreditorsBlacklist::findOrFail($id);
        $blacklist_element->active =  $blacklist_element->active ? 0 : 1;
        $blacklist_element->save();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        $this->addAllRelationshipsBlacklist($blacklist_element);
        $this->addAllRelationshipsBlacklist($old_blacklist_element);
        LogsHelper::addLogEntry($request, "blacklists", "update", $blacklist_element, $old_blacklist_element);

    }

    public function deleteBlackListById(Request $request, $id)
    {
        $old_blacklist_element = CreditorsBlacklist::findOrFail($id);
        $this->addAllRelationshipsBlacklist($old_blacklist_element);

        CreditorsBlacklist::findOrFail($id)->delete();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        LogsHelper::addLogEntry($request, "blacklists", "delete", null, $old_blacklist_element);
    }
}
