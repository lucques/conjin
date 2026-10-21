import { expect, test } from "../../../../fixtures/page-health";

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

async function submitForgedRequest(
  page: import("@playwright/test").Page,
  fields: Record<string, string>,
): Promise<void> {
  await Promise.all([
    page.waitForNavigation(),
    page.evaluate((submittedFields) => {
      const sourceForm = Array.from(document.querySelectorAll('form')).find((candidate) => {
        const action = candidate.querySelector<HTMLInputElement>('input[name="req"]');
        const issue = candidate.querySelector<HTMLInputElement>('input[name="issue_id"]');
        return action?.value === submittedFields.req &&
          (submittedFields.req !== 'new-post' && submittedFields.req !== 'new-additional-email' ||
            issue?.value === submittedFields.issue_id);
      });
      const requestUuid = sourceForm?.querySelector<HTMLInputElement>('input[name="request_uuid"]')?.value ?? 'unissued';
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
}

async function setTagAdditionalEmailAddresses(
  modal: import("@playwright/test").Locator,
  addresses: string[],
): Promise<void> {
  await modal.locator('[data-tag-additional-email-addresses]').evaluate((element, values) => {
    const tomSelect = (element as HTMLInputElement & {
      tomselect: {
        clear: (silent?: boolean) => void;
        clearOptions: () => void;
        addOption: (option: { value: string; text: string }) => void;
        addItem: (value: string, silent?: boolean) => void;
      };
    }).tomselect;
    tomSelect.clear(true);
    tomSelect.clearOptions();
    for (const value of values) {
      tomSelect.addOption({ value, text: value });
      tomSelect.addItem(value, true);
    }
  }, addresses);
}

test.describe("issue tracker", () => {
  test.describe.configure({ mode: "serial" });

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

  test("supports admin-managed authored additional email events", async ({ healthyPage }) => {
    const { page } = healthyPage;
    await reset(page, lifecyclePath);
    await createIssue(page, "Notification audience", "Bug");

    await expect(page.getByRole("button", { name: "E-Mail-Adresse hinzufügen" })).toHaveCount(0);
    await submitForgedRequest(page, {
      req: "new-additional-email",
      issue_id: "1",
      email_address: "forged@example.com",
    });
    await expect(page.locator('[data-timeline-entry="additional-email"]')).toHaveCount(0);

    await page.goto(`/login/?redirect=${encodeURIComponent(lifecyclePath)}`);
    await page.locator("#password").fill("asdf");
    await page.getByRole("button", { name: "Login" }).click();
    await expect(page).toHaveURL(lifecyclePath);

    let issue = page.locator("#open-issues .accordion-item").filter({ hasText: "Notification audience" });
    await issue.locator(".accordion-button").click();
    await issue.getByRole("button", { name: "E-Mail-Adresse hinzufügen" }).click();
    let additionalEmailForm = issue.locator('form:has(input[name="req"][value="new-additional-email"])');
    await additionalEmailForm.locator('input[name="email_address"]').fill("notify@example.com");
    await additionalEmailForm.getByRole("button", { name: "Hinzufügen" }).click();

    await expect(page.locator(".alert-success")).toContainText("notify@example.com");
    let additionalEmailEvent = page.locator('[data-timeline-entry="additional-email"]');
    await expect(additionalEmailEvent).toHaveCount(1);
    await expect(additionalEmailEvent).toContainText("notify@example.com");
    await expect(additionalEmailEvent).toContainText("Test user");
    await expect(additionalEmailEvent.locator("[data-post-edit-modal]")).toHaveCount(0);

    await page.locator('#sidebar-footer-actions').getByRole('button', { name: 'Logout' }).click();
    await expect(page).toHaveURL(/\/login\/$/);
    await page.goto(lifecyclePath);
    additionalEmailEvent = page.locator('[data-timeline-entry="additional-email"]');
    await expect(additionalEmailEvent).toHaveCount(1);
    await expect(additionalEmailEvent.locator("[data-additional-email-delete-modal]")).toHaveCount(0);
    await submitForgedRequest(page, {
      req: "delete-additional-email",
      issue_id: "1",
      additional_email_id: "1",
    });
    await expect(page.locator('[data-timeline-entry="additional-email"]')).toHaveCount(1);

    await page.goto(`/login/?redirect=${encodeURIComponent(lifecyclePath)}`);
    await page.locator("#password").fill("asdf");
    await page.getByRole("button", { name: "Login" }).click();

    issue = page.locator("#open-issues .accordion-item").filter({ hasText: "Notification audience" });
    await issue.locator(".accordion-button").click();
    await issue.getByRole("button", { name: "E-Mail-Adresse hinzufügen" }).click();
    additionalEmailForm = issue.locator('form:has(input[name="req"][value="new-additional-email"])');
    await additionalEmailForm.locator('input[name="email_address"]').fill("NOTIFY@example.com");
    await additionalEmailForm.getByRole("button", { name: "Hinzufügen" }).click();
    await expect(page.locator('input[name="email_address"]')).toHaveClass(/is-invalid/);
    await expect(additionalEmailForm.locator(".invalid-feedback")).toContainText("bereits hinzugefügt");
    await expect(page.locator('[data-timeline-entry="additional-email"]')).toHaveCount(1);

    additionalEmailEvent = page.locator('[data-timeline-entry="additional-email"]');
    await additionalEmailEvent.locator("[data-additional-email-delete-modal]").click();
    await page.locator(".modal.show").getByRole("button", { name: "Endgültig löschen" }).click();
    await expect(page.locator(".alert-success")).toContainText("zusätzliche E-Mail-Adresse wurde vollständig gelöscht");
    await expect(page.locator('[data-timeline-entry="additional-email"]')).toHaveCount(0);
    await expect(page.locator("#open-issues")).toContainText("Notification audience");
  });

  test("shows tags to all users and restricts management to admins", async ({ healthyPage }) => {
    const { page } = healthyPage;
    await reset(page, lifecyclePath);
    await createIssue(page, "Projector failure", "Bug");

    await expect(page.getByRole("heading", { name: "Tags verwalten" })).toBeVisible();
    await page.getByRole("button", { name: "Alle Tags", exact: true }).click();
    await expect(page.locator("#tag-management [data-tag-management]")).toBeVisible();
    await expect(page.locator('#tag-management [data-tag-row-id="bug"]')).toContainText("Bug");
    await expect(page.locator("#tag-management [data-tag-edit-modal]")).toHaveCount(0);
    await expect(page.locator("#tag-management [data-tag-new-modal]")).toHaveCount(0);
    await expect(page.locator("#tag-management [data-mail-tags-modal]")).toHaveCount(0);
    await expect(page.locator("#tag-management button:has-text('E-Mails an alle Tags schreiben')")).toHaveCount(0);
    await submitForgedRequest(page, {
      req: "edit-tag",
      tag_id: "bug",
      name: "Tampered tag",
    });

    await page.goto(`/login/?redirect=${encodeURIComponent(lifecyclePath)}`);
    await page.locator("#password").fill("asdf");
    await page.getByRole("button", { name: "Login" }).click();

    await expect(page.locator("#docs-tag-client-data")).toHaveText("Configured client data");
    await expect(page.getByRole("heading", { name: "Tags verwalten" })).toBeVisible();
    await page.getByRole("button", { name: "Alle Tags", exact: true }).click();
    await expect(page.getByRole("columnheader", { name: "Solved issues", exact: true })).toBeVisible();
    await expect(page.getByRole("columnheader", { name: "Unsolved issues", exact: true })).toBeVisible();
    await expect(page.locator('#tag-management [data-tag-row-id="docs"] [data-tag-client-data-cell]')).toHaveText(
      "Configured client data",
    );

    let bugRow = page.locator('#tag-management [data-tag-row-id="bug"]');
    await expect(bugRow).toContainText("Bug");
    await expect(bugRow.locator("[data-tag-additional-email-addresses-cell] li")).toHaveText(["bugs@example.com"]);
    await expect(bugRow.locator("[data-solved-issue-count]")).toHaveText("0");
    await expect(bugRow.locator("[data-unsolved-issue-count]")).toHaveText("1");
    await expect(page.locator("#tag-management").getByRole("button", { name: /löschen/i })).toHaveCount(0);

    await bugRow.getByRole("button", { name: "Tag bug bearbeiten" }).click();
    const editTagModal = page.locator(".modal.show");
    await expect(editTagModal.locator('input[name="tag_id"]')).toHaveValue("bug");
    await expect(editTagModal.locator('input[name="has_client_data"]')).not.toBeChecked();
    await expect(editTagModal.locator('textarea[name="client_data"]')).toBeDisabled();
    await expect(editTagModal.locator('input[name="additional_email_addresses"]')).toHaveValue("bugs@example.com");
    await editTagModal.locator('input[name="name"]').fill("Hardware");
    await editTagModal.locator('input[name="has_client_data"]').check();
    await editTagModal.locator('textarea[name="client_data"]').fill("First line\nSecond line");
    await setTagAdditionalEmailAddresses(editTagModal, ["bugs@example.com", "hardware@example.com"]);
    await editTagModal.getByRole("button", { name: "Speichern" }).click();

    await expect(page.locator(".alert-success")).toContainText("ID bug");
    bugRow = page.locator('#tag-management [data-tag-row-id="bug"]');
    await expect(bugRow).toContainText("Hardware");
    await expect(page.locator('#tag-management [data-tag-row-id="hardware"]')).toHaveCount(0);
    await expect(page.locator("#open-issues")).toContainText("Hardware");
    await expect(bugRow.locator("[data-tag-additional-email-addresses-cell] li")).toHaveText([
      "bugs@example.com",
      "hardware@example.com",
    ]);

    await bugRow.getByRole("button", { name: "Tag bug bearbeiten" }).click();
    await expect(editTagModal.locator('input[name="has_client_data"]')).toBeChecked();
    await expect(editTagModal.locator('textarea[name="client_data"]')).toBeEnabled();
    await expect(editTagModal.locator('textarea[name="client_data"]')).toHaveValue("First line\nSecond line");
    await expect(editTagModal.locator('input[name="additional_email_addresses"]')).toHaveValue(
      "bugs@example.com,hardware@example.com",
    );
    await editTagModal.locator('input[name="has_client_data"]').uncheck();
    await editTagModal.getByRole("button", { name: "Speichern" }).click();

    bugRow = page.locator('#tag-management [data-tag-row-id="bug"]');
    await bugRow.getByRole("button", { name: "Tag bug bearbeiten" }).click();
    await expect(editTagModal.locator('input[name="has_client_data"]')).not.toBeChecked();
    await expect(editTagModal.locator('textarea[name="client_data"]')).toBeDisabled();
    await editTagModal.getByRole("button", { name: "Abbrechen" }).click();

    await submitForgedRequest(page, {
      req: "new-tag",
      tag_id: "   ",
      name: "   ",
    });
    const newTagForm = page.locator('.modal.show form:has(input[name="req"][value="new-tag"])');
    await expect(newTagForm.locator('input[name="tag_id"]')).toHaveClass(/is-invalid/);
    await expect(newTagForm.locator('input[name="name"]')).toHaveClass(/is-invalid/);
    await expect(newTagForm.locator('textarea[name="client_data"]')).toBeDisabled();
    await newTagForm.locator('input[name="tag_id"]').fill("overview");
    await newTagForm.locator('input[name="name"]').fill("Überblick");
    await newTagForm.locator('input[name="has_client_data"]').check();
    await newTagForm.locator('textarea[name="client_data"]').fill("Alpha\nBeta");
    await setTagAdditionalEmailAddresses(newTagForm, [
      "overview@example.com",
      "OVERVIEW@example.com",
      "team@example.com",
    ]);
    await newTagForm.getByRole("button", { name: "Tag hinzufügen" }).click();

    const newTagRow = page.locator('#tag-management [data-tag-row-id="overview"]');
    await expect(newTagRow).toContainText("Überblick");
    await expect(newTagRow.locator("[data-tag-client-data-cell]")).toHaveText("Alpha\nBeta");
    await expect(newTagRow.locator("[data-tag-additional-email-addresses-cell] li")).toHaveText([
      "overview@example.com",
      "team@example.com",
    ]);
    await expect(newTagRow.locator("[data-solved-issue-count]")).toHaveText("0");
    await expect(newTagRow.locator("[data-unsolved-issue-count]")).toHaveText("0");
    await newTagRow.getByRole("button", { name: "Tag overview bearbeiten" }).click();
    await expect(editTagModal.locator('textarea[name="client_data"]')).toHaveValue("Alpha\nBeta");
    await editTagModal.getByRole("button", { name: "Abbrechen" }).click();

    await submitForgedRequest(page, {
      req: "edit-tag",
      tag_id: "overview",
      name: "Überblick",
      additional_email_addresses: "invalid-address",
    });
    const invalidEmailEditForm = page.locator('.modal.show form:has(input[name="req"][value="edit-tag"])');
    await expect(invalidEmailEditForm.locator('input[name="additional_email_addresses"]')).toHaveClass(/is-invalid/);
    await expect(
      invalidEmailEditForm.locator('[data-tag-additional-email-addresses]').locator("..").locator(".invalid-feedback"),
    ).toContainText("gültige E-Mail-Adressen");
    await expect(invalidEmailEditForm.locator('input[name="additional_email_addresses"]')).toHaveValue(
      "invalid-address",
    );
    await setTagAdditionalEmailAddresses(invalidEmailEditForm, ["overview@example.com"]);
    await invalidEmailEditForm.getByRole("button", { name: "Speichern" }).click();
    await expect(newTagRow.locator("[data-tag-additional-email-addresses-cell] li")).toHaveText([
      "overview@example.com",
    ]);

    await submitForgedRequest(page, {
      req: "new-post",
      issue_id: "1",
      content: "Projector replaced",
      new_status: "closed",
    });

    bugRow = page.locator('#tag-management [data-tag-row-id="bug"]');
    await expect(bugRow.locator("[data-solved-issue-count]")).toHaveText("1");
    await expect(bugRow.locator("[data-unsolved-issue-count]")).toHaveText("0");
  });

  test("ignores forged edit and delete requests without authorization", async ({ healthyPage }) => {
    const { page } = healthyPage;
    await reset(page, unauthorizedPath);
    await createIssue(page, "Protected issue", "Bug");

    await expect(page.locator("[data-issue-edit-modal], [data-issue-delete-modal]")).toHaveCount(0);

    await submitForgedRequest(page, {
      req: "edit-issue",
      issue_id: "1",
      summary: "Tampered issue",
    });
    await expect(page.locator("#open-issues")).toContainText("Protected issue");
    await expect(page.locator("#open-issues")).not.toContainText("Tampered issue");

    await submitForgedRequest(page, {
      req: "delete-issue",
      issue_id: "1",
    });
    await expect(page.locator("#open-issues")).toContainText("Protected issue");
    await expect(page.locator(".alert-success")).toHaveCount(0);
  });
});
