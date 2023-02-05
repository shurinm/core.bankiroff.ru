<?php

namespace App\Http\Controllers;

use App\Models\Currencies\Currency;
use App\Models\Creditors\CreditorsExchangeRate;

use App\Helpers\CurrenciesHelper;
use App\Helpers\LogsHelper;

use Illuminate\Http\Request;

class CurrenciesController extends Controller
{
    public $cache_path;
    public $today;

    public function __construct()
    {
        $this->cache_path = base_path() . '/public/currencies.json';
        $this->today = date("d/m/Y");
    }

    public function getFilters(Request $request)
    {
        return Currency::where('is_filter', 1)->selectFields($request->isKeyValue)->orderBy('created_at')->get();
    }

    public function saveCache($data)
    {
        $data["date"] = $this->today;
        $data = json_encode($data);
        file_put_contents($this->cache_path, $data);
    }

    public function getCache()
    {
        if (!file_exists($this->cache_path)) return null;
        $data = file_get_contents($this->cache_path);
        $data = json_decode($data, true);
        $date = $data["date"];
        unset($data["date"]);
        if ($date == $this->today) {
            return $data;
        } else {
            return null;
        }
    }

    public function getCurrencies($date, $code_in = "")
    {
        static $rates, $result;

        if ($date == $this->today) {
            $rates = $this->getCache();
        }

        if ($rates === null) {
            $xml = file_get_contents('http://www.cbr.ru/scripts/XML_daily.asp?date_req=' . $date);
            $xml = simplexml_load_string($xml);

            for ($i = 0; $i < count($xml->Valute); $i++) {
                $rates[$i]["code"] = (string)$xml->Valute[$i]->CharCode;
                $rates[$i]["value"] = (string)$xml->Valute[$i]->Value;
                $rates[$i]["name"] = (string)$xml->Valute[$i]->Name;
            }

            if ($date == $this->today) {
                $this->saveCache($rates);
            }
        }
        return $rates;
    }

    public function getAllByDate($dd = "", $mm = "", $yyyy = "")
    {
        if (!$dd or !$mm or !$yyyy) {
            $date = date("d/m/Y");
        } else {
            $date = "$dd/$mm/$yyyy";
        }
        return $this->getCurrencies($date);
    }

    public function getByCode(Request $request, $code)
    {
        static $result;

        $rates = $this->getCurrencies($this->today, $code);
        foreach ($rates as $key => $value) {
            if ($rates[$key]['code'] == $code) {
                $result["code"] = $value["code"];
                $result["value"] = $value["value"];
                $result["name"] = $value["name"];
            }
        }
        return $result;
    }

    public function convert($sum, $currency_from, $currency_to, $currrency_base = "RUB")
    {
        $rates = $this->getCurrencies(date("d/m/Y"));

        foreach ($rates as $key => $value) {
            if ($rates[$key]['code'] == $currency_from) {
                $currency_from = $key;
            }
            if ($rates[$key]['code'] == $currency_to) {
                $currency_to = $key;
            }
        }

        if ($currrency_base == "RUB") {
            if ($currency_to == "RUB") {
                $result = round($sum * (float)str_replace(",", ".", $rates[$currency_from]["value"]), 2);
            } else if ($currency_from == "RUB") {
                $result = round($sum / (float)str_replace(",", ".", $rates[$currency_to]["value"]), 2);
            } else {
                $in_rub = round($sum * (float)str_replace(",", ".", $rates[$currency_from]["value"]), 2);
                $result = round($in_rub / (float)str_replace(",", ".", $rates[$currency_to]["value"]), 2);
            }
        }

        $json["currency_from"] = $currency_from;
        $json["currency_to"] = $currency_to;
        $json["currrency_base"] = $currrency_base;
        $json["sum"] = $sum;
        $json["result"] = $result;
        return $json;
    }
    /*----------------------------------------------------------------------------------------------------------------------------------------------- */
    /* Functions for ADMIN, protected by JWT and double verification with email, the verification happens on the middleware EnsureHasPermissions */
    /*----------------------------------------------------------------------------------------------------------------------------------------------- */

