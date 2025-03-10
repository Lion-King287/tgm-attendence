<?php
session_start();

// Überprüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['username'])) {
    header("Location: ../html/login.php");
    exit();
} elseif ($_SESSION['isAdmin'] !== 1) {
    header("Location: ../index.php");
    exit();
}

// LDAP connection settings
$ldap_host = "ldap://dc-01.tgm.ac.at";
$ldap_port = 389;
$ldap_user_domain = "tgm\\"; // Windows domain login prefix
$ldap_base_dn = "ou=People,ou=tgm,dc=tgm,dc=ac,dc=at";
$ldap_user = "tgm\\skarajeh";
$ldap_pass = "";

// Database connection settings
$db_host = "localhost";
$db_name = "attendance";
$db_user = "admin";
$db_pass = "admin";

// Connect to LDAP
$ldap_conn = ldap_connect($ldap_host, $ldap_port);
if (!$ldap_conn) {
    die("Failed to connect to LDAP server.");
}

// Set LDAP options
ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

// Bind to LDAP
if (!@ldap_bind($ldap_conn, $ldap_user, $ldap_pass)) {
    die("Failed to bind to LDAP server: " . ldap_error($ldap_conn));
}

// Search for all teachers
$filter = "(&(objectClass=person)(employeeType=Lehrer))";
$search = ldap_search($ldap_conn, $ldap_base_dn, $filter);
if ($search === false) {
    die("LDAP search failed: " . ldap_error($ldap_conn));
}
$entries = ldap_get_entries($ldap_conn, $search);

// Connect to the database
$pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Insert teachers into the database
foreach ($entries as $entry) {
    if (isset($entry['samaccountname'][0], $entry['givenname'][0], $entry['sn'][0], $entry['employeenumber'][0], $entry['memberof'])) {
        $memberof = $entry['memberof'];
        $isMemberOfTargetGroup = false;

        foreach ($memberof as $group) {
            if (strpos($group, 'CN=lehrer') !== false && strpos($group, 'HIT') !== false) {
                $isMemberOfTargetGroup = true;
                break;
            }
        }

        if ($isMemberOfTargetGroup) {
            $username = $entry['samaccountname'][0];
            $firstname = $entry['givenname'][0];
            $lastname = $entry['sn'][0];
            $shortName = $entry['employeenumber'][0];

            $stmt = $pdo->prepare("INSERT INTO teachers (username, firstname, lastname, shortName) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $firstname, $lastname, $shortName]);
        }
    }
}

// Close LDAP connection
ldap_unbind($ldap_conn);

echo "Import of teachers completed.";
?>