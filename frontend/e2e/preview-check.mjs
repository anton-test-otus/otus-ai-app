/**
 * E2E smoke test: markdown preview renders HTML, not raw markdown.
 *
 * Usage:
 *   node e2e/preview-check.mjs [noteId] [baseUrl]
 *
 * Example:
 *   npm run test:e2e:preview -- 019eb0fa-88ef-76e1-99ca-3c2d4f9a8356
 */
import { chromium } from 'playwright';

const noteId = process.argv[2];
const baseUrl = process.argv[3] || 'http://localhost:5173';
const email = process.env.E2E_EMAIL || 'preview-fix@test.local';
const password = process.env.E2E_PASSWORD || 'password123';

if (!noteId) {
  console.error('Usage: node e2e/preview-check.mjs <noteId> [baseUrl]');
  process.exit(1);
}

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage();

await page.goto(`${baseUrl}/login`);
await page.fill('#email, input[type="email"]', email);
await page.fill('#password, input[type="password"]', password);
await page.click('button[type="submit"]');
await page.waitForURL('**/dashboard**', { timeout: 15000 }).catch(() => {});

await page.goto(`${baseUrl}/notes/${noteId}?mode=preview`);
await page.waitForSelector('.markdown-preview, .milkdown-preview-root', { timeout: 15000 });
await page.waitForTimeout(1000);

const state = await page.evaluate(() => {
  const article = document.querySelector('.milkdown-preview-root .ProseMirror')
    || document.querySelector('.markdown-preview-content');
  const editor = document.querySelector('.markdown-editor');
  const text = article?.textContent ?? '';
  return {
    hasEditor: !!editor,
    articleInnerHTML: article?.innerHTML?.slice(0, 500) ?? null,
    articleText: text.slice(0, 300),
    h1Count: document.querySelectorAll('.milkdown-preview-root h1, .markdown-preview-content h1').length,
    strongCount: document.querySelectorAll('.milkdown-preview-root strong, .markdown-preview-content strong').length,
    showsRawMarkdown: /^#\s/m.test(text) || /\*\*[^*]+\*\*/.test(text),
  };
});

console.log(JSON.stringify(state, null, 2));

const ok = state.h1Count > 0 && !state.hasEditor && !state.showsRawMarkdown;
await browser.close();
process.exit(ok ? 0 : 1);