    public function getAll(Request $request, $xnumber = 10)
    {
        $currencies = Currency::orderByDate()
            ->paginateOrGet($request->page, $xnumber);
        return $currencies;
    }

    public function getByIdFull(Request $request, $id)
    {
        return Currency::findOrFail($id);;
    }

    public function add(Request $request)
    {
        $currency = new Currency();
        $currency->title = $request->title;
        $currency->iso_name = $request->iso_name;
        $currency->is_filter = $request->is_filter ? 1 : 0;
        $currency->save();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        LogsHelper::addLogEntry($request, "currencies", "create", $currency);
    }

    public function updateById(Request $request, $id)
    {
        $old_currency = Currency::findOrFail($id);
        $currency = Currency::findOrFail($id);
        $currency->title = $request->title;
        $currency->iso_name = $request->iso_name;
        $currency->is_filter = $request->is_filter ? 1 : 0;
        $currency->save();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        LogsHelper::addLogEntry($request, "currencies", "update", $currency, $old_currency);
    }

    public function toggleActiveById(Request $request, $id)
    {
        $old_currency = Currency::findOrFail($id);

        $currency = Currency::findOrFail($id);
        $currency->is_filter = $currency->is_filter ? 0 : 1;
        $currency->save();
        
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        LogsHelper::addLogEntry($request, "currencies", "update", $currency, $old_currency);
    }

    public function deleteById(Request $request, $id)
    {
        $old_currency = Currency::findOrFail($id);
        Currency::findOrFail($id)->delete();
        
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        LogsHelper::addLogEntry($request, "currencies", "delete", null, $old_currency);
    }

    /*-------------------------------------------- */
    /* Methods for Creditor exchange rates */
    /*-------------------------------------------- */


    public function getAllCreditorExchangeRates(Request $request, $xnumber = 10)
    {
        $exchange_rates = CreditorsExchangeRate::matchCreditorsIds($request->creditors)
            ->matchCurrenciesIds($request->currencies)
            ->orderByDate()
            ->paginateOrGet($request->page, $xnumber);
        CurrenciesHelper::addCreditor($exchange_rates);
        CurrenciesHelper::addCurrency($exchange_rates);
        return $exchange_rates;
    }

    public function getCreditorExchangeRateByIdFull(Request $request, $id)
    {
        return CreditorsExchangeRate::findOrFail($id);;
    }

    public function addCreditorExchangeRate(Request $request)
    {
        $exchange_rate = new CreditorsExchangeRate();
        $exchange_rate->creditor_id = $request->creditor_id;
        $exchange_rate->currency_id = $request->currency_id;
        $exchange_rate->buy = $request->buy;
        $exchange_rate->sell = $request->sell;
        $exchange_rate->save();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        LogsHelper::addLogEntry($request, "courses.creditors", "create", $exchange_rate);
    }

    public function updateCreditorExchangeRateById(Request $request, $id)
    {
        $old_exchange_rate = CreditorsExchangeRate::findOrFail($id);
        
        $exchange_rate = CreditorsExchangeRate::findOrFail($id);
        $exchange_rate->creditor_id = $request->creditor_id;
        $exchange_rate->currency_id = $request->currency_id;
        $exchange_rate->buy = $request->buy;
        $exchange_rate->sell = $request->sell;
        $exchange_rate->save();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        LogsHelper::addLogEntry($request, "courses.creditors", "update", $exchange_rate, $old_exchange_rate);

    }

    public function deleteCreditorExchangeRateById(Request $request, $id)
    {
        $old_exchange_rate = CreditorsExchangeRate::findOrFail($id);
        CreditorsExchangeRate::findOrFail($id)->delete();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        LogsHelper::addLogEntry($request, "courses.creditors", "delete", null, $old_exchange_rate);
    }
}
