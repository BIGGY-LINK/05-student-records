<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/helpers.php';

$studentId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$pageTitle = 'Student details';
$student = null;
$databaseUnavailable = false;
$recordNotFound = $studentId === false || $studentId === null;

if ($recordNotFound) {
    http_response_code(404);
} else {
    try {
        $statement = db()->prepare(
            'SELECT id, first_name, last_name, email, phone, course, enrolled_on, created_at, updated_at
             FROM students
             WHERE id = :id'
        );
        $statement->execute(['id' => $studentId]);
        $student = $statement->fetch();
        if ($student === false) {
            http_response_code(404);
            $recordNotFound = true;
        } else {
            $pageTitle = $student['first_name'] . ' ' . $student['last_name'];
        }
    } catch (PDOException $exception) {
        http_response_code(503);
        $databaseUnavailable = true;
    }
}

require __DIR__ . '/partials/header.php';
?>
<?php if ($databaseUnavailable): ?>
    <section class="card error-card" aria-labelledby="database-error-title">
        <h1 id="database-error-title">Student records are temporarily unavailable</h1>
        <p>We couldn’t connect to the student database. Please make sure MySQL is running in XAMPP, then refresh this page.</p>
    </section>
<?php elseif ($recordNotFound): ?>
    <section class="card error-card" aria-labelledby="record-not-found-title">
        <h1 id="record-not-found-title">Student not found</h1>
        <p>The requested student record does not exist. <a href="index.php">Return to student records</a>.</p>
    </section>
<?php else: ?>
    <div class="page-heading">
        <h1><?= e($student['first_name'] . ' ' . $student['last_name']) ?></h1>
        <div class="detail-actions">
            <a class="button" href="edit.php?id=<?= e((string) $student['id']) ?>">Edit student</a>
            <a class="button button-secondary" href="index.php">Back to students</a>
        </div>
    </div>

    <section class="card detail-card" aria-label="Student details">
        <dl class="detail-list">
            <div>
                <dt>Email</dt>
                <dd><a href="mailto:<?= e($student['email']) ?>"><?= e($student['email']) ?></a></dd>
            </div>
            <div>
                <dt>Phone</dt>
                <dd>
                    <?php if ($student['phone'] !== null && $student['phone'] !== ''): ?>
                        <a href="tel:<?= e($student['phone']) ?>"><?= e($student['phone']) ?></a>
                    <?php else: ?>
                        <span class="muted">Not provided</span>
                    <?php endif; ?>
                </dd>
            </div>
            <div>
                <dt>Course</dt>
                <dd><?= e($student['course']) ?></dd>
            </div>
            <div>
                <dt>Enrolled on</dt>
                <dd>
                    <?php
                    $enrolledOn = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $student['enrolled_on']);
                    echo $enrolledOn instanceof DateTimeImmutable
                        ? e($enrolledOn->format('d M Y'))
                        : e((string) $student['enrolled_on']);
                    ?>
                </dd>
            </div>
            <div>
                <dt>Created</dt>
                <dd><?= e((string) $student['created_at']) ?></dd>
            </div>
            <div>
                <dt>Last updated</dt>
                <dd><?= e((string) $student['updated_at']) ?></dd>
            </div>
        </dl>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
