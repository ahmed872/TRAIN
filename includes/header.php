<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/hospital-system/assets/css/style.css" rel="stylesheet">
</head>

<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="/hospital-system/departments/index.php">Hospital System</a>
            <div class="navbar-nav me-auto">
                <a class="nav-link text-white" href="/hospital-system/departments/index.php">Departments</a>
                <a class="nav-link text-white" href="/hospital-system/doctors/index.php">Doctors</a>
                <a class="nav-link text-white" href="/hospital-system/patients/index.php">Patients</a>
                <a class="nav-link text-white" href="/hospital-system/appointments/index.php">Appointments</a>
                <a class="nav-link text-white" href="/hospital-system/medical_records/index.php">Medical Records</a>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a class="nav-link text-white" href="/hospital-system/users/index.php">Users</a>
                <?php endif; ?>
            </div>
            <?php if (isset($_SESSION['username'])): ?>
                <div class="navbar-nav">
                    <span class="nav-link text-white-50">
                        <?= htmlspecialchars($_SESSION['full_name'] ?: $_SESSION['username']) ?>
                    </span>
                    <a class="nav-link text-white" href="/hospital-system/auth/logout.php">Logout</a>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container mb-5">