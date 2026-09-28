<?php
/**
 * CampusEvents - Global Header Component
 */
require_once __DIR__ . '/auth.php';

$baseUrl = getBaseUrl();
$title = isset($pageTitle) ? e($pageTitle) . ' | CampusEvents' : 'CampusEvents - Experience. Participate. Celebrate.';
$currentUser = getCurrentUser();
$isDashboard = !empty($isDashboardPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $title ?></title>
  
  <!-- Google Fonts: Plus Jakarta Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <!-- Theme Stylesheets -->
  <link rel="stylesheet" href="<?= $baseUrl ?>assets/css/style.css">
  <?php if ($isDashboard): ?>
    <link rel="stylesheet" href="<?= $baseUrl ?>assets/css/dashboard.css">
  <?php endif; ?>
  <link rel="stylesheet" href="<?= $baseUrl ?>assets/css/print.css">
</head>
<body data-base-url="<?= $baseUrl ?>">
  <!-- Flash Messages Data for Toasts -->
  <script id="flash-messages-data" type="application/json">
    <?= json_encode(getFlashMessages()) ?>
  </script>
