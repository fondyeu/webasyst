<?php
return array(
    array(
        'value'       => '%RELAY_URL%?transaction_result=result',
        'title'       => 'Result URL',
        'description' => 'Адрес отправки оповещения о платеже.',
        //$this->getDirectTransactionResultURL('success', array(__FILE__))).'">'
        ),
    array(
        'value'       => '%RELAY_URL%?transaction_result=success&app_id=%APP_ID%',
        'title'       => 'Success URL',
        'description' => 'Адрес страницы с уведомлением об успешно проведенном платеже.',
        // value="'.xHtmlSpecialChars($this->getTransactionResultURL('success', array(__FILE__))).'">'
        ),
    array(
        'value'       => '%RELAY_URL%?transaction_result=failure&app_id=%APP_ID%',
        'title'       => 'Fail URL',
        'description' => 'Адрес страницы с уведомлением о неуспешном платеже.',
        //"'.xHtmlSpecialChars($this->getTransactionResultURL('failure', array(__FILE__))).'">'
        ),

);
