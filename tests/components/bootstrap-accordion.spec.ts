import { readFileSync } from 'node:fs';
import path from 'node:path';
import { test as base, expect, root } from './fixtures';

const modules = path.join(root, 'composer-projects/conjin/src/modules-shared');
const targetId = 'details:ä[1]';
const targetHash = `#${encodeURIComponent(targetId)}`;
const panel = (id, parent, body, open = false) => `
    <div class="accordion-item">
        <div class="accordion-header">
            <button class="accordion-button ${open ? '' : 'collapsed'}" data-bs-toggle="collapse"
                data-bs-target="#${id}" aria-expanded="${open}">${id}</button>
        </div>
        <div id="${id}" class="accordion-collapse collapse ${open ? 'show' : ''}" data-bs-parent="#${parent}">
            <div class="accordion-body">${body}</div>
        </div>
    </div>`;
const body = `
    <a href="${targetHash}">Nested target</a>
    <p id="ordinary">Ordinary anchor</p>
    <div style="height: 1000px"></div>
    <div class="accordion" id="outer-group">
        ${panel('other', 'outer-group', '<p id="other-target">Other target</p>', true)}
        ${panel('outer', 'outer-group', `
            <div class="accordion" id="middle-group">
                ${panel('middle-other', 'middle-group', 'Other nested item', true)}
                ${panel('middle', 'middle-group', `
                    <div class="accordion" id="inner-group">
                        ${panel('inner', 'inner-group', `<p id="${targetId}">Deep target</p>`)}
                    </div>`)}
            </div>`)}
    </div>
    <div style="height: 1000px"></div>`;

