<form class="card student-form" method="post" action="<?= e($formAction) ?>">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

    <?php if (isset($errors['_form'])): ?>
        <div class="alert alert-error" role="alert"><?= e($errors['_form']) ?></div>
    <?php endif; ?>

    <div class="form-grid">
        <div class="form-field">
            <label for="first_name">First name</label>
            <input id="first_name" name="first_name" type="text" maxlength="60" required
                value="<?= e($student['first_name']) ?>" autocomplete="given-name">
            <?php if (isset($errors['first_name'])): ?><p class="field-error"><?= e($errors['first_name']) ?></p><?php endif; ?>
        </div>

        <div class="form-field">
            <label for="last_name">Last name</label>
            <input id="last_name" name="last_name" type="text" maxlength="60" required
                value="<?= e($student['last_name']) ?>" autocomplete="family-name">
            <?php if (isset($errors['last_name'])): ?><p class="field-error"><?= e($errors['last_name']) ?></p><?php endif; ?>
        </div>

        <div class="form-field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" required
                value="<?= e($student['email']) ?>" autocomplete="email">
            <?php if (isset($errors['email'])): ?><p class="field-error"><?= e($errors['email']) ?></p><?php endif; ?>
        </div>

        <div class="form-field">
            <label for="phone">Phone <span class="optional">(optional)</span></label>
            <input id="phone" name="phone" type="tel"
                value="<?= e($student['phone']) ?>" autocomplete="tel">
            <?php if (isset($errors['phone'])): ?><p class="field-error"><?= e($errors['phone']) ?></p><?php endif; ?>
        </div>

        <div class="form-field">
            <label for="course">Course</label>
            <select id="course" name="course" required>
                <option value="">Select a course</option>
                <?php foreach ($courseOptions as $course): ?>
                    <option value="<?= e($course) ?>" <?= $student['course'] === $course ? 'selected' : '' ?>>
                        <?= e($course) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['course'])): ?><p class="field-error"><?= e($errors['course']) ?></p><?php endif; ?>
        </div>

        <div class="form-field">
            <label for="enrolled_on">Enrolled on</label>
            <input id="enrolled_on" name="enrolled_on" type="date" required
                value="<?= e($student['enrolled_on']) ?>" max="<?= e(date('Y-m-d')) ?>">
            <?php if (isset($errors['enrolled_on'])): ?><p class="field-error"><?= e($errors['enrolled_on']) ?></p><?php endif; ?>
        </div>
    </div>

    <div class="form-actions">
        <button class="button" type="submit"><?= e($submitLabel) ?></button>
        <a class="button button-secondary" href="index.php">Cancel</a>
    </div>
</form>
