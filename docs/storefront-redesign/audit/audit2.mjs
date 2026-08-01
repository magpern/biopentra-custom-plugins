import { chromium } from 'playwright';
import fs from 'fs';

const BASE = 'https://dev.biopentra.eu';
const KEY = 'qDpZhfbfNt31hWZx79b2aAwa1UzS5TfZ';
const pages = [
  ['shop', '/shop/'],
  ['wc-cat', '/product-category/research-peptides/'],
  ['prod-simple', '/product/bacteriostatic-water/'],
  ['prod-variable', '/product/tirzepatide/'],
  ['cart-empty', '/cart/'],
];
const viewports = {
  m360: { width: 360, height: 800 },
  m390: { width: 390, height: 844 },
  m430: { width: 430, height: 932 },
  t768: { width: 768, height: 1024 },
  d1440: { width: 1440, height: 1000 },
};

const results = {};
const browser = await chromium.launch();

async function newCtx(vp, mobile) {
  const ctx = await browser.newContext({ viewport: vp, deviceScaleFactor: 1, userAgent: mobile ? 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Mobile Safari/537.36' : undefined });
  await ctx.addCookies([{ name: 'woo-share', value: KEY, domain: 'dev.biopentra.eu', path: '/' }]);
  return ctx;
}

async function tidy(page) {
  // accept cookie banner if present; dismiss age gate if present
  try {
    const cb = page.locator('.cky-btn-accept, button:has-text("Accept all")').first();
    if (await cb.isVisible({ timeout: 1500 })) { await cb.click(); await page.waitForTimeout(600); }
  } catch (e) {}
  try {
    const ag = page.locator('[class*="age-gate"] button, .age-gate input[type=submit]').first();
    if (await ag.isVisible({ timeout: 1000 })) { await ag.click(); await page.waitForTimeout(800); }
  } catch (e) {}
}

async function metrics(page) {
  return page.evaluate(() => {
    const vw = window.innerWidth, vh = window.innerHeight;
    const doc = document.documentElement;
    const q = (sel) => document.querySelector(sel);
    const top = (el) => el ? Math.round(el.getBoundingClientRect().top + window.scrollY) : null;
    const firstCard = q('.biopentra-loop-card-root, ul.products li.product, .e-loop-item.product');
    const firstH1 = q('h1');
    const addToCart = q('button[name=add-to-cart], .single_add_to_cart_button, form.cart button[type=submit]');
    const gallery = q('.woocommerce-product-gallery, .elementor-widget-woocommerce-product-images');
    const search = q('input[type=search], .bp-shop-search, [class*=shop-search] input');
    const chips = q('.e-filter, .elementor-widget-taxonomy-filter');
    const sticky = [];
    document.querySelectorAll('body *').forEach(el => {
      const cs = getComputedStyle(el);
      if ((cs.position === 'fixed' || cs.position === 'sticky') && el.offsetHeight > 20 && el.offsetWidth > 100) {
        const r = el.getBoundingClientRect();
        if (r.height < vh) sticky.push({ cls: (el.className + '').slice(0, 70), h: Math.round(r.height), pos: cs.position });
      }
    });
    const bigImgs = [];
    document.querySelectorAll('img').forEach(im => {
      const r = im.getBoundingClientRect();
      if (r.width > 40 && im.naturalWidth > r.width * window.devicePixelRatio * 1.8) {
        bigImgs.push({ src: im.currentSrc.split('/').pop().slice(0, 55), nat: im.naturalWidth, disp: Math.round(r.width) });
      }
    });
    const tallSections = [];
    document.querySelectorAll('.e-con.e-parent, main > *, .ct-container > article > *').forEach(el => {
      const h = el.offsetHeight;
      if (h > vh * 0.9) tallSections.push({ id: (el.getAttribute('data-id') || el.className + '').slice(0, 50), vhx: +(h / vh).toFixed(1), top: top(el) });
    });
    const smallTargets = [];
    document.querySelectorAll('a, button, input, select').forEach(el => {
      const r = el.getBoundingClientRect();
      if (r.width > 0 && r.height > 0 && (r.top + window.scrollY) < vh * 3 && (r.height < 32 || r.width < 32)) {
        const t = (el.innerText || el.getAttribute('aria-label') || '').trim().slice(0, 35);
        if (t) smallTargets.push({ t, w: Math.round(r.width), h: Math.round(r.height) });
      }
    });
    return {
      title: document.title.slice(0, 80), docH: doc.scrollHeight, vh,
      viewports: +(doc.scrollHeight / vh).toFixed(1),
      hOverflow: doc.scrollWidth > vw + 1 ? doc.scrollWidth - vw : 0,
      firstH1: firstH1 ? { text: firstH1.innerText.slice(0, 60), top: top(firstH1) } : null,
      firstProductTop: top(firstCard), firstProductVh: firstCard ? +(top(firstCard) / vh).toFixed(2) : null,
      searchTop: top(search), chipsTop: top(chips),
      addToCartTop: top(addToCart), galleryH: gallery ? gallery.offsetHeight : null,
      productCount: document.querySelectorAll('.biopentra-loop-card-root, ul.products li.product').length,
      sticky: sticky.slice(0, 6), bigImgs: bigImgs.slice(0, 10),
      tallSections: tallSections.slice(0, 20), smallTargets: smallTargets.slice(0, 20),
    };
  });
}

for (const [vpName, vp] of Object.entries(viewports)) {
  const ctx = await newCtx(vp, vpName.startsWith('m'));
  const page = await ctx.newPage();
  for (const [name, path] of pages) {
    try {
      await page.goto(BASE + path, { waitUntil: 'networkidle', timeout: 45000 });
      await tidy(page);
      await page.waitForTimeout(600);
      results[`${name}@${vpName}`] = await metrics(page);
      await page.screenshot({ path: `shots2/${name}@${vpName}.png`, fullPage: true });
    } catch (e) { results[`${name}@${vpName}`] = { error: String(e).slice(0, 180) }; }
  }
  await ctx.close();
}

// interactions at m390: quick-add overlay on shop, variable product add flow, cart+checkout
{
  const ctx = await newCtx(viewports.m390, true);
  const page = await ctx.newPage();
  try {
    await page.goto(BASE + '/shop/', { waitUntil: 'networkidle' });
    await tidy(page);
    // open quick-add overlay on a variable product card (one with pills)
    const cards = page.locator('.biopentra-loop-card-root');
    const n = await cards.count();
    for (let i = 0; i < n; i++) {
      const card = cards.nth(i);
      const dataAttr = await card.getAttribute('data-biopentra-product');
      if (dataAttr && JSON.parse(dataAttr).is_variable) {
        await card.scrollIntoViewIfNeeded();
        await card.locator('.biopentra-loop-card-action').click();
        await page.waitForTimeout(600);
        await card.screenshot({ path: 'shots2/card-overlay-menu@m390.png' });
        const quick = card.locator('.biopentra-loop-overlay__btn--primary');
        if (await quick.isVisible()) { await quick.click(); await page.waitForTimeout(500); await card.screenshot({ path: 'shots2/card-overlay-quick@m390.png' }); }
        break;
      }
    }
    // add simple product via product page, then cart + checkout
    await page.goto(BASE + '/product/bacteriostatic-water/', { waitUntil: 'networkidle' });
    await tidy(page);
    await page.locator('button[name=add-to-cart], .single_add_to_cart_button').first().click();
    await page.waitForTimeout(2500);
    await page.screenshot({ path: 'shots2/prod-simple-after-add@m390.png', fullPage: false });
    await page.goto(BASE + '/cart/', { waitUntil: 'networkidle' });
    await tidy(page);
    results['cart-filled@m390'] = await metrics(page);
    await page.screenshot({ path: 'shots2/cart-filled@m390.png', fullPage: true });
    await page.goto(BASE + '/checkout/', { waitUntil: 'networkidle', timeout: 60000 });
    await tidy(page);
    await page.waitForTimeout(1500);
    results['checkout@m390'] = await metrics(page);
    await page.screenshot({ path: 'shots2/checkout@m390.png', fullPage: true });
  } catch (e) { results['flow@m390'] = { error: String(e).slice(0, 300) }; }
  await ctx.close();
}

await browser.close();
fs.writeFileSync('metrics2.json', JSON.stringify(results, null, 1));
console.log('done', Object.keys(results).length);
