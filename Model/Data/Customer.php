<?php
namespace Omise\Payment\Model\Data;

use Magento\Framework\App\ResourceConnection;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Model\Customer as CustomerModel;
use Omise\Payment\Model\Omise;

class Customer
{
    /**
     * @var string
     */
    const TABLE = 'omise_customer';

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $customer;

    /**
     * @var \Omise\Payment\Model\Omise
     */
    protected $omise;

    public function __construct(
        ResourceConnection  $resourceConnection,
        CustomerSession     $customerSession,
        CustomerModel       $customer,
        Omise               $omise
    )
    {
        $this->resourceConnection   = $resourceConnection;
        $this->customerSession      = $customerSession;
        $this->customer             = $customer;
        $this->omise                = $omise;
    }

    /**
     * @return  \Omise\Payment\Model\Data\Card
     */
    public function initEnvironment()
    {
        $this->omise->defineUserAgent();
        $this->omise->defineApiVersion();
        $this->omise->defineApiKeys();
        return $this;
    }

    /**
     * @return  int|bool
     */
    public function getCustomerId()
    {
        return $this->customerSession->getCustomerId();
    }

    /**
     * @return  string|bool
     */
    public function getOmiseCustomerId()
    {
        $customer_id = $this->getCustomerId();
        if(! $customer_id){
            return false;
        }

        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName(self::TABLE);
        $data = $connection->fetchRow("SELECT * FROM `$table` WHERE customer_id = :id", ['id' => $customer_id]);
        return $data ? $data['omise_customer'] : false;
    }

    /**
     * @param   string  $id
     *
     * @return  bool
     */
    public function saveOmiseCustomerId($id)
    {
        $customer_id = $this->getCustomerId();
        if(! $customer_id){
            return false;
        }

        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName(self::TABLE);
        $exists = $connection->fetchRow("SELECT * FROM `$table` WHERE customer_id = :id", ['id' => $customer_id]);
        if($exists){
            $connection->query("UPDATE `$table` SET omise_customer = :omise_id WHERE customer_id = :id", [
                'omise_id'  => $id,
                'id'        => $customer_id
            ]);
        } else {
            $connection->query("INSERT INTO `$table` (`omise_customer`, `customer_id`) VALUES (:omise_id, :id)", [
                'omise_id'  => $id,
                'id'        => $customer_id
            ]);
        }
        return true;
    }

    /**
     * @return  bool
     */
    public function createOmiseCustomer()
    {
        $customer_id = $this->getCustomerId();
        if(! $customer_id){
            return false;
        }
        $this->initEnvironment();
        $customer       = $this->customer->load($customer_id);
        $customer_name  = $customer->getFirstname() . ' ' . $customer->getLastname();
        $customer_name  = trim($customer_name);
        $customer_email = $customer->getEmail();
        $data = [
            'email'         => $customer_email,
            'description'   => $customer_name
        ];
        $create = \OmiseCustomer::create($data);
        if ( $create['object'] == "error" ) {
            return false;
        }
        $id = $create['id'];
        $this->saveOmiseCustomerId($id);
        return $id;
    }

    /**
     * @param   string  $id
     *
     * @return  \OmiseCustomer
     */
    public function getOmiseCustomer($id)
    {
        $this->initEnvironment();
        try {
            $customer = \OmiseCustomer::retrieve($id);
            return $customer;
        } catch (\Exception $e){
            return false;
        }
    }

    /**
     * @return  \OmiseCustomer|bool
     */
    public function omiseCustomer()
    {
        $customerId = $this->getCustomerId();
        if(! $customerId){
            return false;
        }
        $this->initEnvironment();
        $omise_customer_id = $this->getOmiseCustomerId();

        if(! $omise_customer_id){
            $omise_customer_id = $this->createOmiseCustomer();
        }

        if(! $omise_customer_id){
            return false;
        }

        $omiseCustomer = $this->getOmiseCustomer($omise_customer_id);
        if(! $omiseCustomer){
            $omise_customer_id = $this->createOmiseCustomer();
        }

        if(! $omise_customer_id){
            return false;
        }

        $omiseCustomer = $this->getOmiseCustomer($omise_customer_id);
        if(! $omiseCustomer){
            return false;
        }

        return $omiseCustomer;
    }
}
