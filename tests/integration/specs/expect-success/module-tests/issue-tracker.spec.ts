import { expect, test } from "../../../fixtures/page-health";

const lifecyclePath = "/module-tests-expect-success/issue-tracker/lifecycle/";
const unauthorizedPath = "/module-tests-expect-success/issue-tracker/unauthorized/";

async function reset(page: import("@playwright/test").Page, path: string): Promise<void> {
  await page.goto(`${path}?reset=1`);
  await page.goto(path);
}

async function createIssue(
  page: import("@playwright/test").Page,
  summary: string,
  tag: string,
): Promise<void> {
  await page.getByRole("button", { name: "Neues Issue anlegen" }).click();
  const form = page.locator("#new-issue form");
  await form.locator('select[name="tags[]"]').selectOption({ label: tag });
  await form.locator('textarea[name="content"]').fill(summary);
  await form.getByRole("button", { name: "Issue anlegen" }).click();
}

test.describe("issue tracker", () => {
  test("validates input and supports the complete issue lifecycle", async ({ healthyPage }) => {
    const { page } = healthyPage;
    await reset(page, lifecyclePath);

    await expect(page.locator('#open-counts dd[data-tag="Bug"]')).toHaveText("0");
    await expect(page.locator("#open-issues")).toContainText("No open issues.");

    await page.getByRole("button", { name: "Neues Issue anlegen" }).click();
    const newIssueForm = page.locator("#new-issue form");
    await newIssueForm.locator('select[name="tags[]"]').selectOption({ label: "Bug" });
    await newIssueForm.getByRole("button", { name: "Issue anlegen" }).click();

    await expect(page.locator("#new-issue .modal")).toHaveClass(/show/);
    await expect(page.locator('#new-issue textarea[name="content"]')).toHaveClass(/is-invalid/);
    await expect(page.locator("#open-issues")).toContainText("No open issues.");
    await expect(page.locator('#new-issue select[name="tags[]"] option:checked')).toHaveText("Bug");

    await page.locator('#new-issue textarea[name="content"]').fill("Broken search");
    await page.locator("#new-issue form").getByRole("button", { name: "Issue anlegen" }).click();

    await expect(page.locator(".alert-success")).toContainText("wurde angelegt");
    await expect(page.locator('#open-counts dd[data-tag="Bug"]')).toHaveText("1");
    await expect(page.locator("#open-issues")).toContainText("Broken search");
    await expect(page.locator("#open-issues")).toContainText("Bug");

    await page.locator('#open-issues [data-issue-edit-modal][data-issue-id="1"]').click();
    const editIssueModal = page.locator(".modal.show");
    await editIssueModal.locator('input[name="summary"]').fill("Search documentation");
    await editIssueModal.locator('select[name="tags[]"]').selectOption({ label: "Docs" });
    await editIssueModal.getByRole("button", { name: "Speichern" }).click();

    await expect(page.locator(".alert-success")).toContainText("Issue #1 wurde bearbeitet");
    await expect(page.locator("#open-issues")).toContainText("Search documentation");
    await expect(page.locator('#open-counts dd[data-tag="Bug"]')).toHaveText("0");
    await expect(page.locator('#open-counts dd[data-tag="Docs"]')).toHaveText("1");

    const issue = page.locator("#open-issues .accordion-item").filter({ hasText: "Search documentation" });
    await issue.getByRole("button", { name: "Post hinzufügen" }).click();
    const newPostForm = issue.locator('form:has(input[name="req"][value="new-post"])');
    await newPostForm.locator('textarea[name="content"]').fill("Fixed and verified");
    await newPostForm.locator('select[name="new_status"]').selectOption("closed");
    await newPostForm.getByRole("button", { name: "Abschicken" }).click();

    await expect(page.locator("#open-issues")).toContainText("No open issues.");
    await expect(page.locator("#closed-issues")).toContainText("Search documentation");
    await expect(page.locator("#closed-issues")).toContainText("Fixed and verified");
    await expect(page.locator('#open-counts dd[data-tag="Docs"]')).toHaveText("0");

    await page.locator('#closed-issues [data-post-edit-modal][data-post-id="2"]').click();
    const editPostModal = page.locator(".modal.show");
    await editPostModal.locator('textarea[name="content"]').fill("Fix needs another look");
    await editPostModal.locator('select[name="new_status"]').selectOption("open");
    await editPostModal.getByRole("button", { name: "Speichern" }).click();

    await expect(page.locator("#open-issues")).toContainText("Fix needs another look");
    await expect(page.locator("#closed-issues")).toContainText("No closed issues.");

    await page.locator('#open-issues [data-post-delete-modal][data-post-id="2"]').click();
    await page.locator(".modal.show").getByRole("button", { name: "Endgültig löschen" }).click();
    await expect(page.locator(".alert-success")).toContainText("Post #2 wurde vollständig gelöscht");
    await expect(page.locator("#open-issues")).not.toContainText("Fix needs another look");

    await page.locator('#open-issues [data-issue-delete-modal][data-issue-id="1"]').click();
    await page.locator(".modal.show").getByRole("button", { name: "Endgültig löschen" }).click();
    await expect(page.locator(".alert-success")).toContainText("Issue #1 wurde vollständig gelöscht");
    await expect(page.locator("#open-issues")).toContainText("No open issues.");
  });

  test("ignores forged edit and delete requests without authorization", async ({ healthyPage }) => {
    const { page } = healthyPage;
    await reset(page, unauthorizedPath);
    await createIssue(page, "Protected issue", "Bug");

    await expect(page.locator("[data-issue-edit-modal], [data-issue-delete-modal]")).toHaveCount(0);

    const submitForgedRequest = async (fields: Record<string, string>): Promise<void> => {
      await Promise.all([
        page.waitForNavigation(),
        page.evaluate((submittedFields) => {
          const requestUuid = (
            document.querySelector('input[name="request_uuid"]') as HTMLInputElement
          ).value;
          const form = document.createElement("form");
          form.method = "post";
          for (const [name, value] of Object.entries({ ...submittedFields, request_uuid: requestUuid })) {
            const input = document.createElement("input");
            input.name = name;
            input.value = value;
            form.append(input);
          }
          document.body.append(form);
          form.submit();
        }, fields),
      ]);
    };

    await submitForgedRequest({
      req: "edit-issue",
      issue_id: "1",
      summary: "Tampered issue",
    });
    await expect(page.locator("#open-issues")).toContainText("Protected issue");
    await expect(page.locator("#open-issues")).not.toContainText("Tampered issue");

    await submitForgedRequest({
      req: "delete-issue",
      issue_id: "1",
    });
    await expect(page.locator("#open-issues")).toContainText("Protected issue");
    await expect(page.locator(".alert-success")).toHaveCount(0);
  });
});
