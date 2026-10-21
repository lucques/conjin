import { test, expect } from './fixtures';

test.beforeEach(async ({ page, feedbackHtml }) => {
    await page.route('http://feedback.test/**', route => route.fulfill({ contentType: 'text/html', body: feedbackHtml }));
    await page.goto('http://feedback.test/');
});

test('section headings and follow-up hints', async ({ page }) => {
    const field = label => page.getByLabel(label, { exact: true });
    const option = label => page.getByLabel(label, { exact: true });
    const valid = () => page.locator('form').evaluate(form => form.checkValidity());
    await expect(page.locator('[data-feedback-section]')).toHaveCount(2);
    await expect(page.getByRole('heading', { name: 'WLAN <&>', exact: true })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Equipment', exact: true })).toBeVisible();
    await expect(page.locator('label').filter({ hasText: 'Problematisch' }).locator('em')).toHaveText('(enthält Folgefragen)');
    await expect(page.locator('label').filter({ hasText: 'Problematisch' }).locator('em')).toBeVisible();
    await expect(page.locator('label').filter({ hasText: 'Etwas anderes' }).locator('em')).toHaveText('(enthält Folgefrage)');
    await expect(page.locator('label').filter({ hasText: 'Etwas anderes' }).locator('em')).toBeVisible();
});

test('anonymity controls submit exactly one choice', async ({ page }) => {
    const field = label => page.getByLabel(label, { exact: true });
    const option = label => page.getByLabel(label, { exact: true });
    const valid = () => page.locator('form').evaluate(form => form.checkValidity());
    const anonymous = page.getByRole('radio', { name: 'Anonym', exact: true });
    const identified = page.getByRole('radio', { name: 'Mit Name (teacher-private@example.test)', exact: true });
    await expect(anonymous).toBeChecked();
    await expect(identified).toBeVisible();
    await expect(identified).not.toBeChecked();
    expect(await page.locator('form').evaluate(form => new FormData(form).getAll('feedback_anonymous'))).toEqual(['1']);
    await identified.check();
    await expect(anonymous).not.toBeChecked();
    expect(await page.locator('form').evaluate(form => new FormData(form).getAll('feedback_anonymous'))).toEqual(['0']);
    await anonymous.check();
    await expect(identified).not.toBeChecked();
});

test('conditional validation and restored values', async ({ page }) => {
    const field = label => page.getByLabel(label, { exact: true });
    const option = label => page.getByLabel(label, { exact: true });
    const valid = () => page.locator('form').evaluate(form => form.checkValidity());
    await expect(field('Welche Probleme? *')).toBeHidden();
    await expect(field('Welche Probleme? *')).toBeDisabled();
    await expect(field('Welches Netz? *')).toBeHidden();
    expect(await valid()).toBe(false);

    await option('Unproblematisch').check();
    expect(await valid()).toBe(false); // Required checkbox group still unanswered.
    await option('PC').check();
    expect(await valid()).toBe(true);

    await option('Problematisch (enthält Folgefragen)').check();
    await expect(field('Welche Probleme? *')).toBeVisible();
    await expect(field('Welche Probleme? *')).toHaveAttribute('required', '');
    expect(await valid()).toBe(false);
    await field('Welche Probleme? *').fill('Slow in room 1');
    await option('Ja (enthält Folgefrage)').check();
    await expect(field('Welches Netz? *')).toBeVisible();
    expect(await valid()).toBe(false);
    await field('Welches Netz? *').fill('LTE');
    expect(await valid()).toBe(true);

    await option('Unproblematisch').check();
    await expect(field('Welches Netz? *')).toBeHidden();
    await expect(field('Welches Netz? *')).toBeDisabled();
    expect(await valid()).toBe(true);
    const postedKeys = await page.locator('form').evaluate(form => [...new FormData(form).keys()]);
    expect(postedKeys).not.toContain(await field('Welche Probleme? *').getAttribute('name'));
    expect(postedKeys).not.toContain(await field('Welches Netz? *').getAttribute('name'));
    await option('Problematisch (enthält Folgefragen)').check();
    await expect(field('Welches Netz? *')).toHaveValue('LTE');
    await option('Nein').check();
    await expect(field('Welches Netz? *')).toBeHidden();
    expect(await valid()).toBe(true);
});

test('optional follow-ups disable their fields and IDs are unique', async ({ page }) => {
    const field = label => page.getByLabel(label, { exact: true });
    const option = label => page.getByLabel(label, { exact: true });
    const valid = () => page.locator('form').evaluate(form => form.checkValidity());
    await option('AppleTV').check();
    await option('Etwas anderes (enthält Folgefrage)').check();
    await expect(field('Was sonst?')).toBeVisible();
    await field('Was sonst?').fill('HDMI adapter');
    await option('Etwas anderes (enthält Folgefrage)').uncheck();
    await expect(field('Was sonst?')).toBeDisabled();

    await option('Optional answer').check();
    await option('Keine Angabe').check();
    const ids = await page.locator('[id]').evaluateAll(elements => elements.map(element => element.id));
    expect(new Set(ids).size).toBe(ids.length);
});

test.describe('without JavaScript', () => {
    test.use({ javaScriptEnabled: false });
    test('anonymity choices remain exclusive', async ({ page }) => {
        await expect(page.locator('[data-feedback-anonymous]')).toBeChecked();
        await page.getByRole('radio', { name: 'Mit Name (teacher-private@example.test)', exact: true }).check();
        await expect(page.locator('[data-feedback-anonymous]')).not.toBeChecked();
        await expect(page.locator('[data-feedback-identified]')).toBeChecked();
    });
});
