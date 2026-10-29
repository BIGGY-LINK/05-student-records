<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function flash(string $type, string $msg): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION['flash'] = [
        'type' => $type,
        'msg' => $msg,
    ];
}

function getFlash(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return is_array($message) ? $message : null;
}

function csrfToken(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    return isset($_SESSION['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && $token !== null
        && hash_equals($_SESSION['csrf_token'], $token);
}

function studentCourseOptions(PDO $connection): array
{
    $statement = $connection->query(
        "SELECT COLUMN_TYPE
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'students'
           AND COLUMN_NAME = 'course'"
    );
    $columnType = $statement->fetchColumn();

    if (!is_string($columnType) || !preg_match('/^enum\((.*)\)$/i', $columnType, $matches)) {
        return [];
    }

    preg_match_all("~'((?:\\\\.|[^'])*)'~", $matches[1], $values);

    return array_map('stripslashes', $values[1]);
}

function validateStudentInput(
    array $input,
    PDO $connection,
    array $courseOptions,
    ?int $excludeId = null
): array {
    $student = [
        'first_name' => trim(is_scalar($input['first_name'] ?? null) ? (string) $input['first_name'] : ''),
        'last_name' => trim(is_scalar($input['last_name'] ?? null) ? (string) $input['last_name'] : ''),
        'email' => trim(is_scalar($input['email'] ?? null) ? (string) $input['email'] : ''),
        'phone' => trim(is_scalar($input['phone'] ?? null) ? (string) $input['phone'] : ''),
        'course' => trim(is_scalar($input['course'] ?? null) ? (string) $input['course'] : ''),
        'enrolled_on' => trim(is_scalar($input['enrolled_on'] ?? null) ? (string) $input['enrolled_on'] : ''),
    ];
    $errors = [];

    foreach (['first_name' => 'First name', 'last_name' => 'Last name'] as $field => $label) {
        if (preg_match('/\A(?=.*\p{L})[\p{L} -]{2,60}\z/u', $student[$field]) !== 1) {
            $errors[$field] = $label . ' must be 2–60 characters and contain only letters, spaces, or hyphens.';
        }
    }

    if (filter_var($student['email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Enter a valid email address.';
    } else {
        $sql = 'SELECT id FROM students WHERE LOWER(email) = LOWER(:email)';
        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude_id';
        }
        $sql .= ' LIMIT 1';

        $statement = $connection->prepare($sql);
        $parameters = ['email' => $student['email']];
        if ($excludeId !== null) {
            $parameters['exclude_id'] = $excludeId;
        }
        $statement->execute($parameters);

        if ($statement->fetch() !== false) {
            $errors['email'] = 'That email address is already in use.';
        }
    }

    if ($student['phone'] !== '' && preg_match('/\A0[789][01]\d{8}\z/', $student['phone']) !== 1) {
        $errors['phone'] = 'Enter a valid phone number or leave this field blank.';
    }

    if (!in_array($student['course'], $courseOptions, true)) {
        $errors['course'] = 'Select a valid course.';
    }

    $enrolledOn = DateTimeImmutable::createFromFormat('!Y-m-d', $student['enrolled_on']);
    $dateErrors = DateTimeImmutable::getLastErrors();
    if (
        !$enrolledOn
        || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
        || $enrolledOn->format('Y-m-d') !== $student['enrolled_on']
        || $enrolledOn > new DateTimeImmutable('today')
    ) {
        $errors['enrolled_on'] = 'Enter a valid enrollment date that is not in the future.';
    }

    return ['student' => $student, 'errors' => $errors];
}
