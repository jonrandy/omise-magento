<?php

namespace Omise\Payment\Model\Data;

use Omise\Payment\Gateway\Request\PaymentCcTokenBuilder;
use Omise\Payment\Gateway\Request\PaymentCardBuilder;

class Card
{
    /**
     * @var \Omise\Payment\Model\Data\Customer
     */
    protected $omiseCustomer;

    public function __construct(
        Customer  $omiseCustomer
    )
    {
        $this->omiseCustomer = $omiseCustomer;
    }

    /**
     * @param   string  $card_id
     *
     * @return  string|bool
     */
    public function addCard($card_id)
    {
        $omiseCustomer = $this->omiseCustomer->omiseCustomer();
        if(! $omiseCustomer){
            return false;
        }

        try {
            $omiseCustomer->update([
                'card' => $card_id
            ]);
            $cards = $omiseCustomer->cards( array(
                'limit' => 1,
                'order' => 'reverse_chronological'
            ) );

            $card_id = $cards['data'][0]['id'];
            return $card_id;
        } catch (\Exception $e){
            return false;
        }
    }

    /**
     * @return  \OmiseCardList
     */
    public function getCards()
    {

        $omiseCustomer = $this->omiseCustomer->omiseCustomer();
        if(! $omiseCustomer){
            return false;
        }

        return $omiseCustomer->getCards();
    }

    /**
     * @param   array   $params
     *
     * @return  array
     */
    public function createPaymentParams($params)
    {
        $token      = $params[PaymentCcTokenBuilder::CARD];
        $card_id    = $params[PaymentCardBuilder::CARD_ID];
        $save_card  = $params[PaymentCardBuilder::SAVE_CARD];

        $payment_params = [];
        foreach($params as $key => $value){
            if(in_array($key, [PaymentCcTokenBuilder::CARD, PaymentCardBuilder::CARD_ID, PaymentCardBuilder::SAVE_CARD])){
                continue;
            }
            $payment_params[$key] = $value;
        }
        if($save_card && $token){
            $card_id = $this->addCard($token);
        }
        if($card_id){
            $customer_id = $this->omiseCustomer->getOmiseCustomerId();
            $payment_params['customer'] = $customer_id;
            $payment_params['card']     = $card_id;
        } else {
            $payment_params['card']     = $token;
        }

        return $payment_params;
    }
}
