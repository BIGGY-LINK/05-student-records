<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/helpers.php';

$pageTitle = 'Add student';
$databaseUnavailable = false;
$student = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'course' => '',
    'enrolled_on' => date('Y-m-d'),
];
$errors = [];
$courseOptions = [];

try {
    $connection = db();
    $courseOptions = studentCourseOptions($connection);
} catch (PDOException $exception) {
    http_response_code(503);
    $databaseUnavailable = true;
}

if (!$databaseUnavailable && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null;
    if (!verifyCsrfToken($token)) {
        $errors['_form'] = 'Your form session expired. Please try again.';
        foreach (array_keys($student) as $field) {
            $value = $_POST[$field] ?? null;
            $student[$field] = is_scalar($value) ? trim((string) $value) : '';
        }
    } else {
        $result = validateStudentInput($_POST, $connection, $courseOptions);
        $student = $result['student'];
        $errors = $result['errors'];

        if ($errors === []) {
            try {
                $statement = $connection->prepare(
                    'INSERT INTO students (first_name, last_name, email, phone, course, enrolled_on)
                     VALUES (:first_name, :last_name, :email, :phone, :course, :enrolled_on)'
                );
                $statement->execute($student);
                flash('success', 'Student added');
                redirect('index.php');
            } catch (PDOException $exception) {
                error_log('Unable to add student: ' . $exception->getMessage());
                $errors['_form'] = 'The student could not be saved. Please try again.';
            }
        }
    }
}

$csrfToken = csrfToken();
$formAction = 'create.php';
$submitLabel = 'Add student';
require __DIR__ . '/partials/header.php';
?>
<?php if ($databaseUnavailable): ?>
    <section class="card error-card" aria-labelledby="database-error-title">
        <h1 id="database-error-title">Student records are temporarily unavailable</h1>
        <p>We couldn’t connect to the student database. Please make sure MySQL is running in XAMPP, then refresh this page.</p>
    </section>
<?php else: ?>
    <div class="page-heading">
        <h1>Add student</h1>
    </div>
    <?php require __DIR__ . '/partials/student-form.php'; ?>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