for (const module of ['bootstrap-extended', 'bootstrap-alternative']) {
    const test = base.extend<{ accordion: {
        go: (hash: string) => Promise<unknown>;
        setHash: (hash: string) => Promise<void>;
        expanded: (id: string, value: boolean) => Promise<void>;
        revealed: (id: string) => Promise<void>;
        macros: () => void;
    } }>({
        accordion: async ({ page, componentHtml }, use) => {
            const bootstrap = module === 'bootstrap-extended';
            let currentBody = body;
            const assets = {
                '/acc.js': readFileSync(path.join(modules, module, 'res/acc.js'), 'utf8'),
                '/style.css': bootstrap
                    ? readFileSync(process.env.BOOTSTRAP_EXTENDED_CSS!, 'utf8')
                    : ['core.css', 'acc.css', 'collapse.css'].map(file =>
                        readFileSync(path.join(modules, module, 'res', file), 'utf8')).join('\n'),
            };
            if (bootstrap) {
                assets['/bootstrap.js'] = readFileSync(path.join(root, 'node_modules/bootstrap/dist/js/bootstrap.bundle.js'), 'utf8');
            }
            await page.route('http://accordion.test/**', route => {
                const pathname = new URL(route.request().url()).pathname;
                return route.fulfill({
                    contentType: pathname.endsWith('.js') ? 'text/javascript' : pathname.endsWith('.css') ? 'text/css' : 'text/html',
                    body: assets[pathname] ?? `<!doctype html><html><head><meta charset="utf-8">
                        <link rel="stylesheet" href="/style.css">
                        <!-- Keep collapse transitions, but make viewport assertions independent of native smooth-scroll timing. -->
                        <style>html { scroll-behavior: auto !important; }</style>
                        ${bootstrap ? '<script src="/bootstrap.js"></script>' : ''}
                        <script src="/acc.js" defer></script></head><body>${currentBody}</body></html>`,
                });
            });
            await page.addInitScript(() => {
                window.scrolls = [];
                const scroll = Element.prototype.scrollIntoView;
                Element.prototype.scrollIntoView = function (...args) {
                    window.scrolls.push({ id: this.id, visible: this.getClientRects().length > 0,
                        transitioning: document.querySelectorAll('.collapsing').length });
                    return scroll.apply(this, args);
                };
            });
            let navigation = 0;
            const go = hash => page.goto(`http://accordion.test/?case=${++navigation}${hash}`);
            const setHash = hash => page.evaluate(value => { location.hash = value; }, hash);
            const expanded = async (id, value) => {
                if (value) await expect(page.locator(`#${id}`)).toHaveClass(/\bshow\b/);
                else await expect(page.locator(`#${id}`)).not.toHaveClass(/\bshow\b/);
                await expect(page.locator(`[data-bs-target="#${id}"]`)).toHaveAttribute('aria-expanded', String(value));
            };
            const revealed = async id => {
                await expect.poll(() => page.evaluate(() => window.scrolls.at(-1))).toEqual({
                    id, visible: true, transitioning: 0,
                });
                await expect(page.locator(`[id="${id}"]`)).toBeInViewport();
            };

            await use({ go, setHash, expanded, revealed, macros: () => { currentBody = componentHtml[module]; } });
        },
    });
    test.describe(module, () => {
        test('opens nested initial fragments and closes exclusive peers', async ({ page, accordion }) => {
            const { go, setHash, expanded, revealed } = accordion;
            // Initial navigation opens three levels and closes exclusive peers.
            await go(targetHash);
            await revealed(targetId);
            for (const id of ['outer', 'middle', 'inner']) await expanded(id, true);
            for (const id of ['other', 'middle-other']) await expanded(id, false);
        });

        test('reveals changed hashes and already visible targets', async ({ page, accordion }) => {
            const { go, setHash, expanded, revealed } = accordion;
            await go('');
                    // Hash changes work for panel IDs and for targets already visible.
                    await setHash('#inner');
                    await revealed('inner');
                    await setHash(targetHash);
                    await revealed(targetId);
                    await setHash('#other-target');
                    await revealed('other-target');
                    await expanded('other', true);
                    await expanded('outer', false);
        });

        test('anchor clicks reveal hidden hierarchies', async ({ page, accordion }) => {
            const { go, setHash, expanded, revealed } = accordion;
            await go('');
                    // A normal anchor click also reveals a hidden hierarchy.
                    await page.getByRole('link', { name: 'Nested target' }).click();
                    await revealed(targetId);
                    await expanded('outer', true);
        });

        test('ignores empty, missing, malformed and unrelated fragments', async ({ page, accordion }) => {
            const { go, setHash, expanded, revealed } = accordion;
            // Empty, missing, malformed and unrelated fragments do not change panels.
            for (const hash of ['', '#missing', '#%E0%A4%A', '#ordinary']) {
                await go(hash);
                await expanded('outer', false);
                expect(await page.evaluate(() => window.scrolls)).toEqual([]);
            }
        });

        test('direct panel fragments include the collapse itself', async ({ page, accordion }) => {
            const { go, setHash, expanded, revealed } = accordion;
            await go('#inner');
            await revealed('inner');
            for (const id of ['outer', 'middle', 'inner']) await expanded(id, true);
        });

        if (module === 'bootstrap-extended') {
            test('hash changes during transitions abandon the old scroll', async ({ page, accordion }) => {
                const { go, setHash, expanded, revealed } = accordion;
                // Changing the hash during animation must abandon the old scroll
                // and wait for the exclusive sibling to finish transitioning.
                await go('');
                await page.evaluate(hash => {
                    document.getElementById('outer').addEventListener('show.bs.collapse', () => {
                        location.hash = '#other-target';
                    }, { once: true });
                    location.hash = hash;
                }, targetHash);
                await revealed('other-target');
                expect(await page.evaluate(() => window.scrolls.map(entry => entry.id))).toEqual(['other-target']);
                await expanded('outer', false);
            });

            test('respects prevented opening events', async ({ page, accordion }) => {
                const { go, setHash, expanded, revealed } = accordion;
                // Application code can still prevent a panel from opening.
                await go('');
                await page.evaluate(hash => {
                    document.getElementById('outer').addEventListener('show.bs.collapse', event => {
                        event.preventDefault();
                        document.body.dataset.prevented = 'true';
                    }, { once: true });
                    location.hash = hash;
                }, targetHash);
                await expect(page.locator('body')).toHaveAttribute('data-prevented', 'true');
                await expanded('outer', false);
                expect(await page.evaluate(() => window.scrolls)).toEqual([]);
            });

        }
        test('PHP macros render unique IDs and item permalinks', async ({ page, accordion }) => {
            const { go, setHash, expanded, revealed } = accordion;
            accordion.macros();
            await go('');
            await go('#detail-item');
            await revealed('detail-item');
            await expanded('detail-item-collapse', true);
            await go('');
            await expect(page.locator('#hidden-item')).toHaveCount(0);
            await expect(page.locator('#accordion-0-0 .accordion-anchor')).toHaveCount(0);
            await expect(page.locator('#fixture-set > .accordion-anchor')).toHaveCount(0);
            await expect(page.locator('#single-entry > .accordion-header > .accordion-anchor')).toHaveAttribute('href', '#single-entry');
            await expect(page.locator('#single-block > .block-header > .accordion-anchor')).toHaveAttribute('href', '#single-block');
            const ids = await page.locator('[id]').evaluateAll(elements => elements.map(element => element.id));
            expect(new Set(ids).size).toBe(ids.length);
        });

        test('permalinks preserve state and reopen manually closed items', async ({ page, accordion }) => {
            const { go, setHash, expanded, revealed } = accordion;
            accordion.macros();
            await go('');
            const itemLink = page.locator('#detail-item > .accordion-header > .accordion-anchor');
            const itemButton = page.locator('#detail-item > .accordion-header > .accordion-button');
            await expect(itemLink).toHaveAttribute('href', '#detail-item');
            await expect(itemButton.locator('a')).toHaveCount(0);
            await itemLink.hover();
            expect(await itemButton.evaluate(element => element.matches(':hover'))).toBe(false);
            await itemLink.click();
            await revealed('detail-item');
            await expanded('detail-item-collapse', true);
            await itemLink.click();
            await expanded('detail-item-collapse', true);
            await itemButton.locator('span').first().hover();
            expect(await itemLink.evaluate(element => element.matches(':hover'))).toBe(false);
            await itemLink.hover();
            expect(await itemButton.evaluate(element => element.matches(':hover'))).toBe(false);
            await itemButton.locator('span').first().click();
            await expanded('detail-item-collapse', false);
            await itemLink.hover();
            expect(await itemButton.evaluate(element => element.matches(':hover'))).toBe(false);
            // Reusing the current URL fragment must reopen a manually closed item.
            await itemLink.click();
            await expanded('detail-item-collapse', true);
        });

        test('nested item and summary links open the appropriate parents', async ({ page, accordion }) => {
            const { go, setHash, expanded, revealed } = accordion;
            accordion.macros();
            await go('');
            await go('#nested-item');
            await revealed('nested-item');
            await expanded('detail-item-collapse', true);
            await expanded('nested-item-collapse', true);
            await go('');
            await go('#nested-summary');
            await revealed('nested-summary');
            await expanded('detail-item-collapse', true);
            await expanded('nested-item-collapse', false);
        });

        test('header actions and custom block permalinks', async ({ page, accordion }) => {
            const { go, setHash, expanded, revealed } = accordion;
            accordion.macros();
            await go('');
            const action = page.locator('.accordion-header-action');
            await action.click();
            await expect(action).toHaveAttribute('data-clicked', 'true');
            await expect(action.locator('..').locator('.accordion-button')).toHaveAttribute('aria-expanded', 'false');
            const customLink = page.locator('a.accordion-anchor[href="#custom%3A%C3%A4%5B1%5D"]');
            await customLink.click();
            await revealed('custom:ä[1]');
            await expect(action.locator('..').locator('.accordion-button')).toHaveAttribute('aria-expanded', 'true');

            const blockLink = page.locator('#summary-block > .block-header > .accordion-anchor');
            await blockLink.click();
            await expect(page).toHaveURL(/#summary-block$/);
            await expect(page.locator('#summary-block > .accordion-body')).toBeVisible();
        });

        for (const width of [320, 900]) {
            test(`layout at ${width}px`, async ({ page, accordion }) => {
                const { go, setHash, expanded, revealed } = accordion;
                accordion.macros();
                await go('');
                await page.setViewportSize({ width, height: 700 });
                const links = [
                    page.locator('#detail-item > .accordion-header > .accordion-anchor'),
                    page.locator('a.accordion-anchor[href="#custom%3A%C3%A4%5B1%5D"]'),
                    page.locator('#summary-block > .block-header > .accordion-anchor'),
                ];
                for (const link of links) {
                    const geometry = await link.evaluate(element => {
                        const header = element.parentElement;
                        const title = header.querySelector('.accordion-button > span, .block-title > span');
                        const button = header.querySelector('.accordion-button');
                        const icon = button ? getComputedStyle(button, '::after') : null;
                        const iconWidth = icon ? parseFloat(icon.width) + (icon.boxSizing === 'border-box' ? 0 :
                            parseFloat(icon.borderLeftWidth) + parseFloat(icon.borderRightWidth)) : 0;
                        return {
                            rightGap: header.getBoundingClientRect().right - element.getBoundingClientRect().right,
                            iconLeft: button ? button.getBoundingClientRect().right - parseFloat(getComputedStyle(button).paddingRight) - iconWidth : null,
                            titleRight: title.getBoundingClientRect().right,
                            linkLeft: element.getBoundingClientRect().left,
                            linkRight: element.getBoundingClientRect().right,
                            color: getComputedStyle(element).color,
                        };
                    });
                    if (geometry.iconLeft !== null) {
                        expect(geometry.rightGap).toBeGreaterThan(0);
                        expect(Math.abs(geometry.linkRight - geometry.iconLeft)).toBeLessThan(1);
                    } else {
                        expect(Math.abs(geometry.rightGap)).toBeLessThan(1);
                    }
                    expect(geometry.titleRight).toBeLessThanOrEqual(geometry.linkLeft + 1);
                    expect(geometry.color).toBe('rgb(108, 117, 125)');
                    await link.hover();
                    expect(await link.evaluate(element => element.parentElement.querySelector('.accordion-button, .block-title').matches(':hover'))).toBe(false);
                }
            });

        }
    });
}
