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
        $override_path = DIR_EXTENSION . 'playground_theme/catalog/view/template/' . $route . '.twig';
        if (is_file($override_path)) {
            $route = 'extension/playground_theme/' . $route;
        }
    }
}
