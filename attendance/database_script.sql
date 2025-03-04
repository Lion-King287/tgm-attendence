CREATE TABLE students
(
    id             INT AUTO_INCREMENT PRIMARY KEY,
    username       VARCHAR(50)  NOT NULL,
    firstname      VARCHAR(100) NOT NULL,
    lastname       VARCHAR(100) NOT NULL,
    class          VARCHAR(50)  NOT NULL,
    catalog_number INT          NOT NULL,
    UNIQUE (username)
);
CREATE TABLE teachers
(
    id        INT AUTO_INCREMENT PRIMARY KEY,
    username  VARCHAR(50)  NOT NULL,
    firstname VARCHAR(100) NOT NULL,
    lastname  VARCHAR(100) NOT NULL,
    UNIQUE (username)
);
CREATE TABLE student_cards
(
    id               INT AUTO_INCREMENT PRIMARY KEY,
    card_id          VARCHAR(50) NOT NULL,
    student_username VARCHAR(50) NOT NULL,
    FOREIGN KEY (student_username) REFERENCES students (username) ON DELETE CASCADE
);
CREATE TABLE teacher_cards
(
    id               INT AUTO_INCREMENT PRIMARY KEY,
    card_id          VARCHAR(50) NOT NULL,
    teacher_username VARCHAR(50) NOT NULL,
    FOREIGN KEY (teacher_username) REFERENCES teachers (username) ON DELETE CASCADE
);
CREATE TABLE api_keys_rooms
(
    id        INT AUTO_INCREMENT PRIMARY KEY,
    api_key   VARCHAR(50)  NOT NULL,
    room_name VARCHAR(100) NOT NULL
);
CREATE TABLE subjects
(
    id         INT AUTO_INCREMENT PRIMARY KEY,
    short_name VARCHAR(50)  NOT NULL,
    long_name  VARCHAR(100) NOT NULL
);
CREATE TABLE attendance_sessions
(
    id                       INT AUTO_INCREMENT PRIMARY KEY,
    room_name                VARCHAR(100) NOT NULL, -- Der Raum, in dem die Anwesenheit erfasst wird
    teacher_username         VARCHAR(50)  NOT NULL, -- Der Lehrer, der die Sitzung erfasst
    subject                  VARCHAR(100) NOT NULL, -- Fach (z.B. Mathematik, Englisch)
    lesson_hours             VARCHAR(50)  NOT NULL, -- Stunden (z.B. "1-2", "3", "4-6")
    date                     DATE         NOT NULL, -- Datum der Sitzung
    transferred_to_classbook BOOLEAN DEFAULT FALSE, -- Ob die gesamte Sitzung ins Klassenbuch übertragen wurde
    FOREIGN KEY (teacher_username) REFERENCES teachers (username) ON DELETE CASCADE
);
CREATE TABLE attendance_entries
(
    id                       INT AUTO_INCREMENT PRIMARY KEY,
    attendance_session_id    INT         NOT NULL,  -- Die ID der Anwesenheits-Sitzung aus der `attendance_sessions`-Tabelle
    student_username         VARCHAR(50) NOT NULL,  -- Der Benutzername des Schülers
    login_timestamp          BIGINT      NOT NULL,  -- Der Zeitpunkt des Logins in Millisekunden
    transferred_to_classbook BOOLEAN DEFAULT FALSE, -- Ob die Anwesenheit ins Klassenbuch übertragen wurde
    FOREIGN KEY (attendance_session_id) REFERENCES attendance_sessions (id) ON DELETE CASCADE,
    FOREIGN KEY (student_username) REFERENCES students (username) ON DELETE CASCADE
);
