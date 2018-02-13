<?php

namespace Omise\Payment\Gateway\Http\Client;

use Exception;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Omise\Payment\Model\Config\Config;
use Omise\Payment\Model\Omise;
use Omise\Payment\Model\Api\Charge as ApiCharge;
use Omise\Payment\Model\Data\Card;

class Payment implements ClientInterface
{
    /**
     * Client request status represented to successful request step.
     *
     * @var string
     */
    const PROCESS_STATUS_SUCCESSFUL = 'successful';

    /**
     * Client request status represented to failed request step.
     *
     * @var string
     */
    const PROCESS_STATUS_FAILED = 'failed';

    /**
     * @var Omise\Payment\Model\Omise
     */
    protected $omise;

    /**
     * @var \Omise\Payment\Model\Api\Charge
     */
    protected $apiCharge;

    protected $card;

    public function __construct(
        ApiCharge $apiCharge,
        Omise     $omise,
        Card $card
    ) {
        $this->omise     = $omise;
        $this->apiCharge = $apiCharge;
        $this->card = $card;
    }

    /**
     * @param  \Magento\Payment\Gateway\Http\TransferInterface $transferObject
     *
     * @return \Omise\Payment\Model\Api\Charge|\Omise\Payment\Model\Api\Error
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $this->omise->defineUserAgent();
        $this->omise->defineApiVersion();
        $this->omise->defineApiKeys();

        $params = $transferObject->getBody();
        $payment_params = $this->card->createPaymentParams($params);
        return ['charge' => $this->apiCharge->create($payment_params)];
    }
}
