<?php

/**
 * Class ControllerPaymentIngIdeal
 */
class ControllerExtensionPaymentEmspayIdeal extends Controller
{
    /**
     * Prefix for fields in admin settings page
     */
    const POST_FIELD_PREFIX = 'payment_';

    /**
     * @var array
     */
    static $update_fields = [
        'api_key',
        'status',
        'sort_order',
        'order_status_id_new',
        'order_status_id_processing',
        'order_status_id_completed',
        'order_status_id_expired',
        'order_status_id_cancelled',
        'order_status_id_error',
        'order_status_id_captured',
        'total',
        'bundle_cacert',
        'ip_filter',
        'test_api_key',
        'country_access'
    ];

    /**
     * @var string
     */
    private $emsModuleName;

    /**
     * @var array
     */
    private $error = array();

    /**
     * @param string $emsModuleName
     */
    public function index($emsModuleName = 'emspay_ideal')
    {
        $this->setModuleName($emsModuleName);

        $this->language->load('extension/payment/'.$emsModuleName);
        $this->load->model('setting/setting');
        $this->load->model('localisation/order_status');

        $this->document->setTitle($this->language->get('heading_title'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->updateSettings();
        }

        $data = $this->getTemplateData();
        $data = $this->prepareSettingsData($data);

        $data['breadcrumbs'] = $this->getBreadcrumbs();
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $data['emspay_module'] = $this->getModuleName();

        $this->response->setOutput($this->load->view('extension/payment/emspay', $data));
    }

	/**
	 * @return bool
	 */
	protected function validate()
	{
            if (!$this->request->post[$this->getModuleFieldName('api_key')]){
                if ($this->emsModuleName == 'emspay_klarnapaylater' || $this->emsModuleName == 'emspay_afterpay') {
                    if (!$this->request->post[$this->getModuleFieldName('test_api_key')]) {
                        $this->error['missing_api'] = $this->language->get('error_missing_api_key');
                    }
                } else {
                    $this->error['missing_api'] = $this->language->get('error_missing_api_key');
                }
            }

		if (!$this->user->hasPermission('modify', 'extension/payment/'.$this->getModuleName())) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	/**
	 * Method updates Payment Settings and redirects back to payment plugin page
	 */
	protected function updateSettings()
	{
		$this->model_setting_setting->editSetting(static::POST_FIELD_PREFIX . $this->getModuleName(), $this->mapPostData());

		$this->session->data['success'] = $this->language->get('text_settings_saved');

		$this->response->redirect(
			$this->url->link('marketplace/extension', 'user_token='.$this->session->data['user_token'] . '&type=payment', true)
		);
	}

    /**
     * @return array
     */
    protected function getTemplateData()
    {

        $data = [
        'heading_title' => $this->language->get('heading_title'),
        'text_edit_ems' => $this->language->get('text_edit_ems'),
        'info_help_api_key' => $this->language->get('info_help_api_key'),
        'info_help_total' => $this->language->get('info_help_total'),
        'entry_ems_api_key' => $this->language->get('entry_ems_api_key'),
        'entry_order_completed' => $this->language->get('entry_order_completed'),
        'entry_order_new' => $this->language->get('entry_order_new'),
        'entry_order_error' => $this->language->get('entry_order_error'),
        'entry_order_expired' => $this->language->get('entry_order_expired'),
        'entry_order_cancelled' => $this->language->get('entry_order_cancelled'),
        'entry_order_processing' => $this->language->get('entry_order_processing'),
        'entry_order_captured' => $this->language->get('entry_order_captured'),
        'entry_sort_order' => $this->language->get('entry_sort_order'),
        'entry_status' => $this->language->get('entry_status'),
        'entry_ems_total' => $this->language->get('entry_ems_total'),
        'entry_country_access' => $this->language->get('entry_country_access'),
        'entry_cacert' =>  $this->language->get('entry_cacert'),
        'text_enabled' => $this->language->get('text_enabled'),
        'text_disabled' => $this->language->get('text_disabled'),
        'button_save' => $this->language->get('text_button_save'),
        'button_cancel' => $this->language->get('text_button_cancel'),
        'text_yes' => $this->language->get('text_yes'),
        'text_no' => $this->language->get('text_no'),
        'action' => $this->url->link(
            'extension/payment/'.$this->getModuleName(), 'user_token='.$this->session->data['user_token'],
            true
        ),
        'cancel' => $this->url->link(
            'marketplace/extension', 'user_token='.$this->session->data['user_token'] . '&type=payment',
            true
        )
    ];
        $emsModuleName = $this->getModuleName();
        if($emsModuleName == 'emspay_klarnapaylater'){
            $data['info_help_klarnapaylater_ip_filter'] = $this->language->get('info_help_klarnapaylater_ip_filter');
            $data['info_help_klarnapaylater_test_api_key'] = $this->language->get('info_help_klarnapaylater_test_api_key');
            $data['entry_klarnapaylater_ip_filter'] = $this->language->get('entry_klarnapaylater_ip_filter');
            $data['entry_klarnapaylater_test_api_key'] = $this->language->get('entry_klarnapaylater_test_api_key');
        }
        if($emsModuleName == 'emspay_afterpay'){
            $data['info_help_afterpay_ip_filter'] = $this->language->get('info_help_afterpay_ip_filter');
            $data['info_help_afterpay_test_api_key'] = $this->language->get('info_help_afterpay_test_api_key');
            $data['entry_afterpay_ip_filter'] = $this->language->get('entry_afterpay_ip_filter');
            $data['entry_afterpay_test_api_key'] = $this->language->get('entry_afterpay_test_api_key');
            $data['info_example_country_access'] = $this->language->get('info_example_country_access');
        }
        return $data;
    }

	/**
	 * Process and prepare data for configuration page
	 *
	 * @param array $data
	 * @return array
	 */
	protected function prepareSettingsData(array $data)
	{
		foreach (static::$update_fields AS $fieldToUpdate) {
			$moduleFieldName = $this->getModuleFieldName($fieldToUpdate);

			if (isset($this->request->post[$moduleFieldName])) {
				$data[$moduleFieldName] = $this->request->post[$moduleFieldName];
			} else {
				$data[$moduleFieldName] = $this->config->get($moduleFieldName);
			}
		}

		if (empty($this->config->get($this->getModuleFieldName('api_key')))) {
			$data['info_message'] = $this->language->get('info_plugin_not_configured');
		}

		if (isset($this->error['missing_api'])) {
			$data['error_missing_api_key'] = $this->error['missing_api'];
		} else {
			$data['error_missing_api_key'] = '';
		}

		return $data;
	}

    /**
     * Generate configuration page breadcrumbs
     *
     * @return array
     */
    protected function getBreadcrumbs()
    {
        return [
            [
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token='.$this->session->data['user_token'], true)
            ],
            [
                'text' => $this->language->get('text_extension'),
                'href' => $this->url->link('marketplace/extension', 'user_token='.$this->session->data['user_token'].'&type=payment', true)
            ],
            [
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/payment/'.$this->getModuleName(),
                    'user_token='.$this->session->data['user_token'], true)
            ]
        ];
    }

	/**
	 * @return array
	 */
	protected function mapPostData()
	{
		$postFields = [];
		foreach (static::$update_fields AS $field) {
			$moduleFieldName = $this->getModuleFieldName($field);
			if ( array_key_exists($moduleFieldName, $this->request->post) ) {
				$postFields[$moduleFieldName] = $this->request->post[$moduleFieldName];
			}
		}

		return $postFields;
	}

    /**
     * @param string $emsModuleName
     */
    protected function setModuleName($emsModuleName)
    {
        $this->emsModuleName = $emsModuleName;
    }

    /**
     * @return string
     */
    protected function getModuleName()
    {
        return $this->emsModuleName;
    }

	/**
	 * @param  string $fieldName
	 * @return string
	 */
	protected function getModuleFieldName($fieldName)
	{
		return static::POST_FIELD_PREFIX . $this->getModuleName().'_'.$fieldName;
	}

    public function install()
    {
        $this->load->model('setting/event');

        $refundOrderEvent = $this->model_setting_event->getEventByCode('emspay_refund_order');
        if (empty($refundOrderEvent)) {
            $this->model_setting_event->addEvent(
                'emspay_refund_order',
                'admin/model/sale/return/addReturnHistory/after',
                'extension/payment/emspay_ideal/refund_an_order'
            );
        }
    }

    /**
     * Function refund_an_order - refund EMS order
     */
    public function refund_an_order(){
        try {
            if ($this->request->post['return_status_id'] == 3) {
                $return_id = $this->request->get['return_id'];
                $this->load->model('sale/return');
                $this->load->model('localisation/return_reason');
                $return_info = $this->model_sale_return->getReturn($return_id);
                $orderId = $return_info['order_id'];
                $this->load->model('sale/order');
                $orderInfo = $this->model_sale_order->getOrder($orderId);

                $this->language->load('extension/payment/' . $orderInfo['payment_code']);
                if (empty($return_info)) {
                    throw new Exception('Product return information is empty');
                }
                $return_reason = $this->model_localisation_return_reason->getReturnReason($return_info["return_reason_id"]);
                $product = $return_info['model'];
                $orderProducts = $this->model_sale_order->getOrderProducts($orderId);

                foreach ($orderProducts as $orderProduct) {
                    if ($orderProduct['model'] == $product) {
                        if (!(int) $orderProduct['total']) {
                            throw new Exception($orderInfo['payment_method'] . ': ' . $this->language->get('empty_price'));
                        }
                        $amount = (int) $orderProduct['total'] * 100;
                    }
                }
                $order_history = $this->model_sale_order->getOrderHistories($orderId, 1, 1);
                $emsOrderId = substr($order_history[0]['comment'], strpos($order_history[0]['comment'], ":") + 2);

                $emsHelper = new EmsHelper($orderInfo['payment_code']);
                $ems = $emsHelper->getClient($this->config);

                if ($emsOrderId) {
                    $emsOrder = $ems->getOrder(
                        $emsOrderId
                    );
                }

                if ($emsOrder['status'] != 'completed') {
                    throw new Exception($orderInfo['payment_method'] . ': ' . $this->language->get('wrong_order_status'));
                }

                $refund_data = [
                    'amount' => $amount,
                    'description' => 'OrderID: #' . $orderId . ', Reason: ' . $return_reason['name']
                ];

                if ($orderInfo['payment_code'] == 'emspay_klarna-pay-later' || $orderInfo['payment_code'] == 'emspay_afterpay') {
                    if (!isset($emsOrder['transactions']['flags']['has-captures'])) {
                        throw new Exception($orderInfo['payment_method'] . ': ' . $this->language->get('order_not_captured'));
                    };
                    $orderInfo['total'] = $amount;
                    $refund_data['order_lines'] = $emsHelper->getOrderLines($orderInfo,
                                                                            $orderInfo['payment_code'],
                                                                            $emsHelper->getAmountInCents($orderInfo,
                                                                                                         $this->currency));
                }

                $ems_refund_order = $ems->refundOrder($emsOrder['id'], $refund_data);
                if (in_array($ems_refund_order['status'], ['error', 'cancelled', 'expired'])) {
                    if (isset(current($ems_refund_order['transactions'])['reason'])) {
                        throw new Exception($orderInfo['payment_method'] . ': ' . current($ems_refund_order['transactions'])['reason']);
                    }
                    throw new Exception($orderInfo['payment_method'] . ': ' . $this->language->get('refund_not_completed'));
                }
            }
        } catch (Exception $e) {
            $this->log->write($e->getMessage());
            exit();
        }
    }
}