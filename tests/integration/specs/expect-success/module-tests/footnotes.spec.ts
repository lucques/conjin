import { expect, test } from "../../../fixtures/page-health";

test.describe("footnotes", () => {
  test("renders numbered references, notes, and backlinks after the content", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto("/module-tests-expect-success/footnotes/with-notes/");

    expect(response?.ok()).toBe(true);

    const references = page.locator('#footnote-source sup > a');
    await expect(references).toHaveText(["1", "2"]);
    await expect(references.nth(0)).toHaveAttribute("id", "footnote-loc-1");
    await expect(references.nth(0)).toHaveAttribute("href", "#footnote-note-1");
    await expect(references.nth(1)).toHaveAttribute("id", "footnote-loc-2");
    await expect(references.nth(1)).toHaveAttribute("href", "#footnote-note-2");

    const notes = page.locator("#content main > section .footnotes-notes > li");
    await expect(notes).toHaveCount(2);
    await expect(notes.nth(0).locator("#footnote-note-1")).toContainText("First footnote");
    await expect(notes.nth(0).locator("strong")).toHaveText("footnote");
    await expect(notes.nth(0).locator(".footnote-note-backlink")).toHaveAttribute(
      "href",
      "#footnote-loc-1",
    );
    await expect(notes.nth(1).locator("#footnote-note-2")).toHaveText("Second footnote");

    const notesFollowContent = await page.evaluate(() => {
      const content = document.querySelector("#footnote-source")!;
      const list = document.querySelector(".footnotes-notes")!;
      return Boolean(content.compareDocumentPosition(list) & Node.DOCUMENT_POSITION_FOLLOWING);
    });
    expect(notesFollowContent).toBe(true);

    await references.nth(0).click();
    await expect(page).toHaveURL(/#footnote-note-1$/);
    await notes.nth(0).locator(".footnote-note-backlink").click();
    await expect(page).toHaveURL(/#footnote-loc-1$/);
  });

  test("does not render an empty footnote section", async ({ healthyPage }) => {
    const { page } = healthyPage;
    await page.goto("/module-tests-expect-success/footnotes/without-notes/");

    await expect(page.locator("#content-without-footnotes")).toHaveCount(1);
    await expect(page.locator(".footnotes-notes")).toHaveCount(0);
  });
});
