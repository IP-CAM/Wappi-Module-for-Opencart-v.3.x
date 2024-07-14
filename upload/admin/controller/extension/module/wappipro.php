<?php

/**
 * Class ControllerExtensionModuleWappiPro
 */
class ControllerExtensionModuleWappiPro extends Controller
{

    private $error       = [];
    private $code        = ['wappipro_test', 'wappipro'];
    public  $testResult  = true;
    private $fields_test = [
        "wappipro_test_phone_number" => [
            "label"    => "Phone Number",
            "type"     => "isPhoneNumber",
            "value"    => "",
            "validate" => true,
        ],
    ];
    private $fields               = [
        "wappipro_username" => ["label" => "Username", "type" => "isEmpty", "value" => "", "validate" => true],
        "wappipro_apiKey"   => ["label" => "API Key", "type" => "isEmpty", "value" => "", "validate" => true],
        "wappipro_active" => ["value" => ""],

        "wappipro_canceled_active"  => ["value" => ""],
        "wappipro_canceled_message" => ["value" => ""],

        "wappipro_canceled_reversal_active"  => ["value" => ""],
        "wappipro_canceled_reversal_message" => ["value" => ""],

        "wappipro_self_sending_active"  => ["value" => ""],

        "wappipro_chargeback_active"  => ["value" => ""],
        "wappipro_chargeback_message" => ["value" => ""],

        "wappipro_complete_active"  => ["value" => ""],
        "wappipro_complete_message" => ["value" => ""],

        "wappipro_denied_active"  => ["value" => ""],
        "wappipro_denied_message" => ["value" => ""],

        "wappipro_refunded_active"  => ["value" => ""],
        "wappipro_refunded_message" => ["value" => ""],

        "wappipro_expired_active"  => ["value" => ""],
        "wappipro_expired_message" => ["value" => ""],

        "wappipro_failed_active"  => ["value" => ""],
        "wappipro_failed_message" => ["value" => ""],

        "wappipro_pending_active"  => ["value" => ""],
        "wappipro_pending_message" => ["value" => ""],

        "wappipro_processed_active"  => ["value" => ""],
        "wappipro_processed_message" => ["value" => ""],

        "wappipro_processing_active"  => ["value" => ""],
        "wappipro_processing_message" => ["value" => ""],

        "wappipro_reversed_active"  => ["value" => ""],
        "wappipro_reversed_message" => ["value" => ""],

        "wappipro_shipped_active"  => ["value" => ""],
        "wappipro_shipped_message" => ["value" => ""],

        "wappipro_voided_active"  => ["value" => ""],
        "wappipro_voided_message" => ["value" => ""],

        "wappipro_admin_voided_active"  => ["value" => ""],
        "wappipro_admin_shipped_active"  => ["value" => ""],
        "wappipro_admin_reversed_active"  => ["value" => ""],
        "wappipro_admin_refunded_active"  => ["value" => ""],
        "wappipro_admin_processing_active"  => ["value" => ""],
        "wappipro_admin_processed_active"  => ["value" => ""],
        "wappipro_admin_pending_active"  => ["value" => ""],
        "wappipro_admin_failed_active"  => ["value" => ""],
        "wappipro_admin_expired_active"  => ["value" => ""],
        "wappipro_admin_denied_active"  => ["value" => ""],
        "wappipro_admin_complete_active"  => ["value" => ""],
        "wappipro_admin_chargeback_active"  => ["value" => ""],
        "wappipro_admin_canceled_reversal_active"  => ["value" => ""],
        "wappipro_admin_canceled_active"  => ["value" => ""],
    ];

    public function index()
    {
        if (!$this->isModuleEnabled()) {
            $this->response->redirect(
                $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
            );
            exit;
        }

        $this->load->language('extension/module/wappipro');

        $this->document->setTitle($this->language->get('heading_title'));
        $this->document->addStyle('view/stylesheet/wappipro/wappipro.css');

        $this->load->model('setting/setting');
        $this->load->model('setting/module');
        $this->load->model('design/layout');
        $this->load->model('extension/wappipro/validator');
        $this->load->model('extension/wappipro/helper');

        $this->submitted();
        $this->loadFieldsToData($data);

        $data['error_warning'] = $this->error;

        $data['wappipro_logo'] = 'view/image/wappipro/logo.jpg';

        $data['about_title'] = $this->language->get('about_title');
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_edit']     = $this->language->get('text_edit');

        $data['btn_test_text']        = $this->language->get('btn_test_text');
        $data['btn_test_placeholder'] = $this->language->get('btn_test_placeholder');
        $data['btn_test_description'] = $this->language->get('btn_test_description');
        $data['btn_test_send']        = $this->language->get('btn_test_send');

        $data['btn_wappipro_self_sending_active']        = $this->language->get('btn_wappipro_self_sending_active');

        $data['btn_apiKey_text']        = $this->language->get('btn_apiKey_text');
        $data['btn_apiKey_placeholder'] = $this->language->get('btn_apiKey_placeholder');
        $data['btn_apiKey_description'] = $this->language->get('btn_apiKey_description');
        $data['btn_duble_admin']        = $this->language->get('btn_duble_admin');

        $data['btn_username_text']        = $this->language->get('btn_username_text');
        $data['btn_username_placeholder'] = $this->language->get('btn_username_placeholder');
        $data['btn_username_description'] = $this->language->get('btn_username_description');

        $data['btn_token_save_all'] = $this->language->get('btn_token_save_all');

        $data['btn_status_order_description'] = $this->language->get('btn_status_order_description');

        $data['btn_status_order_canceled']          = $this->language->get('btn_status_order_canceled');
        $data['btn_status_order_canceled_reversal'] = $this->language->get('btn_status_order_canceled_reversal');
        $data['btn_status_order_chargebackd']       = $this->language->get('btn_status_order_chargebackd');
        $data['btn_status_order_complete']          = $this->language->get('btn_status_order_complete');
        $data['btn_status_order_denied']            = $this->language->get('btn_status_order_denied');
        $data['btn_status_order_expired']           = $this->language->get('btn_status_order_expired');
        $data['btn_status_order_failed']            = $this->language->get('btn_status_order_failed');
        $data['btn_status_order_pending']           = $this->language->get('btn_status_order_pending');
        $data['btn_status_order_processed']         = $this->language->get('btn_status_order_processed');
        $data['btn_status_order_processing']        = $this->language->get('btn_status_order_processing');
        $data['btn_status_order_refunded']          = $this->language->get('btn_status_order_refunded');
        $data['btn_status_order_reversed']          = $this->language->get('btn_status_order_reversed');
        $data['btn_status_order_shipped']           = $this->language->get('btn_status_order_shipped');
        $data['btn_status_order_voided']            = $this->language->get('btn_status_order_voided');
        $data['instructions_title']  = $this->language->get('instructions_title');

        $data['step_1']            = $this->language->get('step_1');
        $data['step_2']            = $this->language->get('step_2');
        $data['step_3']            = $this->language->get('step_3');
        $data['step_4']            = $this->language->get('step_4');
        $data['step_5'] = $this->language->get('step_5');

        $data['order_status_list']    = $this->order_status_list;  // ??
        $data['wappipro_test_result'] = $this->testResult;

        # common template
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/wappipro', $data));
    }

