<?php

namespace Plisio\PlisioSdkLaravel;

class Payment
{
    protected $secretKey = '';
    public $apiEndPoint = 'https://plisio.net/api/v1';

    /**
     * Initiate payment object with an api key.
     * @param string $secretKey
     */
    public function __construct(string $secretKey)
    {
        $this->secretKey = $secretKey;
    }

    protected function getApiUrl($commandUrl): string
    {
        return trim($this->apiEndPoint, '/') . '/' . $commandUrl;
    }

    /**
     * Check the wallet balance of the specified cryptocurrency. BTC/ETH/DASH etc.
     * @param string $currency
     * @return string
     */
    public function getBalances(string $currency)
    {
        return $this->apiCall('balances', array('currency' => $currency));
    }

    /**
     * Get shop settings information.
     * @return string
     */
    public function getShopInfo()
    {
        return $this->apiCall('shops');
    }

    /**
     * Get information about the cryptocurrencies that are enabled in the store. Specify $source_currency to get fiat/crypto rate otherwise USD/crypto will be shown.
     * @param string $source_currency
     * @return array
     */
    public function getCurrencies(string $source_currency = 'USD'): array
    {
        $currencies = $this->guestApiCall("currencies/$source_currency");
        return array_filter($currencies['data'], function ($currency) {
            return $currency['hidden'] == 0;
        });
    }

    /**
     * Create an invoice with specified parameters.
     * Returns invoice_url if white label is not enabled in the store and returns full invoice information if white label is enabled.
     * In the first case, you should redirect the user to the invoice_url , and in the second case, use the received information to render the invoice on the page.
     * Existing parameters: https://plisio.net/documentation/endpoints/create-an-invoice .
     * @param array $req
     * @return string
     */
    public function createTransaction(array $req)
    {
        return $this->apiCall('invoices/new', $req);
    }

    /**
     * Checks the Plisio API callback for validity.
     * Use to check incoming invoice status callbacks for validity.
     * @param array $post
     * @return bool
     */
    public function verifyCallbackData(array $post): bool
    {
        if (!isset($post['verify_hash'])) {
            return false;
        }

        $verifyHash = $post['verify_hash'];
        unset($post['verify_hash']);
        ksort($post);
        if (isset($post['expire_utc'])){
            $post['expire_utc'] = (string)$post['expire_utc'];
        }
        if (isset($post['tx_urls'])){
            $post['tx_urls'] = html_entity_decode($post['tx_urls']);
        }
        $postString = serialize($post);
        $checkKey = hash_hmac('sha1', $postString, $this->secretKey);
        if ($checkKey != $verifyHash) {
            return false;
        }

        return true;
    }

    private function isSetup(): bool
    {
        return !empty($this->secretKey);
    }

    protected function getCurlOptions($url): array
    {
        return [
            CURLOPT_URL => $url,
            CURLOPT_HTTPGET => true,
            CURLOPT_FAILONERROR => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
        ];
    }

    private function apiCall($cmd, $req = array())
    {
        if (!$this->isSetup()) {
            return array('error' => 'You have not called the Setup function with your private and public keys!');
        }
        return $this->guestApiCall($cmd, $req);
    }

    private function guestApiCall($cmd, $req = array())
    {
        // Generate the query string
        $queryString = '';
        if (!empty($this->secretKey)){
            $req['api_key'] = $this->secretKey;
        }
        if (!empty($req)) {
            $post_data = http_build_query($req, '', '&');
            $queryString = '?' . $post_data;
        }

        try {
            $apiUrl = $this->getApiUrl($cmd . $queryString);

            $ch = curl_init();
            curl_setopt_array($ch, $this->getCurlOptions($apiUrl));
            $data = curl_exec($ch);

            if ($data !== FALSE) {
                $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $body = substr($data, $header_size);
                $dec = $this->jsonDecode($body);
                if ($dec !== NULL && count($dec)) {
                    return $dec;
                } else {
                    // If you are using PHP 5.5.0 or higher you can use json_last_error_msg() for a better error message
                    return array('status' => 'error', 'message' => 'Unable to parse JSON result (' . json_last_error() . ')');
                }
            } else {
                return array('status' => 'error', 'message' => 'cURL error: ' . curl_error($ch));
            }
        } catch (\Exception $e) {
            return array('status' => 'error', 'message' => 'Could not send request to API : ' . $apiUrl);
        }
    }

    private function jsonDecode($data)
    {
        if (PHP_INT_SIZE < 8 && version_compare(PHP_VERSION, '5.4.0') >= 0) {
            // We are on 32-bit PHP, so use the bigint as string option. If you are using any API calls with Satoshis it is highly NOT recommended to use 32-bit PHP
            $dec = json_decode($data, TRUE, 512, JSON_BIGINT_AS_STRING);
        } else {
            $dec = json_decode($data, TRUE);
        }
        return $dec;
    }
}
