<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <title>Barangay Management System</title>
    <!-- Tailwind via CDN for simplicity -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    @viteReactRefresh
    @vite('resources/js/app.jsx')
    @inertiaHead
  </head>
  <body class="bg-gray-100 font-sans antialiased">
    @inertia
  </body>
</html>