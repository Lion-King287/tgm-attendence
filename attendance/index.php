<?php
session_start();

// Überprüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['username'])) {
    header("Location: html/login.php");
    exit();
}

// Benutzerdaten aus der Session laden
$fullName = isset($_SESSION['cn']) ? $_SESSION['cn'] : "Unbekannt";
if ($_SESSION['employeeType'] === "lehrer") {
    $role = "Lehrkraft";
} else if ($_SESSION['employeeType'] === "schueler") {
    $role = "Schüler";
} else {
    $role = "Unbekannte Rolle";
}

$initials = strtoupper(substr($fullName, 0, 1)) . strtoupper(substr(isset(explode(' ', $fullName)[1]) ? explode(' ', $fullName)[1] : '', 0, 1));

// Verbindung zur Datenbank herstellen
$host = 'localhost';
$dbname = 'attendance';
$username = 'admin';
$password = 'admin';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Verbindung fehlgeschlagen: " . $e->getMessage());
}

// Räume aus der Datenbank abrufen
$stmt = $pdo->query('SELECT room_name FROM api_keys_rooms');
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query('SELECT short_name, long_name FROM subjects');
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1" name="viewport">
    <title>tgm | Anwesenheiten</title>
    <link crossorigin="anonymous" href="bootstrap/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="img/tgm_logo_triangle.svg" rel="icon" type="image/svg+xml">
    <link href="css/global_style.css" rel="stylesheet">
</head>
<body data-bs-theme="dark">

<?php if (isset($_GET['login']) && $_GET['login'] == 'success') { ?>
    <div class="toast-container position-fixed top-0 start-50 translate-middle-x pt-3">
        <div id="logoutToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <img alt="tgm Logo" class="me-2" src="img/tgm_logo_triangle.svg" style="width: 15px;">
                <strong class="me-auto">tgm | Anwesenheiten</strong>
                <small>Jetzt</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Du hast dich erfolgreich eingeloggt!
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var toastEl = document.getElementById('logoutToast');
            var toast = new bootstrap.Toast(toastEl, {delay: 2500});
            toast.show();
        });
    </script>
<?php } ?>

<header>
    <nav class="navbar navbar-expand-lg bg-body-tertiary rounded m-3 shadow">
        <div class="container-fluid">
            <a class="navbar-brand me-4" href="#">
                <div>
                    <img alt="Logo" class="d-inline-block align-text-top"
                         src="img/tgm_logo_light.svg" width="70">
                </div>
                <div>
                    Anwesenheiten
                </div>
            </a>
            <div class="collapse navbar-collapse" id="navbarToggler">
                <div class="col-12">
                    <div class="d-flex align-items-center justify-content-between">
                        <ul class="navbar-nav me-auto">
                            <li class="nav-item">
                                <button class="btn btn-primary me-2 mb-1">Startseite</button>
                            </li>
                            <li class="nav-item">
                                <button class="btn btn-outline-primary me-2 mb-1"
                                        onclick="window.location.href='html/statistics.html'">Statistiken
                                </button>
                            </li>
                        </ul>
                        <div class="d-flex align-items-center">
                            <div class="dropdown">
                                <button class="btn dropdown-toggle d-flex align-items-center" type="button"
                                        id="userMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                    <div class="text-end me-3">
                                        <div class="fw-bold"><?php echo $fullName; ?></div>
                                        <span class="badge text-bg-dark"><div><?php echo $role; ?></div></span>
                                    </div>
                                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center"
                                         style="width: 50px; height: 50px;">
                                        <?php echo $initials; ?>
                                    </div>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenuButton">
                                    <li>
                                        <form action="html/logout.php" method="post">
                                            <button class="dropdown-item text-danger" type="submit"><i
                                                        class="bi bi-door-closed"></i> Abmelden
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <button aria-controls="navbarToggler" aria-expanded="false" aria-label="Toggle navigation"
                    class="navbar-toggler col-12 mt-1"
                    data-bs-target="#navbarToggler" data-bs-toggle="collapse" type="button">
                <span class="navbar-toggler-icon"></span> Navigation
            </button>
        </div>
    </nav>
</header>

<main>
    <div class="container-fluid">
        <div class="row justify-content-center m-1 mb-3 bg-body-tertiary rounded shadow">
            <div class="col-12 text-center">
                <h1 class="mt-3">Startseite</h1>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#attendanceModal">
                Neue Anwesenheitsprüfung starten
            </button>

        </div>
    </div>
</main>

