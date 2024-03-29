<?php

class ControllerExtensionModuleSmsAlert extends Controller
{

    public const DEFAULT_ORDER_STATUS = [
        'canceled',
        'reversal',
        'chargebackd',
        'complete',
        'denied',
        'expired',
        'failed',
        'pending',
        'processed',
        'processing',
        'refunded',
        'reversed',
        'shipped',
        'voided',
    ];

    public function status_change($route, $data)
    {
        $orderStatusId = $data[1];
        $orderId       = $data[0];

        $this->load->model('setting/setting');
        $this->load->model('checkout/order');
        $this->load->model('extension/smsalert/order');
        $this->load->model('extension/smsalert/helper');

        $order        = $this->model_checkout_order->getOrder($orderId);
        $statusName   = $this->model_extension_smsalert_order->getStatusName($orderStatusId);
        $isActive     = $this->model_setting_setting->getSettingValue("smsalert_active");

        if (empty($statusName) || !in_array(strtolower($statusName), self::DEFAULT_ORDER_STATUS )) {
            foreach (self::DEFAULT_ORDER_STATUS as $value) {
                $id = $this->model_setting_setting->getSettingValue(sprintf('smsalert_%s_status_id', $value));

                if ($id == $orderStatusId) {
                    $statusName = $value;
                    break;
                }
            }
        }

        if ($this->isModuleEnabled() && !empty($isActive) && !empty($statusName)) {
            $statusName     = str_replace(" ", "_", $statusName);
            $statusActivate = $this->model_setting_setting->getSettingValue(
                "smsalert_" . strtolower($statusName) . "_active"
            );
            $statusMessage  = $this->model_setting_setting->getSettingValue(
                "smsalert_" . strtolower($statusName) . "_message"
            );

            if (!empty($statusActivate) && !empty($statusMessage)) {
                $replace = [
                    '{order_number}'       => $order['order_id'],
                    '{order_date}'         => $order['date_added'],
                    '{order_total}'        => round(
                                                  $order['total'] * $order['currency_value'],
                                                  2
                                              ) . ' ' .$order['currency_code'],
                    '{billing_first_name}' => $order['payment_firstname'],
                    '{billing_last_name}'  => $order['payment_lastname'],
                    '{shipping_method}'    => $order['shipping_method'],
                ];

                foreach ($replace as $key => $value) {
                    $statusMessage = str_replace($key, $value, $statusMessage);
                }

                try{
                    $this->model_extension_smsalert_helper->sendSms($order['telephone'], $statusMessage);
                } catch (Exception $e) {
                    //
                }

            }
        }
    }

    public function isModuleEnabled()
    {
        $sql    = "SELECT * FROM " . DB_PREFIX . "extension WHERE code = 'smsalert'";
        $result = $this->db->query($sql);
        if ($result->num_rows) {
            return true;
        }

        return false;
    }

}