<?php

namespace Seablast\BriefApiClient;

use Psr\Log\LoggerInterface;

/**
 * Very simple JSON RESTful API client
 * It just sends (by HTTP POST) JSON and returns what is to be returned with few optional decorators and error logging.
 *
 * @author Stanislav Rejthar
 */
class BriefApiClient
{
    /** @var ?\Psr\Log\LoggerInterface */
    protected $logger;

    /** @var string */
    private $apiUrl;

    /** @var ?string */
    private $appLogFolder;

    /**
     *
     * @param string $apiUrl
     * @param ?string $appLogFolder OPTIONAL string without trailing / or if null => the applogs will not be created
     * @param \Psr\Log\LoggerInterface $logger OPTIONAL but really recommended - throws \Exception otherwise
     */
    public function __construct($apiUrl, $appLogFolder = null, LoggerInterface $logger = null)
    {
        //error_log("debug: " . __CLASS__ . ' ' . __METHOD__);
        $this->logger = $logger;
        $this->apiUrl = $apiUrl;
        $this->appLogFolder = $appLogFolder;
    }

    /**
     * Each call returns string starting with timestamp
     * and ending with unique identifier based on the current time in microseconds.
     *
     * @return string
     */
    private function getCommunicationId()
    {
        return uniqid(date("Y-m-d-His_"));
    }

    /**
     * Send a JSON to the API and returns whatever is to return
     *
     * @param string $json
     * @param string $httpVerb POST default, or PUT/DELETE/GET
     * @return bool|string <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * However, if the <b>CURLOPT_RETURNTRANSFER</b>
     * option is set, it will return the result on success, <b>FALSE</b> on failure.
     */
    public function sendJsonLoad($json, $httpVerb = 'POST')
    {
        $communicationId = $this->getCommunicationId();
        $this->logCommunication($json, $httpVerb, $communicationId);
        $ch = curl_init($this->apiUrl);
        if ($ch === false) {
            throw new \Exception('Curl failed to init');
        }
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $json, //json_encode($postData)
            CURLOPT_SSL_VERIFYPEER => false,
            //accepts also private SSL certificates
            //@todo try without that option and if it fails, it may try with this option and inform about it
            CURLOPT_SSL_VERIFYHOST => false,
        ));
        switch ($httpVerb) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
            // no break
            case 'GET':
            case 'DELETE':
                curl_setopt_array($ch, array(
                    CURLOPT_HTTPHEADER => array(
                        //'Authorization: '.$authToken,
                        'Content-Type: application/json'
                    ),
                ));
                if (in_array($httpVerb, array('GET', 'DELETE'))) {
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $httpVerb);
                }
                break;
            case 'PUT':
                curl_setopt_array($ch, array(
                    CURLOPT_HTTPHEADER => array(
                        //'Authorization: '.$authToken,
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($json)
                    ),
                    CURLOPT_CUSTOMREQUEST => 'PUT',
                ));
                break;
            default:
                if (!is_null($this->logger)) {
                    $this->logger->error("Unknown verb {$httpVerb}");
                }
                return false;
        }
        $result = curl_exec($ch);
        if ($result) {
            $this->logCommunication((string) $result, 'resp', $communicationId);
            return $result;
        }
        if (!is_null($this->logger)) {
            $this->logger->error("Curl failed with (" . curl_errno($ch) . ") " . curl_error($ch));
            return false;
        }
        throw new \Exception("Curl failed with (" . curl_errno($ch) . ") " . curl_error($ch));
    }

    /**
     *
     * @param string $message
     * @param string $filePrefix
     * @param string $communicationId
     * @return bool Returns <b><code>TRUE</code></b> on logging or <b><code>FALSE</code></b> on not logging.
     */
    private function logCommunication($message, $filePrefix, $communicationId)
    {
        if (!$this->appLogFolder) {
            return false;
        }
        return error_log(
            $message,
            3,
            "{$this->appLogFolder}/{$filePrefix}-"
            . ($communicationId ? $communicationId : $this->getCommunicationId()) . ".json"
        );
    }

    /**
     * Sends JSON and return array decoded from the received JSON response
     *
     * @param string $json
     * @return array<mixed>
     */
    public function getJsonArray($json)
    {
        $response = (string) $this->sendJsonLoad($json);
        $result = json_decode($response, true);
        if (!$result) {
            if (!is_null($this->logger)) {
                $this->logger->error("json decode failed for " . substr($response, 0, 100)
                    . " that resulted from " . substr($json, 0, 100));
            } else {
                throw new \Exception("json decode of " . $this->apiUrl
                    . " failed for " . substr($response, 0, 100)
                    . " that resulted from " . substr($json, 0, 100));
            }
        }
        if (!is_array($result)) {
            throw new \Exception('Set json_decode to associative.');
        }
        return $result;
    }

    /**
     * Translates array to JSON, send it to API and return array decoded from the received JSON response
     *
     * @param array<mixed> $arr
     * @return array<mixed>
     */
    public function getArrayArray(array $arr)
    {
        $encoded = json_encode($arr);
        if ($encoded === false) {
            throw new \Exception('Argument MUST be an array.');
        }
        return $this->getJsonArray($encoded);
    }
}
