<?
    namespace Conjin\Modules\FeedbackCollector;

    trait View {
        private static function escape(string $text): string {
            return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        private function identification_label(): string {
            $email = auth_get_user_email();
            if ($email !== null && $email !== '') {
                return $email;
            }
            if ($this->config->author_identity_to_label !== null) {
                return ($this->config->author_identity_to_label)($this->identity());
            }
            $identity = auth_get_user()->get_user_identity();
            return $identity['tag'] === 'Static' ? $identity['contents'] : $identity['contents']['id'];
        }

        public function print_submission_result(?SubmissionResult $result): void {
            if ($result === null) {
                return;
            }
            [$variant, $message] = match ($result->kind) {
                SubmissionResultKind::Saved => ['success', 'Vielen Dank! Ihre Rückmeldung wurde gespeichert und '],
                SubmissionResultKind::SavedMailFailed => ['warning', 'Ihre Rückmeldung wurde gespeichert. Die E-Mail konnte nicht versendet werden; der Fehler wurde protokolliert. Bitte senden Sie dieselbe Rückmeldung nicht erneut.'],
                SubmissionResultKind::Invalid => ['danger', 'Bitte prüfen Sie die markierten Antworten. Ihre Rückmeldung wurde noch nicht gespeichert.'],
                SubmissionResultKind::Expired => ['warning', 'Das Formular ist abgelaufen oder wurde zwischenzeitlich geändert. Bitte füllen Sie es erneut aus.'],
                SubmissionResultKind::Forbidden => ['danger', 'Sie sind nicht berechtigt, eine Rückmeldung abzugeben.'],
                SubmissionResultKind::ErrorDb => ['danger', 'Ihre Rückmeldung konnte nicht gespeichert werden. Bitte versuchen Sie es später erneut.'],
                SubmissionResultKind::Duplicate => ['info', 'Diese Rückmeldung wurde bereits gespeichert.']
            };
            echo '<div class="alert alert-' . $variant . '" role="status">' . self::escape($message);
            if ($result->kind === SubmissionResultKind::Saved) {
                if ($result->anonymous === true) {
                    echo '<strong>anonym</strong> ';
                }
                echo 'per E-Mail an ' . self::escape($this->config->email_address) . ' versendet.';
            }
            if (isset($result->errors['_form'])) {
                echo '<p>' . self::escape($result->errors['_form']) . '</p>';
            }
            echo '</div>';
        }

        public function print_form(?SubmissionResult $result = null): void {
            if (!$this->may_submit()) {
                echo '<p>Bitte melden Sie sich mit einem berechtigten Konto an, um eine Rückmeldung abzugeben.</p>';
                return;
            }
            if ($this->db === null) {
                echo '<div class="alert alert-danger" role="status">Das Feedbackformular ist vorübergehend nicht verfügbar. Bitte versuchen Sie es später erneut.</div>';
                return;
            }
            $values = $result?->values ?? [];
            $errors = $result?->errors ?? [];
            $form_id = $this->form_id();
            echo '<form method="post" id="' . $form_id . '" data-feedback-collector>';
            echo '<input type="hidden" name="feedback_form" value="' . $form_id . '">';
            echo '<input type="hidden" name="feedback_token" value="' . self::escape($this->issue_token()) . '">';
            if ($this->has_required_questions($this->config->form->questions)) {
                echo '<p>Mit * markierte Fragen sind Pflichtfelder.</p>';
            }
            if ($this->config->anonymity === Anonymity::Optional) {
                $anonymous = match ($result?->kind) {
                    SubmissionResultKind::Saved, SubmissionResultKind::SavedMailFailed => true,
                    default => $result?->anonymous ?? true
                };
                echo '<div class="mb-4" role="radiogroup" aria-label="Identität der Rückmeldung">';
                echo '<div class="form-check">';
                echo '<input class="form-check-input" type="radio" id="' . $form_id . '-anonymous" name="feedback_anonymous" value="1" data-feedback-anonymous' . ($anonymous ? ' checked' : '') . '>';
                echo '<label class="form-check-label" for="' . $form_id . '-anonymous">Anonym</label>';
                echo '</div><div class="form-check">';
                echo '<input class="form-check-input" type="radio" id="' . $form_id . '-identified" name="feedback_anonymous" value="0" data-feedback-identified' . (!$anonymous ? ' checked' : '') . '>';
                echo '<label class="form-check-label" for="' . $form_id . '-identified">Mit Name (' . self::escape($this->identification_label()) . ')</label>';
                echo '</div>';
                if (isset($errors['_anonymous'])) {
                    echo '<div class="invalid-feedback d-block">' . self::escape($errors['_anonymous']) . '</div>';
                }
                echo '</div>';
            }
            elseif ($this->config->anonymity === Anonymity::Anonymous) {
                echo '<p>Die Rückmeldung erfolgt anonym. Ihre angemeldete Identität und E-Mail-Adresse werden nicht mitgespeichert oder übermittelt.</p>';
            }
            else {
                echo '<p>Ihre angemeldete Identität und E-Mail-Adresse werden mitgespeichert und übermittelt.</p>';
            }
            echo '<noscript><p>Bitte beantworten Sie Folgefragen nur, wenn Sie die zugehörige Option ausgewählt haben.</p></noscript>';
            $this->print_questions($this->config->form->questions, $values, $errors, $form_id, true);
            echo '<button class="btn btn-primary" type="submit">Rückmeldung absenden</button>';
            echo '</form>';
        }

        /** @param list<Question|Section> $questions */
        private function has_required_questions(array $questions): bool {
            foreach ($questions as $question) {
                if ($question instanceof Section) {
                    if ($this->has_required_questions($question->questions)) {
                        return true;
                    }
                }
                elseif ($question->required) {
                    return true;
                }
                elseif (!($question->type instanceof Text)) {
                    foreach ($question->type->options as $option) {
                        if ($this->has_required_questions($option->follow_up_questions)) {
                            return true;
                        }
                    }
                }
            }
            return false;
        }

        /**
         * Count immediate follow-up questions, ignoring layout wrappers.
         * @param list<Question|Section> $questions
         */
        private function count_questions(array $questions): int {
            $count = 0;
            foreach ($questions as $question) {
                $count += $question instanceof Section ? $this->count_questions($question->questions) : 1;
            }
            return $count;
        }

        /** @param list<Question|Section> $questions */
        private function print_questions(array $questions, array $values, array $errors, string $prefix, bool $active, string $path = '', int &$question_index = 0): void {
            foreach ($questions as $question) {
                if ($question instanceof Section) {
                    echo '<div class="card mb-4" data-feedback-section>';
                    echo '<div class="card-header"><h3 class="h5 mb-0">' . self::escape($question->title) . '</h3></div>';
                    echo '<div class="card-body">';
                    $this->print_questions($question->questions, $values, $errors, $prefix, $active, $path, $question_index);
                    echo '</div></div>';
                    continue;
                }
                $question_key = $path . 'q' . $question_index++;
                $id = $prefix . '-' . $question_key;
                $label = self::escape($question->label) . ($question->required ? ' *' : '');
                $error = $errors[$question_key] ?? null;
                $invalid = $error === null ? '' : ' aria-invalid="true" aria-describedby="' . $id . '-error"';
                $required = $question->required && $active ? ' required' : '';
                $name = 'answers[' . $question_key . ']';
                echo '<fieldset class="mb-4" data-feedback-question data-required="' . ($question->required ? '1' : '0') . '">';
                if ($question->type instanceof Text) {
                    echo '<label class="form-label" for="' . $id . '">' . $label . '</label>';
                    $value = $values[$question_key] ?? '';
                    $value = is_string($value) ? $value : '';
                    $attributes = ' class="form-control' . ($error === null ? '' : ' is-invalid') . '" id="' . $id . '" name="' . $name
                        . '" maxlength="' . $question->type->max_length . '" data-feedback-required="' . ($question->required ? '1' : '0') . '"' . $required . $invalid;
                    if ($question->type->multiline) {
                        echo '<textarea rows="4"' . $attributes . '>' . self::escape($value) . '</textarea>';
                    }
                    else {
                        echo '<input type="text"' . $attributes . ' value="' . self::escape($value) . '">';
                    }
                }
                else {
                    $multiple = $question->type instanceof MultipleChoice;
                    echo '<legend class="fs-5"><p>' . $label;
                    if ($multiple) {
                        echo ' <em class="form-text">Mehrere Antworten möglich.</em>';
                    }
                    echo '</p></legend>';
                    $value = $values[$question_key] ?? ($multiple ? [] : '');
                    foreach ($question->type->options as $option_index => $option) {
                        $option_key = 'o' . $option_index;
                        $option_id = $id . '-' . $option_key;
                        $selected = $multiple ? (is_array($value) && in_array($option_key, $value, true)) : $value === $option_key;
                        echo '<div class="form-check mb-2">';
                        echo '<input class="form-check-input" type="' . ($multiple ? 'checkbox' : 'radio') . '" id="' . $option_id
                            . '" name="' . $name . ($multiple ? '[]' : '') . '" value="' . $option_key . '"'
                            . ($selected ? ' checked' : '') . (!$multiple ? $required : '') . $invalid . '>';
                        echo '<label class="form-check-label" for="' . $option_id . '">' . self::escape($option->label);
                        $follow_up_count = $this->count_questions($option->follow_up_questions);
                        if ($follow_up_count > 0) {
                            echo ' <em>(' . ($follow_up_count === 1 ? 'enthält Folgefrage' : 'enthält Folgefragen') . ')</em>';
                        }
                        echo '</label>';
                        echo '</div>';
                        if ($option->follow_up_questions !== []) {
                            echo '<fieldset class="ms-4 ps-3 border-start" data-follow-up-for="' . $option_id . '">';
                            $this->print_questions($option->follow_up_questions, $values, $errors, $prefix, $active && $selected, $question_key . '-' . $option_key . '-');
                            echo '</fieldset>';
                        }
                    }
                    if (!$multiple && !$question->required) {
                        echo '<div class="form-check"><input class="form-check-input" type="radio" id="' . $id . '-empty" name="'
                            . $name . '" value=""' . ($value === '' ? ' checked' : '') . '>';
                        echo '<label class="form-check-label" for="' . $id . '-empty">Keine Angabe</label></div>';
                    }
                }
                if ($error !== null) {
                    echo '<div class="invalid-feedback d-block" id="' . $id . '-error">' . self::escape($error) . '</div>';
                }
                echo '</fieldset>';
            }
        }
    }
