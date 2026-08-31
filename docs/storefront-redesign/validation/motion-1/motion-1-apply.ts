/**
 * Delayed-JS injection of MOTION-1 assets for Playwright against live DEV.
 * Live bind-mount serves main; this is not a substitute for the WP-CLI enqueue proof.
 */
import fs from 'fs';
import path from 'path';
import type { Page } from '@playwright/test';

export const GATE_JS = `(function(){var m=window.matchMedia&&window.matchMedia("(prefers-reduced-motion: reduce)").matches;var io="IntersectionObserver"in window;window.bpMotion=window.bpMotion||{};if(m||!io){window.bpMotion.allowed=false;return;}document.documentElement.classList.add("bp-motion");window.bpMotion.allowed=true;window.bpMotion.failsafeId=window.setTimeout(function(){var h=document.documentElement;h.classList.remove("bp-motion");h.classList.add("bp-motion-failsafe");if(typeof window.bpMotionCleanup==="function"){window.bpMotionCleanup("failsafe");}},2000);})();`;

const ASSET_DIR = process.env.MOTION_1_ASSET_DIR || '/work/tmp-motion';

function asset(name: string) {
  return path.join(ASSET_DIR, name);
}

function motionCss() {
  return fs.readFileSync(asset('bp-motion.css'), 'utf8');
}

function motionJs() {
  return fs.readFileSync(asset('bp-motion.js'), 'utf8');
}

export async function applyMotion(page: Page, opts: { controller?: boolean } = {}) {
  const withController = opts.controller !== false;
  await page.evaluate(GATE_JS);
  await page.addStyleTag({ content: motionCss() });
  if (withController) {
    await page.evaluate(motionJs());
  }
}
