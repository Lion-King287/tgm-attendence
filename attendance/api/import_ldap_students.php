<?php
        // LDAP connection settings
        $ldap_host = "ldap://dc-01.tgm.ac.at";
        $ldap_port = 389;
        $ldap_user_domain = "tgm\\"; // Windows domain login prefix
        $ldap_base_dn = "ou=HIT,ou=Schueler,ou=People,ou=tgm,dc=tgm,dc=ac,dc=at";
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

        // Search for students
        $filter = "(objectClass=person)";
        $search = ldap_search($ldap_conn, $ldap_base_dn, $filter);
        if ($search === false) {
            die("LDAP search failed: " . ldap_error($ldap_conn));
        }
        $entries = ldap_get_entries($ldap_conn, $search);

        // Connect to the database
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Insert students into the database
        foreach ($entries as $entry) {
            if (isset($entry['samaccountname'][0], $entry['givenname'][0], $entry['sn'][0], $entry['department'][0])) {
                $username = $entry['samaccountname'][0];
                $firstname = $entry['givenname'][0];
                $lastname = $entry['sn'][0];
                $class = $entry['department'][0];

                $stmt = $pdo->prepare("INSERT INTO students (username, firstname, lastname, class, catalog_number) VALUES (?, ?, ?, ?, 0)");
                $stmt->execute([$username, $firstname, $lastname, $class]);
            }
        }

        // Calculate and update catalog numbers
        $classes = $pdo->query("SELECT DISTINCT class FROM students")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($classes as $class) {
            $students = $pdo->prepare("SELECT id FROM students WHERE class = ? ORDER BY lastname, firstname");
            $students->execute([$class]);
            $students = $students->fetchAll(PDO::FETCH_COLUMN);

            foreach ($students as $index => $student_id) {
                $catalog_number = $index + 1;
                $stmt = $pdo->prepare("UPDATE students SET catalog_number = ? WHERE id = ?");
                $stmt->execute([$catalog_number, $student_id]);
            }
        }

        // Close LDAP connection
        ldap_unbind($ldap_conn);

        echo "Import and catalog number assignment completed.";
        ?>