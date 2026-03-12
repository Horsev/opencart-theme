<?php
namespace Opencart\Catalog\Controller\Extension\PlaygroundTheme\Common;

/**
 * Returns the full header cart widget (button + dropdown list) for AJAX refresh.
 * Used after add/remove so both the button and #header-cart-feather update.
 */
class CartHeader extends \Opencart\System\Engine\Controller {

	/**
	 * Outputs button + cart-feather HTML for #header-cart replacement.
	 *
	 * @return void
	 */
	public function info(): void {
		$data = $this->getCartWidgetData();
		$html = $this->load->view('extension/playground_theme/common/cart_header', $data);
		$this->response->setOutput($html);
	}

	/**
	 * Outputs only the cart list HTML for #header-cart-feather replacement.
	 *
	 * @return void
	 */
	public function feather(): void {
		$data = $this->getCartWidgetData();
		$data['list'] = $this->url->link('extension/playground_theme/common/cart_header.feather', 'language=' . $this->config->get('config_language'));
		$html = $this->load->view('extension/playground_theme/common/cart_feather_inner', $data);
		$this->response->setOutput($html);
	}

	/**
	 * Builds the same data as header cart (button + feather) for templates.
	 *
	 * @return array<string, mixed>
	 */
	private function getCartWidgetData(): array {
		$this->load->language('common/cart');
		$this->load->language('default');

		$totals = [];
		$taxes = $this->cart->getTaxes();
		$total = 0;
		$this->load->model('checkout/cart');
		if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
			($this->model_checkout_cart->getTotals)($totals, $taxes, $total);
		}

		$data['text_items'] = sprintf(
			$this->language->get('text_items'),
			$this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0),
			$this->currency->format($total, $this->session->data['currency'])
		);

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
					$trial_frequency = $this->language->get('text_' . $product['subscription']['trial_frequency']);
					$description .= sprintf($this->language->get('text_subscription_trial'), $trial_price, $product['subscription']['trial_cycle'], $trial_frequency, $product['subscription']['trial_duration']);
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
		$data['cart_url'] = $this->url->link('checkout/cart', 'language=' . $this->config->get('config_language'));
		$data['checkout'] = $this->url->link('checkout/checkout', 'language=' . $this->config->get('config_language'));
		$data['text_cart'] = $this->language->get('text_cart');
		$data['text_checkout'] = $this->language->get('text_checkout');
		$data['text_no_results'] = $this->language->get('text_no_results');
		$data['text_points'] = $this->language->get('text_points');
		$data['text_subscription'] = $this->language->get('text_subscription');
		$data['text_shopping_cart'] = $this->language->get('text_cart');
		$data['button_remove'] = $this->language->get('button_remove');

		return $data;
	}
}
