<?php

namespace Omise\Payment\Model\Data;

use Magento\Framework\App\ResourceConnection;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Model\Customer;
use Omise\Payment\Model\Omise;
use Omise\Payment\Gateway\Request\PaymentCcTokenBuilder;

class Card
{
    const TABLE = 'omise_customer';

    protected $resourceConnection;
    protected $customerSession;
    protected $customer;
    protected $omise;

    public function __construct(
        ResourceConnection $resourceConnection,
        CustomerSession $customerSession,
        Customer $customer,
        Omise $omise
    ){
        $this->resourceConnection = $resourceConnection;
        $this->customerSession = $customerSession;
        $this->customer = $customer;
        $this->omise = $omise;
    }

    public function initEnvironment()
    {
        $this->omise->defineUserAgent();
        $this->omise->defineApiVersion();
        $this->omise->defineApiKeys();
        return $this;
    }

    public function addCard($card_id){
        $customerId = $this->getCustomerId();
        if(!$customerId){
            return false;
        }
        $omiseCustomer = $this->omiseCustomer();
        if(!$omiseCustomer)
            return false;
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

    public function getCards(){

        $omiseCustomer = $this->omiseCustomer();
        if(!$omiseCustomer)
            return false;

        return $omiseCustomer->getCards();
    }

    public function getCustomerId(){
        return $this->customerSession->getCustomerId();
    }

    public function getOmiseCustomerId(){
        $customer_id = $this->getCustomerId();
        if(!$customer_id)
            return false;

        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName(self::TABLE);
        $data = $connection->fetchRow("SELECT * FROM `$table` WHERE customer_id = :id", ['id' => $customer_id]);
        return $data ? $data['omise_customer'] : false;
    }

    public function saveOmiseCustomerId($id){
        $customer_id = $this->getCustomerId();
        if(!$customer_id)
            return false;

        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName(self::TABLE);
        $exists = $connection->fetchRow("SELECT * FROM `$table` WHERE customer_id = :id", ['id' => $customer_id]);
        if($exists){
            $connection->query("UPDATE `$table` SET omise_customer = :omise_id WHERE customer_id = :id", [
                'omise_id' => $id,
                'id' => $customer_id
            ]);
        } else {
            $connection->query("INSERT INTO `$table` (`omise_customer`, `customer_id`) VALUES (:omise_id, :id)", [
                'omise_id' => $id,
                'id' => $customer_id
            ]);
        }
        return true;
    }

    public function createOmiseCustomer(){
        $customer_id = $this->getCustomerId();
        if(!$customer_id){
            return false;
        }
        $this->initEnvironment();
        $customer = $this->customer->load($customer_id);
        $customer_name = $customer->getFirstname() . ' ' . $customer->getLastname();
        $customer_name = trim($customer_name);
        $customer_email = $customer->getEmail();
        $data = [
            'email' => $customer_email,
            'description' => $customer_name
        ];
        $create = \OmiseCustomer::create($data);
        if ( $create['object'] == "error" ) {
            return false;
        }
        $id = $create['id'];
        $this->saveOmiseCustomerId($id);
        return $id;
    }

    public function getOmiseCustomer($id){
        $this->initEnvironment();
        try {
            $customer = \OmiseCustomer::retrieve($id);
            return $customer;
        } catch (\Exception $e){
            return false;
        }
    }

    public function omiseCustomer()
    {
        $customerId = $this->getCustomerId();
        if(!$customerId){
            return false;
        }
        $this->initEnvironment();
        $omise_customer_id = $this->getOmiseCustomerId();

        if(!$omise_customer_id)
            $omise_customer_id = $this->createOmiseCustomer();

        if(!$omise_customer_id)
            return false;

        $omiseCustomer = $this->getOmiseCustomer($omise_customer_id);
        if(!$omiseCustomer)
            $omise_customer_id = $this->createOmiseCustomer();

        if(!$omise_customer_id)
            return false;

        $omiseCustomer = $this->getOmiseCustomer($omise_customer_id);
        if(!$omiseCustomer)
            return false;

        return $omiseCustomer;
    }

    public function createPaymentParams($params){
        $token = $params[PaymentCcTokenBuilder::CARD];
        $card_id = $params[PaymentCcTokenBuilder::CARD_ID];
        $save_card = $params[PaymentCcTokenBuilder::SAVE_CARD];
        $payment_params = [];
        foreach($params as $key => $value){
            if(in_array($key, [PaymentCcTokenBuilder::CARD, PaymentCcTokenBuilder::CARD_ID, PaymentCcTokenBuilder::SAVE_CARD])){
                continue;
            }
            $payment_params[$key] = $value;
        }
        if($save_card && $token){
            $card_id = $this->addCard($token);
        }
        if($card_id){
            $customer_id = $this->getOmiseCustomerId();
            $payment_params['customer'] = $customer_id;
            $payment_params['card'] = $card_id;
        } else {
            $payment_params['card'] = $token;
        }

        return $payment_params;
    }
}