<div class="modal fade" id="attendanceModal" tabindex="-1" aria-labelledby="attendanceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="attendanceModalLabel">Neue Anwesenheitsprüfung</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="html/attendance.php" method="post">
                    <div class="mb-3">
                        <i class="bi bi-door-closed"></i> <label for="roomSelect" class="form-label">Raum</label>
                        <select class="form-select" id="roomSelect" name="room" required>
                            <option selected disabled value="">Wählen Sie einen Raum</option>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?php echo htmlspecialchars($room['room_name']); ?>">
                                    <?php echo htmlspecialchars($room['room_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <i class="bi bi-journal-text"></i> <label for="subjectSelect" class="form-label">Fach</label>
                        <select class="form-select" id="subjectSelect" name="subject" required>
                            <option selected disabled value="">Wählen Sie ein Fach</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo htmlspecialchars($subject['short_name']); ?>">
                                    <?php echo htmlspecialchars($subject['short_name']); ?>
                                    (<?php echo htmlspecialchars($subject['long_name']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <i class="bi bi-calendar-event"></i> <label for="datePicker" class="form-label">Datum</label>
                        <input type="date" class="form-control" id="datePicker" name="date" required>
                    </div>
                    <div class="mb-3">
                        <i class="bi bi-clock"></i> <label class="form-label">Unterrichtseinheiten</label>
                        <div class="btn-group" role="group" aria-label="Unterrichtseinheiten">
                            <input type="checkbox" class="btn-check" id="unit1" name="units[]" value="1"
                                   autocomplete="off">
                            <label class="btn btn-outline-primary" for="unit1">1</label>

                            <input type="checkbox" class="btn-check" id="unit2" name="units[]" value="2"
                                   autocomplete="off">
                            <label class="btn btn-outline-primary" for="unit2">2</label>

                            <input type="checkbox" class="btn-check" id="unit3" name="units[]" value="3"
                                   autocomplete="off">
                            <label class="btn btn-outline-primary" for="unit3">3</label>

                            <input type="checkbox" class="btn-check" id="unit4" name="units[]" value="4"
                                   autocomplete="off">
                            <label class="btn btn-outline-primary" for="unit4">4</label>

                            <input type="checkbox" class="btn-check" id="unit5" name="units[]" value="5"
                                   autocomplete="off">
                            <label class="btn btn-outline-primary" for="unit5">5</label>

                            <input type="checkbox" class="btn-check" id="unit6" name="units[]" value="6"
                                   autocomplete="off">
                            <label class="btn btn-outline-primary" for="unit6">6</label>

                            <input type="checkbox" class="btn-check" id="unit7" name="units[]" value="7"
                                   autocomplete="off">
                            <label class="btn btn-outline-primary" for="unit7">7</label>

                            <input type="checkbox" class="btn-check" id="unit8" name="units[]" value="8"
                                   autocomplete="off">
                            <label class="btn btn-outline-primary" for="unit8">8</label>

                            <input type="checkbox" class="btn-check" id="unit9" name="units[]" value="9"
                                   autocomplete="off">
                            <label class="btn btn-outline-primary" for="unit9">9</label>

                            <input type="checkbox" class="btn-check" id="unit10" name="units[]" value="10"
                                   autocomplete="off">
                            <label class="btn btn-outline-primary" for="unit10">10</label>

                            <input type="checkbox" class="btn-check" id="unit11" name="units[]" value="11"
                                   autocomplete="off">
                            <label class="btn btn-outline-primary" for="unit11">11</label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Anwesenheitsprüfung starten</button>
                </form>
            </div>
        </div>
    </div>
</div>

<footer>
    <!-- Footer -->
</footer>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Set the current date in the date picker
        var today = new Date().toISOString().split('T')[0];
        document.getElementById('datePicker').value = today;

        // Lesson times
        var lessonTimes = [
            {start: '08:00', end: '08:50', unit: 'unit1'},
            {start: '08:50', end: '09:40', unit: 'unit2'},
            {start: '09:50', end: '10:40', unit: 'unit3'},
            {start: '10:40', end: '11:30', unit: 'unit4'},
            {start: '11:30', end: '12:20', unit: 'unit5'},
            {start: '12:30', end: '13:20', unit: 'unit6'},
            {start: '13:20', end: '14:10', unit: 'unit7'},
            {start: '14:10', end: '15:00', unit: 'unit8'},
            {start: '15:10', end: '16:00', unit: 'unit9'},
            {start: '16:00', end: '16:50', unit: 'unit10'},
            {start: '17:00', end: '17:45', unit: 'unit11'}
        ];

        // Get current time
        var now = new Date();
        var currentTime = ('0' + now.getHours()).slice(-2) + ':' + ('0' + now.getMinutes()).slice(-2);

        // Preselect the current lesson unit
        lessonTimes.forEach(function (lesson) {
            if (currentTime >= lesson.start && currentTime <= lesson.end) {
                document.getElementById(lesson.unit).checked = true;
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function (event) {
            var unitsChecked = document.querySelectorAll('input[name="units[]"]:checked').length;
            if (unitsChecked === 0) {
                event.preventDefault();
                alert('Bitte wählen Sie mindestens eine Unterrichtseinheit aus.');
            }
        });
    });
</script>

<script crossorigin="anonymous"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        src="bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>