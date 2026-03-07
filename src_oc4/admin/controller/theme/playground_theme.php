<?php
namespace Opencart\Admin\Controller\Extension\PlaygroundTheme\Theme;

class PlaygroundTheme extends \Opencart\System\Engine\Controller {
    public function index(): void {
        $this->load->language('extension/playground_theme/theme/playground_theme');
        $this->document->setTitle($this->language->get('heading_title'));

        $store_id = isset($this->request->get['store_id']) ? (int)$this->request->get['store_id'] : 0;

        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=theme')
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/playground_theme/theme/playground_theme', 'user_token=' . $this->session->data['user_token'] . '&store_id=' . $store_id)
        ];

        $data['save'] = $this->url->link('extension/playground_theme/theme/playground_theme.save', 'user_token=' . $this->session->data['user_token'] . '&store_id=' . $store_id);
        $data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=theme');

        $data['theme_playground_theme_status'] = $this->config->get('theme_playground_theme_status');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/playground_theme/theme/playground_theme', $data));
    }

    public function save(): void {
        $this->load->language('extension/playground_theme/theme/playground_theme');
        $store_id = isset($this->request->get['store_id']) ? (int)$this->request->get['store_id'] : 0;
        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/playground_theme/theme/playground_theme')) {
            $json['error'] = $this->language->get('error_permission');
        }

        if (!$json) {
            $this->load->model('setting/setting');
            $this->model_setting_setting->editSetting('theme_playground_theme', $this->request->post, $store_id);
            $json['success'] = $this->language->get('text_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function install(): void {
        if ($this->user->hasPermission('modify', 'extension/theme')) {
            $this->load->model('setting/startup');
            $this->model_setting_startup->addStartup([
                'code'        => 'playground_theme',
                'description' => 'Playground Theme',
                'action'      => 'catalog/extension/playground_theme/startup/playground_theme',
                'status'      => 1,
                'sort_order'  => 0
            ]);
        }
    }

    public function uninstall(): void {
        if ($this->user->hasPermission('modify', 'extension/theme')) {
            $this->load->model('setting/startup');
            $this->model_setting_startup->deleteStartupByCode('playground_theme');
        }
    }
}
