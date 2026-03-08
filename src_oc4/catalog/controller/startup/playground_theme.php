<?php
namespace Opencart\Catalog\Controller\Extension\PlaygroundTheme\Startup;

class PlaygroundTheme extends \Opencart\System\Engine\Controller {
    public function index(): void {
        if ($this->config->get('config_theme') === 'playground_theme' && $this->config->get('theme_playground_theme_status')) {
            $this->event->register(
                'view/*/before',
                new \Opencart\System\Engine\Action('extension/playground_theme/startup/playground_theme.event')
            );
        }
    }

    public function event(string &$route, array &$args, mixed &$output): void {
        // Inject menu data for menu view (route can be 'common/menu' or already overridden to extension/.../common/menu)
        if (str_contains($route, 'common/menu') && isset($args['categories']) && is_array($args['categories'])) {
            $this->injectMenuData($args);
        }
        $override_path = DIR_EXTENSION . 'playground_theme/catalog/view/template/' . $route . '.twig';
        if (is_file($override_path)) {
            $route = 'extension/playground_theme/' . $route;
        }
    }

    /**
     * Injects current route, path and category_id into menu data so the template can add active class.
     */
    private function injectMenuData(array &$data): void {
        $data['current_route'] = isset($this->request->get['route']) ? (string) $this->request->get['route'] : 'common/home';
        $data['current_path'] = isset($this->request->get['path']) ? (string) $this->request->get['path'] : '';

        if (isset($data['categories']) && is_array($data['categories'])) {
            foreach ($data['categories'] as &$category) {
                $category['category_id'] = null;
                if (!empty($category['href'])) {
                    $href = str_replace('&amp;', '&', $category['href']);
                    if (preg_match('/[?&]path=([^&\s]+)/', $href, $m)) {
                        $parts = explode('_', $m[1]);
                        $category['category_id'] = (string) $parts[0];
                    }
                }
            }
            unset($category);
        }
    }
}