    public function isModuleEnabled()
    {
        $sql    = sprintf("SELECT * FROM %sextension WHERE code = 'wappipro'", DB_PREFIX);
        $result = $this->db->query($sql);
        if ($result->num_rows) {
            return true;
        }

        return false;
    }

    public function submitted()
    {
        if (!empty($_POST)) {
            if (!empty($_POST['wappipro_test'])) {
                $this->validateFields();
                if (empty($_POST['wappipro_apiKey'])) {
                    $this->error[] = ["error" => "Field api key is required for testing."];
                }

                if (empty($_POST['wappipro_username'])) {
                    $this->error[] = ["error" => "Username is required for testing."];
                }

                if (empty($this->error)) {
                    $this->saveFiledsToDB();
                    $fields = $this->getFieldsValue();

                    $message = 'Test message from wappi.pro';

                    $this->model_extension_wappipro_helper->_save_user();
                    $platform = $this->model_extension_wappipro_helper->get_platform_info();
                    if ($platform !== false) {
                        if ($platform === 'wz') $platform = '';
                        else {
                            $platform = 't';
                        }
                        $this->model_setting_setting->editSetting("wappipro_platform", array('wappipro_platform' => $platform));  
                        $settings["wappipro_platform"] = $platform;
                        $result = $this->model_extension_wappipro_helper->sendTestSMS(
                            $fields['wappipro_test_phone_number']['value'],
                            $message
                        );
                        $this->testResult = $result;
                    } else {
                        $this->testResult = false;
                        $this->error[] = ["error" => "Site request error"];
                    }
                }
            } else {
                $this->testResult = true;
                $this->validateFields();
                if (empty($this->error)) {
                    $this->saveFiledsToDB();
                }
            }

            return true;
        }

        return false;
    }

    public function loadFieldsToData(&$data)
    {
        foreach ($this->fields as $key => $value) {
            $data[$key] = $this->model_setting_setting->getSettingValue($key);
        }

        foreach ($this->fields_test as $key => $value) {
            $data[$key] = $this->model_setting_setting->getSettingValue($key);
        }
    }

    public function saveFiledsToDB()
    {
        $fields = $this->getPostFiles();

        foreach (array_keys($fields) as $key) {
            if (isset($_POST[$key])) {
                $fields[$key] = $_POST[$key];
            } else {
                $fields[$key] = "";
            }
        }

        if (empty($_POST['wappipro_test'])) {
            $module_fields = [];
            if ($fields['wappipro_active']) {
                $module_fields['module_wappipro_status'] = 'true';
            } else {
                $module_fields['module_wappipro_status'] = 'false';
            }
            $this->model_setting_setting->editSetting("module_wappipro", $module_fields);
        }

        $this->model_setting_setting->editSetting($this->getCode(), $fields);
    }

    public function validateFields()
    {
        $fields = $this->getPostFiles();

        foreach ($fields as $key => $value) {
            if (isset($value['validate'])) {
                $result = call_user_func_array(
                    [$this->model_extension_wappipro_validator, $value['type']],
                    [$_POST[$key]]
                );
                if (!$result) {
                    $this->error[] = ["error" => "Field " . $value['label'] . " is required for testing."];
                }
            }
        }
    }

    public function getFieldsValue()
    {
        $fields = $this->getPostFiles();

        foreach ($fields as $key => $value) {
            $fields[$key]["value"] = $this->model_setting_setting->getSettingValue($key);
        }

        return $fields;
    }

    public function getPostFiles()
    {
        return (!empty($_POST['wappipro_test']) ? $this->fields_test : $this->fields);
    }

    public function getCode()
    {
        return (!empty($_POST['wappipro_test']) ? $this->code[0] : $this->code[1]);
    }


    public function install()
    {
        $this->load->model('setting/event');
        $this->model_setting_event->addEvent(
            'wappipro',
            'catalog/model/checkout/order/addOrderHistory/before',
            'extension/module/wappipro/status_change'
        );
    }

    public function uninstall()
    {
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode(
            'wappipro',
            'catalog/model/checkout/order/addOrderHistory/before',
            'extension/module/wappipro/status_change'
        );
    }
}
