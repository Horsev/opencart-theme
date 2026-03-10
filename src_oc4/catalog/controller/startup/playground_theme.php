<?php
namespace Opencart\Catalog\Controller\Extension\PlaygroundTheme\Startup;

class PlaygroundTheme extends \Opencart\System\Engine\Controller {
    public function index(): void {
        if ($this->config->get('config_theme') === 'playground_theme' && $this->config->get('theme_playground_theme_status')) {
            $this->config->set('config_pagination', 12);
            $this->event->register(
                'view/*/before',
                new \Opencart\System\Engine\Action('extension/playground_theme/startup/playground_theme.event')
            );
            $this->event->register(
                'controller/product/thumb/before',
                new \Opencart\System\Engine\Action('extension/playground_theme/startup/playground_theme.thumbBefore')
            );
        }
    }

    /** Limit options for product listing (category, search, special, manufacturer). */
    private const LIMITS_PER_PAGE = [12, 24, 48, 96];

    public function event(string &$route, array &$args, mixed &$output): void {
        // Inject menu data for menu view (route can be 'common/menu' or already overridden to extension/.../common/menu)
        if (str_contains($route, 'common/menu') && isset($args['categories']) && is_array($args['categories'])) {
            $this->injectMenuData($args);
        }
        if (in_array($route, ['product/category', 'product/search', 'product/special', 'product/manufacturer_info'], true) && isset($args['limits'])) {
            $this->injectLimits($route, $args);
        }
        $override_path = DIR_EXTENSION . 'playground_theme/catalog/view/template/' . $route . '.twig';
        if (is_file($override_path)) {
            $route = 'extension/playground_theme/' . $route;
        }
    }

    /**
     * Replaces limits dropdown with 12, 24, 48, 96 for product listing views.
     */
    private function injectLimits(string $route, array &$data): void {
        $url = '';
        if (!empty($this->request->get['path'])) {
            $url .= '&path=' . $this->request->get['path'];
        }
        if (!empty($this->request->get['filter'])) {
            $url .= '&filter=' . $this->request->get['filter'];
        }
        if (!empty($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }
        if (!empty($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }
        $data['limits'] = [];
        foreach (self::LIMITS_PER_PAGE as $value) {
            $data['limits'][] = [
                'text'  => (string) $value,
                'value' => $value,
                'href'  => $this->url->link($route, 'language=' . $this->config->get('config_language') . $url . '&limit=' . $value),
            ];
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

    /**
     * Injects thumb_40, thumb_136, thumb_250, thumb_500, thumb_800 into product data for picture/srcset.
     * Derives original image path from thumb URL (OC cache format: .../image/cache/path/name-WxH.ext).
     */
    public function thumbBefore(string &$route, array &$args): void {
        if (!isset($args[0]) || !is_array($args[0]) || empty($args[0]['thumb'])) {
            return;
        }
        $data = &$args[0];
        $data['thumb_width'] = (int) $this->config->get('config_image_product_width') ?: 250;
        $data['thumb_height'] = (int) $this->config->get('config_image_product_height') ?: 250;
        if (isset($data['thumb_40'], $data['thumb_136'], $data['thumb_250'], $data['thumb_500'], $data['thumb_800'])) {
            return;
        }
        $thumb = $data['thumb'];
        $path = parse_url($thumb, PHP_URL_PATH);
        if ($path === null || $path === '') {
            return;
        }
        // Strip leading /image/ to get e.g. cache/catalog/demo-228x228.jpg
        if (str_starts_with($path, '/image/')) {
            $path = substr($path, 7);
        } elseif (str_starts_with($path, 'image/')) {
            $path = substr($path, 6);
        } else {
            return;
        }
        // Derive original: cache/catalog/demo-228x228.jpg -> catalog/demo.jpg
        $path = preg_replace('#^cache/#', '', $path);
        $original = preg_replace('/-\d+x\d+\./', '.', $path);
        if ($original === null || $original === $path) {
            return;
        }
        $original = rawurldecode(html_entity_decode($original, ENT_QUOTES, 'UTF-8'));
        if (!is_file(DIR_IMAGE . $original)) {
            return;
        }
        $data['thumb_40'] = $this->model_tool_image->resize($original, 40, 40);
        $data['thumb_136'] = $this->model_tool_image->resize($original, 136, 136);
        $data['thumb_250'] = $this->model_tool_image->resize($original, 250, 250);
        $data['thumb_500'] = $this->model_tool_image->resize($original, 500, 500);
        $data['thumb_800'] = $this->model_tool_image->resize($original, 800, 800);
    }
}
