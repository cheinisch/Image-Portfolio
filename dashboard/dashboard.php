<?php
session_start();
require_once __DIR__ . '/../functions/functions.php';

// Überprüfen, ob der Benutzer angemeldet ist
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Prüfen, ob die Benutzer-Einstellungen angezeigt werden sollen
$showUserSettings = isset($_GET['usersettings']);
$showPageSettings = isset($_GET['pagesettings']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Responsive Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900 dark:bg-gray-900 dark:text-gray-100">
  <div class="flex h-screen">
    <!-- Mobile Menu Button -->
    <button id="menuToggle" class="md:hidden p-4 text-gray-500 dark:text-gray-300">
      <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
      </svg>
    </button>

    <!-- Sidebar -->
    <div id="sidebar" class="fixed top-0 left-0 w-64 bg-white dark:bg-gray-800 shadow-lg flex flex-col min-h-screen transform -translate-x-full md:translate-x-0 transition-transform">
      <div class="flex items-center justify-center h-16 bg-gray-200 dark:bg-gray-700">
        <h1 class="text-lg font-bold">Dashboard</h1>
      </div>
      <nav class="flex-1 px-4 py-2">
        <ul class="space-y-2">
          <li>
            <a href="#" class="block px-4 py-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700">Media</a>
          </li>
          <li>
            <a href="#" class="block px-4 py-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700">Albums</a>
          </li>
          <li>
            <a href="#" class="block px-4 py-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700">Essays</a>
          </li>
          <li>
            <a href="#" class="block px-4 py-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700">Pages</a>
          </li>
        </ul>
      </nav>
      <div class="px-4 py-2 border-t border-gray-300 dark:border-gray-700">
        <ul class="space-y-2">
          <li>
            <a href="dashboard.php?usersettings" class="block px-4 py-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700">Account Settings</a>
          </li>
          <li>
            <a href="dashboard.php?pagesettings" class="block px-4 py-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700">Page Settings</a>
          </li>
          <li>
            <a href="login.php?logout=true" class="block px-4 py-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700">Logout</a>
          </li>
          <li>
            <button 
              id="themeToggle" 
              class="block w-full text-left px-4 py-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700">
              Dark/Light
            </button>
          </li>
        </ul>
      </div>
    </div>

    <!-- Overlay for sidebar on small screens -->
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-10 hidden md:hidden"></div>

    <!-- Main Content -->
    <div class="flex-1 p-6 ml-0 md:ml-64 transition-all">
    <?php if ($showUserSettings): ?>
                <!-- Inhalt der Benutzereinstellungen -->
                <?php include __DIR__ . '/usersettings.php'; ?>
            <?php elseif ($showPageSettings): ?>
              <?php include __DIR__ . '/pagesettings.php'; ?>
            <?php else: ?>
                <!-- Standard Dashboard-Inhalt -->
                <h2 class="text-2xl font-semibold mb-4">Willkommen im Dashboard</h2>
                <p>Hier kannst du deine Daten verwalten.</p>
            <?php endif; ?>
    </div>
  </div>

  <script>
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');

    // Toggle sidebar
    menuToggle.addEventListener('click', () => {
      sidebar.classList.toggle('-translate-x-full');
      overlay.classList.toggle('hidden');
    });

    // Close sidebar when clicking overlay
    overlay.addEventListener('click', () => {
      sidebar.classList.add('-translate-x-full');
      overlay.classList.add('hidden');
    });

    // Toggle dark/light mode
    const themeToggle = document.getElementById('themeToggle');
    themeToggle.addEventListener('click', () => {
      document.documentElement.classList.toggle('dark');
    });
  </script>
</body>
</html>
