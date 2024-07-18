<?

class ControllerExtensionModuleWappiPro extends Controller
{
    public function status_change($route, $data)
    {
        $orderStatusId = $data[1];
        $orderId = $data[0];

        $this->load->model('setting/setting');
        $this->load->model('checkout/order');
        $this->load->model('localisation/order_status');

        $order = $this->model_checkout_order->getOrder($orderId);
        $statusName = $this->model_localisation_order_status->getOrderStatus($orderStatusId)['name'];
        $isActive = $this->model_setting_setting->getSettingValue("wappipro_active");
        $isSelfSendingActive = $this->model_setting_setting->getSettingValue("wappipro_self_sending_active");
        if ($this->isModuleEnabled() && !empty($isActive) && !empty($statusName)) {

            $isAdminSend = $this->model_setting_setting->getSettingValue("wappipro_admin_" . $orderStatusId . "_active");
            $statusActivate = $this->model_setting_setting->getSettingValue("wappipro_" . $orderStatusId . "_active");
            $statusMessage = $this->model_setting_setting->getSettingValue("wappipro_" . $orderStatusId . "_message");

            if (!empty($statusActivate) && !empty($statusMessage)) {
                $replace = [
                    '{order_number}' => $order['order_id'],
                    '{order_date}' => $order['date_added'],
                    '{order_total}' => round($order['total'] * $order['currency_value'], 2) . ' ' . $order['currency_code'],
                    '{billing_first_name}' => $order['payment_firstname'],
                    '{billing_last_name}' => $order['payment_lastname'],
                    '{shipping_method}' => $order['shipping_method'],
                ];

                foreach ($replace as $key => $value) {
                    $statusMessage = str_replace($key, $value, $statusMessage);
                }

                $apiKey = $this->model_setting_setting->getSettingValue('wappipro_apiKey');
                $username = $this->model_setting_setting->getSettingValue('wappipro_username');

                if (!empty($apiKey)) {
                    $platform = $this->model_setting_setting->getSettingValue('wappipro_platform');

                    $req = [
                        'postfields' => json_encode([
                            'recipient' => $order['telephone'],
                            'body' => $statusMessage,
                        ]),
                        'header' => [
                            "accept: application/json",
                            "Authorization: " .  $apiKey,
                            "Content-Type: application/json",
                        ],
                        'url' => 'https://wappi.pro/' . $platform . 'api/sync/message/send?profile_id=' . $username,
                    ];
                    ob_start();
                    var_dump($req);
                    $log_data = ob_get_clean();
                    error_log($log_data, 3, DIR_LOGS . "check.log");
                    if (!empty($isSelfSendingActive)) {
                        $wappipro_self_phone = $this->model_setting_setting->getSettingValue("wappipro_test_phone_number");
                        if (!empty($wappipro_self_phone) && !empty($isAdminSend)) {
                            $req_self = [
                                'postfields' => json_encode([
                                    'recipient' => $wappipro_self_phone,
                                    'body' => $statusMessage,
                                ]),
                                'header' => [
                                    "accept: application/json",
                                    "Authorization: " .  $apiKey,
                                    "Content-Type: application/json",
                                ],
                                'url' => 'https://wappi.pro/' . $platform . 'api/sync/message/send?profile_id=' . $username,
                            ];
                            $response = json_decode($this->curlito(false, $req_self), true);
                            ob_start();
                            var_dump($req_self);
                            $log_data = ob_get_clean();
                            error_log($log_data, 3, DIR_LOGS . "check.log");
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
        $sql = "SELECT * FROM " . DB_PREFIX . "extension WHERE code = 'wappipro'";
        $result = $this->db->query($sql);
        return $result->num_rows > 0;
    }

    private function curlito($wait, $req)
    {
        $curl = curl_init();
        $option = [
            CURLOPT_URL => $req['url'],
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $req['postfields'],
            CURLOPT_HTTPHEADER => $req['header'],
        ];

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
