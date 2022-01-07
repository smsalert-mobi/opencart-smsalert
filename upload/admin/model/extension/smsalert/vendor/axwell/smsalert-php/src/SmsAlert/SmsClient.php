<?php

namespace SmsAlert;

use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use SmsAlert\Exception\InvalidCredentials;
use SmsAlert\Exception\InvalidDate;
use SmsAlert\Exception\InvalidParameters;
use SmsAlert\Dto\PhoneList;

class SmsClient
{

    protected const API_URL = 'https://smsalert.mobi';

    protected const API_VERSION = '/api/sms/';

    protected const SEND_SMS = 'send';

    protected const SEND_BULK_SMS = 'sendBulk';

    protected const SCHEDULE_SMS = 'schedule';

    protected const CHECK_SMS_STATUS = 'checkStatus';


    private string $username;

    private string $apiKey;

    /**
     * @param string $username
     * @param string $apiKey
     */
    public function __construct(string $username, string $apiKey)
    {
        $this->username = $username;
        $this->apiKey   = $apiKey;
    }

    /**
     * @param string $tel
     * @param string $message
     * @param bool   $cleanupUtf8
     *
     * @throws InvalidCredentials
     * @throws InvalidParameters
     * @throws GuzzleException
     */
    public function sendSms(string $tel, string $message, bool $cleanupUtf8 = false)
    {
        $response = $this->request(self::SEND_SMS, [
            'tel'         => $tel,
            'message'     => $message,
            'cleanupUtf8' => $cleanupUtf8,
        ]);

        return $response['id'];
    }

    /**
     * @param PhoneList $phoneList
     * @param string    $message
     *
     * @return array|null
     * @throws GuzzleException
     * @throws InvalidCredentials
     * @throws InvalidParameters
     */
    public function sendBulkSms(PhoneList $phoneList, string $message): ?array
    {
        $response = $this->request(self::SEND_BULK_SMS, [
            'tel'         => $phoneList->getCombinedList(),
            'message'     => $message
        ]);

        return $response['ids'];
    }

    /**
     * @param string $tel
     * @param string $message
     * @param string $date
     *
     * @return mixed
     * @throws GuzzleException
     * @throws InvalidCredentials
     * @throws InvalidDate
     * @throws InvalidParameters
     */
    public function scheduleSms(string $tel, string $message, string $date)
    {
        if (!$this->validateDate($date)) {
            throw new InvalidDate('Date format should be Y-m-d H:i:s');
        }

        $response = $this->request(self::SCHEDULE_SMS, [
            'tel'         => $tel,
            'message'     => $message,
            'schedule'    => $date,
        ]);

        return $response['id'];
    }

    /**
     * @param        $date
     * @param string $format
     *
     * @return bool
     */
    private function validateDate(string $date, string $format = 'Y-m-d H:i:s'): bool
    {
        $dateTime = DateTime::createFromFormat($format, $date);
        return $dateTime && $dateTime->format($format) == $date;
    }

    /**
     * @param int $id
     *
     * @return string
     * @throws GuzzleException
     * @throws InvalidCredentials
     * @throws InvalidParameters
     */
    public function checkSmsStatus(int $id): string
    {
        $response = $this->request(self::CHECK_SMS_STATUS, [
            'id' => $id,
        ]);

        return $response['status'];
    }

    /**
     * @param string $uri
     * @param        $params
     *
     * @return bool
     * @throws InvalidCredentials
     * @throws InvalidParameters
     * @throws GuzzleException
     */
    private function request(string $uri, $params): ?array
    {
        $client = new Client(
            [
                'base_uri' => self::API_URL
            ]
        );

        try {
            $request = $client->request(
                'POST',
                self::API_VERSION . $uri,
                [
                    'form_params' => array_merge(
                        [
                            'username' => $this->username,
                            'apiKey'   => $this->apiKey,
                        ],
                        $params
                    )
                ]
            );

            $response = $request->getBody()->getContents();
            return json_decode($response, true);
        } catch (ClientException $e) {
            $error =  json_decode($e->getResponse()->getBody(), true);
            switch ($error['errorCode']) {
                case 100:
                    throw new InvalidCredentials();
                case 101:
                    throw new InvalidParameters($error['message']);
            }
        }

        return false;
    }
}