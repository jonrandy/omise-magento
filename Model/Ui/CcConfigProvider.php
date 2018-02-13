<?php
namespace Omise\Payment\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Model\CcConfig as MagentoCcConfig;
use Omise\Payment\Model\Config\Cc as OmiseCcConfig;
use Omise\Payment\Model\Data\Card;

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
    protected $card;

    public function __construct(
        MagentoCcConfig $magentoCcConfig,
        OmiseCcConfig $omiseCcConfig,
        Card $card
    )
    {
        $this->magentoCcConfig = $magentoCcConfig;
        $this->omiseCcConfig   = $omiseCcConfig;
        $this->card = $card;
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
                    'publicKey'      => $this->omiseCcConfig->getPublicKey(),
                    'offsitePayment' => $this->omiseCcConfig->is3DSecureEnabled(),
                    'cards' => $this->getCards(),
                    'is_login' => $this->card->getCustomerId() ? 1 : 0
                ],
            ]
        ];
    }

    public function getCards(){
        $cardList = $this->card->getCards();
        $data = [];
        if($cardList){
            foreach($cardList['data'] as $card){
                $label = $card['brand'] . ' xxxx' . $card['last_digits'];
                $value = $card['id'];
                $data[] = [
                    'value' => $value,
                    'label' => $label
                ];
            }
        }
        return $data;
    }
}
