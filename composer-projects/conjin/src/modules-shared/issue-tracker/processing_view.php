<?
    namespace Conjin\Modules\IssueTracker;

    trait View {

        //////////
        // View //
        //////////

        function print_notification_recipients_for_tag(?array $tag): void {
            echo $this->notification_address_list_html($this->notification_recipients_for_tag($tag));
        }

        private function at_sentence_start(string $text): string {
            return ucfirst($text);
        }

        private function tag_client_data_html(?string $client_data): string {
            if ($client_data === null) {
                return '<span class="text-body-secondary">—</span>';
            }

            $to_text = $this->config->view->tag_client_data_to_text;
            $text = $to_text === null ? $client_data : $to_text($client_data);
            return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        private function own_content_edit_window_hint(string $content_desc): string {
            $minutes = $this->config->may_edit_own_content_within_n_minutes;
            if ($minutes === 0 || $this->may_administer()) {
                return '';
            }

            $unit = $minutes === 1 ? 'Minute' : 'Minuten';

            return '<p><em>' . $this->at_sentence_start($content_desc)
                . ' kann noch ' . $minutes . ' ' . $unit
                . ' lang von Ihnen bearbeitet oder gelöscht werden.</em></p>';
        }

        /** @param list<array{email_address: string, reasons: list<string>}> $recipients */
        private function notification_address_list_html(array $recipients): string {
            return '<ul>' . implode('', array_map(function ($recipient) {
                $escaped_address = htmlspecialchars(
                    $recipient['email_address'],
                    ENT_QUOTES | ENT_SUBSTITUTE,
                    'UTF-8'
                );
                if (count($recipient['reasons']) === 0) {
                    return '<li>' . $escaped_address . '</li>';
                }

                return '<li>' . $escaped_address . ' <em>('
                    . implode('; ', array_map(
                        fn(string $reason): string => htmlspecialchars(
                            $reason,
                            ENT_QUOTES | ENT_SUBSTITUTE,
                            'UTF-8'
                        ),
                        $recipient['reasons']
                    ))
                    . ')</em></li>';
            }, $recipients)) . '</ul>';
        }

        // Display success or error message of the new-issue-handling
        function print_handle_new_issue_res(?HandleNewIssueResult $res) {
            // Only display something if there is something to display
            if ($res !== null) {
                if (
                    $res->kind() == HandleNewIssueResultKind::SuccessWithMails ||
                    $res->kind() == HandleNewIssueResultKind::SuccessButMailsFailed ||
                    $res->kind() == HandleNewIssueResultKind::SuccessWithoutMails
                ) {
                    $message = match ($res->kind()) {
                        HandleNewIssueResultKind::SuccessWithMails =>
                            '<p>' . $this->at_sentence_start($this->config->view->issue_desc_long)
                            . ' wurde angelegt und E-Mails zur Benachrichtigung wurden gesendet an:</p>'
                            . $this->notification_address_list_html(
                                $res->recipients
                            ),
                        HandleNewIssueResultKind::SuccessButMailsFailed =>
                            '<p>' . $this->at_sentence_start($this->config->view->issue_desc_long)
                            . ' wurde angelegt, aber E-Mails zur Benachrichtigung konnten nicht gesendet werden. Das Problem mit der fehlgeschlagenen E-Mail-Benachrichtigung wurde gemeldet.</p>',
                        HandleNewIssueResultKind::SuccessWithoutMails =>
                            '<p>' . $this->at_sentence_start($this->config->view->issue_desc_long)
                            . ' wurde angelegt, jedoch wurden keine E-Mails zur Benachrichtigung gesendet (dies entspricht der gewünschten Konfiguration).</p>'
                    };
                    $message .= $this->own_content_edit_window_hint(
                        $this->config->view->issue_desc_long
                    );

                    echo '<div class="alert alert-success">' . $message . '</div>';
                }
                elseif (
                    // Incomplete form data error is not displayed here (already displayed in modal)
                    $res->kind() == HandleNewIssueResultKind::ErrorDb
                ) {
                    $message =
                        'Fehler: Es ist ein Datenbankfehler aufgetreten.<pre>'
                        . htmlspecialchars($res->details, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                        . '</pre>';

                    echo '<div class="alert alert-danger">' . $message . '</div>';
                }
            }
        }

        // Display success or error message of the new-post-handling
        function print_handle_new_post_res(?HandleNewPostResult $res) {
            // Only display something if there is something to display
            if ($res !== null) {
                if (
                    $res->kind() == HandleNewPostResultKind::SuccessWithMails ||
                    $res->kind() == HandleNewPostResultKind::SuccessButMailsFailed ||
                    $res->kind() == HandleNewPostResultKind::SuccessWithoutMails
                ) {
                    $message = match ($res->kind()) {
                        HandleNewPostResultKind::SuccessWithMails =>
                            '<p>' . $this->at_sentence_start($this->config->view->post_desc_long)
                            . ' wurde angelegt und E-Mails zur Benachrichtigung wurden gesendet an:</p>'
                            . $this->notification_address_list_html(
                                $res->recipients
                            ),
                        HandleNewPostResultKind::SuccessButMailsFailed =>
                            '<p>' . $this->at_sentence_start($this->config->view->post_desc_long)
                            . ' wurde angelegt, aber E-Mails zur Benachrichtigung konnten nicht gesendet werden. Das Problem mit der fehlgeschlagenen E-Mail-Benachrichtigung wurde gemeldet.</p>',
                        HandleNewPostResultKind::SuccessWithoutMails =>
                            '<p>' . $this->at_sentence_start($this->config->view->post_desc_long)
                            . ' wurde angelegt, jedoch wurden keine E-Mails zur Benachrichtigung gesendet (dies entspricht der gewünschten Konfiguration).</p>'
                    };
                    $message .= $this->own_content_edit_window_hint(
                        $this->config->view->post_desc_long
                    );

                    echo '<div class="alert alert-success">' . $message . '</div>';
                }
                else {
                    $message = match ($res->kind()) {
                        HandleNewPostResultKind::ErrorIncomplete =>
                            'Fehler: Bitte füllen Sie alle erforderlichen Felder aus.',
                        HandleNewPostResultKind::ErrorDb =>
                            'Fehler: Es ist ein Datenbankfehler aufgetreten.<pre>'
                            . htmlspecialchars($res->details, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                            . '</pre>'
                    };

                    echo '<div class="alert alert-danger">' . $message . '</div>';
                }
            }
        }

        function print_handle_new_additional_email_res(?HandleNewAdditionalEmailResult $res): void {
            if ($res === null) {
                return;
            }

            if ($res->kind() === HandleNewAdditionalEmailResultKind::Success) {
                echo '<div class="alert alert-success"><p>Die E-Mail-Adresse '
                    . htmlspecialchars($res->email_address, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    . ' wurde zur Benachrichtigung hinzugefügt.</p></div>';
            }
            elseif ($res->kind() === HandleNewAdditionalEmailResultKind::ErrorDb) {
                echo '<div class="alert alert-danger">Fehler: Beim Hinzufügen der E-Mail-Adresse ist ein Datenbankfehler aufgetreten.<pre>'
                    . htmlspecialchars($res->details, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    . '</pre></div>';
            }
        }

        function print_handle_new_tag_res(?HandleNewTagResult $res): void {
            if ($res === null) {
                return;
            }

            $tag_desc_singular = htmlspecialchars(
                $this->config->view->tag_desc_singular,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );

            if ($res->kind() === HandleNewTagResultKind::Success) {
                echo '<div class="alert alert-success"><p>' . $tag_desc_singular . ' '
                    . htmlspecialchars($res->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    . ' mit der ID '
                    . htmlspecialchars($res->tag_id, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    . ' wurde hinzugefügt.</p></div>';
            }
            elseif ($res->kind() === HandleNewTagResultKind::ErrorDb) {
                echo '<div class="alert alert-danger">Fehler: ' . $tag_desc_singular
                    . ' konnte wegen eines Datenbankfehlers nicht hinzugefügt werden.<pre>'
                    . htmlspecialchars($res->details, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    . '</pre></div>';
            }
        }

        function print_handle_edit_tag_res(?HandleEditTagResult $res): void {
            if ($res === null) {
                return;
            }

            $tag_desc_singular = htmlspecialchars(
                $this->config->view->tag_desc_singular,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );

            if ($res->kind() === HandleEditTagResultKind::Success) {
                echo '<div class="alert alert-success"><p>' . $tag_desc_singular . ' mit der ID '
                    . htmlspecialchars($res->tag_id(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    . ' wurde bearbeitet.</p></div>';
            }
            elseif ($res->kind() === HandleEditTagResultKind::ErrorDb) {
                echo '<div class="alert alert-danger">Fehler: ' . $tag_desc_singular
                    . ' konnte wegen eines Datenbankfehlers nicht bearbeitet werden.<pre>'
                    . htmlspecialchars($res->details, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    . '</pre></div>';
            }
        }

        // Display success or error message of the edit-issue handling
        function print_handle_edit_issue_res(?HandleEditIssueResult $res) {
            if ($res === null) {
                return;
            }

            if ($res->kind() === HandleEditIssueResultKind::Success) {
                echo '<div class="alert alert-success"><p>'
                    . $this->at_sentence_start($this->config->view->issue_desc_long)
                    . ' #' . $res->issue_id()
                    . ' wurde bearbeitet.</p></div>';
            }
            elseif ($res->kind() === HandleEditIssueResultKind::ErrorDb) {
                $message = 'Fehler: Beim Bearbeiten von '
                    . $this->config->view->issue_desc_long . ' #' . $res->issue_id()
                    . ' ist ein Datenbankfehler aufgetreten.<pre>'
                    . htmlspecialchars($res->details, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    . '</pre>';

                echo '<div class="alert alert-danger">' . $message . '</div>';
            }
        }

        // Display success or error message of the edit-post handling
        function print_handle_edit_post_res(?HandleEditPostResult $res) {
            if ($res === null) {
                return;
            }

            if ($res->kind() === HandleEditPostResultKind::Success) {
                echo '<div class="alert alert-success"><p>'
                    . $this->at_sentence_start($this->config->view->post_desc_long)
                    . ' #' . $res->post_id()
                    . ' wurde bearbeitet.</p></div>';
            }
            elseif ($res->kind() === HandleEditPostResultKind::ErrorDb) {
                $message = 'Fehler: Beim Bearbeiten von '
                    . $this->config->view->post_desc_long . ' #' . $res->post_id()
                    . ' ist ein Datenbankfehler aufgetreten.<pre>'
                    . htmlspecialchars($res->details, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    . '</pre>';

                echo '<div class="alert alert-danger">' . $message . '</div>';
            }
        }

        // Display success or error message of the delete-issue handling
        function print_handle_delete_issue_res(?HandleDeleteIssueResult $res) {
            if ($res === null) {
                return;
            }

            if ($res->kind() === HandleDeleteIssueResultKind::Success) {
                echo '<div class="alert alert-success"><p>'
                    . $this->at_sentence_start($this->config->view->issue_desc_long)
                    . ' #' . $res->issue_id()
                    . ' wurde vollständig gelöscht.</p></div>';
            }
            elseif ($res->kind() === HandleDeleteIssueResultKind::ErrorDb) {
                $message = 'Fehler: Beim Löschen von '
                    . $this->config->view->issue_desc_long . ' #' . $res->issue_id()
                    . ' ist ein Datenbankfehler aufgetreten.<pre>'
                    . htmlspecialchars($res->details, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    . '</pre>';

                echo '<div class="alert alert-danger">' . $message . '</div>';
            }
        }

        // Display success or error message of the delete-post handling
        function print_handle_delete_post_res(?HandleDeletePostResult $res) {
            if ($res === null) {
                return;
            }

            if ($res->kind() === HandleDeletePostResultKind::Success) {
                $message = $this->at_sentence_start($this->config->view->post_desc_long)
                    . ' #' . $res->post_id()
                    . ' wurde vollständig gelöscht.';
                if ($res->issue_deleted()) {
                    $message .= ' Da dies der letzte Eintrag war, wurde auch '
                        . $this->config->view->issue_desc_long . ' #' . $res->issue_id()
                        . ' gelöscht.';
                }

                echo '<div class="alert alert-success"><p>' . $message . '</p></div>';
            }
            elseif ($res->kind() === HandleDeletePostResultKind::ErrorDb) {
                $message = 'Fehler: Beim Löschen von '
                    . $this->config->view->post_desc_long . ' #' . $res->post_id()
                    . ' ist ein Datenbankfehler aufgetreten.<pre>'
                    . htmlspecialchars($res->details, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    . '</pre>';

                echo '<div class="alert alert-danger">' . $message . '</div>';
            }
        }

        function print_handle_delete_additional_email_res(?HandleDeleteAdditionalEmailResult $res): void {
            if ($res === null) {
                return;
            }

            if ($res->kind() === HandleDeleteAdditionalEmailResultKind::Success) {
                echo '<div class="alert alert-success"><p>Die zusätzliche E-Mail-Adresse wurde vollständig gelöscht.</p></div>';
            }
            elseif ($res->kind() === HandleDeleteAdditionalEmailResultKind::ErrorDb) {
                echo '<div class="alert alert-danger">Fehler: Beim Löschen der zusätzlichen E-Mail-Adresse ist ein Datenbankfehler aufgetreten.<pre>'
                    . htmlspecialchars($res->details, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    . '</pre></div>';
            }
        }

        private function print_tag_form_fields(
            string $name_id,
            string $url_id,
            string $has_client_data_id,
            string $client_data_id,
            string $additional_email_addresses_id,
            string $name,
            ?string $url,
            ?string $client_data,
            array $additional_email_addresses,
            bool $name_is_invalid,
            bool $url_is_invalid,
            bool $additional_email_addresses_are_invalid
        ): void {
?>
<div class="mb-3">
    <label for="<?= $name_id ?>" class="form-label">Name</label>
    <input type="text" class="form-control<?= $name_is_invalid ? ' is-invalid' : '' ?>" id="<?= $name_id ?>" name="name" value="<?= htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" required>
    <div class="invalid-feedback">Bitte füllen Sie dieses Feld aus.</div>
</div>
<div class="mb-3">
    <label for="<?= $url_id ?>" class="form-label">URL (optional)</label>
    <input type="url" class="form-control<?= $url_is_invalid ? ' is-invalid' : '' ?>" id="<?= $url_id ?>" name="url" value="<?= htmlspecialchars($url ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="https://example.com/">
    <div class="invalid-feedback">Bitte geben Sie eine vollständige HTTP- oder HTTPS-URL ein.</div>
</div>
<div class="form-check mb-2">
    <input type="checkbox" class="form-check-input" id="<?= $has_client_data_id ?>" name="has_client_data" value="1" data-tag-client-data-toggle<?= $client_data !== null ? ' checked' : '' ?>>
    <label for="<?= $has_client_data_id ?>" class="form-check-label"><?= htmlspecialchars($this->config->view->tag_client_data_desc, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> angeben</label>
</div>
<div>
    <label for="<?= $client_data_id ?>" class="form-label"><?= htmlspecialchars($this->config->view->tag_client_data_desc, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> (optional)</label>
    <textarea class="form-control" id="<?= $client_data_id ?>" name="client_data" rows="4" data-tag-client-data-value<?= $client_data === null ? ' disabled' : '' ?>><?= htmlspecialchars($client_data ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
</div>
<div class="mt-3">
    <label for="<?= $additional_email_addresses_id ?>" class="form-label">Zusätzliche E-Mail-Adressen (optional)</label>
    <input type="text" class="form-control<?= $additional_email_addresses_are_invalid ? ' is-invalid' : '' ?>" id="<?= $additional_email_addresses_id ?>" name="additional_email_addresses" value="<?= htmlspecialchars(implode(',', $additional_email_addresses), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-tag-additional-email-addresses>
    <div class="form-text">Mehrere Adressen können mit Enter oder Komma getrennt werden.</div>
    <div class="invalid-feedback">Bitte geben Sie ausschließlich gültige E-Mail-Adressen ein.</div>
</div>
<?
        }

        private function print_tag_client_data_toggle_script(string $modal_id): void {
?>
<script>
(() => {
    const modal = document.getElementById(<?= json_encode($modal_id, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
    const toggle = modal?.querySelector('[data-tag-client-data-toggle]');
    const value = modal?.querySelector('[data-tag-client-data-value]');
    if (!toggle || !value) return;

    const sync = () => {
        value.disabled = !toggle.checked;
    };
    toggle.addEventListener('change', sync);
    sync();
})();
</script>
<?
        }

        private function print_additional_email_script(string $modal_id): void {
?>
<script>
(() => {
    const modal = document.getElementById(<?= json_encode($modal_id, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
    const input = modal?.querySelector('[data-tag-additional-email-addresses]');
    if (!input || input.tomselect) return;

    const emailSelect = new TomSelect(input, {
        plugins: ['remove_button'],
        create(value) {
            value = value.trim();
            return value === '' ? false : { value, text: value };
        },
        createOnBlur: true,
        persist: false,
        maxItems: null,
        delimiter: ',',
        splitOn: /[\s,;]+/
    });
    if (input.classList.contains('is-invalid')) {
        emailSelect.wrapper.classList.add('is-invalid');
    }
    modal.querySelector('form')?.addEventListener('submit', () => {
        emailSelect.createItem(emailSelect.control_input.value);
    });
})();
</script>
<?
        }

        function print_new_tag_modal(
            ?HandleNewTagResult $handle_new_tag_res = null
        ): ?string {
            if (!$this->may_administer()) {
                return null;
            }

            $id = next_unique_id();
            $modal_id = 'new-tag-modal-' . $id;
            $modal_title_id = $modal_id . '-title';
            $tag_id_id = $modal_id . '-tag-id';
            $name_id = $modal_id . '-name';
            $url_id = $modal_id . '-url';
            $has_client_data_id = $modal_id . '-has-client-data';
            $client_data_id = $modal_id . '-client-data';
            $additional_email_addresses_id = $modal_id . '-additional-email-addresses';
            $form_was_incomplete = $handle_new_tag_res !== null &&
                $handle_new_tag_res->kind() === HandleNewTagResultKind::ErrorIncomplete;
            $url_is_invalid = $handle_new_tag_res !== null &&
                $handle_new_tag_res->kind() === HandleNewTagResultKind::ErrorInvalidUrl;
            $email_addresses_are_invalid = $handle_new_tag_res !== null &&
                $handle_new_tag_res->kind() === HandleNewTagResultKind::ErrorInvalidEmailAddresses;
            $form_has_validation_error = $form_was_incomplete || $url_is_invalid || $email_addresses_are_invalid;
            $restore_form = $handle_new_tag_res !== null &&
                $handle_new_tag_res->kind() !== HandleNewTagResultKind::Success;
            $tag_desc_singular = htmlspecialchars(
                $this->config->view->tag_desc_singular,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );
?>
<div class="modal" id="<?= $modal_id ?>" tabindex="-1" aria-labelledby="<?= $modal_title_id ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="<?= $modal_title_id ?>"><?= $tag_desc_singular ?> hinzufügen</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <form method="post">
                <input type="hidden" name="req" value="new-tag">
                <input type="hidden" name="request_uuid" value="<?= htmlspecialchars($this->issue_submission_token('new-tag'), ENT_QUOTES, 'UTF-8') ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="<?= $tag_id_id ?>" class="form-label"><?= $tag_desc_singular ?>-ID</label>
                        <input type="text" class="form-control<?= $form_was_incomplete && $handle_new_tag_res->tag_id === '' ? ' is-invalid' : '' ?>" id="<?= $tag_id_id ?>" name="tag_id" value="<?= htmlspecialchars($restore_form ? $handle_new_tag_res->tag_id : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" required>
                        <div class="invalid-feedback">Bitte füllen Sie dieses Feld aus.</div>
                    </div>
<?
            $this->print_tag_form_fields(
                $name_id,
                $url_id,
                $has_client_data_id,
                $client_data_id,
                $additional_email_addresses_id,
                $restore_form ? $handle_new_tag_res->name : '',
                $restore_form ? $handle_new_tag_res->url : null,
                $restore_form ? $handle_new_tag_res->client_data : null,
                $restore_form ? $handle_new_tag_res->additional_email_addresses : [],
                $form_was_incomplete && $handle_new_tag_res->name === '',
                $url_is_invalid,
                $email_addresses_are_invalid
            );
?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary"><?= $tag_desc_singular ?> hinzufügen</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?
            $this->print_tag_client_data_toggle_script($modal_id);
            $this->print_additional_email_script($modal_id);
            if ($form_has_validation_error) {
?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelector('[data-tag-new-modal="<?= $modal_id ?>"]')?.click();
}, { once: true });
</script>
<?
            }

            return $modal_id;
        }

        function print_edit_tag_modal(
            ?HandleEditTagResult $handle_edit_tag_res = null
        ): ?string {
            if (!$this->may_administer()) {
                return null;
            }

            $id = next_unique_id();
            $modal_id = 'edit-tag-modal-' . $id;
            $modal_title_id = $modal_id . '-title';
            $name_id = $modal_id . '-name';
            $url_id = $modal_id . '-url';
            $has_client_data_id = $modal_id . '-has-client-data';
            $client_data_id = $modal_id . '-client-data';
            $additional_email_addresses_id = $modal_id . '-additional-email-addresses';
            $form_was_incomplete = $handle_edit_tag_res !== null &&
                $handle_edit_tag_res->kind() === HandleEditTagResultKind::ErrorIncomplete;
            $url_is_invalid = $handle_edit_tag_res !== null &&
                $handle_edit_tag_res->kind() === HandleEditTagResultKind::ErrorInvalidUrl;
            $email_addresses_are_invalid = $handle_edit_tag_res !== null &&
                $handle_edit_tag_res->kind() === HandleEditTagResultKind::ErrorInvalidEmailAddresses;
            $form_has_validation_error = $form_was_incomplete || $url_is_invalid || $email_addresses_are_invalid;
            $tag_desc_singular = htmlspecialchars(
                $this->config->view->tag_desc_singular,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );
?>
<div class="modal" id="<?= $modal_id ?>" tabindex="-1" aria-labelledby="<?= $modal_title_id ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="<?= $modal_title_id ?>"><?= $tag_desc_singular ?> bearbeiten</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <form method="post">
                <input type="hidden" name="req" value="edit-tag">
                <input type="hidden" name="request_uuid" value="<?= htmlspecialchars($this->issue_submission_token('edit-tag'), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="tag_id" value="">
                <div class="modal-body">
<?
            $this->print_tag_form_fields(
                $name_id,
                $url_id,
                $has_client_data_id,
                $client_data_id,
                $additional_email_addresses_id,
                '',
                null,
                null,
                [],
                false,
                $url_is_invalid,
                $email_addresses_are_invalid
            );
?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?
            $this->print_tag_client_data_toggle_script($modal_id);
            $this->print_additional_email_script($modal_id);
?>
<script>
(() => {
    const modalId = <?= json_encode($modal_id, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const tagDescription = <?= json_encode($this->config->view->tag_desc_singular, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const invalidEdit = <?= json_encode($form_has_validation_error ? [
        'tagId' => $handle_edit_tag_res->tag_id(),
        'name' => $handle_edit_tag_res->name,
        'url' => $handle_edit_tag_res->url,
        'urlIsInvalid' => $url_is_invalid,
        'clientData' => $handle_edit_tag_res->client_data,
        'additionalEmailAddresses' => $handle_edit_tag_res->additional_email_addresses,
        'nameIsInvalid' => $form_was_incomplete && $handle_edit_tag_res->name === '',
        'emailAddressesAreInvalid' => $email_addresses_are_invalid
    ] : null, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const modal = document.getElementById(modalId);
    const form = modal?.querySelector('form');
    const title = modal?.querySelector('.modal-title');
    const tagIdInput = form?.querySelector('[name="tag_id"]');
    const nameInput = form?.querySelector('[name="name"]');
    const urlInput = form?.querySelector('[name="url"]');
    const clientDataToggle = form?.querySelector('[data-tag-client-data-toggle]');
    const clientDataValue = form?.querySelector('[data-tag-client-data-value]');
    const additionalEmailAddressesInput = form?.querySelector('[data-tag-additional-email-addresses]');

    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-tag-edit-modal]');
        if (!trigger || trigger.dataset.tagEditModal !== modalId || !form) return;

        form.reset();
        tagIdInput.value = trigger.dataset.tagId;
        const isInvalidEdit = invalidEdit && invalidEdit.tagId === trigger.dataset.tagId;
        const clientData = isInvalidEdit
            ? invalidEdit.clientData
            : trigger.dataset.tagHasClientData === '1' ? trigger.dataset.tagClientData : null;
        const additionalEmailAddresses = isInvalidEdit
            ? invalidEdit.additionalEmailAddresses
            : JSON.parse(trigger.dataset.tagAdditionalEmailAddresses);
        nameInput.value = isInvalidEdit ? invalidEdit.name : trigger.dataset.tagName;
        urlInput.value = isInvalidEdit ? (invalidEdit.url ?? '') : (trigger.dataset.tagUrl ?? '');
        urlInput.classList.toggle('is-invalid', Boolean(isInvalidEdit && invalidEdit.urlIsInvalid));
        nameInput.classList.toggle('is-invalid', Boolean(isInvalidEdit && invalidEdit.nameIsInvalid));
        clientDataToggle.checked = clientData !== null;
        clientDataValue.value = clientData ?? '';
        clientDataToggle.dispatchEvent(new Event('change'));
        additionalEmailAddressesInput.tomselect.clear(true);
        additionalEmailAddressesInput.tomselect.clearOptions();
        additionalEmailAddresses.forEach(address => {
            additionalEmailAddressesInput.tomselect.addOption({ value: address, text: address });
            additionalEmailAddressesInput.tomselect.addItem(address, true);
        });
        additionalEmailAddressesInput.classList.toggle(
            'is-invalid',
            Boolean(isInvalidEdit && invalidEdit.emailAddressesAreInvalid)
        );
        additionalEmailAddressesInput.tomselect.wrapper.classList.toggle(
            'is-invalid',
            Boolean(isInvalidEdit && invalidEdit.emailAddressesAreInvalid)
        );
        title.textContent = `${tagDescription} ${trigger.dataset.tagId} bearbeiten`;
    }, true);

    if (invalidEdit) {
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelector(
                `[data-tag-edit-modal="${modalId}"][data-tag-id="${CSS.escape(invalidEdit.tagId)}"]`
            )?.click();
        }, { once: true });
    }
})();
</script>
<?
            return $modal_id;
        }

        function print_edit_issue_modal(array $tags): ?string {
            $id = next_unique_id();
            $modal_id = 'edit-issue-modal-' . $id;
            $modal_title_id = $modal_id . '-title';
            $summary_id = $modal_id . '-summary';
            $tags_id = $modal_id . '-tags';
?>
<div class="modal" id="<?= $modal_id ?>" tabindex="-1" aria-labelledby="<?= $modal_title_id ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="<?= $modal_title_id ?>"><?= $this->config->view->issue_desc_short ?> bearbeiten</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <form method="post">
                <input type="hidden" name="req" value="edit-issue">
                <input type="hidden" name="request_uuid" value="<?= htmlspecialchars($this->issue_submission_token('edit-issue'), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="issue_id" value="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="<?= $tags_id ?>" class="form-label"><?= $this->config->view->tag_desc ?></label>
                        <select class="form-select tom-select" id="<?= $tags_id ?>" name="tags[]" multiple>
<?
            foreach ($tags as $tag) {
                echo '<option value="' . htmlspecialchars((string)$tag['id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">'
                    . htmlspecialchars($tag['name']) . '</option>';
            }
?>
                        </select>
                    </div>
                    <div>
                        <label for="<?= $summary_id ?>" class="form-label">Zusammenfassung</label>
                        <input type="text" class="form-control" id="<?= $summary_id ?>" name="summary" value="">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(() => {
    const modalId = <?= json_encode($modal_id, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const issueLabel = <?= json_encode($this->config->view->issue_desc_short, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const modal = document.getElementById(modalId);
    const form = modal?.querySelector('form');
    const title = modal?.querySelector('.modal-title');
    const issueIdInput = form?.querySelector('[name="issue_id"]');
    const summaryInput = form?.querySelector('[name="summary"]');
    const tagsSelect = form?.querySelector('[name="tags[]"]');

    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-issue-edit-modal]');
        if (!trigger || trigger.dataset.issueEditModal !== modalId || !form) return;

        form.reset();
        issueIdInput.value = trigger.dataset.issueId;
        summaryInput.value = trigger.dataset.issueSummary;

        const selectedTagIds = new Set(JSON.parse(trigger.dataset.issueTagIds || '[]').map(String));
        [...tagsSelect.options].forEach(option => {
            option.selected = selectedTagIds.has(option.value);
        });
        tagsSelect.tomselect?.sync?.();

        title.textContent = `${issueLabel} #${trigger.dataset.issueId} bearbeiten`;
    }, true);
})();
</script>
<?
            return $modal_id;
        }

        function print_edit_post_modal(
            ?HandleEditPostResult $handle_edit_post_res = null
        ): ?string {
            $id = next_unique_id();
            $modal_id = 'edit-post-modal-' . $id;
            $modal_title_id = $modal_id . '-title';
            $status_id = $modal_id . '-status';
            $content_id = $modal_id . '-content';
            $form_was_incomplete = $handle_edit_post_res !== null &&
                $handle_edit_post_res->kind() === HandleEditPostResultKind::ErrorIncomplete;
?>
<div class="modal" id="<?= $modal_id ?>" tabindex="-1" aria-labelledby="<?= $modal_title_id ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="<?= $modal_title_id ?>"><?= $this->config->view->post_desc_short ?> bearbeiten</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <form method="post">
                <input type="hidden" name="req" value="edit-post">
                <input type="hidden" name="request_uuid" value="<?= htmlspecialchars($this->issue_submission_token('edit-post'), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="issue_id" value="">
                <input type="hidden" name="post_id" value="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="<?= $status_id ?>" class="form-label">Status</label>
                        <select class="form-select" id="<?= $status_id ?>" name="new_status">
<?
            foreach (['open', 'closed'] as $status) {
                $label = ($this->config->view->status_to_label)($status);
?>
                            <option value="<?= $status ?>"><?= $label ?></option>
<?
            }
?>
                        </select>
                    </div>
                    <div>
                        <label for="<?= $content_id ?>" class="form-label">Beschreibung</label>
                        <textarea class="form-control<?= $form_was_incomplete ? ' is-invalid' : '' ?>" id="<?= $content_id ?>" name="content" rows="5"></textarea>
<?
            if ($form_was_incomplete) {
?>
                        <div class="invalid-feedback">
                            Bitte füllen Sie dieses Feld aus.
                        </div>
<?
            }
?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(() => {
    const modalId = <?= json_encode($modal_id, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const postLabel = <?= json_encode($this->config->view->post_desc_short, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const incompleteEdit = <?= json_encode($form_was_incomplete ? [
        'issueId' => (string)$handle_edit_post_res->issue_id(),
        'postId' => (string)$handle_edit_post_res->post_id(),
        'content' => $handle_edit_post_res->content,
        'status' => $handle_edit_post_res->selected_status
    ] : null, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const modal = document.getElementById(modalId);
    const form = modal?.querySelector('form');
    const title = modal?.querySelector('.modal-title');
    const issueIdInput = form?.querySelector('[name="issue_id"]');
    const postIdInput = form?.querySelector('[name="post_id"]');
    const statusSelect = form?.querySelector('[name="new_status"]');
    const contentInput = form?.querySelector('[name="content"]');

    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-post-edit-modal]');
        if (!trigger || trigger.dataset.postEditModal !== modalId || !form) return;

        form.reset();
        issueIdInput.value = trigger.dataset.issueId;
        postIdInput.value = trigger.dataset.postId;
        const isIncompleteEdit = incompleteEdit &&
            incompleteEdit.issueId === trigger.dataset.issueId &&
            incompleteEdit.postId === trigger.dataset.postId;
        statusSelect.value = isIncompleteEdit ? incompleteEdit.status : trigger.dataset.postStatus;
        contentInput.value = isIncompleteEdit ? incompleteEdit.content : trigger.dataset.postContent;
        title.textContent = `${postLabel} #${trigger.dataset.postId} bearbeiten`;
    }, true);

    if (incompleteEdit) {
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelector(
                `[data-post-edit-modal="${modalId}"][data-issue-id="${incompleteEdit.issueId}"][data-post-id="${incompleteEdit.postId}"]`
            )?.click();
        }, { once: true });
    }
})();
</script>
<?
            return $modal_id;
        }

        function print_delete_issue_modal(): ?string {
            $id = next_unique_id();
            $modal_id = 'delete-issue-modal-' . $id;
            $modal_title_id = $modal_id . '-title';
?>
<div class="modal" id="<?= $modal_id ?>" tabindex="-1" aria-labelledby="<?= $modal_title_id ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="<?= $modal_title_id ?>"><?= $this->config->view->issue_desc_short ?> löschen</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <form method="post">
                <input type="hidden" name="req" value="delete-issue">
                <input type="hidden" name="request_uuid" value="<?= htmlspecialchars($this->issue_submission_token('delete-issue'), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="issue_id" value="">
                <div class="modal-body">
                    <p data-delete-issue-confirmation></p>
                    <p><strong>Alle zugehörigen Einträge und Zuordnungen werden ebenfalls gelöscht. Dieser Vorgang kann nicht rückgängig gemacht werden.</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-danger">Endgültig löschen</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(() => {
    const modalId = <?= json_encode($modal_id, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const issueLabel = <?= json_encode($this->config->view->issue_desc_short, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const modal = document.getElementById(modalId);
    const form = modal?.querySelector('form');
    const title = modal?.querySelector('.modal-title');
    const confirmation = modal?.querySelector('[data-delete-issue-confirmation]');
    const issueIdInput = form?.querySelector('[name="issue_id"]');

    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-issue-delete-modal]');
        if (!trigger || trigger.dataset.issueDeleteModal !== modalId || !form) return;

        form.reset();
        issueIdInput.value = trigger.dataset.issueId;
        title.textContent = `${issueLabel} #${trigger.dataset.issueId} löschen`;
        confirmation.textContent = trigger.dataset.issueSummary
            ? `${issueLabel} #${trigger.dataset.issueId} „${trigger.dataset.issueSummary}“ wirklich löschen?`
            : `${issueLabel} #${trigger.dataset.issueId} wirklich löschen?`;
    }, true);
})();
</script>
<?
            return $modal_id;
        }

        function print_delete_post_modal(): ?string {
            $id = next_unique_id();
            $modal_id = 'delete-post-modal-' . $id;
            $modal_title_id = $modal_id . '-title';
?>
<div class="modal" id="<?= $modal_id ?>" tabindex="-1" aria-labelledby="<?= $modal_title_id ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="<?= $modal_title_id ?>"><?= $this->config->view->post_desc_short ?> löschen</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <form method="post">
                <input type="hidden" name="req" value="delete-post">
                <input type="hidden" name="request_uuid" value="<?= htmlspecialchars($this->issue_submission_token('delete-post'), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="issue_id" value="">
                <input type="hidden" name="post_id" value="">
                <div class="modal-body">
                    <p data-delete-post-confirmation></p>
                    <p data-delete-post-consequence hidden><strong></strong></p>
                    <p><strong>Dieser Vorgang kann nicht rückgängig gemacht werden.</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-danger">Endgültig löschen</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(() => {
    const modalId = <?= json_encode($modal_id, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const issueLabel = <?= json_encode($this->config->view->issue_desc_short, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const postLabel = <?= json_encode($this->config->view->post_desc_short, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const modal = document.getElementById(modalId);
    const form = modal?.querySelector('form');
    const title = modal?.querySelector('.modal-title');
    const confirmation = modal?.querySelector('[data-delete-post-confirmation]');
    const consequence = modal?.querySelector('[data-delete-post-consequence]');
    const issueIdInput = form?.querySelector('[name="issue_id"]');
    const postIdInput = form?.querySelector('[name="post_id"]');

    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-post-delete-modal]');
        if (!trigger || trigger.dataset.postDeleteModal !== modalId || !form) return;

        form.reset();
        issueIdInput.value = trigger.dataset.issueId;
        postIdInput.value = trigger.dataset.postId;
        title.textContent = `${postLabel} vom ${trigger.dataset.postCreatedOn} löschen`;
        confirmation.textContent = `${postLabel} vom ${trigger.dataset.postCreatedOn} aus ${issueLabel} #${trigger.dataset.issueId} wirklich löschen?`;

        const deletesIssue = Number(trigger.dataset.issuePostCount) === 1;
        consequence.hidden = !deletesIssue;
        consequence.querySelector('strong').textContent = deletesIssue
            ? `Dies ist der letzte Eintrag. ${issueLabel} #${trigger.dataset.issueId} wird ebenfalls gelöscht.`
            : '';
    }, true);
})();
</script>
<?
            return $modal_id;
        }

        function print_delete_additional_email_modal(): ?string {
            if (!$this->may_administer()) {
                return null;
            }

            $id = next_unique_id();
            $modal_id = 'delete-additional-email-modal-' . $id;
            $modal_title_id = $modal_id . '-title';
?>
<div class="modal" id="<?= $modal_id ?>" tabindex="-1" aria-labelledby="<?= $modal_title_id ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="<?= $modal_title_id ?>">Zusätzliche E-Mail-Adresse löschen</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <form method="post">
                <input type="hidden" name="req" value="delete-additional-email">
                <input type="hidden" name="request_uuid" value="<?= htmlspecialchars($this->issue_submission_token('delete-additional-email'), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="issue_id" value="">
                <input type="hidden" name="additional_email_id" value="">
                <div class="modal-body">
                    <p data-delete-additional-email-confirmation></p>
                    <p><strong>Die Adresse erhält danach keine Benachrichtigungen mehr über diesen Eintrag. Dieser Vorgang kann nicht rückgängig gemacht werden.</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-danger">Endgültig löschen</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(() => {
    const modalId = <?= json_encode($modal_id, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const issueLabel = <?= json_encode($this->config->view->issue_desc_short, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const modal = document.getElementById(modalId);
    const form = modal?.querySelector('form');
    const confirmation = modal?.querySelector('[data-delete-additional-email-confirmation]');
    const issueIdInput = form?.querySelector('[name="issue_id"]');
    const additionalEmailIdInput = form?.querySelector('[name="additional_email_id"]');

    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-additional-email-delete-modal]');
        if (!trigger || trigger.dataset.additionalEmailDeleteModal !== modalId || !form) return;

        form.reset();
        issueIdInput.value = trigger.dataset.issueId;
        additionalEmailIdInput.value = trigger.dataset.additionalEmailId;
        confirmation.textContent = `E-Mail-Adresse ${trigger.dataset.emailAddress} vom ${trigger.dataset.createdOn} aus ${issueLabel} #${trigger.dataset.issueId} wirklich löschen?`;
    }, true);
})();
</script>
<?
            return $modal_id;
        }

        private function print_author_override_fields(bool $form_was_incomplete): void {
            $openid_provider_names = $this->openid_provider_names();
            if (!$this->may_administer() || count($openid_provider_names) === 0) {
                return;
            }

            $id = next_unique_id();
            $set_other_openid_author = $form_was_incomplete && isset($_POST['set_other_openid_author']);
            $selected_openid_provider = $set_other_openid_author && is_string($_POST['author_openid_provider'] ?? null)
                ? $_POST['author_openid_provider']
                : $openid_provider_names[0];
            $other_openid_id = $set_other_openid_author && is_string($_POST['author_openid_id'] ?? null)
                ? $_POST['author_openid_id']
                : '';
            $other_openid_email = $set_other_openid_author && is_string($_POST['author_openid_email'] ?? null)
                ? $_POST['author_openid_email']
                : '';
            $author_override_id = 'form-author-override-' . $id;
            $author_override_fields_id = $author_override_id . '-fields';
?>
            <div class="mt-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="<?= $author_override_id ?>" name="set_other_openid_author" value="1" aria-controls="<?= $author_override_fields_id ?>"<?= $set_other_openid_author ? ' checked' : '' ?>>
                    <label class="form-check-label" for="<?= $author_override_id ?>">
                        Anderen OpenID-Benutzer als Autor setzen
                    </label>
                </div>
                <div id="<?= $author_override_fields_id ?>" class="mt-3<?= $set_other_openid_author ? '' : ' d-none' ?>">
                    <div class="mb-3">
                        <label for="form-author-openid-provider-<?= $id ?>" class="form-label">OpenID-Provider</label>
                        <select class="form-select" id="form-author-openid-provider-<?= $id ?>" name="author_openid_provider" data-author-override-required>
<?
            foreach ($openid_provider_names as $provider_name) {
                $selected = $provider_name === $selected_openid_provider ? ' selected' : '';
                echo '<option value="' . htmlspecialchars($provider_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"' . $selected . '>'
                    . htmlspecialchars($provider_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</option>';
            }
?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="form-author-openid-id-<?= $id ?>" class="form-label">OpenID-ID</label>
                        <input class="form-control" type="text" id="form-author-openid-id-<?= $id ?>" name="author_openid_id" value="<?= htmlspecialchars($other_openid_id, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="<?= htmlspecialchars($this->config->view->author_openid_id_placeholder, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-author-override-required>
                    </div>
                    <div>
                        <label for="form-author-openid-email-<?= $id ?>" class="form-label">E-Mail-Adresse (optional)</label>
                        <input class="form-control" type="email" id="form-author-openid-email-<?= $id ?>" name="author_openid_email" value="<?= htmlspecialchars($other_openid_email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                    </div>
                </div>
            </div>
            <script>
                (() => {
                    const checkbox = document.getElementById('<?= $author_override_id ?>');
                    const fields = document.getElementById('<?= $author_override_fields_id ?>');
                    const controls = fields?.querySelectorAll('select, input') ?? [];
                    const update = () => {
                        const enabled = checkbox?.checked ?? false;
                        fields?.classList.toggle('d-none', !enabled);
                        controls.forEach(control => {
                            control.disabled = !enabled;
                            control.required = enabled && control.hasAttribute('data-author-override-required');
                        });
                    };
                    checkbox?.addEventListener('change', update);
                    update();
                })();
            </script>
<?
        }

        // Precondition: Any handling result's issue_id must correspond to `$issue`.
        function print_issue_as_acc_item(
            array $issue,
            ?HandleNewPostResult     $handle_new_post_res,
            ?HandleEditIssueResult   $handle_edit_issue_res,
            ?HandleEditPostResult    $handle_edit_post_res,
            ?HandleDeleteIssueResult $handle_delete_issue_res,
            ?HandleDeletePostResult  $handle_delete_post_res,
            bool $show_issue_admin_actions,
            bool $may_administer_issue,
            bool $show_post_admin_actions,
            array $may_administer_posts,
            ?string $edit_issue_modal_id,
            ?string $edit_post_modal_id,
            ?string $delete_issue_modal_id,
            ?string $delete_post_modal_id,
            ?HandleNewAdditionalEmailResult $handle_new_additional_email_res = null,
            ?HandleDeleteAdditionalEmailResult $handle_delete_additional_email_res = null,
            ?string $delete_additional_email_modal_id = null
        ) {
            $issue_status_variant = ($this->config->view->status_to_variant)($issue['last_post_status']);

            $header_actions_html = '';
            if ($show_issue_admin_actions && ($edit_issue_modal_id !== null || $delete_issue_modal_id !== null)) {
                ob_start();
                if ($edit_issue_modal_id !== null) {
                    if ($may_administer_issue) {
                        $issue_tag_ids_json = json_encode(array_values(array_map('strval', $issue['tag_ids'])));
?>
                <button type="button" class="accordion-header-action" data-bs-toggle="modal" data-bs-target="#<?= $edit_issue_modal_id ?>" data-issue-edit-modal="<?= $edit_issue_modal_id ?>" data-issue-id="<?= htmlspecialchars((string)$issue['id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-issue-summary="<?= htmlspecialchars($issue['summary'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-issue-tag-ids="<?= htmlspecialchars($issue_tag_ids_json, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" aria-label="<?= $this->config->view->issue_desc_short ?> bearbeiten" title="<?= $this->config->view->issue_desc_short ?> bearbeiten">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 13l1-3 7.5-7.5a1.4 1.4 0 0 1 2 2L6 12l-3 1z"></path>
                        <path d="M10 4l2 2M4 10l2 2"></path>
                    </svg>
                </button>
<?
                    }
                    else {
?>
                <span class="accordion-header-action" style="box-sizing: border-box; cursor: default; pointer-events: none;" aria-hidden="true"></span>
<?
                    }
                }
                if ($delete_issue_modal_id !== null) {
                    if ($may_administer_issue) {
?>
                <button type="button" class="accordion-header-action" data-bs-toggle="modal" data-bs-target="#<?= $delete_issue_modal_id ?>" data-issue-delete-modal="<?= $delete_issue_modal_id ?>" data-issue-id="<?= htmlspecialchars((string)$issue['id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-issue-summary="<?= htmlspecialchars($issue['summary'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" aria-label="<?= $this->config->view->issue_desc_short ?> löschen" title="<?= $this->config->view->issue_desc_short ?> löschen">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 4h10M6 4V2h4v2M5 6l.6 8h4.8l.6-8M7 7v5M9 7v5"></path>
                    </svg>
                </button>
<?
                    }
                    else {
?>
                <span class="accordion-header-action" style="box-sizing: border-box; cursor: default; pointer-events: none;" aria-hidden="true"></span>
<?
                    }
                }
                $header_actions_html = ob_get_clean();
            }

            // Prepare the accordion item's header
            ob_start();
?>
        <span style="display: flex; column-gap: 1rem; width: 100%; text-align: left;">
            <span style="flex: 0 0 <?= $this->config->view->issue_column_width ?>;">
                <strong>#<?= $issue['id'] ?></strong>
            </span>
            <span style="flex: 1 1 <?= $this->config->view->tag_column_width ?>; min-width: 0; overflow-wrap: anywhere;">
                <?= $issue['summary'] !== null ? htmlspecialchars($issue['summary']) : '' ?>
            </span>
            <span style="flex: 0 1 <?= $this->config->view->tag_column_width ?>; min-width: 0; max-width: <?= $this->config->view->tag_column_width ?>; overflow-wrap: anywhere;">
<?
            if ($issue['tags'] != '') {
?>
            <span><?= $this->config->view->tag_desc ?>: <strong><?= htmlspecialchars($issue['tags']) ?></strong></span>
<?
            }
?>
            </span>
        </span>
<?
            $header_html = ob_get_clean();
?>
    <? acc_item_start($header_html, variant: $issue_status_variant, id: IssueTracker::make_view_issue_html_id($issue['id']), open: $handle_new_post_res !== null || $handle_new_additional_email_res !== null || $handle_edit_issue_res !== null || $handle_edit_post_res !== null || $handle_delete_issue_res !== null || $handle_delete_post_res !== null || $handle_delete_additional_email_res !== null, header_actions: $header_actions_html); ?>
    <div class="d-flex flex-column gap-3">
        <table class="table" style="margin-bottom: 0;">
            <thead>
                <tr>
<?
            if ($show_post_admin_actions) {
?>
                    <th scope="col" aria-label="Bearbeiten" style="width: 2.25rem;"></th>
                    <th scope="col" aria-label="Löschen" style="width: 2.25rem;"></th>
<?
            }
?>
                    <th style="width:<?= $this->config->view->date_column_width ?>;">Datum</th>
                    <th style="width:<?= $this->config->view->author_column_width ?>;">Autor/in</th>
                    <th><?= $this->config->view->post_desc_short ?></th>
                    <th style="width:<?= $this->config->view->status_column_width ?>;">Status</th>
                </tr>
            </thead>
            <tbody>
<?
    $timeline_entries = [];
    foreach ($issue['posts'] as $post) {
        $timeline_entries[] = ['kind' => 'post', 'sort_kind' => 0, 'data' => $post];
    }
    foreach ($issue['additional_emails'] as $additional_email) {
        $timeline_entries[] = ['kind' => 'additional_email', 'sort_kind' => 1, 'data' => $additional_email];
    }
    usort($timeline_entries, function (array $left, array $right): int {
        $created_on_comparison = strcmp($left['data']['created_on'], $right['data']['created_on']);
        if ($created_on_comparison !== 0) {
            return $created_on_comparison;
        }

        $kind_comparison = $left['sort_kind'] <=> $right['sort_kind'];
        return $kind_comparison !== 0
            ? $kind_comparison
            : ((int)$left['data']['id'] <=> (int)$right['data']['id']);
    });
    $last_post_id = count($issue['posts']) > 0
        ? $issue['posts'][count($issue['posts']) - 1]['id']
        : null;

    foreach ($timeline_entries as $entry) {
        $is_post = $entry['kind'] === 'post';
        $entry_data = $entry['data'];
        $is_last_post = $is_post && $entry_data['id'] === $last_post_id;
        $may_administer_post = $is_post && $may_administer_posts[$entry_data['id']];

        $timestamp = strtotime($entry_data['created_on']);
        $timestamp_short = date('d.m.Y', $timestamp);
        $timestamp_long  = date('d.m.Y, H:i:s', $timestamp);
        $author_label = ($this->config->view->author_identity_to_label)($entry_data['author_identity']);
?>
                <tr id="issue-<?= $issue['id'] ?>-<?= $is_post ? 'post' : 'additional-email' ?>-<?= $entry_data['id'] ?>" data-timeline-entry="<?= $is_post ? 'post' : 'additional-email' ?>">
<?
        if ($show_post_admin_actions) {
?>
                    <td>
<?
            if ($is_post && $edit_post_modal_id !== null && $may_administer_post) {
?>
                        <button type="button" class="btn btn-secondary" style="display: inline-flex; align-items: center; justify-content: center; padding: .25rem; line-height: 1;" data-bs-toggle="modal" data-bs-target="#<?= $edit_post_modal_id ?>" data-post-edit-modal="<?= $edit_post_modal_id ?>" data-issue-id="<?= htmlspecialchars((string)$issue['id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-post-id="<?= htmlspecialchars((string)$entry_data['id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-post-status="<?= htmlspecialchars($entry_data['new_status'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-post-content="<?= htmlspecialchars($entry_data['content'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" aria-label="<?= $this->config->view->post_desc_short ?> bearbeiten" title="<?= $this->config->view->post_desc_short ?> bearbeiten">
                            <svg viewBox="0 0 16 16" width=".9rem" height=".9rem" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 13l1-3 7.5-7.5a1.4 1.4 0 0 1 2 2L6 12l-3 1z"></path>
                                <path d="M10 4l2 2M4 10l2 2"></path>
                            </svg>
                        </button>
<?
            }
?>
                    </td>
                    <td>
<?
            if ($is_post && $delete_post_modal_id !== null && $may_administer_post) {
?>
                        <button type="button" class="btn btn-secondary" style="display: inline-flex; align-items: center; justify-content: center; padding: .25rem; line-height: 1;" data-bs-toggle="modal" data-bs-target="#<?= $delete_post_modal_id ?>" data-post-delete-modal="<?= $delete_post_modal_id ?>" data-issue-id="<?= htmlspecialchars((string)$issue['id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-post-id="<?= htmlspecialchars((string)$entry_data['id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-post-created-on="<?= htmlspecialchars($timestamp_long, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-issue-post-count="<?= count($issue['posts']) ?>" aria-label="<?= $this->config->view->post_desc_short ?> löschen" title="<?= $this->config->view->post_desc_short ?> löschen">
                            <svg viewBox="0 0 16 16" width=".9rem" height=".9rem" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 4h10M6 4V2h4v2M5 6l.6 8h4.8l.6-8M7 7v5M9 7v5"></path>
                            </svg>
                        </button>
<?
            }
            elseif (!$is_post && $delete_additional_email_modal_id !== null && $this->may_administer()) {
?>
                        <button type="button" class="btn btn-secondary" style="display: inline-flex; align-items: center; justify-content: center; padding: .25rem; line-height: 1;" data-bs-toggle="modal" data-bs-target="#<?= $delete_additional_email_modal_id ?>" data-additional-email-delete-modal="<?= $delete_additional_email_modal_id ?>" data-issue-id="<?= htmlspecialchars((string)$issue['id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-additional-email-id="<?= htmlspecialchars((string)$entry_data['id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-email-address="<?= htmlspecialchars($entry_data['email_address'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-created-on="<?= htmlspecialchars($timestamp_long, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" aria-label="Zusätzliche E-Mail-Adresse löschen" title="Zusätzliche E-Mail-Adresse löschen">
                            <svg viewBox="0 0 16 16" width=".9rem" height=".9rem" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 4h10M6 4V2h4v2M5 6l.6 8h4.8l.6-8M7 7v5M9 7v5"></path>
                            </svg>
                        </button>
<?
            }
?>
                    </td>
<?
        }
?>
                    <td>
                        <span data-bs-toggle="tooltip" title="<?= $timestamp_long ?>">
                            <?= $timestamp_short ?>
                        </span>
                    </td>
                    <td class="text-truncate" style="max-width:<?= $this->config->view->author_column_width ?>;">
                        <span data-bs-toggle="tooltip" title="<?= htmlspecialchars($author_label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-bs-placement="left">
                            <?= htmlspecialchars($author_label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                        </span>
                    </td>
                    <td>
<?
        if ($is_post) {
?>
                        <?= nl2br(htmlspecialchars($entry_data['content'])) ?>
<?
        }
        else {
?>
                        E-Mail-Adresse <strong><?= htmlspecialchars($entry_data['email_address'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong> zur Benachrichtigung hinzugefügt.
<?
        }
?>
                    </td>
                    <td>
<?
        if ($is_post) {
            $post_status_variant = ($this->config->view->status_to_variant)($entry_data['new_status']);
            $post_status_label = ($this->config->view->status_to_label)($entry_data['new_status']);
            $post_status_opacity = $is_last_post ? '100' : '50';
?>
                        <span class="badge bg-<?= $post_status_variant ?> bg-opacity-<?= $post_status_opacity ?>">
                            <?= $post_status_label ?>
                        </span>
<?
        }
?>
                    </td>
                </tr>
<?
    }
?>
            </tbody>
        </table>
<?
    ///////////////////
    // New post form //
    ///////////////////

    if ($this->may_write()) {
        // Show form only if there was a problem with the last submission
        $new_post_form_was_incomplete = $handle_new_post_res !== null && $handle_new_post_res->kind() === HandleNewPostResultKind::ErrorIncomplete;
        $current_author_label = ($this->config->view->author_identity_to_label)(
            $this->current_user_identity_serialized()
        );
?>
        <div>
            <div class="d-flex">
<?
    $new_post_collapse_id = collapse_button($this->config->view->post_desc_short . ' hinzufügen', variant: 'secondary');
?>
            </div>
            <div id="<?= $new_post_collapse_id ?>" class="collapse<?= $new_post_form_was_incomplete ? ' show' : '' ?>">
                <form method="post">
                    <input type="hidden" name="req" value="new-post">
                    <input type="hidden" name="request_uuid" value="<?= htmlspecialchars($this->issue_submission_token('new-post', (int)$issue['id']), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="issue_id" value="<?= $issue['id'] ?>">
                    <table class="table">
                        <tbody>
                            <tr>
<?
    if ($show_post_admin_actions) {
?>
                                <td style="width: 2.25rem;"></td>
                                <td style="width: 2.25rem;"></td>
<?
    }
?>
                                <td style="width:<?= $this->config->view->date_column_width ?>;" class="text-center">
                                    <em>Jetzt</em>
                                </td>
                                <td class="text-truncate" style="width:<?= $this->config->view->author_column_width ?>; max-width:<?= $this->config->view->author_column_width ?>;">
                                    <span data-bs-toggle="tooltip" title="<?= htmlspecialchars($current_author_label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-bs-placement="left">
                                        <?= htmlspecialchars($current_author_label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="input-group has-validation">
                                        <textarea class="form-control<?= $new_post_form_was_incomplete ? ' is-invalid' : '' ?>" name="content" rows="3"></textarea>
<?
    if ($new_post_form_was_incomplete) {
?>
                                        <div class="invalid-feedback">
                                            Bitte füllen Sie dieses Feld aus.
                                        </div>
<?
    }
?>
                                    </div>
                                </td>
                                <td style="width:<?= $this->config->view->status_column_width ?>;">
                                    <select class="form-select" id="new_status" name="new_status">
<?
    $status_options = ['open', 'closed'];

    foreach ($status_options as $status) {
        $label = ($this->config->view->status_to_label)($status);

        if (
            ($handle_new_post_res !== null &&
            $handle_new_post_res->kind() === HandleNewPostResultKind::ErrorIncomplete &&
            $handle_new_post_res->selected_status === $status) ||
            ($handle_new_post_res === null && $issue['last_post_status'] === $status)
        ) {
?>
                                        <option value="<?= $status ?>" selected><?= $label ?></option>
<?
        }
        else {
?>
                                        <option value="<?= $status ?>"><?= $label ?></option>
<?
        }
    }
?>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
<?
        $this->print_author_override_fields($new_post_form_was_incomplete);
?>
                    <div class="d-flex justify-content-center mt-4">
                        <button type="submit" class="btn btn-primary">Abschicken</button>
                    </div>
                </form>
            </div>
        </div>
<?
    }

    if ($this->may_administer()) {
        $new_additional_email_form_has_error = $handle_new_additional_email_res !== null && in_array(
            $handle_new_additional_email_res->kind(),
            [
                HandleNewAdditionalEmailResultKind::ErrorIncomplete,
                HandleNewAdditionalEmailResultKind::ErrorDuplicate
            ],
            true
        );
        $new_additional_email_value = $new_additional_email_form_has_error
            ? $handle_new_additional_email_res->email_address
            : '';
        $additional_email_author_label = ($this->config->view->author_identity_to_label)(
            $this->current_user_identity_serialized()
        );
?>
        <div>
            <div class="d-flex">
<?
        $new_additional_email_collapse_id = collapse_button('E-Mail-Adresse hinzufügen', variant: 'secondary');
?>
            </div>
            <div id="<?= $new_additional_email_collapse_id ?>" class="collapse<?= $new_additional_email_form_has_error ? ' show' : '' ?>">
                <form method="post" class="mt-3">
                    <input type="hidden" name="req" value="new-additional-email">
                    <input type="hidden" name="request_uuid" value="<?= htmlspecialchars($this->issue_submission_token('new-additional-email', (int)$issue['id']), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="issue_id" value="<?= $issue['id'] ?>">
                    <div>
                        <label for="new-additional-email-<?= $issue['id'] ?>" class="form-label">E-Mail-Adresse</label>
                        <input type="email" class="form-control<?= $new_additional_email_form_has_error ? ' is-invalid' : '' ?>" id="new-additional-email-<?= $issue['id'] ?>" name="email_address" value="<?= htmlspecialchars($new_additional_email_value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" required>
<?
        if ($new_additional_email_form_has_error) {
            $additional_email_error_message = $handle_new_additional_email_res->kind() === HandleNewAdditionalEmailResultKind::ErrorDuplicate
                ? 'Diese E-Mail-Adresse wurde bereits hinzugefügt.'
                : 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
?>
                        <div class="invalid-feedback"><?= $additional_email_error_message ?></div>
<?
        }
?>
                    </div>
                    <div class="d-flex justify-content-center mt-3">
                        <button type="submit" class="btn btn-primary">Hinzufügen</button>
                    </div>
                    <div class="form-text">Autor/in: <?= htmlspecialchars($additional_email_author_label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
                </form>
            </div>
        </div>
<?
    }
?>
    </div>
    <? acc_item_end(); ?>
<?
        }

        function print_issues_as_acc(
            array $issues_to_posts,
            string $message_none,
            ?HandleNewPostResult $handle_new_post_res,
            ?HandleEditIssueResult $handle_edit_issue_res,
            ?HandleEditPostResult $handle_edit_post_res,
            ?HandleDeleteIssueResult $handle_delete_issue_res,
            ?HandleDeletePostResult $handle_delete_post_res,
            ?string $edit_issue_modal_id,
            ?string $edit_post_modal_id,
            ?string $delete_issue_modal_id,
            ?string $delete_post_modal_id,
            ?HandleNewAdditionalEmailResult $handle_new_additional_email_res = null,
            ?HandleDeleteAdditionalEmailResult $handle_delete_additional_email_res = null,
            ?string $delete_additional_email_modal_id = null
        ) {
            if (count($issues_to_posts) > 0) {
                $table_min_width = htmlspecialchars($this->config->view->table_min_width, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $may_administer_issues = [];
                $show_issue_admin_actions = false;
                $may_administer_posts = [];
                $show_post_admin_actions = [];
                foreach ($issues_to_posts as $issue) {
                    $may_administer_issue = $this->may_administer_issue($issue);
                    $may_administer_issues[$issue['id']] = $may_administer_issue;
                    $show_issue_admin_actions = $show_issue_admin_actions || $may_administer_issue;

                    $may_administer_posts[$issue['id']] = [];
                    $show_post_admin_actions[$issue['id']] = false;
                    foreach ($issue['posts'] as $post) {
                        $may_administer_post = $this->may_administer_post($post);
                        $may_administer_posts[$issue['id']][$post['id']] = $may_administer_post;
                        $show_post_admin_actions[$issue['id']] =
                            $show_post_admin_actions[$issue['id']] || $may_administer_post;
                    }
                }
?>
<div class="table-responsive">
    <? acc_start(style: 'min-width: ' . $table_min_width . ';'); ?>
<?
                foreach ($issues_to_posts as $issue) {
                    $issue_handle_post_res = (
                        $handle_new_post_res != null &&
                        $handle_new_post_res->issue_id() === $issue['id']
                    ) ? $handle_new_post_res : null;
                    $issue_handle_new_additional_email_res = (
                        $handle_new_additional_email_res !== null &&
                        $handle_new_additional_email_res->issue_id() === $issue['id']
                    ) ? $handle_new_additional_email_res : null;
                    $issue_handle_edit_res = (
                        $handle_edit_issue_res != null &&
                        $handle_edit_issue_res->issue_id() === $issue['id']
                    ) ? $handle_edit_issue_res : null;
                    $issue_handle_edit_post_res = (
                        $handle_edit_post_res != null &&
                        $handle_edit_post_res->issue_id() === $issue['id']
                    ) ? $handle_edit_post_res : null;
                    $issue_handle_delete_res = (
                        $handle_delete_issue_res != null &&
                        $handle_delete_issue_res->issue_id() === $issue['id']
                    ) ? $handle_delete_issue_res : null;
                    $issue_handle_delete_post_res = (
                        $handle_delete_post_res != null &&
                        $handle_delete_post_res->issue_id() === $issue['id']
                    ) ? $handle_delete_post_res : null;
                    $issue_handle_delete_additional_email_res = (
                        $handle_delete_additional_email_res !== null &&
                        $handle_delete_additional_email_res->issue_id() === $issue['id']
                    ) ? $handle_delete_additional_email_res : null;

                    $this->print_issue_as_acc_item(
                        $issue,
                        handle_new_post_res: $issue_handle_post_res,
                        handle_new_additional_email_res: $issue_handle_new_additional_email_res,
                        handle_edit_issue_res: $issue_handle_edit_res,
                        handle_edit_post_res: $issue_handle_edit_post_res,
                        handle_delete_issue_res: $issue_handle_delete_res,
                        handle_delete_post_res: $issue_handle_delete_post_res,
                        handle_delete_additional_email_res: $issue_handle_delete_additional_email_res,
                        show_issue_admin_actions: $show_issue_admin_actions,
                        may_administer_issue: $may_administer_issues[$issue['id']],
                        show_post_admin_actions: $show_post_admin_actions[$issue['id']],
                        may_administer_posts: $may_administer_posts[$issue['id']],
                        edit_issue_modal_id: $edit_issue_modal_id,
                        edit_post_modal_id: $edit_post_modal_id,
                        delete_issue_modal_id: $delete_issue_modal_id,
                        delete_post_modal_id: $delete_post_modal_id,
                        delete_additional_email_modal_id: $delete_additional_email_modal_id
                    );
                }
?>
    <? acc_end(); ?>
</div>
<?
            }
            else {
?>
<p>
    <em><?= $message_none ?></em>
</p>
<?
            }
        }

        /** @param ?array{kind: string, subject?: string, content?: string, sent?: list<array{name: string, addresses: list<string>}>, skipped?: list<string>, failed?: list<array{name: string, addresses: list<string>, error: string}>, details?: string} $result */
        function print_handle_mail_tags_res(?array $result): void {
            if ($result === null) {
                return;
            }
            if ($result['kind'] === 'incomplete') {
                echo '<div class="alert alert-danger">Bitte füllen Sie Betreff und Inhalt aus.</div>';
                return;
            }
            if ($result['kind'] === 'error_db') {
                echo '<div class="alert alert-danger">Die E-Mails konnten wegen eines Datenbankfehlers nicht vorbereitet werden.<pre>'
                    . htmlspecialchars($result['details'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    . '</pre></div>';
                return;
            }

            echo '<div class="alert ' . ($result['failed'] === [] ? 'alert-success' : 'alert-warning') . '">';
            echo '<p><strong>Gesendet: ' . count($result['sent']) . '</strong></p>';
            if ($result['sent'] !== []) {
                echo '<ul>';
                foreach ($result['sent'] as $sent) {
                    echo '<li>' . htmlspecialchars($sent['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '<ul>';
                    foreach ($sent['addresses'] as $address) {
                        echo '<li>' . htmlspecialchars($address, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>';
                    }
                    echo '</ul></li>';
                }
                echo '</ul>';
            }
            echo '<p><strong>Übersprungen (keine zusätzlichen E-Mail-Adressen): ' . count($result['skipped']) . '</strong></p>';
            if ($result['skipped'] !== []) {
                echo '<ul>';
                foreach ($result['skipped'] as $name) {
                    echo '<li>' . htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>';
                }
                echo '</ul>';
            }
            echo '<p><strong>Fehlgeschlagen: ' . count($result['failed']) . '</strong></p>';
            if ($result['failed'] !== []) {
                echo '<ul>';
                foreach ($result['failed'] as $failure) {
                    echo '<li>' . htmlspecialchars($failure['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                        . ': ' . htmlspecialchars($failure['error'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '<ul>';
                    foreach ($failure['addresses'] as $address) {
                        echo '<li>' . htmlspecialchars($address, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>';
                    }
                    echo '</ul></li>';
                }
                echo '</ul>';
            }
            echo '</div>';
        }

        /** @param ?array{kind: string, subject?: string, content?: string} $result */
        function print_mail_tags_modal(?array $result = null): ?string {
            if (!$this->may_administer() || $this->config->mail === null) {
                return null;
            }

            $modal_id = 'mail-tags-modal-' . next_unique_id();
            $subject_id = $modal_id . '-subject';
            $content_id = $modal_id . '-content';
            $invalid = $result !== null && $result['kind'] === 'incomplete';
            $restore_form = $result !== null && in_array($result['kind'], ['incomplete', 'error_db'], true);
?>
<div class="modal" id="<?= $modal_id ?>" tabindex="-1" aria-labelledby="<?= $modal_id ?>-title" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="<?= $modal_id ?>-title">E-Mails an alle <?= htmlspecialchars($this->config->view->tag_desc, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> schreiben</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <form method="post">
                <input type="hidden" name="req" value="mail-tags">
                <input type="hidden" name="request_uuid" value="<?= htmlspecialchars($this->issue_submission_token('mail-tags'), ENT_QUOTES, 'UTF-8') ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="<?= $subject_id ?>" class="form-label">Betreff</label>
                        <input type="text" class="form-control<?= $invalid && trim($result['subject']) === '' ? ' is-invalid' : '' ?>" id="<?= $subject_id ?>" name="subject" value="<?= htmlspecialchars($restore_form ? $result['subject'] : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" required>
                        <div class="invalid-feedback">Bitte geben Sie einen Betreff ein.</div>
                    </div>
                    <div class="mb-3">
                        <label for="<?= $content_id ?>" class="form-label">Inhalt</label>
                        <textarea class="form-control<?= $invalid && trim($result['content']) === '' ? ' is-invalid' : '' ?>" id="<?= $content_id ?>" name="content" rows="8" required><?= htmlspecialchars($restore_form ? $result['content'] : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
                        <div class="invalid-feedback">Bitte geben Sie einen Inhalt ein.</div>
                        <div class="form-text">In Betreff und Inhalt können Sie <code>{{tag}}</code> für den Namen, <code>{{url}}</code> für die URL und <code>{{client_data}}</code> für <?= htmlspecialchars($this->config->view->tag_client_data_desc, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> verwenden. Ist für <code>{{url}}</code> oder <code>{{client_data}}</code> kein Wert hinterlegt, wird <code>(nicht vorhanden)</code> eingesetzt.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">E-Mails senden</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?
            if ($invalid) {
?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelector('[data-mail-tags-modal="<?= $modal_id ?>"]')?.click();
}, { once: true });
</script>
<?
            }
            return $modal_id;
        }

        function print_tag_management(
            array $tags,
            ?string $new_tag_modal_id,
            ?string $edit_tag_modal_id,
            ?string $mail_tags_modal_id = null
        ): void {
            $may_administer = $this->may_administer();

            $tag_desc = htmlspecialchars(
                $this->config->view->tag_desc,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );
            $tag_desc_singular = htmlspecialchars(
                $this->config->view->tag_desc_singular,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );
?>
<div class="table-responsive">
    <table class="table align-middle" data-tag-management>
        <thead>
            <tr>
<? if ($may_administer) { ?>
                <th scope="col" aria-label="Bearbeiten" style="width: 2.75rem;"></th>
<? } ?>
                <th scope="col">Name</th>
                <th scope="col">Link</th>
                <th scope="col"><?= htmlspecialchars($this->config->view->tag_client_data_desc, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></th>
                <th scope="col">Zusätzliche E-Mail-Adressen</th>
                <th scope="col" class="text-end"><?= htmlspecialchars($this->config->view->unsolved_issue_count_desc, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></th>
                <th scope="col" class="text-end"><?= htmlspecialchars($this->config->view->solved_issue_count_desc, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></th>
            </tr>
        </thead>
        <tbody>
<?
            foreach ($tags as $tag) {
?>
            <tr data-tag-row-id="<?= htmlspecialchars($tag['id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
<?
                if ($may_administer) {
?>
                <td>
<?
                    if ($edit_tag_modal_id !== null) {
?>
                    <button type="button" class="btn btn-secondary" style="display: inline-flex; align-items: center; justify-content: center; padding: .25rem; line-height: 1;" data-bs-toggle="modal" data-bs-target="#<?= $edit_tag_modal_id ?>" data-tag-edit-modal="<?= $edit_tag_modal_id ?>" data-tag-id="<?= htmlspecialchars($tag['id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-tag-name="<?= htmlspecialchars($tag['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-tag-url="<?= htmlspecialchars($tag['url'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-tag-has-client-data="<?= $tag['client_data'] !== null ? '1' : '0' ?>" data-tag-client-data="<?= htmlspecialchars($tag['client_data'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-tag-additional-email-addresses="<?= htmlspecialchars(json_encode($tag['additional_email_addresses']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" aria-label="<?= $tag_desc_singular ?> <?= htmlspecialchars($tag['id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> bearbeiten" title="<?= $tag_desc_singular ?> bearbeiten">
                        <svg viewBox="0 0 16 16" width=".9rem" height=".9rem" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 13l1-3 7.5-7.5a1.4 1.4 0 0 1 2 2L6 12l-3 1z"></path>
                            <path d="M10 4l2 2M4 10l2 2"></path>
                        </svg>
                    </button>
<?
                    }
?>
                </td>
<?
                }
?>
                <td><?= htmlspecialchars($tag['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                <td style="overflow-wrap: anywhere;" data-tag-url-cell>
<?
                if ($tag['url'] === null) {
                    echo '<span class="text-body-secondary">—</span>';
                }
                else {
                    $url_html = htmlspecialchars($tag['url'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    echo $this->valid_tag_url($tag['url'])
                        ? '<a href="' . $url_html . '" target="_blank" rel="noopener noreferrer">Link</a>'
                        : $url_html;
                }
?>
                </td>
                <td style="white-space: pre-wrap;" data-tag-client-data-cell><?= $this->tag_client_data_html($tag['client_data']) ?></td>
                <td data-tag-additional-email-addresses-cell>
<?
                if (count($tag['additional_email_addresses']) === 0) {
                    echo '<span class="text-body-secondary">—</span>';
                }
                else {
                    echo '<ul class="mb-0">';
                    foreach ($tag['additional_email_addresses'] as $email_address) {
                        echo '<li>' . htmlspecialchars($email_address, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>';
                    }
                    echo '</ul>';
                }
?>
                </td>
                <td class="text-end" data-unsolved-issue-count><?= $tag['unsolved_issue_count'] ?></td>
                <td class="text-end" data-solved-issue-count><?= $tag['solved_issue_count'] ?></td>
            </tr>
<?
            }
?>
        </tbody>
    </table>
</div>
<?
            if ($may_administer && $new_tag_modal_id !== null) {
?>
<button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#<?= $new_tag_modal_id ?>" data-tag-new-modal="<?= $new_tag_modal_id ?>"><?= $tag_desc_singular ?> hinzufügen</button>
<?
            }
            if ($may_administer && $mail_tags_modal_id !== null) {
?>
<button type="button" class="btn btn-secondary mt-3" data-bs-toggle="modal" data-bs-target="#<?= $mail_tags_modal_id ?>" data-mail-tags-modal="<?= $mail_tags_modal_id ?>">E-Mails an alle <?= $tag_desc ?> schreiben</button>
<?
            }
            elseif ($may_administer && $this->config->mail === null) {
?>
<button type="button" class="btn btn-secondary mt-3" disabled>E-Mails an alle <?= $tag_desc ?> schreiben</button>
<div class="form-text">E-Mail-Versand ist nicht konfiguriert.</div>
<?
            }
        }

        function print_new_issue_form(
            array $tags,
            ?HandleNewIssueResult $handle_issue_res,
            array $preselected_tag_names = [],
            array $preselected_tag_ids = []
        ) {
            if (!$this->may_write()) {
                return;
            }

            $id = next_unique_id();
            $form_was_incomplete = $handle_issue_res !== null && $handle_issue_res->kind() === HandleNewIssueResultKind::ErrorIncomplete;
            $incomplete_selected_tag_ids = $form_was_incomplete
                ? array_map('strval', $handle_issue_res->selected_tags)
                : [];
            $preselected_tag_ids = array_map('strval', $preselected_tag_ids);
            $modal_id = 'new-issue-modal-' . $id;
            $modal_title_id = $modal_id . '-title';
?>
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#<?= $modal_id ?>">
    Neues <?= $this->config->view->issue_desc_short ?> anlegen
</button>
<div class="modal" id="<?= $modal_id ?>" tabindex="-1" aria-labelledby="<?= $modal_title_id ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="<?= $modal_title_id ?>">Neues <?= $this->config->view->issue_desc_short ?> anlegen</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <form method="post">
                <input type="hidden" name="req" value="new-issue">
                <input type="hidden" name="request_uuid" value="<?= htmlspecialchars($this->issue_submission_token('new-issue'), ENT_QUOTES, 'UTF-8') ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="form-tags-<?= $id ?>" class="form-label"><?= $this->config->view->tag_desc ?></label>
                        <select class="form-select tom-select" id="form-tags-<?= $id ?>" name="tags[]" multiple>
<?
            foreach ($tags as $tag) {
                $selected =
                    (($form_was_incomplete && in_array((string)$tag['id'], $incomplete_selected_tag_ids, true)) ||
                    (!$form_was_incomplete && (
                        in_array($tag['name'], $preselected_tag_names, true) ||
                        in_array((string)$tag['id'], $preselected_tag_ids, true)
                    )))
                    ? ' selected'
                    : '';
                echo '<option value="' . htmlspecialchars((string)$tag['id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"' . $selected . '>'
                    . htmlspecialchars($tag['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</option>';
            }
?>
                        </select>
                    </div>
                    <div>
                        <label for="form-content-<?= $id ?>" class="form-label">Beschreibung</label>
                        <div class="input-group has-validation">
                            <textarea class="form-control<?= $form_was_incomplete && $handle_issue_res->content === '' ? ' is-invalid' : '' ?>" id="form-content-<?= $id ?>" name="content" rows="3"><?= $form_was_incomplete ? htmlspecialchars($handle_issue_res->content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '' ?></textarea>
<?
            if ($form_was_incomplete && $handle_issue_res->content === '') {
?>
                            <div class="invalid-feedback">
                                Bitte füllen Sie dieses Feld aus.
                            </div>
<?
            }
?>
                        </div>
                    </div>
<?
            $this->print_author_override_fields($form_was_incomplete);
            if ($this->may_administer()) {
                $invalid_addresses = $form_was_incomplete ? $handle_issue_res->invalid_email_addresses : [];
?>
                    <div class="mt-3">
                        <label for="form-additional-emails-<?= $id ?>" class="form-label">Zusätzliche E-Mail-Adressen (optional)</label>
                        <input type="text" class="form-control<?= $invalid_addresses !== [] ? ' is-invalid' : '' ?>" id="form-additional-emails-<?= $id ?>" name="additional_email_addresses" value="<?= htmlspecialchars($form_was_incomplete ? implode(',', $handle_issue_res->additional_email_addresses) : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-tag-additional-email-addresses>
                        <div class="form-text">Mehrere Adressen mit Kommas, Semikolons oder Leerzeichen trennen.</div>
<? if ($invalid_addresses !== []) { ?>
                        <div class="invalid-feedback">Ungültige E-Mail-Adressen: <?= htmlspecialchars(implode(', ', $invalid_addresses), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
<? } ?>
                    </div>
<?
            }
?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary"><?= $this->config->view->issue_desc_short ?> anlegen</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?
            if ($this->may_administer()) {
                $this->print_additional_email_script($modal_id);
            }
?>
<? if ($form_was_incomplete) { ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelector('[data-bs-target="#<?= $modal_id ?>"]')?.click();
    }, { once: true });
</script>
<? } ?>
<?
        }


        /////////////
        // Helpers //
        /////////////

        static function make_view_issue_html_id(string $issue_id): string {
            return 'issue-' . $issue_id;
        }
    }
?>
