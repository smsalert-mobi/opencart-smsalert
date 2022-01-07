<?php

require_once  __DIR__ . '/../../../../admin/model/extension/smsalert/vendor/autoload.php';

/**
 * Class ModelExtensionSmsAlertHelper
 */
class ModelExtensionSmsAlertHelper extends Model
{
    public function sendSms($to, $body): bool
    {
        $apiKey   = $this->model_setting_setting->getSettingValue('smsalert_apiKey');
        $username = $this->model_setting_setting->getSettingValue('smsalert_username');

        if (!empty($apiKey)) {
            $client = new SmsAlert\SmsClient($username, $apiKey);
            try {
                $result = $client->sendSms($to, $body);
                if (is_int($result)) {
                    return true;
                }
            } catch (Exception $e) {
                return false;
            }
        }

        return false;
    }
}