<?php

/**
 * Class ModelExtensionWappiProHelper
 */
class ModelExtensionWappiProHelper extends Model
{
    public function sendTestSMS($to, $body)
    {
        $apiKey = $this->model_setting_setting->getSettingValue("wappipro_apiKey");
        $username = $this->model_setting_setting->getSettingValue("wappipro_username");
        $platform = $this->model_setting_setting->getSettingValue("wappipro_platform");

        if (!empty($apiKey)) {

            $req = array();
            $req['postfields'] = json_encode(array(
                'recipient' => $to,
                'body' => $body,
            ));

            $req['header'] = array(
                "accept: application/json",
                "Authorization: " .  $apiKey,
                "Content-Type: application/json",
            );
            $req['url'] = 'https://wappi.pro/'. $platform . 'api/sync/message/send?profile_id=' . $username;

            try {
                $answer = json_decode($this->curlito(false, $req), true);
                if ($answer === 200) {
                    return true;
                }
            } catch (Exception $e) {
                return false;
            }
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
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($err) {
            error_log($err . PHP_EOL, 3, DIR_LOGS . "wappi-errors.log");
            return "cURL Error #:" . $err;
        } else {
            return $http_status;
        }
    }

    public function get_profile_info() {
        $apikey = $this->model_setting_setting->getSettingValue("wappipro_apiKey");
        $username = $this->model_setting_setting->getSettingValue("wappipro_username");
        
        if (!$apikey || !$username) {
            error_log('Missing API key or username' . PHP_EOL, 3, DIR_LOGS . "wappi-errors.log");
            return ['error' => 'Missing API key or username'];
        }
    
        $url = 'https://wappi.pro/api/sync/get/status?profile_id=' . urlencode($username);
        $headers = array(
            "accept: application/json",
            "Authorization: " . $apikey,
            "Content-Type: application/json",
        );
    
        $curl = curl_init();
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => $headers,
        );
    
        curl_setopt_array($curl, $options);
        $result = curl_exec($curl);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);
    
        if ($err) {
            error_log('cURL Error: ' . $err . PHP_EOL, 3, DIR_LOGS . "wappi-errors.log");
            return ['error' => "cURL Error #: " . $err];
        }
    
        if ($http_status != 200) {
            error_log('HTTP Status: ' . $http_status . ' - ' . $result . PHP_EOL, 3, DIR_LOGS . "wappi-errors.log");
            return ['error' => 'HTTP Status: ' . $http_status];
        }
    
        $data = json_decode($result, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Ошибка JSON: ' . json_last_error_msg() . PHP_EOL, 3, DIR_LOGS . "wappi-errors.log");
            return ['error' => 'JSON Error: ' . json_last_error_msg()];
        }
    
        if (isset($data['status'])) {
            error_log('Ошибка отправки GET-запроса: ' . $data['status'] . PHP_EOL, 3, DIR_LOGS . "wappi-errors.log");
            return ['error' => 'Ошибка отправки GET-запроса: ' . $data['status']];
        }
    
        $platform = $data['platform'];
        if (array_key_exists('platform', $data) && $data['platform']) {
            $platform = ($data['platform'] === 'tg') ? 't' : '';
        } else {
            $platform = false;
        }

        $data["platform"] = $platform;
        $data['payment_time_string'] = $this->_parse_time($data);

        return $data;
    }

    private function _parse_time($data) {
        $result_string = '';
        $time_sub = new DateTime($data['payment_expired_at']);
        $time_curr = new DateTime;

        if ($time_sub > $time_curr) {
            $time_diff = $time_curr->diff($time_sub);
            $days_diff = $time_diff->days;
            $hours_diff = $time_diff->h;
            $result_string .= $this->language->get("wappi_green_span_and_first_part") 
                            . $time_sub->format('Y-m-d') . $this->language->get("wappi_second_part");
    
            $days_diff_last_num = $days_diff % 10;
            $hours_diff_last_num = $hours_diff % 10;
    
            if ($days_diff !== 0) {
                $result_string .= $days_diff;
    
                if ($days_diff_last_num > 4 || ($days_diff > 10 && $days_diff < 21))
                    $result_string .= $this->language->get("wappi_days");
                else if ($days_diff_last_num === 1 )
                    $result_string .= $this->language->get("wappi_day");
                else
                    $result_string .= $this->language->get("wappi_day2");
            }
            $result_string .= $hours_diff;

            if ($hours_diff_last_num > 4 || ($hours_diff > 10 && $hours_diff < 20) || $hours_diff_last_num === 0) 
                $result_string .= $this->language->get("wappi_hours");    
            else if ($hours_diff_last_num === 1)
                $result_string .= $this->language->get("wappi_hour");
            else 
                $result_string .= $this->language->get("wappi_hour2");  
        } else {
            $result_string .= $this->language->get("wappi_subscription_period_expired");
        }
        return $result_string;        
    }
    
    public function _save_user() {
        $apikey = $this->model_setting_setting->getSettingValue("wappipro_apiKey");
        $username = $this->model_setting_setting->getSettingValue("wappipro_username");
        
        $message_json = json_encode(array(
            'url' => $_SERVER['HTTP_REFERER'],
            'module' => 'opencart2x3',
            'profile_uuid' => $username,
        ));
    
        $url = 'https://dev.wappi.pro/tapi/addInstall?profile_id=' . urlencode($username);
    
        $headers = array(
            'Accept: application/json',
            'Authorization: ' . $apikey,
            'Content-Type: application/json',
        );
    
        $curl = curl_init();
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $message_json,
            CURLOPT_HTTPHEADER => $headers,
        );
    
        curl_setopt_array($curl, $options);
    
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
    
        if ($err) {
            error_log("Error save user to db" . PHP_EOL, 3, DIR_LOGS . "wappi-errors.log");
        } else if ($http_status !== 200) {
            error_log('HTTP Status save user: ' . $http_status . PHP_EOL, 3, DIR_LOGS . "wappi-errors.log");
        }
    }
}
