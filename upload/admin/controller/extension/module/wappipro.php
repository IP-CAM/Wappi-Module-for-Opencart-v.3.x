<?

class ControllerExtensionModuleWappiPro extends Controller
{
    private $error = [];
    private $code = ['wappipro_test', 'wappipro'];
    public $testResult = true;
    private $fields_test = [
        "wappipro_test_phone_number" => [
            "label" => "Phone Number",
            "type" => "isPhoneNumber",
            "value" => "",
            "validate" => true,
        ],
    ];
    private $fields = [
        "wappipro_username" => ["label" => "Username", "type" => "isEmpty", "value" => "", "validate" => true],
        "wappipro_apiKey" => ["label" => "API Key", "type" => "isEmpty", "value" => "", "validate" => true],
        "wappipro_active" => ["value" => ""],
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
        $this->load->model('localisation/order_status');

        $this->submitted();
        $this->loadFieldsToData($data);

        $data['error_warning'] = $this->error;

        $data['wappipro_logo'] = 'view/image/wappipro/logo.jpg';

        $data['about_title'] = $this->language->get('about_title');
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_edit'] = $this->language->get('text_edit');

        $data['btn_test_text'] = $this->language->get('btn_test_text');
        $data['btn_test_placeholder'] = $this->language->get('btn_test_placeholder');
        $data['btn_test_description'] = $this->language->get('btn_test_description');
        $data['btn_test_send'] = $this->language->get('btn_test_send');

        $data['btn_wappipro_self_sending_active'] = $this->language->get('btn_wappipro_self_sending_active');

        $data['btn_apiKey_text'] = $this->language->get('btn_apiKey_text');
        $data['btn_apiKey_placeholder'] = $this->language->get('btn_apiKey_placeholder');
        $data['btn_apiKey_description'] = $this->language->get('btn_apiKey_description');
        $data['btn_duble_admin'] = $this->language->get('btn_duble_admin');

        $data['btn_username_text'] = $this->language->get('btn_username_text');
        $data['btn_username_placeholder'] = $this->language->get('btn_username_placeholder');
        $data['btn_username_description'] = $this->language->get('btn_username_description');

        $data['btn_token_save_all'] = $this->language->get('btn_token_save_all');

        $data['btn_status_order_description'] = $this->language->get('btn_status_order_description');

        // Получение списка всех статусов заказов из базы данных
        $data['order_status_list'] = $this->model_localisation_order_status->getOrderStatuses();

        $data['wappipro_test_result'] = $this->testResult;

        $data['wappipro_order_status_active'] = [];
        $data['wappipro_order_status_message'] = [];
        $data['wappipro_admin_order_status_active'] = [];

        foreach ($data['order_status_list'] as $status) {
            $data['wappipro_order_status_active'][$status['order_status_id']] = $this->model_setting_setting->getSettingValue('wappipro_' . $status['order_status_id'] . '_active') ?? '';
            $data['wappipro_order_status_message'][$status['order_status_id']] = $this->model_setting_setting->getSettingValue('wappipro_' . $status['order_status_id'] . '_message') ?? '';
            $data['wappipro_admin_order_status_active'][$status['order_status_id']] = $this->model_setting_setting->getSettingValue('wappipro_admin_' . $status['order_status_id'] . '_active') ?? '';
        }

        # common template
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/wappipro', $data));
    }

    public function isModuleEnabled()
    {
        $sql = sprintf("SELECT * FROM %sextension WHERE code = 'wappipro'", DB_PREFIX);
        $result = $this->db->query($sql);
        return $result->num_rows > 0;
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
                    $this->saveFieldsToDB();
                    $fields = $this->getFieldsValue();

                    $message = 'Test message from wappi.pro';

                    $this->model_extension_wappipro_helper->_save_user();
                    $platform = $this->model_extension_wappipro_helper->get_platform_info();
                    if ($platform !== false) {
                        if ($platform === 'wz') {
                            $platform = '';
                        } else {
                            $platform = 't';
                        }
                        $this->model_setting_setting->editSetting("wappipro_platform", ['wappipro_platform' => $platform]);
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
                    $this->saveFieldsToDB();
                }
            }

            return true;
        }

        return false;
    }

    public function loadFieldsToData(&$data)
    {
        foreach ($this->fields as $key => $value) {
            $data[$key] = $this->model_setting_setting->getSettingValue($key) ?? '';
        }

        foreach ($this->fields_test as $key => $value) {
            $data[$key] = $this->model_setting_setting->getSettingValue($key) ?? '';
        }

        // Подгрузка сообщений для каждого статуса заказа
        $order_status_list = $this->model_localisation_order_status->getOrderStatuses();
        foreach ($order_status_list as $status) {
            $data['wappipro_' . $status['order_status_id'] . '_active'] = $this->model_setting_setting->getSettingValue('wappipro_' . $status['order_status_id'] . '_active') ?? '';
            $data['wappipro_' . $status['order_status_id'] . '_message'] = $this->model_setting_setting->getSettingValue('wappipro_' . $status['order_status_id'] . '_message') ?? '';
            $data['wappipro_admin_' . $status['order_status_id'] . '_active'] = $this->model_setting_setting->getSettingValue('wappipro_admin_' . $status['order_status_id'] . '_active') ?? '';
        }
    }

    public function saveFieldsToDB()
    {
        $fields = $this->getPostFiles();

        if (!is_array($fields)) {
            $fields = [];
        }

        foreach (array_keys($fields) as $key) {
            $fields[$key] = $_POST[$key] ?? '';
        }

        if (empty($_POST['wappipro_test'])) {
            $module_fields = [];
            $module_fields['module_wappipro_status'] = !empty($fields['wappipro_active']) ? 'true' : 'false';
            $this->model_setting_setting->editSetting("module_wappipro", $module_fields);
        }

        // Сохранение сообщений для каждого статуса заказа
        $order_status_list = $this->model_localisation_order_status->getOrderStatuses();
        foreach ($order_status_list as $status) {
            $fields['wappipro_' . $status['order_status_id'] . '_message'] = $_POST['wappipro_' . $status['order_status_id'] . '_message'] ?? '';
            $fields['wappipro_' . $status['order_status_id'] . '_active'] = isset($_POST['wappipro_' . $status['order_status_id'] . '_active']) ? 'true' : 'false';
            $fields['wappipro_admin_' . $status['order_status_id'] . '_active'] = isset($_POST['wappipro_admin_' . $status['order_status_id'] . '_active']) ? 'true' : 'false';
        }

        $this->model_setting_setting->editSetting($this->getCode(), $fields);
    }

    public function validateFields()
    {
        $fields = $this->getPostFiles();

        if (!is_array($fields)) {
            $fields = [];
        }

        foreach ($fields as $key => $value) {
            if (!empty($value['validate'])) {
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

        if (!is_array($fields)) {
            $fields = [];
        }

        foreach ($fields as $key => $value) {
            $fields[$key]["value"] = $this->model_setting_setting->getSettingValue($key) ?? '';
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
