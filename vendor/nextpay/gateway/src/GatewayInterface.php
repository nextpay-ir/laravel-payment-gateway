<?php

namespace NextPay\Gateway;

interface GatewayInterface
{

    public function getGatewayName();

    /**
     * Get transaction id
     *
     * @return int|null
     */
    public function getTransId();

    /**
     * Return card number
     *
     * @return string
     */
    public function cardNumber();

    /**
     * Sets callback url
     *
     * @param string
     */
    public function setCallbackUri($url);

    /**
     * Gets callback uri
     *
     * @return string
     */
    public function getCallbackUri();

    /**
     * This method use for redirect to gateway
     *
     * @return mixed
     */
    public function send();
}
