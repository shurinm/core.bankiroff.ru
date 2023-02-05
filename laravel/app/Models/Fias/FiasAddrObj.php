<?php

namespace App\Models\Fias;

use Illuminate\Database\Eloquent\Model;

class FiasAddrObj extends Model
{

    /*
    ----------------------------
    Уровень ФИАС	Описание ФИАС
    -----------------------------
            1	регион
            2	автономный округ
            3	район
            4	город
            5	внутригородская территория
            6	населенный пункт
            65	планировочная структура
            7	улица
            90	не используется
            91	не используется
            8	дом
            8	дом
            9	помещение
    */

    public function scopeSelectFields($query, $isKeyValue)
    {
        if ($isKeyValue) {
            return $query
                ->select('id AS value', 'name as title');
        }
        return $query->select('aoid', 'formalname as name', 'shortname as type', 'aolevel', 'regioncode', 'actstatus');
    }

    public function scopeActive($query)
    {
        return $query->where('actstatus', 1);
    }

    public function scopeMatchAreasLevel($query)
    {
        return $query
            ->where('aolevel', 1);
    }

    public function scopeMatchSettlementsLevel($query)
    {
        $allowed_types = ['г', 'с', 'пгт', 'п', 'д', 'нп', 'ст', 'ст-ца', 'рп', 'сл', 'аул', 'дп'];
        return $query
            ->whereIn('aolevel', [6, 1, 4])->whereIn('shortname', $allowed_types);
    }

    public function scopeMatchSearch($query, $search)
    {
        if ($search) {
            return $query
                // ->where('formalname','like', "%{$search}%");
                ->where('formalname', 'like', $search);
        }
    }

    public function scopeMatchType($query, $type)
    {
        if ($type && $type != 'all') {
            return $query
                ->where('shortname', $type);
        }
    }

    public function area()
    {
        return $this->hasOne('App\Models\Fias\FiasArea', 'regioncode', 'regioncode')->select('aolevel', 'formalname as name', 'shortname as type');
    }
}
