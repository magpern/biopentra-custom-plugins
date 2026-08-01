import { chromium } from 'playwright';
import fs from 'fs';

const BASE = 'https://dev.biopentra.eu';
const pages = [
  ['home', '/'],
  ['shop', '/shop/'],
  ['cat-metabolic', '/metabolic-research-peptides/'],
  ['cat-ghrp', '/growth-hormone-releasing-peptides/'],
  ['cat-lyo', '/lyophilized-research-materials/'],
  ['prod-simple', '/product/bacteriostatic-water/'],
  ['prod-variable', '/product/tirzepatide/'],
  ['cart-empty', '/cart/'],
  ['contact', '/contact/'],
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
// Log in once (admin bypasses WooCommerce Coming Soon mode); reuse storage state.
let authState = null;
async function buildAuthState() {
  const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
  const p = await ctx.newPage();
  await p.goto(BASE + '/wp-login.php', { waitUntil: 'domcontentloaded', timeout: 45000 });
  await p.fill('#user_login', process.env.WP_USER);
  await p.fill('#user_pass', process.env.WP_PASS);
  await Promise.all([p.waitForNavigation({ timeout: 45000 }), p.click('#wp-submit')]);
  authState = await ctx.storageState();
  await ctx.close();
}
await buildAuthState();
// strip logged-in chrome so measurements match a normal visitor
async function stripAdminChrome(page) {
  await page.evaluate(() => {
    document.getElementById('wpadminbar')?.remove();
    document.getElementById('query-monitor-main')?.remove();
    document.querySelector('#qm')?.remove();
    document.documentElement.style.setProperty('margin-top', '0', 'important');
    document.body.classList.remove('admin-bar');
  }).catch(() => {});
}

async function dismissAgeGate(page) {
  try {
    const btn = page.locator('.age-gate button, .age-gate input[type=submit], button.age-gate-submit-yes, .age-gate__submit--yes, [class*="age-gate"] button').first();
    if (await btn.isVisible({ timeout: 2500 })) {
      await btn.click();
      await page.waitForTimeout(1200);
    }
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
    const addToCart = q('button[name=add-to-cart], .single_add_to_cart_button, form.cart button');
    // sticky/fixed elements
    const sticky = [];
    document.querySelectorAll('body *').forEach(el => {
      const cs = getComputedStyle(el);
      if ((cs.position === 'fixed' || cs.position === 'sticky') && el.offsetHeight > 20 && el.offsetWidth > 100) {
        const r = el.getBoundingClientRect();
        if (r.height < vh) sticky.push({ cls: (el.className + '').slice(0, 80), h: Math.round(r.height), pos: cs.position, topPx: Math.round(r.top) });
      }
    });
    // oversized images: natural vs displayed
    const bigImgs = [];
    document.querySelectorAll('img').forEach(im => {
      const r = im.getBoundingClientRect();
      if (r.width > 40 && im.naturalWidth > r.width * window.devicePixelRatio * 1.8) {
        bigImgs.push({ src: im.currentSrc.split('/').pop().slice(0, 60), nat: im.naturalWidth, disp: Math.round(r.width), h: Math.round(r.height) });
      }
      });
    // tall sections relative to viewport (top-level elementor containers)
    const tallSections = [];
    document.querySelectorAll('.e-con.e-parent, section.elementor-section, main > *').forEach(el => {
      const h = el.offsetHeight;
      if (h > vh * 0.9) tallSections.push({ cls: (el.getAttribute('data-id') || el.className + '').slice(0, 60), h: Math.round(h), vhx: +(h / vh).toFixed(1), top: top(el) });
    });
    // small touch targets among visible interactive elements in first 3 viewports
    const smallTargets = [];
    document.querySelectorAll('a, button, input, select, [role=button]').forEach(el => {
      const r = el.getBoundingClientRect();
      if (r.width > 0 && r.height > 0 && (r.top + window.scrollY) < vh * 3 && (r.height < 32 || r.width < 32)) {
        const t = (el.innerText || el.getAttribute('aria-label') || el.className || '').trim().slice(0, 40);
        if (t) smallTargets.push({ t, w: Math.round(r.width), h: Math.round(r.height) });
      }
    });
    return {
      title: document.title.slice(0, 90),
      docH: doc.scrollHeight, vh, vw,
      viewports: +(doc.scrollHeight / vh).toFixed(1),
      hOverflow: doc.scrollWidth > vw + 1 ? doc.scrollWidth - vw : 0,
      firstH1: firstH1 ? { text: firstH1.innerText.slice(0, 70), top: top(firstH1) } : null,
      firstProductTop: top(firstCard),
      firstProductVh: firstCard ? +(top(firstCard) / vh).toFixed(2) : null,
      addToCartTop: top(addToCart),
      productCount: document.querySelectorAll('.biopentra-loop-card-root, ul.products li.product').length,
      sticky: sticky.slice(0, 8),
      bigImgs: bigImgs.slice(0, 10),
      tallSections: tallSections.slice(0, 25),
      smallTargets: smallTargets.slice(0, 25),
    };
  });
}

