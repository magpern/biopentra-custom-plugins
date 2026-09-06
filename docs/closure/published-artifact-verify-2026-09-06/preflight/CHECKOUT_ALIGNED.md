# Checkout alignment to origin/main — 2026-09-06T10:30:48Z

## /opt/biopentra/dev/universal-commerce-bundles
Your branch is behind 'origin/main' by 6 commits, and can be fast-forwarded.
  (use "git pull" to update your local branch)
Updating c0c9dde..63046f3
Fast-forward
 CHANGELOG.md                                       |  18 ++
 docs/ARCHITECTURE.md                               | 181 +++++++++------
 ...le-product-representation-and-static-pricing.md |   4 +-
 ...ervation-reduction-and-restoration-lifecycle.md |   4 +-
 .../0003-versioned-cart-order-snapshot-contract.md |   4 +-
 ...-plugin-expansion-and-compatibility-contract.md |   4 +-
 ...n-component-visibility-and-direct-url-policy.md |   4 +-
 ...ut-readiness-gate-and-inactive-plugin-safety.md |   4 +-
 ...s-cutting-cart-order-line-exclusion-contract.md |  27 ++-
 .../0008-promotions-plugin-integration-closure.md  | 247 +++++++++++++++++++++
 docs/m1-closure.md                                 |  31 ++-
 tests/Unit/PluginRuntimeReadyTest.php              |   4 +-
 tests/Unit/PluginSafeFailTest.php                  |   2 +-
 universal-commerce-bundles.php                     |  10 +-
 14 files changed, 443 insertions(+), 101 deletions(-)
 create mode 100644 CHANGELOG.md
 create mode 100644 docs/adr/0008-promotions-plugin-integration-closure.md
## main...origin/main
HEAD=63046f303e8e6ff0c5e74a5af07853c6d193b780
origin/main=63046f303e8e6ff0c5e74a5af07853c6d193b780

## /opt/biopentra/dev/mp-commerce-fulfillment
Your branch is behind 'origin/main' by 5 commits, and can be fast-forwarded.
  (use "git pull" to update your local branch)
Updating 404b6c2..efe7b49
Fast-forward
 docs/ARCHITECTURE_PLAN.md                          |   1 +
 docs/COMPATIBILITY.md                              |  22 ++
 languages/mp-commerce-fulfillment.pot              |   2 +-
 mp-commerce-fulfillment.php                        |   4 +-
 readme.txt                                         |   6 +-
 src/Woo/WooOrderSource.php                         |  15 ++
 tests/integration/Woo/IntakeHooksTest.php          |  33 +++
 tests/integration/Woo/OrderFactoryTrait.php        | 111 ++++++++++
 tests/integration/Woo/RefundObserverTest.php       |  65 ++++++
 .../integration/Woo/WooOrderSourceKitLineTest.php  | 229 +++++++++++++++++++++
 tests/unit/Application/IntakeServiceTest.php       |  31 +++
 tests/unit/FrozenContractInventoryTest.php         |  10 +-
 12 files changed, 520 insertions(+), 9 deletions(-)
 create mode 100644 tests/integration/Woo/WooOrderSourceKitLineTest.php
## main...origin/main
HEAD=efe7b49764715f7dc5f1a64a3c1804d8618ad270
origin/main=efe7b49764715f7dc5f1a64a3c1804d8618ad270

## /opt/biopentra/dev/mp-commerce-promotions
Your branch is behind 'origin/main' by 2 commits, and can be fast-forwarded.
  (use "git pull" to update your local branch)
Updating 7a28d8f..57b102a
Fast-forward
 CHANGELOG.md                       | 18 ++++++++++++++++++
 docs/GITHUB_RELEASE_NOTES_0.6.0.md | 29 +++++++++++++++++++++++++++++
 mp-commerce-promotions.php         |  4 ++--
 readme.txt                         |  6 +++++-
 4 files changed, 54 insertions(+), 3 deletions(-)
 create mode 100644 docs/GITHUB_RELEASE_NOTES_0.6.0.md
## main...origin/main
HEAD=57b102a2014b9b03c74bf3759dca779682cece85
origin/main=57b102a2014b9b03c74bf3759dca779682cece85
