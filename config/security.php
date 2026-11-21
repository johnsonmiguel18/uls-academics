<?php

return [

    /*
    | Content-Security-Policy mode. true (the default): browsers only report violations
    | (in the console), nothing is blocked. Set CSP_REPORT_ONLY=false once the pages show no
    | violations, so the policy is enforced.
    */
    'csp_report_only' => (bool) env('CSP_REPORT_ONLY', true),

    /*
    | Proxies in front of the site (load balancer, Cloudflare, a reverse proxy): "*" for any,
    | or a comma-separated list of IP addresses or ranges. Needed so Laravel sees the visitor's
    | IP and knows when the visit came over HTTPS (secure cookies, HSTS, links). Empty: none.
    */
    'trusted_proxies' => env('TRUSTED_PROXIES'),

];
