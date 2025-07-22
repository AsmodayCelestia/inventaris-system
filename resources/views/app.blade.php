<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventaris APP</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Custom CSS untuk layout tambahan (jika ada, nanti bisa diatur via Vite) -->
    <style>
        /* Beberapa gaya dasar untuk memastikan Vue app mengisi layar */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden; //Hindari scrollbar ganda 
        }
        #app {
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        /* Gaya dasar untuk halaman yang akan dimuat Vue (misalnya loading atau login) */
        .initial-app-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            width: 100%;
            background-color: #f4f6f9; /* Warna background AdminLTE */
        }
    </style>
</head>
<body class="hold-transition">
    <div id="app">
        <!-- Aplikasi Vue.js akan di-mount di sini -->
    </div>

    <!-- jQuery (AdminLTE dependency) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <!-- Bootstrap 4 (AdminLTE dependency) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE App -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

    <!-- Vite (untuk memuat bundle Vue.js kamu) -->
    @vite('resources/js/app.js')
</body>
</html>