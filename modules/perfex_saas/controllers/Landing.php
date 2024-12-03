<?php

defined('BASEPATH') or exit('No direct script access allowed');

use Proxy\Http\Request;
use Proxy\Proxy;
use Proxy\Plugin\ProxifyPlugin;
use Proxy\Plugin\CorsPlugin;

class Landing extends ClientsController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Method to server the active landing page theme
     *
     * @return void
     */
    public function index()
    {
        $this->check_for_redirection();

        return redirect(base_url('authentication/login'));
    }

    /**
     * Method to serve the proxied landing page.
     * Its essensial the proxied adddress runs on same domain to prevent CORS or whitelabeled for this installation domain.
     *
     * @return void
     */
    public function proxy()
    {

        $this->check_for_redirection();

        $url = get_option('perfex_saas_landing_page_url');

        if ($url && $url !== base_url()) {
            if (get_option('perfex_saas_landing_page_url_mode') === 'redirection') {
                redirect($url);
            }
        }

        $request = Request::createFromGlobals();

        $proxy = new Proxy();

        $proxy->getEventDispatcher()->addListener('request.before_send', function ($event) {

            $event['request']->headers->set('X-Forwarded-For', 'php-proxy');
        });

        $proxy->getEventDispatcher()->addListener('request.sent', function ($event) {

            if ($event['response']->getStatusCode() != 200) {
                show_error("Bad status code!", $event['response']->getStatusCode(), "Landing");
            }
        });

        $proxy->getEventDispatcher()->addListener('request.complete', function ($event) {

            $content = $event['response']->getContent();
            $content .= '<!-- via php-proxy -->';
            $event['response']->setContent($content);
        });

        $dispatcher = $proxy->getEventDispatcher();
        $proxify = new ProxifyPlugin();
        $proxify->subscribe($dispatcher);

        $cors = new CorsPlugin();
        $cors->subscribe($dispatcher);

        if (isset($_GET['q'])) {
            $url = url_decrypt($_GET['q']);
        }

        $response = $proxy->forward($request, $url);

        // send the response back to the client
        $response->send();
    }

    public function show_404()
    {
        // ensure not servable by proxy, then server 404
        show_404();
    }

    /**
     * Check if there is an active session and redirect to the dashboard if loggedin.
     *
     * @return void
     */
    private function check_for_redirection()
    {
        if (get_option('perfex_saas_force_redirect_to_dashboard') == "1") {
            if (is_client_logged_in()) {
                return redirect('clients');
            }

            if (is_staff_logged_in()) {
                return redirect('admin');
            }
        }
    }
}