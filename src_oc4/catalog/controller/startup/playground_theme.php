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
        // Inject cart data for header so cart-feather.twig (included in header) can show products
        if ($route === 'common/header') {
            $this->injectCartDataForHeader($args);
        }
        // Use feather URL so updated cart list is rendered in #header-cart-feather
        if (str_contains($route, 'product/thumb')) {
            $args['cart'] = $this->url->link('extension/playground_theme/common/cart_header.feather', 'language=' . $this->config->get('config_language'));
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
     * Injects cart products, vouchers, totals and labels into header view data so cart-feather.twig can show them.
     */
    private function injectCartDataForHeader(array &$data): void {
        $this->load->language('common/cart');
        $this->load->language('default');
        $totals = [];
        $taxes = $this->cart->getTaxes();
        $total = 0;
        $this->load->model('checkout/cart');
        if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
            ($this->model_checkout_cart->getTotals)($totals, $taxes, $total);
        }
        $data['products'] = [];
        $products = $this->model_checkout_cart->getProducts();
        foreach ($products as $product) {
            if ($product['option']) {
                foreach ($product['option'] as $key => $option) {
                    $product['option'][$key]['value'] = (oc_strlen($option['value']) > 20 ? oc_substr($option['value'], 0, 20) . '..' : $option['value']);
                }
            }
            $unit_price = (float)$this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'));
            $price = $this->customer->isLogged() || !$this->config->get('config_customer_price')
                ? $this->currency->format($unit_price, $this->session->data['currency'])
                : false;
            $total_val = $this->customer->isLogged() || !$this->config->get('config_customer_price')
                ? $this->currency->format($unit_price * $product['quantity'], $this->session->data['currency'])
                : false;
            $description = '';
            if ($product['subscription']) {
                if ($product['subscription']['trial_status']) {
                    $trial_price = $this->currency->format($this->tax->calculate($product['subscription']['trial_price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    $description .= sprintf($this->language->get('text_subscription_trial'), $trial_price, $product['subscription']['trial_cycle'], $this->language->get('text_' . $product['subscription']['trial_frequency']), $product['subscription']['trial_duration']);
                }
                if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                    $price = $this->currency->format($this->tax->calculate($product['subscription']['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                }
                $cycle = $product['subscription']['cycle'];
                $frequency = $this->language->get('text_' . $product['subscription']['frequency']);
                $duration = $product['subscription']['duration'];
                $description .= $duration
                    ? sprintf($this->language->get('text_subscription_duration'), $price, $cycle, $frequency, $duration)
                    : sprintf($this->language->get('text_subscription_cancel'), $price, $cycle, $frequency);
            }
            $data['products'][] = [
                'cart_id'      => $product['cart_id'],
                'thumb'        => $product['image'],
                'name'         => $product['name'],
                'model'        => $product['model'],
                'option'       => $product['option'],
                'subscription' => $description,
                'quantity'     => $product['quantity'],
                'price'        => $price,
                'total'        => $total_val,
                'reward'       => $product['reward'],
                'href'         => $this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . $product['product_id']),
            ];
        }
        $data['vouchers'] = [];
        foreach ($this->model_checkout_cart->getVouchers() as $key => $voucher) {
            $data['vouchers'][] = [
                'key'         => $key,
                'description' => $voucher['description'],
                'amount'      => $this->currency->format($voucher['amount'], $this->session->data['currency']),
            ];
        }
        $data['totals'] = [];
        foreach ($totals as $t) {
            $data['totals'][] = [
                'title' => $t['title'],
                'text'  => $this->currency->format($t['value'], $this->session->data['currency']),
            ];
        }
        $data['list'] = $this->url->link('extension/playground_theme/common/cart_header.feather', 'language=' . $this->config->get('config_language'));
        $data['product_remove'] = $this->url->link('common/cart.removeProduct', 'language=' . $this->config->get('config_language'));
        $data['voucher_remove'] = $this->url->link('common/cart.removeVoucher', 'language=' . $this->config->get('config_language'));
        $data['cart_info_url'] = $this->url->link('common/cart.info', 'language=' . $this->config->get('config_language'));
        $data['cart_url'] = $this->url->link('checkout/cart', 'language=' . $this->config->get('config_language'));
        $data['checkout'] = $this->url->link('checkout/checkout', 'language=' . $this->config->get('config_language'));
        $data['text_cart'] = $this->language->get('text_cart');
        $data['text_checkout'] = $this->language->get('text_checkout');
        $data['text_no_results'] = $this->language->get('text_no_results');
        $data['text_points'] = $this->language->get('text_points');
        $data['text_subscription'] = $this->language->get('text_subscription');
        $data['button_remove'] = $this->language->get('button_remove');
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
