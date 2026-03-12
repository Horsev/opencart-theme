/**
 * Cart header behaviour — mirrors common/cart-feather.twig structure:
 * - #header-cart-feather: collapse container (section with products/vouchers/totals)
 * - #header-cart: button (label); refreshed from data-cart-info-url when feather updates
 */
(function () {
	'use strict';

	// Same IDs/attrs as in cart-feather.twig and header.twig
	var SELECTORS = {
		feather: 'header-cart-feather',   // div.container.collapse (cart list)
		button:  'header-cart',            // cart button with count
		buttonUrlAttr: 'data-cart-info-url'
	};

	function getEl(id) {
		return document.getElementById(id);
	}

	// #header-cart-feather: init Bootstrap Collapse so the toggle works
	function initFeatherCollapse() {
		var el = getEl(SELECTORS.feather);
		if (!el || typeof bootstrap === 'undefined') return;
		try {
			bootstrap.Collapse.getOrCreateInstance(el, { toggle: false });
		} catch (e) {}
	}

	// #header-cart: replace with fresh button HTML (count/total) from cart info URL
	function refreshCartButton() {
		var url = document.body.getAttribute(SELECTORS.buttonUrlAttr);
		var target = getEl(SELECTORS.button);
		if (!url || !target || typeof jQuery === 'undefined') return;
		jQuery(target).load(url.replace(/&amp;/g, '&'));
	}

	// When #header-cart-feather content changes (AJAX cart update), re-init collapse and refresh button
	function onFeatherUpdated() {
		initFeatherCollapse();
		refreshCartButton();
	}

	// DOM ready: init collapse for initial render (same as Twig output)
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initFeatherCollapse);
	} else {
		initFeatherCollapse();
	}

	// Observe #header-cart-feather (like re-render of cart-feather): run same flow
	var featherEl = getEl(SELECTORS.feather);
	if (featherEl && typeof MutationObserver !== 'undefined') {
		var observer = new MutationObserver(onFeatherUpdated);
		observer.observe(featherEl, { childList: true, subtree: true });
	}
})();
