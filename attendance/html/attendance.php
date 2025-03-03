<?php
session_start();

// Überprüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['username'])) {
    header("Location: html/login.php");
    exit();
}

// Datenbankverbindung
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

// POST-Daten aus dem Formular im Modal empfangen
$roomName = $_POST['room'];
$date = $_POST['date'];
$units = $_POST['units'];
$subject = $_POST['subject'];
$teacherShortName = 'STRR';

// Anwesenheitsdaten abrufen
$attendances = [];

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
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1" name="viewport">
    <title>tgm | Anwesenheiten</title>
    <link crossorigin="anonymous" href="../bootstrap/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../img/tgm_logo_triangle.svg" rel="icon" type="image/svg+xml">
    <link href="../css/global_style.css" rel="stylesheet">
</head>
<body data-bs-theme="dark">

<header>
    <nav class="navbar navbar-expand-lg bg-body-tertiary rounded m-3 shadow">
        <div class="container-fluid">
            <a class="navbar-brand me-4" href="#">
                <div>
                    <img alt="Logo" class="d-inline-block align-text-top" src="../img/tgm_logo_light.svg" width="70" onclick="window.location.href='../index.php'">
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
                                <button class="btn btn-primary me-2 mb-1" onclick="window.location.href='../index.php'">Startseite</button>
                            </li>
                            <li class="nav-item">
                                <button class="btn btn-outline-primary me-2 mb-1"
                                        onclick="window.location.href='statistics.php'">Statistiken
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
            <div class="col-12 text-center mb-3">
                <h1 class="mt-3">Anwesenheitskontrolle</h1>
                <div class="d-flex justify-content-center">
                    <h3>
                        <span class="badge bg-success me-2"><i class="bi bi-door-closed"></i> <?php echo htmlspecialchars($roomName); ?></span>
                        <span class="badge bg-primary me-2"><i class="bi bi-journal-text"></i> <?php echo htmlspecialchars($subject); ?></span>
                        <span class="badge bg-secondary me-2"><i class="bi bi-calendar-event"></i> <?php echo htmlspecialchars($date); ?></span>
                        <span class="badge bg-secondary me-2"><i class="bi bi-clock"></i> <?php echo htmlspecialchars(implode(', ', $units)); ?></span>
                        <span class="badge bg-secondary me-2"><i class="bi bi-person"></i> <?php echo htmlspecialchars($teacherShortName); ?></span>
                    </h3>
                </div>
                <div class="text-center mt-3">
                    <button class="btn btn-success" id="exportButton">Ins Klassenbuch übertragen</button>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-11 col-md-10 col-lg-8" id="attendanceTables">
                    <!-- Tabellen werden hier dynamisch eingefügt -->
                </div>
            </div>
        </div>
    </div>
</main>

<footer>
    <!-- Footer -->
</footer>


