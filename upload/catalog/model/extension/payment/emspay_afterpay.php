<?php

class ModelExtensionPaymentEmspayAfterpay extends Model
{
    protected static $defaultCountries = ['NL', 'BE'];

    public function getMethod($address, $total)
    {
        $this->load->language('extension/payment/emspay_afterpay');

        $query = $this->db->query("SELECT * 
            FROM " . DB_PREFIX . "zone_to_geo_zone 
            WHERE geo_zone_id = '" . (int)$this->config->get('emspay_afterpay_geo_zone_id') . "' 
            AND country_id = '" . (int)$address['country_id'] . "' 
            AND (zone_id = '" . (int)$address['zone_id'] . "' 
            OR zone_id = '0');"
        );

        if ($this->config->get('emspay_afterpay_total') > $total) {
            $status = false;
        } elseif (!$this->config->get('emspay_afterpay_geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }
        if (!EmsHelper::countryValidator($this->config->get('payment_emspay_afterpay_country_access'),
            $this->session->data['payment_address']['iso_code_2'])) {
            $status = false;
        }
        if (!EmsHelper::ipIsEnabled($this->config->get('emspay_afterpay_afterpay_ip_filter'))) {
            $status = false;
        }

        $method_data = [];

        if ($status) {
            $method_data = [
                'code' => 'emspay_afterpay',
                'title' => $this->language->get('text_title'),
                'terms' => $this->language->get('text_payment_terms'),
                'sort_order' => $this->config->get('emspay_afterpay_sort_order')
            ];
        }

        return $method_data;
    }
}
