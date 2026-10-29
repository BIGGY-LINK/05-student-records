<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/helpers.php';

$studentId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($studentId === false || $studentId === null) {
    http_response_code(404);
}

$pageTitle = 'Edit student';
$databaseUnavailable = false;
$student = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'course' => '',
    'enrolled_on' => '',
];
$errors = [];
$courseOptions = [];
$recordNotFound = $studentId === false || $studentId === null;

if (!$recordNotFound) {
    try {
        $connection = db();
        $courseOptions = studentCourseOptions($connection);
        $statement = $connection->prepare(
            'SELECT id, first_name, last_name, email, phone, course, enrolled_on
             FROM students
             WHERE id = :id'
        );
        $statement->execute(['id' => $studentId]);
        $record = $statement->fetch();
        if ($record === false) {
            http_response_code(404);
            $recordNotFound = true;
        } else {
            foreach (array_keys($student) as $field) {
                $student[$field] = (string) $record[$field];
            }
        }
    } catch (PDOException $exception) {
        http_response_code(503);
        $databaseUnavailable = true;
    }
}

if (!$databaseUnavailable && !$recordNotFound && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null;
    if (!verifyCsrfToken($token)) {
        $errors['_form'] = 'Your form session expired. Please try again.';
        foreach (array_keys($student) as $field) {
            $value = $_POST[$field] ?? null;
            $student[$field] = is_scalar($value) ? trim((string) $value) : '';
        }
    } else {
        $result = validateStudentInput($_POST, $connection, $courseOptions, $studentId);
        $student = $result['student'];
        $errors = $result['errors'];

        if ($errors === []) {
            try {
                $statement = $connection->prepare(
                    'UPDATE students
                     SET first_name = :first_name,
                         last_name = :last_name,
                         email = :email,
                         phone = :phone,
                         course = :course,
                         enrolled_on = :enrolled_on
                     WHERE id = :id'
                );
                $statement->execute($student + ['id' => $studentId]);
                flash('success', 'Student updated');
                redirect('index.php');
            } catch (PDOException $exception) {
                error_log('Unable to update student: ' . $exception->getMessage());
                $errors['_form'] = 'The student could not be saved. Please try again.';
            }
        }
    }
}

$csrfToken = csrfToken();
$formAction = 'edit.php?id=' . (int) $studentId;
$submitLabel = 'Save changes';
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
        <h1>Edit student</h1>
    </div>
    <?php require __DIR__ . '/partials/student-form.php'; ?>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
