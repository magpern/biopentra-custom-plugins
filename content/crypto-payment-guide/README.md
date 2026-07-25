# Content bundle: crypto payment guide

Source Word doc (input only, never published): `source/paying-with-cryptochain-dot-com.docx`

Extracted screenshots: `media/*.png`

Editorial copy reference: `content.md`

## Import guide page (dev)

```bash
cd /opt/biopentra/apps/wordpress
docker compose --profile tools run --rm \
  -v /opt/biopentra/dev/biopentra-custom-plugins/content/crypto-payment-guide:/bundle:ro \
  wpcli wp eval-file /bundle/import-crypto-payment-guide.php
```

## Mega-menu link (after page import)

```bash
docker compose --profile tools run --rm \
  -v /opt/biopentra/dev/biopentra-custom-plugins/plugins/biopentra-information-megamenu:/var/www/html/wp-content/plugins/biopentra-information-megamenu:ro \
  wpcli sh -c 'BIOPENTRA_MEGA_CRYPTO_GUIDE_LINK=1 BIOPENTRA_MEGA_HEADER_POST_ID=3782 wp eval-file wp-content/plugins/biopentra-information-megamenu/cli-update-megamenu.php'
```

## Elementor CSS

```bash
docker compose --profile tools run --rm wpcli wp elementor flush-css --regenerate
```

See [`docs/deployments/crypto-payment-guide.md`](../../../docs/deployments/crypto-payment-guide.md) for production migration.
