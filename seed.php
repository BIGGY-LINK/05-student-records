<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/helpers.php';

$pageTitle = 'Seed test students';
$databaseUnavailable = false;
$errorMessage = null;
$csrfToken = csrfToken();
$confirmationRequested = ($_GET['confirm'] ?? null) === 'yes';
$alreadySeeded = ($_GET['done'] ?? null) === 'yes';

$firstNames = [
    'Adebayo', 'Aisha', 'Chiamaka', 'Chinedu', 'Damilola',
    'Emeka', 'Fatima', 'Femi', 'Hadiza', 'Ifeanyi',
    'Kelechi', 'Ngozi', 'Obinna', 'Oluwaseun', 'Sade',
    'Tunde', 'Yemi', 'Zainab', 'Abiola', 'Babatunde',
    'Chisom', 'Efe', 'Folake', 'Ibrahim', 'Jumoke',
    'Kabiru', 'Nneka', 'Ogechi', 'Segun', 'Yakubu',
    'Aminat', 'Bolanle', 'Ebuka', 'Gbemisola', 'Ismail',
    'Morenike', 'Nnamdi', 'Olumide', 'Temitope', 'Uche',
    'Bisi', 'Chukwudi', 'Esther', 'Funmi', 'Ikenna',
    'Ladi', 'Musa', 'Onyeka', 'Rashida', 'Taiwo',
];
$lastNames = [
    'Adeyemi', 'Bello', 'Okafor', 'Okonkwo', 'Afolabi',
    'Eze', 'Yusuf', 'Bakare', 'Abubakar', 'Nwosu',
    'Ibrahim', 'Olawale', 'Ogunleye', 'Mohammed', 'Balogun',
    'Akinyemi', 'Umar', 'Ezeh', 'Ojo', 'Danladi',
    'Adeleke', 'Chukwu', 'Garba', 'Oni', 'Suleiman',
    'Nwachukwu', 'Okechukwu', 'Adesina', 'Usman', 'Aina',
    'Ibe', 'Onyema', 'Lawal', 'Ogunbiyi', 'Abiodun',
    'Danjuma', 'Ojukwu', 'Adewale', 'Obi', 'Musa',
    'Akinola', 'Udo', 'Alabi', 'Chinwe', 'Sani',
    'Oladipo', 'Iroko', 'Ekwueme', 'Fashola', 'Kalu',
];
$phonePrefixes = ['070', '071', '080', '081', '090', '091'];

if ($confirmationRequested && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null;
    if (!verifyCsrfToken($token)) {
        http_response_code(403);
        $errorMessage = 'The confirmation could not be verified. Reload the page and try again.';
    } else {
        try {
            $connection = db();
            $courseOptions = studentCourseOptions($connection);
            if ($courseOptions === []) {
                throw new RuntimeException('No course ENUM options are configured for the students table.');
            }

            $insertStatement = $connection->prepare(
                'INSERT INTO students (first_name, last_name, email, phone, course, enrolled_on)
                 VALUES (:first_name, :last_name, :email, :phone, :course, :enrolled_on)'
            );

            $connection->beginTransaction();
            $runId = bin2hex(random_bytes(4));
            $startDate = new DateTimeImmutable('2018-01-01');
            for ($index = 0; $index < 50; $index++) {
                $firstName = $firstNames[$index];
                $lastName = $lastNames[$index];
                $emailName = strtolower($firstName . '.' . $lastName);
                $enrolledOn = $startDate->modify('+' . (($index * 53) % 2900) . ' days');

                $insertStatement->execute([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $emailName . '.' . $runId . '@example.com',
                    'phone' => sprintf(
                        '%s%08d',
                        $phonePrefixes[$index % count($phonePrefixes)],
                        10000000 + $index
                    ),
                    'course' => $courseOptions[$index % count($courseOptions)],
                    'enrolled_on' => $enrolledOn->format('Y-m-d'),
                ]);
            }
            $connection->commit();
            flash('success', '50 fake student records were added.');
            redirect('seed.php?done=yes');
        } catch (PDOException $exception) {
            if (isset($connection) && $connection->inTransaction()) {
                $connection->rollBack();
            }
            error_log('Unable to seed student records: ' . $exception->getMessage());
            http_response_code(503);
            $databaseUnavailable = true;
        } catch (RuntimeException $exception) {
            $errorMessage = $exception->getMessage();
            http_response_code(500);
        }
    }
}

require __DIR__ . '/partials/header.php';
?>
<div class="page-heading">
    <h1>Seed test students</h1>
    <a class="button button-secondary" href="index.php">Back to students</a>
</div>
<?php if ($databaseUnavailable): ?>
    <section class="card error-card">
        <h2>Student records are temporarily unavailable</h2>
        <p>We couldn’t connect to the database or complete the seed operation. Please check that MySQL is running and try again.</p>
    </section>
<?php elseif ($errorMessage !== null): ?>
    <section class="card error-card">
        <h2>Seed setup incomplete</h2>
        <p><?= e($errorMessage) ?></p>
    </section>
<?php elseif ($alreadySeeded): ?>
    <section class="card error-card">
        <h2>Test students added</h2>
        <p><a href="index.php">View the student list</a>.</p>
    </section>
<?php elseif ($confirmationRequested): ?>
    <section class="card error-card">
        <h2>Confirm test data insertion</h2>
        <p>This will insert 50 fake Nigerian-named student records into your database. The records will be added only after you submit this form.</p>
        <form method="post" action="seed.php?confirm=yes">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <button class="button" type="submit">Insert 50 test students</button>
        </form>
    </section>
<?php else: ?>
    <section class="card error-card">
        <h2>Ready to add test data?</h2>
        <p>This will insert 50 fake Nigerian-named student records into your database. Use it to test search and pagination.</p>
        <p><a class="button" href="seed.php?confirm=yes">Continue to confirmation</a></p>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
