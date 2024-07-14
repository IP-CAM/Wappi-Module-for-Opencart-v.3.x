<?php

class ControllerExtensionModuleWappiPro extends Controller
{

    public function status_change($route, $data)
    {
        $orderStatusId = $data[1];
        $orderId       = $data[0];

        $this->load->model('setting/setting');
        $this->load->model('checkout/order');
        $this->load->model('extension/wappipro/order');

        $order        = $this->model_checkout_order->getOrder($orderId);
        $statusName   = $this->model_extension_wappipro_order->getStatusName($orderStatusId);
        $isActive     = $this->model_setting_setting->getSettingValue("wappipro_active");
        $isSelfSendingActive     = $this->model_setting_setting->getSettingValue("wappipro_self_sending_active");
        $language = $this->config->get('config_language');


        if ($this->isModuleEnabled() && !empty($isActive) && !empty($statusName)) {

            if ($language == "ru-ru") {

                $status_name = "";

                if (strpos($statusName, "Ожидание") !== false) {
                    $status_name = "pending";
                } else if (strpos($statusName, "В обработке") !== false) {
                    $status_name = "processing";
                } else if (strpos($statusName, "Доставлено") !== false) {
                    $status_name = "shipped";
                } else if (strpos($statusName, "Отменено") !== false) {
                    $status_name = "canceled";
                } else if (strpos($statusName, "Возврат") !== false) {
                    $status_name = "reversed";
                } else if (strpos($statusName, "Отмена и аннулирование") !== false) {
                    $status_name = "canceled_reversal";
                } else if (strpos($statusName, "Возмещенный") !== false) {
                    $status_name = "chargeback";
                } else if (strpos($statusName, "Полный возврат") !== false) {
                    $status_name = "refunded";
                } else if (strpos($statusName, "Аннулированный") !== false) {
                    $status_name = "voided";
                } else if (strpos($statusName, "Обработанный") !== false) {
                    $status_name = "processed";
                } else if (strpos($statusName, "Просроченный") !== false) {
                    $status_name = "expired";
                } else if (strpos($statusName, "Полностью измененный") !== false) {
                    $status_name = "denied";
                } else if (strpos($statusName, "Неудавшийся") !== false) {
                    $status_name = "failed";
                } else if (strpos($statusName, "Сделка завершена") !== false) {
                    $status_name = "complete";
                } else if (strpos($statusName, "Canceled Reversal") !== false) {
                    $status_name = "canceled_reversal";
                } else {
                    $status_name = strtolower($statusName);
                }
            } else if (strpos($statusName, "Canceled Reversal") !== false) {
                    $status_name = "canceled_reversal";
            } else {
                $status_name = strtolower($statusName);
            }

            $isAdminSend = $this->model_setting_setting->getSettingValue(
                "wappipro_admin_" . $status_name . "_active"
            );

            $statusActivate = $this->model_setting_setting->getSettingValue(
                "wappipro_" . $status_name . "_active"
            );
            $statusMessage  = $this->model_setting_setting->getSettingValue(
                "wappipro_" . $status_name . "_message"
            );


            if (!empty($statusActivate) && !empty($statusMessage)) {
                $replace = [
                    '{order_number}'       => $order['order_id'],
                    '{order_date}'         => $order['date_added'],
                    '{order_total}'        => round(
                        $order['total'] * $order['currency_value'],
                        2
                    ) . ' ' . $order['currency_code'],
                    '{billing_first_name}' => $order['payment_firstname'],
                    '{billing_last_name}'  => $order['payment_lastname'],
                    '{shipping_method}'    => $order['shipping_method'],
                ];

                foreach ($replace as $key => $value) {
                    $statusMessage = str_replace($key, $value, $statusMessage);
                }

                $apiKey   = $this->model_setting_setting->getSettingValue('wappipro_apiKey');
                $username = $this->model_setting_setting->getSettingValue('wappipro_username');

                if (!empty($apiKey)) {

                    $platform = $this->model_setting_setting->getSettingValue('wappipro_platform');

                    $req = array();
                    $req['postfields'] = json_encode(array(
                        'recipient' => $order['telephone'],
                        'body' => $statusMessage,
                    ));

                    $req['header'] = array(
                        "accept: application/json",
                        "Authorization: " .  $apiKey,
                        "Content-Type: application/json",
                    );

                    $req['url'] = 'https://wappi.pro/'. $platform . 'api/sync/message/send?profile_id=' . $username;

                    if (!empty($isSelfSendingActive)) {

                        $wappipro_self_phone = $this->model_setting_setting->getSettingValue(
                            "wappipro_test_phone_number"
                        );

                        if (!empty($wappipro_self_phone)) {

                            if (!empty($isAdminSend)) {
                                $req_self = array();
                                $req_self['postfields'] = json_encode(array(
                                    'recipient' => $wappipro_self_phone,
                                    'body' => $statusMessage,
                                ));

                                $req_self['header'] = array(
                                    "accept: application/json",
                                    "Authorization: " .  $apiKey,
                                    "Content-Type: application/json",
                                );

                                $req_self['url'] = 'https://wappi.pro/'. $platform . 'api/sync/message/send?profile_id=' . $username;
                                $response = json_decode($this->curlito(false, $req_self), true);
                            }
                        }
                    }

                    try {
                        $response = json_decode($this->curlito(false, $req), true);
                    } catch (Exception $e) {
                        var_dump($e->getMessage());
                        die();
                    }
                }
            }
        }
    }

    public function isModuleEnabled()
    {
        $sql    = "SELECT * FROM " . DB_PREFIX . "extension WHERE code = 'wappipro'";
        $result = $this->db->query($sql);
        if ($result->num_rows) {
            return true;
        }

        return false;
    }

    private function curlito($wait, $req, $method = '')
    {

        $curl = curl_init();
        $option = array(
            CURLOPT_URL => $req['url'],
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $req['postfields'],
            CURLOPT_HTTPHEADER => $req['header'],
        );

        if ($wait) {
            $option[CURLOPT_TIMEOUT] = 30;
        } else {
            $option[CURLOPT_TIMEOUT_MS] = 5000;
            $option[CURLOPT_HEADER] = 0;
        }

        curl_setopt_array($curl, $option);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            error_log($err . PHP_EOL, 3, DIR_LOGS . "wappi-errors.log");
            return "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }
}
