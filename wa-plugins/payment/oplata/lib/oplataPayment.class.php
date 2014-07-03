<?php

class oplataPayment extends waPayment implements waIPayment
{

    private $url = 'https://api.oplata.com/api/checkout/redirect/';

    const ORDER_APPROVED = 'approved';
    const ORDER_DECLINED = 'declined';

    const SIGNATURE_SEPARATOR = '|';

    const ORDER_SEPARATOR = ":";

    protected static $responseFields = array('rrn',
        'masked_card',
        'sender_cell_phone',
        'response_status',
        'currency',
        'fee',
        'reversal_amount',
        'settlement_amount',
        'actual_amount',
        'order_status',
        'response_description',
        'order_time',
        'actual_currency',
        'order_id',
        'tran_type',
        'eci',
        'settlement_date',
        'payment_system',
        'approval_code',
        'merchant_id',
        'settlement_currency',
        'payment_id',
        'sender_account',
        'card_bin',
        'response_code',
        'card_type',
        'amount',
        'sender_email'
    );

    public function allowedCurrency()
    {
        return array('UAH', 'RUB', 'USD');
    }

    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        $order = waOrder::factory($order_data);
        $description = preg_replace('/[^\.\?,\[]\(\):;"@\\%\s\w\d]+/', ' ', $order->description);
        $description = preg_replace('/[\s]{2,}/', ' ', $description);

        if (!in_array($order->currency, $this->allowedCurrency())) {
            throw new waPaymentException('Invalid currency');
        }

        list(, $lang) = explode("_", wa()->getLocale());

        $contact = new waContact(wa()->getUser()->getId());
        list($email) = $contact->get('email', 'value');

        $redirectUrl = $this->getRelayUrl() . '?&merchant_id=' . $this->merchant_id .
                            '&app_id=' . $this->app_id;

        $formFields = array(
            'order_id' => $order_data['order_id'] . self::ORDER_SEPARATOR . time(),
            'merchant_id' => $this->merchant_id,
            'order_desc' => $description,
            'amount' => $this->getAmount($order),
            'currency' => $order->currency,
            'server_callback_url' => $redirectUrl,
            'response_url' => $redirectUrl . '&show_user_response=1',
            'lang' => strtolower($lang),
            'sender_email' => $email
        );

        $formFields['signature'] = $this->getSignature($formFields);

        $view = wa()->getView();

        $view->assign('form_fields', $formFields);
        $view->assign('form_url', $this->getEndpointUrl());
        $view->assign('auto_submit', $auto_submit);

        return $view->fetch($this->path . '/templates/payment.html');
    }

    private function getAmount($order)
    {
        return round($order->total * 100);
    }

    protected function callbackInit($request)
    {
        if (!empty($request['merchant_id'])) {
            $this->merchant_id = $request['merchant_id'];
            $this->app_id = $request['app_id'];
            list($this->order_id,) = explode(self::ORDER_SEPARATOR, $request['order_id']);
        } else {
            throw new waPaymentException('Invalid invoice number');
        }
        return parent::callbackInit($request);
    }

    public function callbackHandler($request)
    {

        $transactionData = $this->formalizeData($request);
        $transactionData['state'] = self::STATE_CAPTURED;

        $url = null;

        $responseSignatureData = $request;
        foreach ($request as $k => $v) {
            if (!in_array($k, self::$responseFields)) {
                unset($responseSignatureData[$k]);
            }
        }

        if (!empty($request['show_user_response'])) {

            if ($request['order_status'] == self::ORDER_DECLINED) {
                $transactionData['state'] = self::STATE_DECLINED;
                // redirect to fail
                $url = $this->getAdapter()->getBackUrl(waAppPayment::URL_FAIL, $transactionData);
                header("Location: $url");
                exit;
            }

            if ($request['signature'] != $this->getSignature($responseSignatureData)) {

                $transactionData['state'] = self::STATE_DECLINED;
                // redirect to fail
                $url = $this->getAdapter()->getBackUrl(waAppPayment::URL_FAIL, $transactionData);
                header("Location: $url");
                exit;
            }

            // redirect to success
            $url = $this->getAdapter()->getBackUrl(waAppPayment::URL_SUCCESS, $transactionData);
            header("Location: $url");
            exit;
        }

        $appPaymentMethod = self::CALLBACK_PAYMENT;

        if ($request['order_status'] == self::ORDER_DECLINED) {
            $transactionData['state'] = self::STATE_DECLINED;
            $appPaymentMethod = null;
        }

        if ($request['signature'] != $this->getSignature($responseSignatureData)) {
            $transactionData['state'] = self::STATE_DECLINED;
            $appPaymentMethod = null;
            throw new waPaymentException('Invalid signature');
        }

        $transactionData = $this->saveTransaction($transactionData, $request);

        // var_dump($transactionData);
        if ($appPaymentMethod) {
            $result = $this->execAppCallback($appPaymentMethod, $transactionData);
            self::addTransactionData($transactionData['id'], $result);
        }

        echo 'OK';
        return array(
            'template' => false
        );
    }

    protected function formalizeData($transactionRawData)
    {
        $transactionData = parent::formalizeData($transactionRawData);
        $transactionData['native_id'] = $this->order_id;
        $transactionData['order_id'] = $this->order_id;
        $transactionData['amount'] = ifempty($transactionRawData['amount'], '');
        $transactionData['currency_id'] = $transactionRawData['currency'];

        return $transactionData;
    }

    private function getEndpointUrl()
    {
        return $this->url;
    }

    protected function getSignature($data, $encoded = true)
    {
        $data = array_filter($data);
        ksort($data);

        $str = $this->secret_key;
        foreach ($data as $k => $v) {
            $str .= self::SIGNATURE_SEPARATOR . $v;
        }

        if ($encoded) {
            return sha1($str);
        } else {
            return $str;
        }
    }
}
