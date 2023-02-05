<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendMailToStaff extends Mailable
{
    use Queueable, SerializesModels;
    public $data;
    public $action;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $action)
    {
        $this->data = $data;
        $this->action = $action;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $action_notification = $this->action;
        $data = $this->data;
        if($action_notification=='NEW_HELP_REQUEST'){
            $subject = 'Заявка в службу поддержки';
            $body = "
            <p>Новая заявка на сайте в разделе: Служба поддержки</p>
            <p>Почта посетителя: $data->email </p>
            <p>Текст: $data->text </p>
            ";
        }else if($action_notification=='NEW_CREDITOR_REQUEST') {
            $subject = 'Заявка на регистрацию компании';
            $body = "
            <p>Новая заявка на сайте в разделе: Регистрация компании</p>
            <p>Название компании: $data->name </p>
            <p>Почта: $data->email </p>
            <p>Телефон: $data->phone </p>
            ";
        }else if($action_notification=='NEW_CREDIT_HISTORY_REQUEST') {
            $subject = 'Заявка на проверку кредитной истории';
            $body = "
            <p>Новая заявка на сайте в разделе: Кредитная история</p>
            <p>ФИО: $data->full_name </p>
            <p>Почта: $data->email </p>
            <p>Телефон: $data->phone </p>
            ";
        }else if($action_notification=='NEW_BLACKLIST_REQUEST') {
            $subject = 'Заявка о мошенничестве';
            $body = "
            <p>Новая заявка на сайте в разделе: Черный список</p>
            <p>ФИО: $data->full_name </p>
            <p>Почта: $data->email </p>
            <p>Телефон: $data->phone </p>
            <p>Комментарий: $data->text </p>
            <p>Уровень опасности: $data->level </p>
            ";
        }else if($action_notification=='NEW_REVIEW_COMMENT') {
            $subject = 'Новый комментарий для отзывов';
            $body = "
            <p>Новый комментарий на сайте в разделе: Отзыв </p>
            <p>Почта: $data->email </p>
            <p>Комментарий: $data->text </p>
            ";
        }else if($action_notification=='NEW_NEWS_COMMENT') {
            $subject = 'Новый комментарий для новостей';
            $body = "
            <p>Новый отзыв на сайте</p>
            <p>Почта: $data->email </p>
            <p>Комментарий: $data->text </p>
            ";
        }else if($action_notification=='NEW_REVIEW') {
            $subject = 'Новый отзыв';
            $body = "
            <p>Новый отзыв на сайте</p>
            <p>Почта: $data->email </p>
            <p>Комментарий: $data->text </p>
            <p>Оценка: $data->stars </p>
            ";
        }else if($action_notification=='NEW_CALL_REQUEST') {
            $subject = 'Новая заявка - Заказать звонок';
            $body = "
            <p>Новая заявка - Заказать звонок на сайте</p>
            <p>ФИО: $data->full_name </p>
            <p>Комментарий: $data->text </p>
            <p>Телефон: $data->phone </p>
            ";
        }else if($action_notification=='NEW_PRODUCT_REQUEST') {
            $subject = 'Новая заявка - Подать заявку';
            $body = "
            <p>Новая заявка - Подать заявку на сайте</p>
            <p> $data->title_request </p>
            <p>ФИО: $data->full_name </p>
            <p>Комментарий: $data->text </p>
            <p>Телефон: $data->phone </p>
            ";
        }else if($action_notification=='NEW_CREDIT_SELECTION_REQUEST') {
            $subject = 'Новая заявка на подбор кредита';
            $body = "
            <p>Новая заявка на подбор кредита</p>
            <p>ФИО: $data->full_name </p>
            <p>Телефон: $data->phone </p>
            <p>Почта: $data->email </p>
            ";
        }else if($action_notification=='NEW_ADVERTISEMENT_REQUEST') {
            $subject = 'Новая заявка на рекламу';
            $body = "
            <p>Новая заявка на рекламу</p>
            <p>ФИО: $data->full_name </p>
            <p>Телефон: $data->phone </p>
            <p>Почта: $data->email </p>
            <p>Тип рекламы: $data->ad_type </p>
            <p>Комментарий: $data->text </p>
            ";
        }

        return $this->subject($subject)->markdown('mails.body_template')->with([
            'body' => $body
        ]);
    }
}
