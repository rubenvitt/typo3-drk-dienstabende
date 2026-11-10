// Screenshots of the local TYPO3 test server: backend setup and the element inside the live DRK page.
// Usage: cd docs; npm install; node shoot.js   (test server must be running, see README.md)
import { mkdir } from 'node:fs/promises';
import puppeteer from 'puppeteer-core';

const TYPO3 = 'http://localhost:8080';
const LIVE = 'https://www.drk-uelzen.de/angebote/bereitschaft/bereitschaft-kreis-uelzen.html';
const OUT = new URL('./screenshots/', import.meta.url).pathname;
const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const wait = (ms) => new Promise((r) => setTimeout(r, ms));

await mkdir(OUT, { recursive: true });
const browser = await puppeteer.launch({ executablePath: CHROME, headless: true });
const page = await browser.newPage();
await page.setViewport({ width: 1440, height: 1100 });
const shot = async (name, target = page, opts = {}) => {
  await target.screenshot({ path: `${OUT}${name}.png`, ...opts });
  console.log(`${OUT}${name}.png`);
};

// Backend login
await page.goto(`${TYPO3}/typo3/`, { waitUntil: 'networkidle0' });
await page.type('#t3-username', 'admin');
await page.type('#t3-password', 'Dienstabend#2026');
await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('#t3-login-submit')]);

// Page module with the three elements
await page.goto(`${TYPO3}/typo3/module/web/layout?id=1`, { waitUntil: 'networkidle0' });
await wait(1500);
await shot('1-seitenmodul');

// Content element wizard, category "Plugins"
// The top URL mirrors the module URL, so skip the main frame
const moduleFrame = (part) => page.frames().find((f) => f !== page.mainFrame() && f.url().includes(part));
await moduleFrame('layout?token').click('typo3-backend-new-content-element-wizard-button');
await wait(2500);
await page.evaluate(() => {
  // The wizard lives in nested shadow roots: search them all for the category button
  const buttons = (root) => [...root.querySelectorAll('*')].flatMap((e) => [
    ...(e.tagName === 'BUTTON' ? [e] : []), ...(e.shadowRoot ? buttons(e.shadowRoot) : [])]);
  buttons(document).find((b) => /^\s*Plugins/.test(b.textContent))?.click();
});
await wait(1200);
await shot('2-neues-element');
await page.keyboard.press('Escape');
await wait(800);

// Edit form of the list element (uid 2), scrolled to the "Termine" fields
await moduleFrame('layout?token').click('a[href*="tt_content%5D%5B2%5D=edit"]');
await wait(3500);
await moduleFrame('record/edit').evaluate(() => {
  const field = [...document.querySelectorAll('label')].find((l) => /Adresse der Termindaten/.test(l.textContent));
  field.scrollIntoView({ block: 'start' });
  field.closest('.module-body, .module')?.scrollBy(0, -90);
});
await wait(800);
await shot('3-element-bearbeiten');

// Plain frontend of the test server (no DRK theme)
await page.goto(`${TYPO3}/`, { waitUntil: 'networkidle0' });
await shot('4-frontend-ohne-drk-design', page, { fullPage: true });
const fragment = await page.evaluate(() => ['#c1', '#c2'].map((s) => document.querySelector(s).outerHTML).join(''));

// Same HTML injected into the live page where variant A puts the calendar
await page.setViewport({ width: 1440, height: 2400 });
await page.goto(LIVE, { waitUntil: 'networkidle2' });
await page.evaluate(() => [...document.querySelectorAll('button')].find((b) => /Alles klar/.test(b.textContent))?.click());
const box = await page.evaluate((html) => {
  const t = document.createElement('template');
  t.innerHTML = `<div id="dienstabende-preview">${html}</div>`;
  document.querySelector('#c3473').after(t.content);
  const el = document.querySelector('#dienstabende-preview');
  // Below the sticky site header, which would otherwise cover the top of the clip
  scrollTo(0, el.getBoundingClientRect().top + scrollY - 200);
  const r = el.getBoundingClientRect();
  return { x: 0, y: r.top + scrollY - 40, width: innerWidth, height: r.height + 80 };
}, fragment);
await wait(1500);
await shot('5-im-drk-design', page, { clip: box, captureBeyondViewport: false });

await browser.close();
