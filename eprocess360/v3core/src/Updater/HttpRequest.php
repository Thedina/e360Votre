<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 7/8/2015
 * Time: 11:25 AM
 */

namespace eprocess360\v3core\Updater;


use eprocess360\v3core\Updater;
use eprocess360\v3core\Updater\Exceptions\ClientInvalidException;
use eprocess360\v3core\Updater\Module\SQLSync\Exception\InvalidRequestException;

class HttpRequest extends Signature
{
    /**
     * @param Request $request
     * @return HttpRequest
     */
    public function __construct(Request $request)
    {
        $this->addressed = false;
        $this->is_valid = false;
        $this->service_url = '';
        $this->updater = Updater::getInstance();
        $this->signature = [];
        $this->headers = [];
        if ($request->is_valid) {
            if ($request->module == 'updater') {
                $this->service_path = '/'.$request->module . $request->function;
            } else {
                $this->service_path = '/request/accept';
            }
            $this->request = $request;
            $this->is_valid = true;
        }
        return $this;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getPackage()
    {
        $package = [
            'signature' => $this->signature,
            'headers' => $this->getHeaders(),
            'hash' => md5($this->request->getHash().$this->signature['client_auth'].$this->getHeadersHash().Updater::PRIVATE_KEY),
            'request' => $this->request->getContents()
        ];
        if ($this->request->hasAttachment())
            $package['attachment'] = $this->request->getAttachment();
        return $package;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function setHeaders($headers = [])
    {
        $this->headers = array_merge($this->headers, $headers);
    }

    public function getHeadersHash()
    {
        $headers = $this->getHeaders();
        return md5(implode(array_keys($headers)).implode($headers));
    }

    public function getAttachment()
    {
        return $this->data['attachment'];
    }

    /**
     * @param Client $client
     * @throws Exceptions\ClientInvalidException
     * @throws InvalidRequestException
     */
    public function addressTo(Client $client)
    {
        if (!$this->is_valid) throw new InvalidRequestException;
        if (!$client->is_valid) throw new ClientInvalidException;
        $this->service_url = $client->getApiPath();
        $this->addressed = true;
    }

    public function validate()
    {
        return true;
    }

    public function __get ($key) {
        if (property_exists($this->data, $key)) {
            return $this->data->$key;
        }
        throw new \Exception("Key {$key} does not exist.");
    }

    public function __isset ($key) {
        if (property_exists($this->data, $key)) {
            return true;
        }
        return false;
    }

    /**
     * @return HttpAccept
     * @throws \Exception
     */
    public function send()
    {
        $url = $this->service_url . $this->service_path;
        $content = json_encode($this->getPackage());
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($content))
        );
        $json_response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        $this->updater->debug($json_response);
        $response = json_decode($json_response, true);
        if (isset($response['status'])) {
            if ($response['status'] == 200) {
                $httpAccept = new HttpAccept($response);
                return $httpAccept;
            } else {
                throw new \Exception("Error: Service returned error {$response['status']}: {$response['message']}");
            }
        }
        throw new \Exception('Error: Missing or invalid response from server.');
    }
}