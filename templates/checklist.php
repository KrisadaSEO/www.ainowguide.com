<?php declare(strict_types=1);

$questions = site_checklist_questions();
$subscribeError = site_flash_consume('subscribe.error');
?>

<div class="checklist-page" data-checklist-app>
    <header class="checklist-hero">
        <div class="checklist-hero__eyebrow">Krisada.com</div>
        <h1 class="checklist-hero__title">The Digital Independence Self-Assessment</h1>
        <p class="checklist-hero__sub">Answer 10 guided questions, open the plain-English explanations anywhere the wording feels fuzzy, and use the score to see what to fix first.</p>

        <div class="checklist-hero__meta">
            <span>10 questions</span>
            <span>Yes / No / Not sure</span>
            <span>Live score</span>
        </div>

        <p class="checklist-print-note screen-only">Use <strong>Print / Save as PDF</strong> after you finish if you want a copy of your score and recommendations.</p>
    </header>

    <section class="assessment-toolbar screen-only">
        <div class="assessment-toolbar__stats">
            <div class="assessment-stat">
                <span class="assessment-stat__value" data-answered-count>0</span>
                <span class="assessment-stat__label">answered</span>
            </div>
            <div class="assessment-stat">
                <span class="assessment-stat__value" data-score-value>0</span>
                <span class="assessment-stat__label">solid areas</span>
            </div>
            <div class="assessment-stat">
                <span class="assessment-stat__value" data-unsure-count>0</span>
                <span class="assessment-stat__label">not sure</span>
            </div>
        </div>

        <div class="assessment-toolbar__actions">
            <button type="button" class="btn btn-outline-dark assessment-toolbar__button" data-toggle-explainers>Open all explanations</button>
            <button type="button" class="btn btn-outline-dark assessment-toolbar__button" data-reset-assessment>Reset answers</button>
            <button type="button" class="btn btn-primary assessment-toolbar__button" data-print-assessment>Save / Print</button>
        </div>
    </section>

    <div class="assessment-progress screen-only" aria-hidden="true">
        <div class="assessment-progress__track">
            <div class="assessment-progress__fill" data-progress-fill></div>
        </div>
    </div>

    <ol class="assessment-list">
        <?php foreach ($questions as $index => $question): ?>
            <?php
            $questionNumber = $index + 1;
            $inputName = 'assessment-q' . $questionNumber;
            ?>
            <li
                class="assessment-item"
                data-question
                data-title="<?= site_e($question['title']) ?>"
                data-link-url="<?= site_e($question['link_url']) ?>"
                data-link-label="<?= site_e($question['link_label']) ?>"
            >
                <article class="assessment-card">
                    <div class="assessment-card__number" aria-hidden="true"><?= $questionNumber ?></div>

                    <div class="assessment-card__body">
                        <h2 class="assessment-card__title"><?= site_e($question['title']) ?></h2>
                        <p class="assessment-card__note"><?= site_e($question['note']) ?></p>

                        <details class="assessment-explainer">
                            <summary>Open the explanation</summary>
                            <div class="assessment-explainer__body">
                                <p><strong>What this means:</strong> <?= site_e($question['meaning']) ?></p>
                                <p><strong>Why it matters:</strong> <?= site_e($question['why']) ?></p>
                                <p><strong>Quick rule:</strong> <?= site_e($question['rule']) ?></p>
                                <a href="<?= site_e($question['link_url']) ?>" class="assessment-explainer__link"><?= site_e($question['link_label']) ?> &rarr;</a>
                            </div>
                        </details>

                        <fieldset class="assessment-answers">
                            <legend class="visually-hidden"><?= site_e($question['title']) ?></legend>

                            <label class="assessment-answer">
                                <input type="radio" name="<?= site_e($inputName) ?>" value="yes">
                                <span class="assessment-answer__label">Yes</span>
                            </label>

                            <label class="assessment-answer">
                                <input type="radio" name="<?= site_e($inputName) ?>" value="no">
                                <span class="assessment-answer__label">No</span>
                            </label>

                            <label class="assessment-answer">
                                <input type="radio" name="<?= site_e($inputName) ?>" value="unsure">
                                <span class="assessment-answer__label">Not sure</span>
                            </label>
                        </fieldset>
                    </div>
                </article>
            </li>
        <?php endforeach; ?>
    </ol>

    <section class="assessment-results" aria-live="polite">
        <div class="assessment-results__header">
            <div>
                <div class="assessment-results__eyebrow">Your current read</div>
                <h2 class="assessment-results__title"><span data-score-value-large>0</span><span class="assessment-results__title-total">/10</span></h2>
            </div>
            <p class="assessment-results__summary" data-score-summary>Start answering the questions and the score will build as you go.</p>
        </div>

        <p class="assessment-results__detail" data-score-detail>Use "Not sure" whenever you cannot confidently verify something. That is a useful signal, not a failure.</p>

        <div class="assessment-score-bands">
            <div class="assessment-score-band" data-score-band data-min="0" data-max="3">
                <span class="assessment-score-band__range">0-3</span>
                <span class="assessment-score-band__text">The foundation is fragile. Start with ownership, structure, and resilience.</span>
            </div>
            <div class="assessment-score-band" data-score-band data-min="4" data-max="6">
                <span class="assessment-score-band__range">4-6</span>
                <span class="assessment-score-band__text">You have pieces of the system. Now connect them and remove blind spots.</span>
            </div>
            <div class="assessment-score-band" data-score-band data-min="7" data-max="8">
                <span class="assessment-score-band__range">7-8</span>
                <span class="assessment-score-band__text">Strong base. The next gains come from tightening architecture and repeatability.</span>
            </div>
            <div class="assessment-score-band" data-score-band data-min="9" data-max="10">
                <span class="assessment-score-band__range">9-10</span>
                <span class="assessment-score-band__text">You are building compounding infrastructure. Keep refining the weak edges.</span>
            </div>
        </div>
    </section>

    <section class="assessment-recommendations">
        <div class="assessment-recommendations__header">
            <div>
                <div class="assessment-recommendations__eyebrow">Where to go next</div>
                <h2 class="assessment-recommendations__title">Recommended reading for your weak spots</h2>
            </div>
            <p class="assessment-recommendations__intro" data-recommendations-intro>Finish the assessment and I will surface the clearest next reading path from the library.</p>
        </div>

        <ul class="assessment-recommendation-list" data-recommendations-list>
            <li class="assessment-recommendation assessment-recommendation--placeholder">Answer the questions above to generate a more useful next-step path.</li>
        </ul>

        <div class="assessment-recommendations__actions">
            <a href="/library/" class="btn btn-outline-dark">Browse the Library</a>
            <a href="/work-with-me/" class="btn btn-primary">Request an Audit</a>
        </div>
    </section>

    <section class="assessment-delivery" id="assessment-delivery">
        <div class="assessment-delivery__header">
            <div class="assessment-recommendations__eyebrow">Email the result</div>
            <h2 class="assessment-delivery__title">Send yourself the score, weak spots, and reading path</h2>
            <p class="assessment-delivery__intro">This is where the email belongs: after the value is visible. Finish all 10 questions and I will email you a copy of your result and the next steps it points to.</p>
        </div>

        <?php if (is_string($subscribeError) && $subscribeError !== ''): ?>
            <p class="assessment-delivery__error" role="alert"><?= site_e($subscribeError) ?></p>
        <?php endif; ?>

        <form class="assessment-delivery__form" action="/subscribe/" method="POST" data-assessment-delivery-form>
            <input type="hidden" name="form_started" value="<?= time() ?>">
            <input type="hidden" name="source" value="assessment-results">
            <input type="hidden" name="assessment_answers" value="" data-assessment-answers>
            <div style="display:none" aria-hidden="true">
                <input type="text" name="_hp" value="" tabindex="-1" autocomplete="off">
            </div>

            <div class="assessment-delivery__row">
                <input
                    type="email"
                    name="email"
                    placeholder="Your email address"
                    required
                    class="assessment-delivery__input"
                    aria-label="Email address"
                >
                <button type="submit" class="btn btn-primary assessment-delivery__button" data-assessment-submit disabled>Complete all 10 questions first</button>
            </div>

            <p class="assessment-delivery__note" data-assessment-submit-note>Complete all 10 questions to unlock the email summary.</p>
        </form>
    </section>

    <footer class="checklist-footer">
        <span>ainowguide.com</span>
    </footer>
</div>
