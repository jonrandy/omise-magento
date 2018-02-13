<?php
namespace Omise\Payment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

class OmiseDataAssignObserver extends AbstractDataAssignObserver
{
    /**
     * @var string
     */
    const OMISE_CARD_TOKEN = 'omise_card_token';
    const OMISE_CARD_ID = 'omise_card_id';
    const OMISE_SAVE_CARD = 'omise_save_card';

    /**
     * @var array
     */
    protected $additionalInformationList = [
        self::OMISE_CARD_TOKEN,
        self::OMISE_CARD_ID,
        self::OMISE_SAVE_CARD
    ];

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(Observer $observer)
    {
        $dataObject = $this->readDataArgument($observer);

        $additionalData = $dataObject->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        if (!is_array($additionalData)) {
            return;
        }

        $paymentInfo = $this->readPaymentModelArgument($observer);

        if(isset($additionalData[self::OMISE_CARD_TOKEN]))
            $paymentInfo->setOmiseCardToken($additionalData[self::OMISE_CARD_TOKEN]);

        if(isset($additionalData[self::OMISE_CARD_ID]))
            $paymentInfo->setOmiseCardId($additionalData[self::OMISE_CARD_ID]);

        foreach ($this->additionalInformationList as $additionalInformationKey) {
            if (isset($additionalData[$additionalInformationKey])) {
                $paymentInfo->setAdditionalInformation(
                    $additionalInformationKey,
                    $additionalData[$additionalInformationKey]
                );
            }
        }
    }
}