<!-- Modal for assigning card to a student -->
<div class="modal fade" id="assignCardModal" tabindex="-1" aria-labelledby="assignCardModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignCardModalLabel">Assign Card to Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="assignCardForm">
                    <div class="mb-3">
                        <label for="classSelect" class="form-label">Class</label>
                        <select class="form-select" id="classSelect" required>
                            <option value="" selected disabled>Select a class</option>
                            <!-- Options will be populated dynamically -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="studentSelect" class="form-label">Student</label>
                        <select class="form-select" id="studentSelect" required>
                            <option value="" selected disabled>Select a student</option>
                            <!-- Options will be populated dynamically -->
                        </select>
                    </div>
                    <input type="hidden" id="cardIdInput">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="assignCardButton">Assign</button>
            </div>
        </div>
    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', function () {
        var socket = new WebSocket('ws://localhost:8080');

        socket.onopen = function() {
            console.log('WebSocket connection established');
        };

        socket.onmessage = function(event) {
            var data = JSON.parse(event.data);
            console.log('New attendance:', data);

            if (!data.firstname || !data.lastname || !data.class) {
                // Show the modal to assign the card to a student
                document.getElementById('cardIdInput').value = data.card_id;
                fetchClasses();
                var assignCardModal = new bootstrap.Modal(document.getElementById('assignCardModal'));
                assignCardModal.show();
            } else {
                // Update the attendance page with the new data
                updateAttendanceTable(data);
            }
        };

        socket.onerror = function(error) {
            console.error('WebSocket error:', error);
        };

        socket.onclose = function() {
            console.log('WebSocket connection closed');
        };
    });

    function updateAttendanceTable(data) {
        const attendanceTables = document.getElementById('attendanceTables');
        let classTable = document.getElementById('class-' + data.class);

        if (!classTable) {
            // Create a new table for the class if it doesn't exist
            classTable = document.createElement('div');
            classTable.id = 'class-' + data.class;
            classTable.innerHTML = `
        <div style="background: #212529" class="p-2 mb-2 rounded">
            <h2>${data.class}</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Vorname</th>
                        <th scope="col">Nachname</th>
                        <th scope="col">Uhrzeit</th>
                    </tr>
                </thead>
                <tbody id="class-${data.class}-body">
                </tbody>
            </table>
        </div>
        `;

            // Insert the new class table in the correct position
            let inserted = false;
            const existingTables = attendanceTables.children;
            for (let i = 0; i < existingTables.length; i++) {
                if (existingTables[i].id.localeCompare(classTable.id) > 0) {
                    attendanceTables.insertBefore(classTable, existingTables[i]);
                    inserted = true;
                    break;
                }
            }
            if (!inserted) {
                attendanceTables.appendChild(classTable);
            }
        }

        const tableBody = document.getElementById('class-' + data.class + '-body');
        const newRow = document.createElement('tr');

        newRow.innerHTML = `
    <td>${data.catalog_number}</td>
    <td>${data.firstname}</td>
    <td>${data.lastname}</td>
    <td>${new Date(data.login_timestamp).toLocaleTimeString()}</td>
    `;

        // Insert the new row in the correct position to keep the table sorted by catalog_number
        let rowInserted = false;
        for (let i = 0; i < tableBody.rows.length; i++) {
            if (parseInt(tableBody.rows[i].cells[0].innerText) > data.catalog_number) {
                tableBody.insertBefore(newRow, tableBody.rows[i]);
                rowInserted = true;
                break;
            }
        }
        if (!rowInserted) {
            tableBody.appendChild(newRow);
        }
    }

    document.getElementById('exportButton').addEventListener('click', async function () {
        const attendanceTables = document.getElementById('attendanceTables');
        const classTables = attendanceTables.querySelectorAll('div[id^="class-"]');
        const students = [];

        classTables.forEach(classTable => {
            const className = classTable.querySelector('h2').innerText;
            const rows = classTable.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const catalogNumber = row.cells[0].innerText;
                const firstName = row.cells[1].innerText;
                const lastName = row.cells[2].innerText;

                students.push({
                    class: className,
                    catalog_number: catalogNumber,
                    firstname: firstName,
                    lastname: lastName
                });
                console.log('Added student:', students[students.length - 1]);
            });
        });

        const dataToSend = {
            room: '<?php echo $roomName; ?>',
            date: '<?php echo $date; ?>',
            units: '<?php echo implode(", ", $units); ?>',
            subject: '<?php echo $subject; ?>',
            teacherShortName: '<?php echo $teacherShortName; ?>',
            students: students
        };

        try {
            const response = await fetch('../api/export_to_excel.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(dataToSend)
            });

            if (!response.ok) throw new Error('Fehler beim Exportieren der Daten!');
            alert('Daten erfolgreich exportiert!');
        } catch (error) {
            console.error('Fehler:', error);
            alert('Fehler beim Exportieren der Daten!');
        }
    });

    document.getElementById('classSelect').addEventListener('change', function () {
        var className = this.value;
        fetchStudents(className);
    });

    document.getElementById('assignCardButton').addEventListener('click', function () {
        var cardId = document.getElementById('cardIdInput').value;
        var studentUsername = document.getElementById('studentSelect').value;

        if (studentUsername) {
            assignCardToStudent(cardId, studentUsername);
        }
    });

    function fetchClasses() {
        // Fetch the list of classes from the server
        fetch('../api/get_classes.php')
            .then(response => response.json())
            .then(classes => {
                var classSelect = document.getElementById('classSelect');
                classSelect.innerHTML = '<option value="" selected disabled>Select a class</option>';
                classes.forEach(cls => {
                    var option = document.createElement('option');
                    option.value = cls.name;
                    option.textContent = cls.name;
                    classSelect.appendChild(option);
                });
            })
            .catch(error => console.error('Error fetching classes:', error));
    }

    function fetchStudents(className) {
        // Fetch the list of students for the selected class from the server
        fetch(`../api/get_students.php?class=${className}`)
            .then(response => response.json())
            .then(students => {
                var studentSelect = document.getElementById('studentSelect');
                studentSelect.innerHTML = '<option value="" selected disabled>Select a student</option>';
                students.forEach(student => {
                    var option = document.createElement('option');
                    option.value = student.username;
                    option.textContent = `${student.firstname} ${student.lastname}`;
                    studentSelect.appendChild(option);
                });
            })
            .catch(error => console.error('Error fetching students:', error));
    }

    function assignCardToStudent(cardId, studentUsername) {
        // Send the card assignment to the server
        fetch('../api/assign_card.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ card_id: cardId, student_username: studentUsername })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert('Card assigned successfully!');
                var assignCardModal = bootstrap.Modal.getInstance(document.getElementById('assignCardModal'));
                assignCardModal.hide();
            } else {
                alert('Error assigning card: ' + result.error);
            }
        })
        .catch(error => console.error('Error assigning card:', error));
    }
</script>

<script crossorigin="anonymous"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>