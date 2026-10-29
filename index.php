<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/helpers.php';

$pageTitle = 'Students';
$databaseUnavailable = false;
$students = [];
$courseOptions = [];
$csrfToken = csrfToken();
$perPage = 10;

$queryValue = $_GET['q'] ?? '';
$q = trim(is_scalar($queryValue) ? (string) $queryValue : '');
$courseValue = $_GET['course'] ?? '';
$course = is_scalar($courseValue) ? (string) $courseValue : '';
$pageValue = $_GET['page'] ?? 1;
$page = filter_var($pageValue, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$page = $page === false ? 1 : $page;

try {
    $connection = db();
    $courseOptions = studentCourseOptions($connection);

    if (!in_array($course, $courseOptions, true)) {
        $course = '';
    }

    $conditions = [];
    $filterParameters = [];
    if ($q !== '') {
        $conditions[] = '(first_name LIKE :first_name OR last_name LIKE :last_name OR email LIKE :email)';
        $searchPattern = '%' . $q . '%';
        $filterParameters['first_name'] = $searchPattern;
        $filterParameters['last_name'] = $searchPattern;
        $filterParameters['email'] = $searchPattern;
    }
    if ($course !== '') {
        $conditions[] = 'course = :course';
        $filterParameters['course'] = $course;
    }
    $whereSql = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);

    $countStatement = $connection->prepare('SELECT COUNT(*) FROM students' . $whereSql);
    $countStatement->execute($filterParameters);
    $totalStudents = (int) $countStatement->fetchColumn();
    $totalPages = max(1, (int) ceil($totalStudents / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    $listStatement = $connection->prepare(
        'SELECT id, first_name, last_name, email, course, enrolled_on
         FROM students' . $whereSql . '
         ORDER BY last_name, first_name
         LIMIT :limit OFFSET :offset'
    );
    foreach ($filterParameters as $name => $value) {
        $listStatement->bindValue(':' . $name, $value, PDO::PARAM_STR);
    }
    $listStatement->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $listStatement->bindValue(':offset', $offset, PDO::PARAM_INT);
    $listStatement->execute();
    $students = $listStatement->fetchAll();
} catch (PDOException $exception) {
    error_log('Unable to load student list: ' . $exception->getMessage());
    http_response_code(503);
    $databaseUnavailable = true;
    $totalStudents = 0;
    $totalPages = 1;
    $offset = 0;
}

$firstStudent = $totalStudents === 0 ? 0 : $offset + 1;
$lastStudent = min($offset + count($students), $totalStudents);
$paginationFilters = [];
if ($q !== '') {
    $paginationFilters['q'] = $q;
}
if ($course !== '') {
    $paginationFilters['course'] = $course;
}

require __DIR__ . '/partials/header.php';
?>
<?php if ($databaseUnavailable): ?>
    <section class="card error-card" aria-labelledby="database-error-title">
        <h1 id="database-error-title">Student records are temporarily unavailable</h1>
        <p>We couldn’t connect to the student database. Please make sure MySQL is running in XAMPP, then refresh this page.</p>
    </section>
<?php else: ?>
    <div class="page-heading">
        <h1>Students</h1>
        <a class="button" href="create.php">Add student</a>
    </div>

    <form class="filter-form card" method="get" action="index.php">
        <div class="filter-field search-field">
            <label for="q">Search students</label>
            <input id="q" name="q" type="search" value="<?= e($q) ?>" placeholder="Name or email">
        </div>
        <div class="filter-field">
            <label for="course">Course</label>
            <select id="course" name="course">
                <option value="">All courses</option>
                <?php foreach ($courseOptions as $courseOption): ?>
                    <option value="<?= e($courseOption) ?>" <?= $course === $courseOption ? 'selected' : '' ?>>
                        <?= e($courseOption) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-actions">
            <button class="button" type="submit">Search</button>
            <a class="button button-secondary" href="index.php">Clear</a>
        </div>
    </form>

    <p class="results-count">
        Showing <?= number_format($firstStudent) ?>–<?= number_format($lastStudent) ?>
        of <?= number_format($totalStudents) ?> students
    </p>

    <section class="card" aria-label="Student records">
        <div class="table-scroll">
            <table class="student-table">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Course</th>
                        <th scope="col">Enrolled</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($students === []): ?>
                        <tr>
                            <td class="empty-state" colspan="5">No students found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td>
                                    <a href="show.php?id=<?= e((string) $student['id']) ?>">
                                        <?= e($student['first_name'] . ' ' . $student['last_name']) ?>
                                    </a>
                                </td>
                                <td><?= e($student['email']) ?></td>
                                <td><?= e($student['course']) ?></td>
                                <td>
                                    <?php
                                    $enrolledOn = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $student['enrolled_on']);
                                    echo $enrolledOn instanceof DateTimeImmutable
                                        ? e($enrolledOn->format('d M Y'))
                                        : e($student['enrolled_on']);
                                    ?>
                                </td>
                                <td class="actions">
                                    <a href="edit.php?id=<?= e((string) $student['id']) ?>">Edit</a>
                                    <form class="inline-form" method="post" action="delete.php"
                                        data-confirm-message="<?= e('Delete ' . $student['first_name'] . ' ' . $student['last_name'] . '?') ?>"
                                        onsubmit="return confirm(this.dataset.confirmMessage)">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="id" value="<?= e((string) $student['id']) ?>">
                                        <button class="delete-link" type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <?php if ($totalPages > 1): ?>
        <nav class="pagination" aria-label="Student list pages">
            <?php if ($page > 1): ?>
                <?php $previousQuery = http_build_query($paginationFilters + ['page' => $page - 1]); ?>
                <a class="page-link" href="?<?= e($previousQuery) ?>" rel="prev">Previous</a>
            <?php endif; ?>
            <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                <?php $pageQuery = http_build_query($paginationFilters + ['page' => $pageNumber]); ?>
                <?php if ($pageNumber === $page): ?>
                    <span class="page-link current-page" aria-current="page"><?= $pageNumber ?></span>
                <?php else: ?>
                    <a class="page-link" href="?<?= e($pageQuery) ?>"><?= $pageNumber ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <?php $nextQuery = http_build_query($paginationFilters + ['page' => $page + 1]); ?>
                <a class="page-link" href="?<?= e($nextQuery) ?>" rel="next">Next</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
