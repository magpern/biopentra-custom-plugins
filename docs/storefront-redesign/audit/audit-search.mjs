/**
 * Milestone 0.1 — product search results audit.
 * URL: /?s=peptide&post_type=product (Coming Soon gated — uses woo-share cookie).
 *
 * Run from repo root (needs Playwright + network):
 *   node audit-search.mjs
 * Output: metrics-search.json, shots-search/*.png
 */
import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';

const BASE = 'https://dev.biopentra.eu';
const SEARCH_PATH = '/?s=peptide&post_type=product';
const KEY = process.env.WC_SHARE_KEY ?? 'qDpZhfbfNt31hWZx79b2aAwa1UzS5TfZ';

const viewports = {
  m360: { width: 360, height: 800 },
  m390: { width: 390, height: 844 },
  m430: { width: 430, height: 932 },
  t768: { width: 768, height: 1024 },
  d1440: { width: 1440, height: 1000 },
};

const shotsDir = path.join(import.meta.dirname, 'shots-search');
fs.mkdirSync(shotsDir, { recursive: true });

const results = {};
const browser = await chromium.launch();

async function newCtx(vp, mobile) {
  const ctx = await browser.newContext({
    viewport: vp,
    deviceScaleFactor: 1,
    userAgent: mobile
      ? 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Mobile Safari/537.36'
      : undefined,
  });
  await ctx.addCookies([{ name: 'woo-share', value: KEY, domain: 'dev.biopentra.eu', path: '/' }]);
  return ctx;
}

async function tidy(page) {
  try {
    const cb = page.locator('.cky-btn-accept, button:has-text("Accept all")').first();
    if (await cb.isVisible({ timeout: 1500 })) {
      await cb.click();
      await page.waitForTimeout(600);
    }
  } catch {}
  try {
    const ag = page.locator('[class*="age-gate"] button, .age-gate input[type=submit]').first();
    if (await ag.isVisible({ timeout: 1000 })) {
      await ag.click();
      await page.waitForTimeout(800);
    }
  } catch {}
}

async function metrics(page) {
  return page.evaluate(() => {
    const vw = window.innerWidth;
    const vh = window.innerHeight;
    const doc = document.documentElement;
    const q = (sel) => document.querySelector(sel);
    const top = (el) => (el ? Math.round(el.getBoundingClientRect().top + window.scrollY) : null);
    const firstCard = q('.biopentra-loop-card-root, ul.products li.product, .e-loop-item.product');
    const firstH1 = q('h1');
    const search = q('input[type=search], .bp-shop-search, [class*=shop-search] input, form.search-form input');
    const canonicalCards = document.querySelectorAll('.biopentra-loop-card-root').length;
    const blocksyCards = document.querySelectorAll('ul.products li.product').length;
    return {
      title: document.title.slice(0, 90),
      docH: doc.scrollHeight,
      vh,
      viewports: +(doc.scrollHeight / vh).toFixed(1),
      hOverflow: doc.scrollWidth > vw + 1 ? doc.scrollWidth - vw : 0,
      firstH1: firstH1 ? { text: firstH1.innerText.slice(0, 80), top: top(firstH1) } : null,
      firstProductTop: top(firstCard),
      firstProductVh: firstCard ? +(top(firstCard) / vh).toFixed(2) : null,
      searchTop: top(search),
      productCount: canonicalCards + blocksyCards,
      cardRenderer: canonicalCards > 0 ? 'elementor-3608-loop-card' : blocksyCards > 0 ? 'blocksy-native' : 'none',
      canonicalCardCount: canonicalCards,
      blocksyCardCount: blocksyCards,
    };
  });
}

for (const [vpName, vp] of Object.entries(viewports)) {
  const ctx = await newCtx(vp, vpName.startsWith('m'));
  const page = await ctx.newPage();
  try {
    await page.goto(BASE + SEARCH_PATH, { waitUntil: 'networkidle', timeout: 45000 });
    await tidy(page);
    await page.waitForTimeout(800);
    const m = await metrics(page);
    results[`search@${vpName}`] = m;
    await page.screenshot({ path: path.join(shotsDir, `search@${vpName}.png`), fullPage: true });
  } catch (e) {
    results[`search@${vpName}`] = { error: String(e).slice(0, 200) };
  }
  await ctx.close();
}

await browser.close();
fs.writeFileSync(path.join(import.meta.dirname, 'metrics-search.json'), JSON.stringify(results, null, 2) + '\n');
console.log('done', Object.keys(results).length, 'entries → metrics-search.json');
