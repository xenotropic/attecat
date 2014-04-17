PRAGMA foreign_keys = ON;
CREATE TABLE student (student_name TEXT, current_section TEXT, notes TEXT, current_student INTEGER, student_id INTEGER PRIMARY KEY);
CREATE TABLE attendance_record (student_id INTEGER, attendance TEXT, section TEXT, notes TEXT, attendance_date TEXT, FOREIGN KEY (student_id) REFERENCES student (student_id));
CREATE TABLE admin ( password TEXT, salt TEXT);
