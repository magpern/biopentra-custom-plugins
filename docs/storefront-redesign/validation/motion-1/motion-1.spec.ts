/**
 * MOTION-1 acceptance — homepage motion against live DEV DOM.
 *
 * Canonical owner: biopentra-custom-plugins
 *   docs/storefront-redesign/validation/motion-1/
 * Run via: scripts/run-motion-1-acceptance.sh (copies this spec into the
 * sibling storefront-acceptance harness, then removes it).
 *
 * Live DEV bind-mounts main, so this spec injects gate/CSS/controller after
 * navigation (delayed-JS path). The WP enqueue head-vs-footer contract is
 * proven by plugins/biopentra-storefront/scripts/verify-motion-1-enqueue-cli.php
 * — not by this spec.
 */
import { test, expect } from '@playwright/test';
import { dismissOverlays } from '../helpers/dismiss';
import { gotoWithComingSoonBypass } from '../helpers/coming-soon';
import { applyMotion } from './motion-1-apply';

const VIEWPORTS = new Set([
  'mobile-360',
  'mobile-390',
  'mobile-430',
  'tablet-768',
  'desktop-1440',
]);

test.describe('MOTION-1 storefront motion', () => {
  test('homepage: gate, in-view never pending, hero clean, overflow', async ({ page, context }, testInfo) => {
    test.skip(!VIEWPORTS.has(testInfo.project.name), 'MOTION-1 viewport set');

    await gotoWithComingSoonBypass(page, context, '/');
    await dismissOverlays(page);
    await applyMotion(page);

    await expect(page.locator('.bp-home-hero')).toHaveCount(1);
    await expect(page.locator('.bp-home-hero[data-bp-motion-state="pending"]')).toHaveCount(0);
    await expect(page.locator('.bp-home-hero[data-bp-motion]')).toHaveCount(0);

    const htmlClass = await page.evaluate(() => document.documentElement.className);
    expect(htmlClass).toContain('bp-motion');
    expect(htmlClass).not.toContain('bp-motion-failsafe');

    const firstCard = page.locator('.elementor-element-be24b65 .biopentra-loop-card-root').first();
    await expect(firstCard).toHaveCount(1);
    const firstState = await firstCard.evaluate((el) => ({
      pending: el.getAttribute('data-bp-motion-state'),
      inView: (() => {
        const r = el.getBoundingClientRect();
        return r.bottom > 0 && r.top < window.innerHeight && r.right > 0 && r.left < window.innerWidth;
      })(),
    }));
    if (firstState.inView) {
      expect(firstState.pending).not.toBe('pending');
    }

    const overflow = await page.evaluate(
      () => document.documentElement.scrollWidth > window.innerWidth + 1
    );
    expect(overflow).toBe(false);

    const lcpEager = await page.locator(
      '.biopentra-loop-card-root img[fetchpriority="high"], .biopentra-loop-card-root img[loading="eager"]'
    ).count();
    expect(lcpEager).toBeGreaterThan(0);

    await page.screenshot({
      path: `artifacts/motion-1-${testInfo.project.name}.png`,
      fullPage: false,
    });
  });

  test('homepage: below-fold still pending after 2.5s at hero, then reveals once', async ({
    page,
    context,
  }, testInfo) => {
    test.skip(!VIEWPORTS.has(testInfo.project.name), 'MOTION-1 viewport set');

    await gotoWithComingSoonBypass(page, context, '/');
    await dismissOverlays(page);
    await applyMotion(page);
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.waitForTimeout(2500);

    const htmlClass = await page.evaluate(() => document.documentElement.className);
    expect(htmlClass).toContain('bp-motion');
    expect(htmlClass).not.toContain('bp-motion-failsafe');

    const popular = page.locator('.elementor-element-17b27f7');
    await expect(popular).toHaveCount(1);

    const before = await popular.evaluate((el) => {
      const r = el.getBoundingClientRect();
      const inView = r.bottom > 0 && r.top < window.innerHeight && r.right > 0 && r.left < window.innerWidth;
      const cards = el.querySelectorAll('.e-loop-item');
      let pending = 0;
      let inn = 0;
      cards.forEach((c) => {
        const s = c.getAttribute('data-bp-motion-state');
        if (s === 'pending') pending += 1;
        if (s === 'in') inn += 1;
      });
      return { inView, pending, inn, cardCount: cards.length };
    });

    if (!before.inView) {
      expect(before.pending).toBeGreaterThan(0);
    }

    await popular.scrollIntoViewIfNeeded();
    await page.waitForTimeout(500);

    const pendingAfter = await popular.locator('.e-loop-item[data-bp-motion-state="pending"]').count();
    const inAfter = await popular.locator('.e-loop-item[data-bp-motion-state="in"]').count();
    expect(inAfter).toBeGreaterThan(0);
    expect(pendingAfter).toBe(0);

    const y = await page.evaluate(() => window.scrollY);
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.waitForTimeout(200);
    await page.evaluate((top) => window.scrollTo(0, top), y);
    await page.waitForTimeout(200);
    expect(await popular.locator('.e-loop-item[data-bp-motion-state="in"]').count()).toBe(inAfter);
  });

  test('shop: first-viewport cards never pending; below-fold reveals once', async ({ page, context }, testInfo) => {
    test.skip(!VIEWPORTS.has(testInfo.project.name), 'MOTION-1 viewport set');

    await gotoWithComingSoonBypass(page, context, '/shop/');
    await dismissOverlays(page);
    await applyMotion(page);

    const htmlClass = await page.evaluate(() => document.documentElement.className);
    expect(htmlClass).toContain('bp-motion');
    expect(htmlClass).not.toContain('bp-motion-failsafe');

    const grid = page.locator('.elementor-element-ed52b7f .elementor-loop-container > .e-loop-item');
    await expect(grid.first()).toBeVisible({ timeout: 15000 });
    await page.waitForTimeout(150);

    const snapshot = await page.evaluate(() => {
      const cards = Array.from(
        document.querySelectorAll(
          '.elementor-element-ed52b7f .elementor-loop-container > .e-loop-item'
        )
      ) as HTMLElement[];
      const vh = window.innerHeight;
      const vw = window.innerWidth;
      return cards.map((el) => {
        const r = el.getBoundingClientRect();
        const inView = r.width > 0 && r.height > 0 && r.bottom > 0 && r.top < vh && r.right > 0 && r.left < vw;
        return {
          inView,
          state: el.getAttribute('data-bp-motion-state'),
          kind: el.getAttribute('data-bp-motion'),
        };
      });
    });

    expect(snapshot.length).toBeGreaterThan(0);
    for (const card of snapshot) {
      expect(card.kind).toBe('shop-card');
      if (card.inView) {
        expect(card.state).not.toBe('pending');
      }
    }

    const overflow = await page.evaluate(
      () => document.documentElement.scrollWidth > window.innerWidth + 1
    );
    expect(overflow).toBe(false);

    const targetIndex = await page.evaluate(() => {
      const cards = Array.from(
        document.querySelectorAll(
          '.elementor-element-ed52b7f .elementor-loop-container > .e-loop-item'
        )
      ) as HTMLElement[];
      return cards.findIndex((el) => el.getAttribute('data-bp-motion-state') === 'pending');
    });
    if (targetIndex < 0) {
      test.info().annotations.push({ type: 'note', description: 'all shop cards in-view at init' });
    } else {
      const target = grid.nth(targetIndex);
      await target.evaluate((el) => el.scrollIntoView({ block: 'center', inline: 'nearest' }));
      await expect.poll(async () => target.getAttribute('data-bp-motion-state'), { timeout: 4000 }).toBe(
        'in'
      );
      await page.evaluate(() => window.scrollTo(0, 0));
      await page.waitForTimeout(200);
      await target.evaluate((el) => el.scrollIntoView({ block: 'center', inline: 'nearest' }));
      await page.waitForTimeout(200);
      expect(await target.getAttribute('data-bp-motion-state')).toBe('in');
    }

    await page.screenshot({
      path: `artifacts/motion-1-shop-${testInfo.project.name}.png`,
      fullPage: false,
    });
  });

  test('shop: filter and search replacement initialise new cards without hiding visible ones', async ({
    page,
    context,
  }, testInfo) => {
    test.skip(!VIEWPORTS.has(testInfo.project.name), 'MOTION-1 viewport set');

    await gotoWithComingSoonBypass(page, context, '/shop/');
    await dismissOverlays(page);
    await applyMotion(page);

    const wellness = page.locator('.elementor-element-b3a2918 .e-filter-item[data-filter="wellness"]');
    await expect(wellness).toBeVisible();
    await wellness.click();
    await expect(wellness).toHaveAttribute('aria-pressed', 'true', { timeout: 15000 });
    await expect.poll(() => page.url()).toContain('e-filter-ed52b7f-product_cat');
    await expect(page.locator('.elementor-element-ed52b7f .e-loop-item').first()).toBeVisible({
      timeout: 15000,
    });
    await page.waitForTimeout(400);

    const afterFilter = await page.evaluate(() => {
      const cards = Array.from(
        document.querySelectorAll('.elementor-element-ed52b7f .elementor-loop-container > .e-loop-item')
      ) as HTMLElement[];
      const vh = window.innerHeight;
      return cards.map((el) => {
        const r = el.getBoundingClientRect();
        const inView = r.bottom > 0 && r.top < vh && r.right > 0 && r.left < window.innerWidth;
        return { inView, state: el.getAttribute('data-bp-motion-state') };
      });
    });
    expect(afterFilter.length).toBeGreaterThan(0);
    for (const card of afterFilter) {
      if (card.inView) {
        expect(card.state).not.toBe('pending');
      }
    }

    const search = page.locator('#biopentra-shop-s');
    await expect(search).toBeVisible();
    await search.fill('peptide');
    await Promise.all([
      page.waitForURL(/\/shop\/\?.*\bs=/, { timeout: 20000 }),
      page.locator('form.biopentra-shop-search').evaluate((form: HTMLFormElement) => form.submit()),
    ]);
    await dismissOverlays(page);
    await applyMotion(page);
    await expect(page.locator('.elementor-element-ed52b7f .e-loop-item').first()).toBeVisible({
      timeout: 15000,
    });
    const afterSearch = await page.evaluate(() => {
      const first = document.querySelector(
        '.elementor-element-ed52b7f .elementor-loop-container > .e-loop-item'
      ) as HTMLElement | null;
      if (!first) {
        return { state: null, inView: false };
      }
      const r = first.getBoundingClientRect();
      return {
        state: first.getAttribute('data-bp-motion-state'),
        inView: r.bottom > 0 && r.top < window.innerHeight,
      };
    });
    if (afterSearch.inView) {
      expect(afterSearch.state).not.toBe('pending');
    }
  });

  test('shop: load-more appended cards initialise once when the control is present', async ({
    page,
    context,
  }, testInfo) => {
    test.skip(testInfo.project.name !== 'desktop-1440', 'single viewport for load-more');

    await gotoWithComingSoonBypass(page, context, '/shop/');
    await dismissOverlays(page);
    await applyMotion(page);

    const loadMore = page.locator('.elementor-element-ed52b7f .e-loop__load-more .elementor-button');
    if ((await loadMore.count()) === 0 || !(await loadMore.isVisible())) {
      test.skip(true, 'shop load-more control not active');
      return;
    }

    const before = await page.locator(
      '.elementor-element-ed52b7f .elementor-loop-container > .e-loop-item'
    ).count();
    await loadMore.evaluate((el: HTMLElement) => el.click());
    await expect
      .poll(
        async () =>
          page.locator('.elementor-element-ed52b7f .elementor-loop-container > .e-loop-item').count(),
        { timeout: 20000 }
      )
      .toBeGreaterThan(before);
    await page.waitForTimeout(400);

    const states = await page.evaluate(() => {
      const cards = Array.from(
        document.querySelectorAll('.elementor-element-ed52b7f .elementor-loop-container > .e-loop-item')
      ) as HTMLElement[];
      return cards.map((el) => el.getAttribute('data-bp-motion-state'));
    });
    expect(states.every((s) => s === 'in' || s === 'pending')).toBeTruthy();
    expect(states.filter((s) => s === 'in').length).toBeGreaterThan(0);
  });

  test('JS: gate without controller failsafe reveals; reduced-motion never pending-hides', async ({
    page,
    context,
    browser,
  }, testInfo) => {
    test.skip(testInfo.project.name !== 'desktop-1440', 'single viewport for failure paths');

    await gotoWithComingSoonBypass(page, context, '/');
    await dismissOverlays(page);
    await applyMotion(page, { controller: false });
    await page.waitForTimeout(2100);

    const afterGateOnly = await page.evaluate(() => ({
      failsafe: document.documentElement.classList.contains('bp-motion-failsafe'),
      motion: document.documentElement.classList.contains('bp-motion'),
      pending: document.querySelectorAll('[data-bp-motion-state="pending"]').length,
    }));
    expect(afterGateOnly.failsafe).toBe(true);
    expect(afterGateOnly.motion).toBe(false);
    expect(afterGateOnly.pending).toBe(0);

    const reduce = await context.newPage();
    await reduce.emulateMedia({ reducedMotion: 'reduce' });
    await gotoWithComingSoonBypass(reduce, context, '/');
    await dismissOverlays(reduce);
    await applyMotion(reduce);
    const reduceState = await reduce.evaluate(() => ({
      allowed: (window as unknown as { bpMotion?: { allowed?: boolean } }).bpMotion?.allowed,
      pending: document.querySelectorAll('[data-bp-motion-state="pending"]').length,
      motionClass: document.documentElement.classList.contains('bp-motion'),
    }));
    expect(reduceState.allowed).toBe(false);
    expect(reduceState.motionClass).toBe(false);
    expect(reduceState.pending).toBe(0);
    await reduce.close();

    const noJs = await browser.newContext({
      javaScriptEnabled: false,
      viewport: { width: 1440, height: 1000 },
    });
    const noJsPage = await noJs.newPage();
    await noJsPage.goto('https://dev.biopentra.eu/', { waitUntil: 'domcontentloaded', timeout: 45000 });
    const noJsVisible = await noJsPage.locator('.bp-home-hero').isVisible();
    const noJsPending = await noJsPage.locator('[data-bp-motion-state="pending"]').count();
    expect(noJsVisible).toBe(true);
    expect(noJsPending).toBe(0);
    await noJs.close();
  });

  test('keyboard focus does not translate chips', async ({ page, context }, testInfo) => {
    test.skip(testInfo.project.name !== 'desktop-1440', 'desktop keyboard sample');

    await gotoWithComingSoonBypass(page, context, '/');
    await dismissOverlays(page);
    await applyMotion(page);

    const chip = page.locator('.bp-home-cat-chip').first();
    await chip.focus();
    const transform = await chip.evaluate((el) => getComputedStyle(el).transform);
    expect(transform === 'none' || transform === 'matrix(1, 0, 0, 1, 0, 0)').toBeTruthy();
  });
});
