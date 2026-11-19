"""WCAG 2.2 AA spot checks with Playwright (axe-core could not be downloaded here).

Checks per page: lang, title, one h1 and no skipped heading levels, image alt,
accessible names for links/buttons/inputs, duplicate ids, a <main> landmark,
target size (2.5.8, 24x24 px) and text contrast (1.4.3, 4.5:1 / 3:1 for large text).
Contrast is measured against the nearest solid background; text over photos or
gradients is listed as "manual" instead of being guessed.

Usage: python3 wcag_check.py http://127.0.0.1:8124 /path1 /path2 ...
"""
import asyncio
import json
import sys

from playwright.async_api import async_playwright

JS = r"""
() => {
  const issues = [];
  const add = (rule, el, detail) => issues.push({rule, detail,
      where: (el && (el.outerHTML || '').replace(/\s+/g, ' ').slice(0, 140)) || ''});
  const visible = (el) => {
    const r = el.getBoundingClientRect(), s = getComputedStyle(el);
    return r.width > 0 && r.height > 0 && s.visibility !== 'hidden' && s.display !== 'none' && +s.opacity !== 0;
  };
  const name = (el) => (el.getAttribute('aria-label') || el.getAttribute('title') ||
      (el.getAttribute('aria-labelledby') && document.getElementById(el.getAttribute('aria-labelledby'))?.textContent) ||
      el.textContent || [...el.querySelectorAll('img[alt]')].map(i => i.alt).join(' ') || '').trim();

  if (!document.documentElement.lang) add('3.1.1 page language', null, 'html has no lang');
  if (!document.title.trim()) add('2.4.2 page title', null, 'empty <title>');
  if (!document.querySelector('main')) add('1.3.1 landmarks', null, 'no <main>');

  const h1s = document.querySelectorAll('h1');
  if (h1s.length !== 1) add('1.3.1 headings', null, `${h1s.length} h1 elements`);
  let last = 0;
  document.querySelectorAll('h1,h2,h3,h4,h5,h6').forEach((h) => {
    if (!visible(h)) return;
    const lvl = +h.tagName[1];
    if (last && lvl > last + 1) add('1.3.1 heading order', h, `h${last} -> h${lvl}`);
    last = lvl;
  });

  document.querySelectorAll('img').forEach((img) => { if (!img.hasAttribute('alt')) add('1.1.1 image alt', img, 'missing alt'); });
  document.querySelectorAll('svg[role=img]').forEach((svg) => { if (!svg.getAttribute('aria-label')) add('1.1.1 svg name', svg, ''); });

  document.querySelectorAll('a[href], button, summary').forEach((el) => {
    if (!visible(el) || el.closest('[aria-hidden=true]')) return;
    if (!name(el)) add('4.1.2 / 2.4.4 accessible name', el, 'no text or label');
    const r = el.getBoundingClientRect();
    const inline = el.tagName === 'A' && getComputedStyle(el).display === 'inline' && el.closest('p, li, figcaption, span, small, dd, h1, h2, h3, h4, h5, h6');
    if (!inline && (r.width < 24 || r.height < 24)) add('2.5.8 target size', el, `${Math.round(r.width)}x${Math.round(r.height)}`);
  });

  document.querySelectorAll('input:not([type=hidden]), select, textarea').forEach((el) => {
    const labelled = el.labels?.length || el.getAttribute('aria-label') || el.getAttribute('aria-labelledby');
    if (!labelled) add('1.3.1 / 4.1.2 form label', el, 'no label');
  });

  const ids = {};
  document.querySelectorAll('[id]').forEach((el) => { ids[el.id] = (ids[el.id] || 0) + 1; });
  Object.entries(ids).filter(([, n]) => n > 1).forEach(([id]) => add('4.1.1 duplicate id', null, id));

  // ---- contrast ----
  const parse = (c) => { const m = c.match(/rgba?\(([^)]+)\)/); if (!m) return null;
    const p = m[1].split(/[ ,/]+/).filter(Boolean).map(Number); return {r: p[0], g: p[1], b: p[2], a: p.length > 3 ? p[3] : 1}; };
  const lum = ({r, g, b}) => { const f = (v) => { v /= 255; return v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4; };
    return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b); };
  const blend = (fg, bg) => ({r: fg.r * fg.a + bg.r * (1 - fg.a), g: fg.g * fg.a + bg.g * (1 - fg.a), b: fg.b * fg.a + bg.b * (1 - fg.a), a: 1});
  const background = (el) => {
    let layers = [];
    for (let n = el; n; n = n.parentElement) {
      const s = getComputedStyle(n);
      if (s.backgroundImage !== 'none' && !n.matches('body,html')) return {manual: true};
      const c = parse(s.backgroundColor);
      if (c && c.a > 0) { layers.push(c); if (c.a >= 1) break; }
    }
    let bg = {r: 255, g: 255, b: 255, a: 1};
    for (const l of layers.reverse()) bg = blend(l, bg);
    return bg;
  };
  const seen = new Set();
  const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
  while (walker.nextNode()) {
    const t = walker.currentNode; if (!t.textContent.trim()) continue;
    const el = t.parentElement; if (!el || seen.has(el) || !visible(el) || el.closest('.sr-only, [aria-hidden=true], script, style, noscript')) continue;
    seen.add(el);
    const s = getComputedStyle(el);
    let fg = parse(s.color); const bg = background(el);
    if (bg.manual) { issues.push({rule: 'manual contrast (text on image/gradient)', detail: s.color, where: el.textContent.trim().slice(0, 60)}); continue; }
    if (!fg) { issues.push({rule: 'manual contrast (unparsed color)', detail: s.color, where: el.textContent.trim().slice(0, 60)}); continue; }
    fg = blend(fg, bg);
    const op = [...(function* () { for (let n = el; n; n = n.parentElement) yield +getComputedStyle(n).opacity; })()].reduce((a, b) => a * b, 1);
    if (op < 1) fg = blend({...fg, a: op}, bg);
    const L1 = lum(fg), L2 = lum(bg), ratio = (Math.max(L1, L2) + 0.05) / (Math.min(L1, L2) + 0.05);
    const size = parseFloat(s.fontSize), bold = +s.fontWeight >= 700;
    const need = (size >= 24 || (bold && size >= 18.66)) ? 3 : 4.5;
    if (ratio < need) add('1.4.3 contrast', el, `${ratio.toFixed(2)}:1 (needs ${need}:1) "${el.textContent.trim().slice(0, 40)}"`);
  }
  return issues;
}
"""


async def main(base, paths):
    async with async_playwright() as p:
        browser = await p.chromium.launch(args=['--no-sandbox'])
        report = {}
        for width in (1440, 390):
            page = await browser.new_page(viewport={'width': width, 'height': 900})
            for path in paths:
                await page.goto(base + path, wait_until='networkidle')
                issues = await page.evaluate(JS)
                report[f'{width}px {path}'] = issues
            await page.close()
        await browser.close()
    hard = 0
    for key, issues in report.items():
        real = [i for i in issues if not i['rule'].startswith('manual')]
        manual = [i for i in issues if i['rule'].startswith('manual')]
        hard += len(real)
        print(f'\n== {key}: {len(real)} issues, {len(manual)} to check by eye')
        for i in real:
            print(f"  [{i['rule']}] {i['detail']}  <- {i['where']}")
        for i in manual[:4]:
            print(f"  (manual) {i['detail']} \"{i['where']}\"")
    print(f'\nTOTAL issues: {hard}')


asyncio.run(main(sys.argv[1], sys.argv[2:]))