for (const [vpName, vp] of Object.entries(viewports)) {
  const ctx = await browser.newContext({ storageState: authState, viewport: vp, deviceScaleFactor: 1, userAgent: vpName.startsWith('m') ? 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Mobile Safari/537.36' : undefined });
  const page = await ctx.newPage();
  for (const [name, path] of pages) {
    try {
      await page.goto(BASE + path, { waitUntil: 'networkidle', timeout: 45000 });
      await dismissAgeGate(page);
      await page.waitForTimeout(800);
      await stripAdminChrome(page);
      const m = await metrics(page);
      results[`${name}@${vpName}`] = m;
      await page.screenshot({ path: `shots/${name}@${vpName}.png`, fullPage: true });
    } catch (e) {
      results[`${name}@${vpName}`] = { error: String(e).slice(0, 200) };
    }
  }
  // extra captures at m390 and d1440
  if (vpName === 'm390' || vpName === 'd1440') {
    try {
      // product card element shot on homepage
      await page.goto(BASE + '/', { waitUntil: 'networkidle' });
      await dismissAgeGate(page);
      await stripAdminChrome(page);
      const card = page.locator('.biopentra-loop-card-root').first();
      if (await card.count()) await card.screenshot({ path: `shots/component-card@${vpName}.png` });
      // mobile menu open
      const burger = page.locator('[class*=hamburger], .elementor-menu-toggle, button[aria-label*=enu], .ct-header-trigger').first();
      if (await burger.isVisible({ timeout: 2000 }).catch(() => false)) {
        await burger.click();
        await page.waitForTimeout(900);
        await page.screenshot({ path: `shots/menu-open@${vpName}.png` });
      }
    } catch (e) { results[`extras@${vpName}`] = { error: String(e).slice(0, 200) }; }
  }
  await ctx.close();
}

// cart + checkout flow at m390 (add simple product via its page)
{
  const ctx = await browser.newContext({ storageState: authState, viewport: viewports.m390, deviceScaleFactor: 1 });
  const page = await ctx.newPage();
  try {
    await page.goto(BASE + '/product/bacteriostatic-water/', { waitUntil: 'networkidle' });
    await dismissAgeGate(page);
    await page.locator('button[name=add-to-cart], .single_add_to_cart_button').first().click();
    await page.waitForTimeout(2500);
    await page.goto(BASE + '/cart/', { waitUntil: 'networkidle' });
    await stripAdminChrome(page);
    results['cart-filled@m390'] = await metrics(page);
    await page.screenshot({ path: 'shots/cart-filled@m390.png', fullPage: true });
    await page.goto(BASE + '/checkout/', { waitUntil: 'networkidle' });
    await page.waitForTimeout(1500);
    await stripAdminChrome(page);
    results['checkout@m390'] = await metrics(page);
    await page.screenshot({ path: 'shots/checkout@m390.png', fullPage: true });
  } catch (e) { results['cartflow@m390'] = { error: String(e).slice(0, 300) }; }
  await ctx.close();
}

await browser.close();
fs.writeFileSync('metrics.json', JSON.stringify(results, null, 1));
console.log('done', Object.keys(results).length, 'entries');
