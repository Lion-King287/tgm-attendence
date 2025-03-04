<?php
session_start();

// Überprüfen, ob der Benutzer eingeloggt ist
if (isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// LDAP-Server-Details
$ldap_server = "ldap://dc-01.tgm.ac.at";
$ldap_port = 389;
$ldap_user_domain = "tgm\\"; // Windows Domänen-Login-Präfix
$ldap_base_dn = "ou=HIT,ou=Schueler,ou=People,ou=tgm,dc=tgm,dc=ac,dc=at";

// Formular-Verarbeitung
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    // Verbindung zum LDAP-Server herstellen
    $ldap_conn = ldap_connect($ldap_server, $ldap_port);
    if (!$ldap_conn) {
        die("Fehler: Konnte keine Verbindung zum LDAP-Server herstellen.");
    }

    // LDAP-Optionen setzen
    ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

    // Benutzer-Login mit vollständigem DN
    $ldap_bind = @ldap_bind($ldap_conn, $ldap_user_domain . $username, $password);
    if ($ldap_bind) {
        // Suche nach Benutzerdaten
        $filter = "(sAMAccountName=$username)";
        $search = ldap_search($ldap_conn, $ldap_base_dn, $filter);
        $entries = ldap_get_entries($ldap_conn, $search);

        if ($entries["count"] > 0) {
            $cn = $entries[0]["cn"][0]; // Common Name (Vollständiger Name)
            $employeeType = isset($entries[0]["employeetype"][0]) ? $entries[0]["employeetype"][0] : "Unbekannt"; // Lehrer oder Schüler
            $department = isset($entries[0]["department"][0]) ? $entries[0]["department"][0] : "Keine Angabe"; // Klasse für Schüler

            $_SESSION['username'] = $username;
            $_SESSION['cn'] = $cn;
            $_SESSION['employeeType'] = $employeeType;
            $_SESSION['department'] = $department;

            header("Location: ../index.php?login=success"); // Weiterleitung nach erfolgreichem Login
            exit();
        } else {
            $loginError = "Fehler: Benutzer nicht gefunden.";
        }

        ldap_unbind($ldap_conn);
    } else {
        $loginError = "Fehler: Falscher Benutzername oder Passwort.";
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1" name="viewport">
    <title>tgm | Anwesenheiten Login</title>
    <link crossorigin="anonymous" href="../bootstrap/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../img/tgm_logo_triangle.svg" rel="icon" type="image/svg+xml">
</head>
<body data-bs-theme="dark">

<?php if (isset($_GET['logout']) && $_GET['logout'] == 'success') { ?>
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="logoutToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <img alt="tgm Logo" class="me-2" src="../img/tgm_logo_triangle.svg" style="width: 15px;">
                <strong class="me-auto">tgm | Anwesenheiten</strong>
                <small>Jetzt</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Du hast dich erfolgreich ausgeloggt!
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

<main>
    <div class="container">
        <div class="row justify-content-center mt-3 mt-xl-5">
            <div class="col-12 col-md-6 col-lg-4">
                <form class="card shadow p-4 needs-validation" method="post" novalidate
                      style="background-color: rgba(26,29,32,0.52)">
                    <img alt="tgm Logo" class="w-50 mx-auto mb-3" src="../img/tgm_logo_light.svg">
                    <h1 class="h3 mb-3 fw-normal text-center">Benutzerportal Login</h1>

                    <div id="loginErrorMessage">
                        <?php if (isset($loginError)) { ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-triangle"></i>
                                <?php echo $loginError; ?>
                            </div>
                        <?php } ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="username">Benutzername</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input class="form-control rounded-end" id="username" name="username"
                                   placeholder="Benutzername" required
                                   type="text">
                            <div class="invalid-feedback">
                                Bitte geben Sie Ihren Benutzernamen ein.
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Passwort</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-key"></i></span>
                            <input class="form-control rounded-end" id="password" name="password" placeholder="Passwort"
                                   required
                                   type="password">
                            <div class="invalid-feedback">
                                Bitte geben Sie Ihr Passwort ein.
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-primary mb-2" type="submit">
                        <i class="bi bi-check-lg"></i> Anmelden
                    </button>
                    <button class="btn btn-secondary" data-bs-target="#forgotPassword" data-bs-toggle="modal"
                            type="button">
                        Passwort vergessen?
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div aria-hidden="true" aria-labelledby="forgotPasswordLabel" class="modal fade" id="forgotPassword" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="forgotPasswordLabel">Passwort vergessen?</h1>
                    <button aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"></button>
                </div>
                <div class="modal-body">
                    Sie können versuchen, Ihr Passwort unter <a href="https://owa.tgm.ac.at/">owa.tgm.ac.at</a>,
                    während dem Login-Prozess, mithilfe der Schaltfläche "Passwort vergessen", zurückzusetzen.
                    <br><br>
                    Sollte dies nicht funktionieren wenden Sie sich bitte an ihren Abteilungsadministrator und bitten
                    Sie um ein neues Passwort.
                </div>
            </div>
        </div>
    </div>
</main>

<script crossorigin="anonymous"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        src="../bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        'use strict'

        // Fetch all the forms we want to apply custom Bootstrap validation styles to
        let forms = document.querySelectorAll('.needs-validation')

        // Loop over them and prevent submission
        Array.prototype.slice.call(forms)
            .forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                        form.classList.add('was-validated')
                    }
                }, false)
            })
    })()
</script>
</body>
</html>