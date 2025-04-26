<?php
if (!defined('ABSPATH')) {
    exit;
}

class GF_Dynamic_Address {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter('gform_field_types', [$this, 'register_field_types']);
        add_action('wp_ajax_gf_da_get_states', [$this, 'ajax_get_states']);
        add_action('wp_ajax_nopriv_gf_da_get_states', [$this, 'ajax_get_states']);
        add_action('wp_ajax_gf_da_get_cities', [$this, 'ajax_get_cities']);
        add_action('wp_ajax_nopriv_gf_da_get_cities', [$this, 'ajax_get_cities']);
    }

    public function register_field_types($field_types) {
        $field_types['dynamic_address'] = [
            'title' => __('Dynamic Address', 'gf-dynamic-address'),
            'class' => 'GF_Dynamic_Address_Field'
        ];
        return $field_types;
    }

    public function ajax_get_states() {
        check_ajax_referer('gf_da_nonce', 'nonce');
        $country_id = isset($_POST['country_id']) ? absint($_POST['country_id']) : 0;

        $states = json_decode(file_get_contents(GF_DA_PLUGIN_DIR . 'data/states.json'), true);
        $filtered_states = array_filter($states, function($state) use ($country_id) {
            return $state['country_id'] == $country_id;
        });

        wp_send_json_success(array_values($filtered_states));
    }

    public function ajax_get_cities() {
        check_ajax_referer('gf_da_nonce', 'nonce');
        $state_id = isset($_POST['state_id']) ? absint($_POST['state_id']) : 0;

        $cities = json_decode(file_get_contents(GF_DA_PLUGIN_DIR . 'data/cities.json'), true);
        $filtered_cities = array_filter($cities, function($city) use ($state_id) {
            return $city['state_id'] == $state_id;
        });

        wp_send_json_success(array_values($filtered_cities));
    }
}

class GF_Dynamic_Address_Field extends GF_Field {
    public $type = 'dynamic_address';

    public function get_form_editor_field_title() {
        return esc_attr__('Dynamic Address', 'gf-dynamic-address');
    }

    public function get_form_editor_button() {
        return [
            'group' => 'advanced_fields',
            'text'  => $this->get_form_editor_field_title()
        ];
    }

