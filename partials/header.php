<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/helpers.php';

$pageTitle = $pageTitle ?? 'Student Records Manager';
$flashMessage = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | Student Records</title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-content">
            <a class="brand" href="index.php">Student Records</a>
            <span class="header-caption">Administration</span>
        </div>
    </header>
    <main class="container main-content">
        <?php if ($flashMessage !== null): ?>
            <div class="alert alert-<?= e((string) ($flashMessage['type'] ?? 'info')) ?>" role="status">
                <?= e((string) ($flashMessage['msg'] ?? '')) ?>
            </div>
        <?php endif; ?>
