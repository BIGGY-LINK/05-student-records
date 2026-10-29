<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    $pageTitle = 'Method not allowed';
    require __DIR__ . '/partials/header.php';
    ?>
    <section class="card error-card" aria-labelledby="method-error-title">
        <h1 id="method-error-title">Method not allowed</h1>
        <p>Student records can only be deleted by submitting the delete form.</p>
        <p><a href="index.php">Return to student records</a>.</p>
    </section>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

$token = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null;
if (!verifyCsrfToken($token)) {
    http_response_code(403);
    $pageTitle = 'Request could not be verified';
    require __DIR__ . '/partials/header.php';
    ?>
    <section class="card error-card" aria-labelledby="csrf-error-title">
        <h1 id="csrf-error-title">Request could not be verified</h1>
        <p>Please return to the student list and try again.</p>
        <p><a href="index.php">Return to student records</a>.</p>
    </section>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

$studentId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($studentId === false || $studentId === null) {
    http_response_code(400);
    $pageTitle = 'Invalid student';
    require __DIR__ . '/partials/header.php';
    ?>
    <section class="card error-card" aria-labelledby="invalid-student-title">
        <h1 id="invalid-student-title">Invalid student</h1>
        <p>The student record could not be identified. <a href="index.php">Return to student records</a>.</p>
    </section>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

try {
    $statement = db()->prepare('DELETE FROM students WHERE id = :id');
    $statement->execute(['id' => $studentId]);

    if ($statement->rowCount() === 0) {
        http_response_code(404);
        $pageTitle = 'Student not found';
        require __DIR__ . '/partials/header.php';
        ?>
        <section class="card error-card" aria-labelledby="record-not-found-title">
            <h1 id="record-not-found-title">Student not found</h1>
            <p>The requested student record no longer exists. <a href="index.php">Return to student records</a>.</p>
        </section>
        <?php
        require __DIR__ . '/partials/footer.php';
        exit;
    }

    flash('success', 'Student deleted');
    redirect('index.php');
} catch (PDOException $exception) {
    error_log('Unable to delete student: ' . $exception->getMessage());
    http_response_code(503);
    $pageTitle = 'Student records unavailable';
    require __DIR__ . '/partials/header.php';
    ?>
    <section class="card error-card" aria-labelledby="database-error-title">
        <h1 id="database-error-title">Student records are temporarily unavailable</h1>
        <p>The student could not be deleted right now. Please try again later.</p>
        <p><a href="index.php">Return to student records</a>.</p>
    </section>
    <?php
    require __DIR__ . '/partials/footer.php';
}
