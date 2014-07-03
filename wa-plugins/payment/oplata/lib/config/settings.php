<?php

return array(
    'merchant_id'    => array(
        'value'        => '',
        'title'        => 'ID кошелька',
        'description'  => 'Идентификатор электронного кошелька Вашего интернет магазина в системе Oplata',
        'control_type' => waHtmlControl::INPUT,
    ),
    'secret_key' => array(
        'value'        => '',
        'title'        => 'Секретный ключ',
        'description'  => 'Ваше кодовое слово полученное от системы Oplata.',
        'control_type' => waHtmlControl::INPUT,
    ),
//    'currency'           => array(
//        'value'        => 'RUB',
//        'title'        => 'Валюта платежа',
//        'description'  => 'Валюта платежа для обработки платежной системой.',
//        'control_type' => waHtmlControl::SELECT,
//        'options'      => array(
//            'RUB' => 'Российский рубль',
//            'UAH' => 'Украинские гривны',
//            'USD' => 'Доллары США'
//        ),
//    )
);
