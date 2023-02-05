<?php

namespace App\Helpers;


use App\SMSRU;
use \stdClass;

class SmsHelper
{
    /**
     * Метод получения текущей даты с заданым форматом
     * 
     * @param   string $format
     * @return  string $date
     */
    static function sendSMS($message, $number)
    {
        $api_id = env('SMS_RU_API_ID');

        $smsru = new SMSRU($api_id); // Ваш уникальный программный ключ, который можно получить на главной странице

        $data = new \stdClass();
        $data->to = $number;    // Номер куда отправляется сообщение
        $data->text = $message; // Текст сообщения
        // $data->from = 'Bankiroff'; // Если у вас уже одобрен буквенный отправитель, его можно указать здесь, в противном случае будет использоваться ваш отправитель по умолчанию
        // $data->time = time() + 7*60*60; // Отложить отправку на 7 часов
        // $data->translit = 1; // Перевести все русские символы в латиницу (позволяет сэкономить на длине СМС)
        // $data->test = 1; // Позволяет выполнить запрос в тестовом режиме без реальной отправки сообщения
        // $data->partner_id = '1'; // Можно указать ваш ID партнера, если вы интегрируете код в чужую систему
        $sms = $smsru->send_one($data); // Отправка сообщения и возврат данных в переменную

        if ($sms->status == "OK") { // Запрос выполнен успешно
            return $sms->sms_id;
        } else {
            return false;
        }
    }
}