    public function get_field_input($form, $value = '', $entry = null) {
        $form_id = $form['id'];
        $id = $this->id;
        $repeater_data = is_array($value) && isset($value['addresses']) ? $value['addresses'] : [[]];
        $options = get_option('gf_da_repeater_settings', ['min_rows' => 1, 'max_rows' => 5]);

        $html = sprintf(
            '<div class="gf_da_repeater" data-min-rows="%s" data-max-rows="%s">',
            esc_attr($options['min_rows']),
            esc_attr($options['max_rows'])
        );

        foreach ($repeater_data as $index => $data) {
            $html .= '<div class="gf_da_repeater_item">';
            $html .= $this->get_single_address_input($form, $id, $index, $data);
            $html .= '<button type="button" class="gf_da_remove_row">Remove</button>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '<button type="button" class="gf_da_add_row">Add Address</button>';

        return $html;
    }

    private function get_single_address_input($form, $id, $index, $data) {
        $field_id = "input_{$id}_{$index}";
        $countries = json_decode(file_get_contents(GF_DA_PLUGIN_DIR . 'data/countries.json'), true);

        // Country dropdown
        $country_html = sprintf(
            '<select name="input_%s[addresses][%s][country_id]" id="%s_country" class="gf_da_country">',
            $id, $index, $field_id
        );
        $country_html .= '<option value="">Select Country</option>';
        foreach ($countries as $country) {
            $selected = ($data && isset($data['country_id']) && $data['country_id'] == $country['id']) ? 'selected' : '';
            $country_html .= sprintf(
                '<option value="%s" %s>%s</option>',
                esc_attr($country['id']),
                $selected,
                esc_html($country['name'])
            );
        }
        $country_html .= '</select>';

        // State dropdown
        $state_html = sprintf(
            '<select name="input_%s[addresses][%s][state_id]" id="%s_state" class="gf_da_state" disabled>',
            $id, $index, $field_id
        );
        $state_html .= '<option value="">Select State</option>';
        if ($data && isset($data['state_id'])) {
            $states = json_decode(file_get_contents(GF_DA_PLUGIN_DIR . 'data/states.json'), true);
            foreach ($states as $state) {
                if ($state['id'] == $data['state_id']) {
                    $state_html .= sprintf(
                        '<option value="%s" selected>%s</option>',
                        esc_attr($state['id']),
                        esc_html($state['name'])
                    );
                    break;
                }
            }
        }
        $state_html .= '</select>';

        // City dropdown
        $city_html = sprintf(
            '<select name="input_%s[addresses][%s][city_id]" id="%s_city" class="gf_da_city" disabled>',
            $id, $index, $field_id
        );
        $city_html .= '<option value="">Select City</option>';
        if ($data && isset($data['city_id'])) {
            $cities = json_decode(file_get_contents(GF_DA_PLUGIN_DIR . 'data/cities.json'), true);
            foreach ($cities as $city) {
                if ($city['id'] == $data['city_id']) {
                    $city_html .= sprintf(
                        '<option value="%s" selected>%s</option>',
                        esc_attr($city['id']),
                        esc_html($city['name'])
                    );
                    break;
                }
            }
        }
        $city_html .= '</select>';

        return sprintf(
            '<div class="gf_da_container" data-repeater-index="%s">
                <div class="gf_da_field"><label>%s</label>%s</div>
                <div class="gf_da_field"><label>%s</label>%s</div>
                <div class="gf_da_field"><label>%s</label>%s</div>
            </div>',
            esc_attr($index),
            __('Country', 'gf-dynamic-address'), $country_html,
            __('State', 'gf-dynamic-address'), $state_html,
            __('City', 'gf-dynamic-address'), $city_html
        );
    }

    public function get_value_save_entry($value, $form, $input_name, $lead_id, $lead) {
        if (!isset($value['addresses']) || !is_array($value['addresses'])) {
            return '';
        }

        $countries = json_decode(file_get_contents(GF_DA_PLUGIN_DIR . 'data/countries.json'), true);
        $states = json_decode(file_get_contents(GF_DA_PLUGIN_DIR . 'data/states.json'), true);
        $cities = json_decode(file_get_contents(GF_DA_PLUGIN_DIR . 'data/cities.json'), true);

        $saved_data = [];
        foreach ($value['addresses'] as $address) {
            $country_name = '';
            $state_name = '';
            $city_name = '';

            foreach ($countries as $country) {
                if ($country['id'] == ($address['country_id'] ?? 0)) {
                    $country_name = $country['name'];
                    break;
                }
            }
            foreach ($states as $state) {
                if ($state['id'] == ($address['state_id'] ?? 0)) {
                    $state_name = $state['name'];
                    break;
                }
            }
            foreach ($cities as $city) {
                if ($city['id'] == ($address['city_id'] ?? 0)) {
                    $city_name = $city['name'];
                    break;
                }
            }

            $saved_data[] = [
                'country_id' => $address['country_id'] ?? '',
                'country_name' => $country_name,
                'state_id' => $address['state_id'] ?? '',
                'state_name' => $state_name,
                'city_id' => $address['city_id'] ?? '',
                'city_name' => $city_name
            ];
        }

        return maybe_serialize($saved_data);
    }

    public function get_value_entry_detail($value, $currency = '', $use_text = false, $format = 'html', $media = 'screen') {
        $value = maybe_unserialize($value);
        if (!is_array($value)) {
            return '';
        }

        $output = [];
        foreach ($value as $address) {
            $output[] = sprintf(
                '%s, %s, %s',
                esc_html($address['country_name'] ?? ''),
                esc_html($address['state_name'] ?? ''),
                esc_html($address['city_name'] ?? '')
            );
        }
        return implode('; ', $output);
    }
}