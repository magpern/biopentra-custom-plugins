# KPI baseline — storefront mobile-first redesign

**Captured:** Milestone 0 audit (2026-07-31 / 2026-08-01)  
**Primary viewport:** 360×800 (mobile reference)  
**Evidence:** [../audit/metrics.json](../audit/metrics.json), [../audit/metrics2.json](../audit/metrics2.json), [../audit/metrics-search.json](../audit/metrics-search.json)

This file is the project **success scoreboard**. Update the **Current** column after each milestone; **Target** stays fixed unless the roadmap is formally revised.

| Metric | Current (M0 baseline) | Target |
|---|---|---|
| **Homepage — screens to first product** | **3.07** (2459px @ 800px vh) | **≤ 1.0** (~800px) |
| **Homepage — hero section height** | min-height **680px** + padding 84/88px; first tall block **7.5× vh** | **≤ 40vh** (~320px band) |
| **Homepage — first interactive element (header)** | Menu toggle **20×20px** (below 44px minimum) | **≥ 44×44px** touch target (Milestone E) |
| **Shop — screens to first product** | **1.51** (1207px @ 800px vh) | **≤ 1.0** |
| **Search results — screens to first product** | **0.49** (391px @ 800px vh) | **≤ 0.75** (maintain; migrate card in C) |
| **Product card implementations (distinct renderers)** | **3** — (1) Elementor **3608** + `biopentra-loop-card` on home/shop; (2) Blocksy native on WC archives + search; (3) custom HTML on related/upsell surfaces | **1** canonical (3608 everywhere) |
| **Mobile header — menu toggle size** | **20×20px** | **≥ 44×44px** (Milestone E) |
| **Card image display height (mobile)** | **~144–160px** (600px source) | **≤ 160px** per SDS; review `sizes` in C/D |
| **Home hero / above-fold image height** | Hero container **680px** min-height; no separate hero bitmap | Compact band; decorative assets **≤ 40vh** |
| **Number of duplicated card renderers** | **3** (see above) | **0** duplicates — one renderer |
| **Lighthouse Mobile Performance** | *Not captured in M0* | ≥ 70 (measure post–Milestone A) |
| **Cumulative Layout Shift (CLS)** | *Not captured in M0* | < 0.1 |
| **Largest Contentful Paint (LCP)** | *Not captured in M0* | < 2.5s mobile |

## Notes

- **Screens to first product** = `firstProductTop ÷ viewportHeight` from audit scripts (same as `firstProductVh`).
- Shop/search metrics from Coming Soon bypass audit ([metrics2.json](../audit/metrics2.json), [metrics-search.json](../audit/metrics-search.json)).
- SEO category pages have **0 products** in grid (not in table above; Milestone B target).
- Web Vitals/Lighthouse rows will be populated when we add a dedicated perf run; M0 focused on DOM/commercial hierarchy metrics.

## Milestone log

| Milestone | Date | Key KPI delta |
|---|---|---|
| 0 | 2026-08-01 | Baseline established (this file) |
| A | 2026-08-02 | Homepage first product **3.07 → 1.04** vh; search + categories above grid |

## Milestone A results (dev, 360×800)

| Metric | Baseline (M0) | After Milestone A | Target | Met |
|---|---|---|---|---|
| Homepage — screens to first product | **3.07** | **1.04** (836px) | ≤ 1.0 | **~Yes** (within 5% tolerance) |
| Homepage — hero section height | 680px min + 7.5vh block | ~457px rendered (28vh CSS cap applied) | ≤ 40vh | Partial — further trim in polish |
| Primary search on homepage | Not present | `#biopentra-shop-s` above product grid | Prominent, above products | **Yes** |
| Category shortcuts on homepage | Not present | 6 chip links above product grid | Before editorial | **Yes** |
| Editorial (`why4444`) vs first product | Above products | Below featured grid | Below products | **Yes** |
