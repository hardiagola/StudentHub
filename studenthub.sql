
CREATE DATABASE IF NOT EXISTS studenthub_db;
USE studenthub_db;

CREATE TABLE IF NOT EXISTS students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mobile VARCHAR(10) NOT NULL,
    enrollment VARCHAR(30) NOT NULL UNIQUE,
    course VARCHAR(100) NOT NULL,
    year TINYINT UNSIGNED NOT NULL,
    gender VARCHAR(30) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CHECK (year BETWEEN 1 AND 4)
);

CREATE TABLE IF NOT EXISTS events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(150) NOT NULL,
    event_date DATE NOT NULL,
    venue VARCHAR(150) NOT NULL,
    category VARCHAR(50) NOT NULL,
    description TEXT
);

CREATE TABLE IF NOT EXISTS registrations (
    registration_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    event_id INT NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_registration_student
        FOREIGN KEY (student_id)
        REFERENCES students(student_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_registration_event
        FOREIGN KEY (event_id)
        REFERENCES events(event_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT uq_student_event
        UNIQUE (student_id, event_id)
);

INSERT INTO events (event_name, event_date, venue, category, description)
VALUES
('Tech Fest', '2026-11-10', 'Main Auditorium', 'Technical', 'Annual technology festival'),
('Coding Contest', '2026-11-12', 'Computer Lab', 'Technical', 'Programming competition'),
('Cultural Night', '2026-11-15', 'Open Ground', 'Cultural', 'Student cultural event'),
('Robotics Workshop', '2026-11-18', 'Innovation Lab', 'Workshop', 'Hands-on robotics workshop'),
('AI Seminar', '2026-11-20', 'Seminar Hall', 'Technical', 'Seminar on artificial intelligence');
