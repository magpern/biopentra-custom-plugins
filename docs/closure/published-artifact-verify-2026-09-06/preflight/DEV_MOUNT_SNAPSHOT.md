# Preflight DEV mount snapshot — 20260906T102953Z

## compose bind mounts (three plugins + MU guard)
48:  - /opt/biopentra/dev/_release-artifacts/mp-commerce-fulfillment:/var/www/html/wp-content/plugins/mp-commerce-fulfillment
50:  - /opt/biopentra/dev/_release-artifacts/mp-commerce-promotions:/var/www/html/wp-content/plugins/mp-commerce-promotions
51:  - /opt/biopentra/dev/_release-artifacts/universal-commerce-bundles:/var/www/html/wp-content/plugins/universal-commerce-bundles
52:  - /opt/biopentra/dev/_release-artifacts/universal-commerce-bundles-safety-guard.php:/var/www/html/wp-content/mu-plugins/universal-commerce-bundles-safety-guard.php:ro
98:      - /opt/biopentra/dev/_release-artifacts/mp-commerce-fulfillment:/var/www/html/wp-content/plugins/mp-commerce-fulfillment
100:      - /opt/biopentra/dev/_release-artifacts/mp-commerce-promotions:/var/www/html/wp-content/plugins/mp-commerce-promotions
101:      - /opt/biopentra/dev/_release-artifacts/universal-commerce-bundles:/var/www/html/wp-content/plugins/universal-commerce-bundles
102:      - /opt/biopentra/dev/_release-artifacts/universal-commerce-bundles-safety-guard.php:/var/www/html/wp-content/mu-plugins/universal-commerce-bundles-safety-guard.php:ro

## active versions
biopentra-storefront	active	0.9.43
mp-commerce-fulfillment	active	1.1.0
mp-commerce-promotions	active	0.6.0
universal-commerce-bundles	active	0.1.0
universal-commerce-bundles-safety-guard	must-use	

## MU plugins
biopentra-elementor-popup-maintenance-guard.php
biopentra-fluentform-contact-orders.php
biopentra-performance-guards.php
biopentra-rank-math-dev-guard.php
bp-cache-execution-marker.php
universal-commerce-bundles-safety-guard.php
## artifact dir versions/checksums
mp-commerce-fulfillment:  * Version: 1.1.0
mp-commerce-promotions:  * Version:           0.6.0
universal-commerce-bundles:  * Version:              0.1.0
31d81d08604592b6c6d539f2b6420a5b28fc3d8f339ebe12d82d79ee3fa95826  /opt/biopentra/dev/_release-coord/artifacts/mp-commerce-fulfillment-1.1.0.zip
a0d87ea4bf04adc1640dd04336ba98af10545d80be9fb8145c78a6fc1cd31832  /opt/biopentra/dev/_release-coord/artifacts/mp-commerce-promotions-0.6.0.zip
b99b981b3ebee7ddd8088fae2b96053577cb4c25c5e11db0e49d236ca997fd4d  /opt/biopentra/dev/_release-coord/artifacts/universal-commerce-bundles-0.1.0.zip
