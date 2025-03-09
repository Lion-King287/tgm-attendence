<?php
session_start();

// Überprüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['username'])) {
    header("Location: ../html/login.php");
    exit();
} elseif ($_SESSION['isTeacher'] !== 1) {
    header("Location: ../index.php");
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
$teacherShortName = '';
$class = $_POST['class'] ?? '';
$students = [];

if ($class) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE class = :class");
    $stmt->execute(['class' => $class]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Anwesenheitsdaten abrufen
$attendances = [];

// Benutzerdaten aus der Session laden
$fullName = isset($_SESSION['cn']) ? $_SESSION['cn'] : "Unbekannt";
if ($_SESSION['employeeType'] === "lehrer") {
    $role = "Lehrkraft";
    $teacherShortName = $_SESSION['employeeNumber'];
} else if ($_SESSION['employeeType'] === "schueler") {
    $role = "Schüler";
    $teacherShortName = 'KARS';
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
    <script src="https://alcdn.msauth.net/browser/2.28.0/js/msal-browser.min.js"></script>
</head>
<body data-bs-theme="dark">

<header>
    <nav class="navbar navbar-expand-lg bg-body-tertiary rounded m-3 shadow">
        <div class="container-fluid">
            <a class="navbar-brand me-4" href="#">
                <div>
                    <img alt="Logo" class="d-inline-block align-text-top" src="../img/tgm_logo_light.svg" width="70"
                         onclick="window.location.href='../index.php'">
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
                                <button class="btn btn-primary me-2 mb-1" onclick="window.location.href='../index.php'">
                                    Startseite
                                </button>
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
                                        <form action="logout.php" method="post">
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
                    <h4>
                        <span class="badge bg-success me-2"><i
                                class="bi bi-door-closed"></i> <?php echo htmlspecialchars($roomName); ?></span>
                        <span class="badge bg-primary me-2"><i
                                class="bi bi-journal-text"></i> <?php echo htmlspecialchars($subject); ?></span>
                        <span class="badge bg-secondary me-2"><i
                                class="bi bi-calendar-event"></i> <?php $dateObj = new DateTime($date);
                            echo htmlspecialchars($dateObj->format('d.m.Y')); ?></span>
                        <span class="badge bg-secondary me-2"><i
                                class="bi bi-clock"></i> <?php echo htmlspecialchars(implode(', ', $units)); ?></span>
                        <span class="badge bg-secondary me-2"><i
                                class="bi bi-person"></i> <?php echo htmlspecialchars($teacherShortName); ?></span>
                    </h4>
                </div>
                <div class="text-center mt-1">
                    <h4>
                        <span class="badge bg-secondary me-2"><i
                                class="bi bi-person-check"></i> Erfasste Personen: <span
                                id="attendanceCount">0</span></span>
                        <button class="btn btn-success badge" id="exportButton">Ins Klassenbuch übertragen</button>
                    </h4>
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
                <h5 class="modal-title" id="assignCardModalLabel">Karte zuweisen?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning" role="alert">
                    <i class="bi bi-person-vcard"></i> Eine unbekannte Karte wurde gescannt, wollen Sie diese einem Schüler zuweisen?
                </div>
                <form id="assignCardForm">
                    <div class="mb-3">
                        <i class="bi bi-person-video3"></i> <label for="classSelect" class="form-label">Klasse</label>
                        <select class="form-select" id="classSelect" required>
                            <option value="" selected disabled>Wähle eine Klasse</option>
                            <!-- Options will be populated dynamically -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <i class="bi bi-person-plus"></i> <label for="studentSelect" class="form-label">Schüler</label>
                        <select class="form-select" id="studentSelect" required>
                            <option value="" selected disabled>Wähle einen Schüler</option>
                            <!-- Options will be populated dynamically -->
                        </select>
                    </div>
                    <input type="hidden" id="cardIdInput">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-primary" id="assignCardButton">Zuweisen</button>
            </div>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportModalLabel">Exportieren</h5>
            </div>
            <div class="modal-body text-center" id="exportModalBody">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Daten werden ins Klassenbuch übertragen...</p>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="classInput" value="<?php echo htmlspecialchars($class); ?>">


<script>

    document.addEventListener('DOMContentLoaded', function () {
        var token = 'czEZ3TDDWLmk8lXgJKVtcmrs6SOE8PW7ehBlpTW6EVeYaLxD7RlqKT9vdhL91pZU';
        var socket = new WebSocket('ws://localhost:8080');
        fetchStudentsFirst(<?php echo json_encode($class); ?>);

        socket.onopen = function () {
            console.log('WebSocket connection established');
            socket.send(JSON.stringify({ action: 'authenticate', token: token }));
        };

        socket.onmessage = function (event) {
            var data = JSON.parse(event.data);
            if (data.action === 'authenticated') {
                console.log('WebSocket authenticated');
            } else if (data.action === 'error') {
                console.error('WebSocket error:', data.message);
                socket.close();
            } else {

                if (!data.firstname || !data.lastname || !data.class) {
                    document.getElementById('cardIdInput').value = data.card_id;
                    fetchClasses();
                    var assignCardModal = new bootstrap.Modal(document.getElementById('assignCardModal'));
                    assignCardModal.show();
                } else {
                    if (data.class !== document.getElementById('classInput').value) {
                        return;
                    }
                    console.log('New attendance:', data);
                    updateAttendanceTable(data);
                }
            }
        };

        socket.onerror = function (error) {
            console.error('WebSocket error:', error);
        };

        socket.onclose = function () {
            console.log('WebSocket connection closed');
        };
    });

    function updateAttendanceTable(data) {
        const attendanceTables = document.getElementById('attendanceTables');
        let classTable = document.getElementById('class-' + data.class);

        if (!classTable) {
            classTable = document.createElement('div');
            classTable.id = 'class-' + data.class;
            classTable.innerHTML = `
            <div style="background: #212529" class="p-2 mb-2 rounded">
                <h2>${data.class}</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Nachname</th>
                            <th scope="col">Vorname</th>
                            <th scope="col">Uhrzeit</th>
                            <th scope="col">Zu spät</th>
                            <th scope="col">Status</th>
                            <th scope="col">Aktion</th>
                        </tr>
                    </thead>
                    <tbody id="class-${data.class}-body">
                    </tbody>
                </table>
            </div>
        `;
            attendanceTables.appendChild(classTable);
        }

        const tableBody = document.getElementById('class-' + data.class + '-body');
        let existingRow = Array.from(tableBody.rows).find(row => row.cells[0].innerText == data.catalog_number);

        if (existingRow) {
            existingRow.cells[3].innerText = data.login_timestamp ? new Date(data.login_timestamp).toLocaleTimeString() : '';
            existingRow.cells[5].innerHTML = data.login_timestamp ? '<span class="badge bg-success">Anwesend</span>' : '<span class="badge bg-danger">Abwesend</span>';
            existingRow.querySelector('.late-checkbox').disabled = !data.login_timestamp;
            existingRow.querySelector('.btn i').className = data.login_timestamp ? 'bi bi-box-arrow-in-left' : 'bi bi-box-arrow-in-right';
            updateAttendanceCount();
            return;
        }

        const newRow = document.createElement('tr');
        newRow.innerHTML = `
        <td>${data.catalog_number}</td>
        <td>${data.lastname}</td>
        <td>${data.firstname}</td>
        <td>${data.login_timestamp ? new Date(data.login_timestamp).toLocaleTimeString() : ''}</td>
        <td>
            <div class="row">
                <input type="checkbox" class="late-checkbox col-1 me-2" onchange="toggleLateInput(this)" ${!data.login_timestamp ? 'disabled' : ''}>
                <input type="number" class="form-control form-control-sm late-minutes col-1" min="0" placeholder="" style="width: 50px;" disabled>
            </div>
        </td>
        <td>${data.login_timestamp ? '<span class="badge bg-success">Anwesend</span>' : '<span class="badge bg-danger">Fehlend</span>'}</td>
        <td><button class="btn btn-primary btn-sm" onclick="toggleAttendanceStatus(this)"><i class="${data.login_timestamp ? 'bi bi-box-arrow-in-left' : 'bi bi-box-arrow-in-right'}"></i></button></td>
    `;
        tableBody.appendChild(newRow);
        updateAttendanceCount();
    }

    function toggleAttendanceStatus(button) {
        const row = button.closest('tr');
        const statusCell = row.cells[5];
        const isPresent = statusCell.innerHTML.includes('bg-success');
        const lateCheckbox = row.querySelector('.late-checkbox');
        const icon = button.querySelector('i');

        if (isPresent) {
            statusCell.innerHTML = '<span class="badge bg-danger">Fehlend</span>';
            lateCheckbox.disabled = true;
            icon.className = 'bi bi-box-arrow-in-right';
        } else {
            statusCell.innerHTML = '<span class="badge bg-success">Anwesend</span>';
            lateCheckbox.disabled = false;
            icon.className = 'bi bi-box-arrow-in-left';
        }
        updateAttendanceCount();
    }

    function fetchStudentsFirst(className) {
        fetch(`../api/get_students.php?class=${className}`)
            .then(response => response.json())
            .then(students => {
                // Sort students by catalog number
                students.sort((a, b) => a.catalog_number - b.catalog_number);

                students.forEach(student => {
                    updateAttendanceTable({
                        catalog_number: student.catalog_number,
                        lastname: student.lastname,
                        firstname: student.firstname,
                        login_timestamp: null,
                        class: className
                    });
                });
            })
            .catch(error => console.error('Error fetching students:', error));
    }

    function toggleLateInput(checkbox) {
        const minutesInput = checkbox.closest('td').querySelector('.late-minutes');
        if (checkbox.checked) {
            minutesInput.disabled = false;
        } else {
            minutesInput.disabled = true;
            minutesInput.value = ''; // Clear the input field when unchecked
        }
    }

    function deleteStudent(button) {
        const row = button.closest('tr');
        const tableBody = row.parentElement;
        row.remove();

        // Check if the table body is empty
        if (tableBody.rows.length === 0) {
            const classTable = tableBody.closest('div[id^="class-"]');
            classTable.remove();
        }
    }

    function updateAttendanceCount() {
        const attendanceTables = document.getElementById('attendanceTables');
        const rows = attendanceTables.querySelectorAll('tbody tr');
        let count = 0;

        rows.forEach(row => {
            const statusCell = row.cells[5];
            if (statusCell.innerHTML.includes('bg-success')) {
                count++;
            }
        });

        document.getElementById('attendanceCount').innerText = count;
    }

    document.addEventListener('DOMContentLoaded', function () {
        updateAttendanceCount();
    });

    document.getElementById('exportButton').addEventListener('click', async function () {
        document.getElementById('exportModalBody').innerHTML = `
        <div class="spinner-border" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p>Daten werden ins Klassenbuch übertragen...</p>
    `;

        const attendanceTables = document.getElementById('attendanceTables');
        const classTables = attendanceTables.querySelectorAll('div[id^="class-"]');
        const exportStudents = [];

        classTables.forEach(classTable => {
            const className = classTable.querySelector('h2').innerText;
            const rows = classTable.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const catalogNumber = row.cells[0].innerText;
                const firstName = row.cells[1].innerText;
                const lastName = row.cells[2].innerText;
                const lateMinutes = row.querySelector('.late-minutes').value;
                const isAbsent = row.cells[5].innerHTML.includes('bg-danger');

                exportStudents.push({
                    class: className,
                    catalog_number: catalogNumber,
                    firstname: firstName,
                    lastname: lastName,
                    late_minutes: lateMinutes,
                    is_absent: isAbsent
                });
            });
        });

        const dataToSend = {
            room: '<?php echo $roomName; ?>',
            date: '<?php echo $date; ?>',
            units: '<?php echo implode(", ", $units); ?>',
            subject: '<?php echo $subject; ?>',
            cellSubject: 'A',
            teacherShortName: '<?php echo $teacherShortName; ?>',
            students: exportStudents
        };

        const exportModal = new bootstrap.Modal(document.getElementById('exportModal'));
        exportModal.show();

        try {
            const response = await fetch('../api/export_to_excel.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(dataToSend)
            });

            const responseText = await response.text(); // Get the response text
            console.log(responseText); // Log the response text

            const result = JSON.parse(responseText); // Parse the response as JSON

            if (!response.ok) throw new Error(result.error || 'Fehler beim Exportieren der Daten!');

            document.getElementById('exportModalBody').innerHTML = `
            <div class="alert alert-success fade show" role="alert">
                Daten erfolgreich exportiert!
            </div>
            <button type="button" class="btn btn-secondary mt-1" data-bs-dismiss="modal">Schließen</button>
        `;
        } catch (error) {
            let errorMessage = `Fehler beim Exportieren der Daten: ${error.message}`;
            let loginButton = '';

            if (error.message === 'Access token is invalid' || error.message === 'No access token found') {
                errorMessage = 'Sie sind aktuell nicht mit Microsoft angemeldet!';
                loginButton = `
                <button type="button" class="btn btn-primary mt-1 me-2" id="microsoftLoginButton" onclick="loginWithMicrosoft()"><i class="bi bi-microsoft"></i> Mit Microsoft anmelden</button>
            `;
            }

            document.getElementById('exportModalBody').innerHTML = `
            <div class="alert alert-danger fade show" role="alert">
                ${errorMessage}
            </div>
            ${loginButton}
            <button type="button" class="btn btn-secondary mt-1" data-bs-dismiss="modal">Schließen</button>
        `;
        }
    });

    function loginWithMicrosoft() {
        let popup = window.open(
            "https://login.microsoftonline.com/d91e1d12-7b79-4ee7-ac76-168c1e1bd1c0/oauth2/v2.0/authorize?client_id=e6549516-74f9-4887-95df-ef92d24547cd&response_type=token&redirect_uri=https://projekte.tgm.ac.at/3ahit/skarajeh/attendance/ms/auth/callback&scope=User.Read Files.ReadWrite.All Sites.ReadWrite.All",
            "msLogin",
            "width=600,height=600"
        );

        window.addEventListener("message", function(event) {
            if (event.origin !== "https://projekte.tgm.ac.at") return; // Sicherheitsprüfung

            let accessToken = event.data;

            // Per POST an token_receiver.php senden
            fetch("../api/token_receiver.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "microsoft_token=" + encodeURIComponent(accessToken)
            })
                .then(response => response.text())
                .then(data => {
                    console.log("Server Response:", data);
                    displayAlert("Login erfolgreich! Bitte starten Sie den Export erneut.", "success");
                    document.getElementById('microsoftLoginButton').style.display = 'none';
                })
                .catch(error => {
                    console.error("Fehler:", error);
                    displayAlert("Login fehlgeschlagen: " + error.message, "danger");
                });

            const exportModal = new bootstrap.Modal(document.getElementById('exportModal'));
            exportModal.hide();
        }, false);
    }

    function displayAlert(message, type) {
        document.getElementById('exportModalBody').innerHTML = `
        <div class="alert alert-${type} fade show" role="alert">
            ${message}
        </div>
        <button type="button" class="btn btn-primary mt-1 me-2" id="microsoftLoginButton" onclick="loginWithMicrosoft()"><i class="bi bi-microsoft"></i> Mit Microsoft anmelden</button>
        <button type="button" class="btn btn-secondary mt-1" onclick="closeExportModal()">Schließen</button>
            `;
    }

    function closeExportModal() {
        const exportModal = new bootstrap.Modal(document.getElementById('exportModal'));
        exportModal.hide();
    }




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
                // Sort classes alphabetically
                classes.sort((a, b) => a.class.localeCompare(b.class));

                var studentSelect = document.getElementById('studentSelect');
                studentSelect.innerHTML = '<option value="" selected disabled>Wähle einen Schüler</option>';

                var classSelect = document.getElementById('classSelect');
                classSelect.innerHTML = '<option value="" selected disabled>Wähle eine Klasse</option>';
                classes.forEach(cls => {
                    var option = document.createElement('option');
                    option.value = cls.class; // Ensure this matches the key in the returned JSON
                    option.textContent = cls.class; // Ensure this matches the key in the returned JSON
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
                // Sort students by last name, then by first name
                students.sort((a, b) => {
                    if (a.lastname === b.lastname) {
                        return a.firstname.localeCompare(b.firstname);
                    }
                    return a.lastname.localeCompare(b.lastname);
                });

                var studentSelect = document.getElementById('studentSelect');
                studentSelect.innerHTML = '<option value="" selected disabled>Wähle einen Schüler</option>';
                students.forEach(student => {
                    var option = document.createElement('option');
                    option.value = student.username;
                    option.textContent = `${student.lastname} ${student.firstname}`;
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
            body: JSON.stringify({card_id: cardId, student_username: studentUsername})
        })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
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