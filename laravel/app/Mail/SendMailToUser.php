<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendMailToUser extends Mailable
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
            $subject = 'Служба поддержки Bankiroff';
            $body = "
            <p>Здравствуйте!</p>
            <p>Спасибо за ваше обращение! Мы получили сообщение, в ближайшее время найдем решение этой задачи и отправим ответ вам на e-mail.</p>
            <p>Ваша почта: $data->email </p>
            <p>Ваш текст: $data->text </p>
            <p>Александр Петров,</p>
            <p>Служба поддержки клиентов</p>
            ";
        }else if($action_notification=='REGISTRATION_SUCCESFULLY'){
            $subject = 'Регистрация на Bankiroff';
            $body = "
            <p>Добро пожаловать в Bankiroff</p>
            <p>Ваш email: $data->email </p>
            <p>Ваш пароль: $data->password </p>
            ";
        }else if($action_notification=='NEW_REGISTRATION_REQUEST'){
            $subject = 'Регистрация на Bankiroff';
            $body = "
            <p>Здравствуйте!</p>
            <p>Мы получили сообщение, в ближайшее время мы свяжемся с вами.</p>
            <p>Ваше название: $data->name </p>
            <p>Ваша email: $data->email </p>
            <p>Ваш телефон: $data->phone </p>
            ";
            
        }else if($action_notification=='PASSWORD_RESETED'){
            $subject = 'Новый пароль в Bankiroff';
            $body = "
            <p>Здравствуйте!</p>
            <p>Ваш новый пароль: $data->password </p>
            <p>Если это письмо пришло вам по ошибке, то не обращайте на него внимание.</p>
            ";
            
        }else if($action_notification=='NEW_CREDIT_HISTORY_REQUEST') {
            $subject = 'Заявка на проверку кредитной истории';
            $body = "
            <p>Здравствуйте!</p>
            <p>Мы получили вашу заявку, в ближайшее время мы свяжемся с вами.</p>
            </p>
            ";
        }else if($action_notification=='NEW_BLACKLIST_REQUEST') {
            $subject = 'Заявка о мошенничестве';
            $body = "
            <p>Здравствуйте!</p>
            <p>Мы получили вашу заявку о мошенничестве, спасибо.</p>
            </p>
            ";
        }else if($action_notification=='NEW_REVIEW_COMMENT') {
            $subject = 'Новый комментарий';
            $body = "
            <p>Здравствуйте!</p>
            <p>Мы получили ваш комментарий, он находится на рассмотрении, в ближайшие время мы его модерируем.</p>
            </p>
            <p>Ваш комментарий: $data->text</p>
            ";
        }else if($action_notification=='NEW_NEWS_COMMENT') {
            $subject = 'Новый комментарий';
            $body = "
            <p>Здравствуйте!</p>
            <p>Мы получили ваш комментарий, он находится на рассмотрении, в ближайшие время мы его модерируем.</p>
            <p>Ваш комментарий: $data->text </p>
            ";
        }else if($action_notification=='NEWS_COMMENT_UPDATED') {
            $subject = 'Комментарий успешно обновлен';
            $body = "
            <p>Здравствуйте!</p>
            <p>Вы успешно отредактировали ваш комментарий, он находится на рассмотрении, в ближайшие время мы его модерируем.</p>
            <p>Ваш комментарий: $data->text </p>
            ";
        }else if($action_notification=='NEW_REVIEW') {
            $subject = 'Новый отзыв';
            $body = "
            <p>Здравствуйте!</p>
            <p>Мы получили ваш отзыв, он находится на рассмотрении, в ближайшие время мы его модерируем.</p>
            <p>Ваш комментарий: $data->text</p>
            <p>Ваша оценка: $data->stars</p>
            ";
        }else if($action_notification=='NEW_CALL_REQUEST') {
            $subject = 'Новая заявка';
            $body = "
            <p>Здравствуйте! $data->full_name,</p>
            <p>Мы получили вашу заявку, в ближайшее время мы свяжемся с вами.</p>
            <p>Ваш комментарий: $data->text</p>
            <p>Ваш телефон: $data->phone </p>
            ";
        }else if($action_notification=='NEW_PRODUCT_REQUEST') {
            $subject = 'Новая заявка';
            $body = "
            <p>Здравствуйте! $data->full_name,</p>
            <p>Мы получили вашу заявку, в ближайшее время мы свяжемся с вами.</p>
            <p> $data->title_request </p>
            <p>Ваш комментарий: $data->text</p>
            <p>Ваш телефон: $data->phone </p>
            ";
        }else if($action_notification=='NEW_CREDIT_SELECTION_REQUEST') {
            $subject = 'Новая заявка на подбор кредита';
            $body = "
            <p>Здравствуйте! $data->full_name,</p>
            <p>Мы получили вашу заявку, в ближайшее время мы свяжемся с вами.</p>
            <p> $data->title_request </p>
            <p>Ваш телефон: $data->phone </p>
            ";
        }else if($action_notification=='NEW_COMMENT_TO_REVIEW') {
            $subject = 'Новый комментарий на ваш отзыв';
            $body = "
            <p>Здравствуйте!</p>
            <p>На ваш отзыв появился новый комментарий.</p>
            <p>Комментарий:</p>
            <p> $data->text </p>
            <p>Посмотрите все детали на bankiroff.ru</p>
            ";
        }else if($action_notification=='NEW_COMMENT_TO_NEWS') {
            $subject = 'Новый комментарий на новость';
            $body = "
            <p>Здравствуйте!</p>
            <p>Появился новый комментарий, в новости, которую Вы ранее прокомментировали.</p>
            <p>Комментарий:</p>
            <p> $data->text </p>
            <p>Посмотрите все детали на bankiroff.ru</p>
            ";
        }else if($action_notification=='NEW_MATERIAL_WHERE_USER_IS_SUBSCRIBED') {
            $subject = 'Новая новость';
            $body = "
            <p>Здравствуйте!</p>
            <p>Появилась новая новость, в разделе новостей, где Вы подписаны.</p>
            <p>Посмотрите все детали на bankiroff.ru</p>
            ";
        }else if($action_notification=='NEW_ADVERTISEMENT_REQUEST') {
            $subject = 'Новая заявка на рекламу';
            $body = "
            <p>Здравствуйте! $data->full_name,</p>
            <p>Мы получили вашу заявку, в ближайшее время мы свяжемся с вами.</p>
            <p>Тип рекламы: $data->ad_type </p>
            <p>Комментарий: $data->text </p>
            <p>Ваш телефон: $data->phone </p>
            ";
        }
        return $this->subject($subject)->markdown('mails.body_template')->with([
            'body' => $body
        ]);
    }
}
