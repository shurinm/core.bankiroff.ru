<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class Helper
{
    /**
     * Метод получения текущей даты с заданым форматом
     * 
     * @param   string $format
     * @return  string $date
     */
    public static function getCurrentDate($format = 'd-m-Y')
    {
        $date = new Carbon;
        return $date->format($format);
    }

    public static function generateRandomNumber($digits)
    {
        return str_pad(rand(0, pow(10, $digits) - 1), $digits, '0', STR_PAD_LEFT);
    }

    public static function cleanPhoneNumber($phone)
    {
        $phone = str_replace('-', '', $phone);
        $phone =  preg_replace('/[^A-Za-z0-9\-]/', '', $phone);
        return  $phone;
    }


    public static function customPaginate($items, $perPage = 10, $page = null, $options = [])
    {
        // dd($items, $perPage , $page);
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        $paginator = new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
        return $paginator;
    }

    /**
     * Метод замены, по умолчанию дефисы на точки
     * 
     * @param   string $date
     * @param   string $change
     * @param   string $changer
     * @return  string
     */
    public static function changeDots($date, $change = '-', $changer = '.')
    {
        return str_replace($change, $changer, $date);
    }

    /**
     * Метод получения списка всех нужных для сайта дат (будет дорабатываться в процессе разработки, если потребуется добавить новые переменые даты)
     * 
     * @param   string $formats
     * @return  array
     */
    public static function getCurrentDateFormatted($formats)
    {
        return [
            'cure'  =>  self::changeDots(self::getCurrentDate($formats['cure']), '-'),
        ];
    }

    public static function deleteHTMLFromStr($str)
    {
        if(!$str) return;
        $str = strip_tags($str);
        $str = str_replace("'", "&apos;", $str);
        $deletion_rules = ["&nbsp;", "&laquo;", "&raquo;", "&nbsp;", "&ndash;", "&mdash;"];
        foreach ($deletion_rules as $key => $rule) {
            $str = str_replace($rule, '', $str);
        }
        return $str;
    }

    public static function getSubdomainFromURL($url){
        try{
            $parsedUrl = parse_url($url);
            $host = explode('.', $parsedUrl['host']);
            $subdomain = $host[0];
            return $subdomain;
        } catch (Exception $e) {
            return "";
        }
    }

    public static function getWordSingular($word){
        try{
            if (substr($word, -1) == 's'){
                return substr($word, 0, -1);
            }
        }
        catch (Exception $e) {
            return $word;
        }
    }
}
