<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Hide tenants tab as configured in the package
if ($is_tenant) {
    foreach (perfex_saas_app_tabs_group() as $group) {
        hooks()->add_filter("{$group}_tabs", function ($tabs) use ($group, $CI) {
            $hidden_tabs = perfex_saas_tenant()->package_invoice->metadata->hidden_app_tabs->{$group} ?? [];
            foreach ($hidden_tabs as $slug) {

                /**
                 * Capture the default tab in each group. 
                 * When hidden, the default settings page lead to 404, preventing further access to other tabs.
                 * Ideally the default pages should not be hidden, 
                 * However, maintaining this code to meet with unending desire of customer for flexibility!
                 * Also come handy when desire to hide the logo upload management in settings which is the default tab.
                 */
                $reserved =
                    ($group == 'settings' && $slug == 'general') ||
                    ($group == 'customer_profile' && $slug == 'profile') ||
                    ($group == 'project' && $slug == 'project_overview');

                if (isset($tabs[$slug])) {

                    // If first default tab in the group
                    if ($reserved) {
                        $next_slug = '';
                        $slug_list = array_keys($tabs);
                        for ($i = 0; $i < count($slug_list); $i++) {
                            $next_slug = $slug_list[$i];
                            if ($next_slug === $slug) continue;

                            // Stop loop when fit next tab is found
                            if (!in_array($next_slug, $hidden_tabs))
                                break;
                            $next_slug = '';
                        }

                        if (!empty($next_slug)) {
                            // Detect if viewing any of the reserved group
                            $controller = $CI->router->fetch_class();
                            $method = $CI->router->fetch_method();
                            if (
                                ($controller === 'settings' && $method === 'index') ||
                                ($controller === 'clients' && $method === 'client') ||
                                ($controller === 'projects' && $method === 'view')
                            ) {

                                $current_group = $CI->input->get('group');
                                // Redirect the page to the next tab view
                                if (empty($current_group) || $current_group == $slug) {
                                    $current_url = current_url();
                                    $current_url = rtrim($current_url, '/') . '?' . http_build_query(array_merge($_GET, ['group' => $next_slug]));
                                    redirect($current_url);
                                }
                            }
                        }
                    }


                    unset($tabs[$slug]);
                }
            }
            return $tabs;
        });
    }
}