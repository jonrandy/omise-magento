<?php
namespace Omise\Payment\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Model\CcConfig as MagentoCcConfig;
use Omise\Payment\Model\Config\Cc as OmiseCcConfig;
use Omise\Payment\Model\Data\Card;
use Omise\Payment\Model\Data\Customer;

class CcConfigProvider implements ConfigProviderInterface
{
    /**
     * @var \Magento\Payment\Model\CcConfig
     */
    protected $magentoCcConfig;

    /**
     * @var \Omise\Payment\Model\Config\Cc
     */
    protected $omiseCcConfig;

    /**
     * @var \Omise\Payment\Model\Data\Card
     */
    protected $omiseCard;

    /**
     * @var \Omise\Payment\Model\Data\Customer
     */
    protected $omiseCustomer;

    public function __construct(
        MagentoCcConfig $magentoCcConfig,
        OmiseCcConfig   $omiseCcConfig,
        Card            $omiseCard,
        Customer        $omiseCustomer
    )
    {
        $this->magentoCcConfig = $magentoCcConfig;
        $this->omiseCcConfig   = $omiseCcConfig;
        $this->omiseCard       = $omiseCard;
        $this->omiseCustomer   = $omiseCustomer;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                'ccform' => [
                    'months' => [OmiseCcConfig::CODE => $this->magentoCcConfig->getCcMonths()],
                    'years'  => [OmiseCcConfig::CODE => $this->magentoCcConfig->getCcYears()],
                ],
                OmiseCcConfig::CODE => [
                    'publicKey'       => $this->omiseCcConfig->getPublicKey(),
                    'offsitePayment'  => $this->omiseCcConfig->is3DSecureEnabled(),
                    'cards'           => $this->getCards(),
                    'isCustomerLogin' => $this->omiseCustomer->getCustomerId() ? 1 : 0
                ],
            ]
        ];
    }

    /**
     * @return  array
     */
    public function getCards()
    {
        $cardList = $this->omiseCard->getCards();
        $data = [];
        if($cardList){
            foreach($cardList['data'] as $card){
                $label = $card['brand'] . ' xxxx' . $card['last_digits'];
                $data[] = [
                    'value' => $card['id'],
                    'label' => $label
                ];
            }
        }
        return $data;
    }
}
