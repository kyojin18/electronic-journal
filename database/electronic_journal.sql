CREATE DATABASE IF NOT EXISTS electronic_journal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE electronic_journal;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS app_settings, semesters, academic_years, password_reset_log, grades, attendance, lessons, subjects, students, teachers, groups, users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
 id INT AUTO_INCREMENT PRIMARY KEY,
 login VARCHAR(100) NOT NULL UNIQUE,
 email VARCHAR(150) DEFAULT '',
 avatar VARCHAR(255) DEFAULT '',
 password VARCHAR(255) NOT NULL,
 role ENUM('admin','teacher','student') NOT NULL,
 status ENUM('active','blocked') NOT NULL DEFAULT 'active',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE groups (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL UNIQUE,
 specialty VARCHAR(150) NOT NULL,
 course TINYINT NOT NULL DEFAULT 1,
 curator_teacher_id INT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE teachers (
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NOT NULL UNIQUE,
 surname VARCHAR(100) NOT NULL,
 name VARCHAR(100) NOT NULL,
 patronymic VARCHAR(100) DEFAULT '',
 phone VARCHAR(30) DEFAULT '',
 email VARCHAR(150) DEFAULT '',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE subjects (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(150) NOT NULL UNIQUE,
 description TEXT DEFAULT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE students (
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NOT NULL UNIQUE,
 group_id INT NOT NULL,
 surname VARCHAR(100) NOT NULL,
 name VARCHAR(100) NOT NULL,
 patronymic VARCHAR(100) DEFAULT '',
 birth_date DATE DEFAULT NULL,
 phone VARCHAR(30) DEFAULT '',
 email VARCHAR(150) DEFAULT '',
 status ENUM('active','inactive') NOT NULL DEFAULT 'active',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY(group_id) REFERENCES groups(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE lessons (
 id INT AUTO_INCREMENT PRIMARY KEY,
 subject_id INT NOT NULL,
 teacher_id INT NOT NULL,
 group_id INT NOT NULL,
 lesson_date DATE NOT NULL,
 start_time TIME NOT NULL,
 end_time TIME NOT NULL,
 classroom VARCHAR(50) DEFAULT '',
 lesson_type VARCHAR(50) NOT NULL DEFAULT 'Практика',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(subject_id) REFERENCES subjects(id) ON DELETE RESTRICT,
 FOREIGN KEY(teacher_id) REFERENCES teachers(id) ON DELETE RESTRICT,
 FOREIGN KEY(group_id) REFERENCES groups(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE attendance (
 id INT AUTO_INCREMENT PRIMARY KEY,
 lesson_id INT NOT NULL,
 student_id INT NOT NULL,
 status ENUM('PRESENT','ABSENT','LATE','EXCUSED') NOT NULL DEFAULT 'PRESENT',
 comment VARCHAR(255) DEFAULT '',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_attendance(lesson_id,student_id),
 FOREIGN KEY(lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
 FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE grades (
 id INT AUTO_INCREMENT PRIMARY KEY,
 student_id INT NOT NULL,
 subject_id INT NOT NULL,
 teacher_id INT NULL,
 grade TINYINT NOT NULL,
 grade_date DATE NOT NULL,
 comment VARCHAR(255) DEFAULT '',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_grades_student(student_id),
 INDEX idx_grades_subject(subject_id),
 FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE,
 FOREIGN KEY(subject_id) REFERENCES subjects(id) ON DELETE RESTRICT,
 FOREIGN KEY(teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_reset_log (
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NOT NULL,
 reset_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 ip_address VARCHAR(45) DEFAULT '',
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE academic_years (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(20) NOT NULL UNIQUE,
 start_date DATE NOT NULL,
 end_date DATE NOT NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE semesters (
 id INT AUTO_INCREMENT PRIMARY KEY,
 academic_year_id INT NOT NULL,
 name VARCHAR(50) NOT NULL,
 semester_number TINYINT NOT NULL,
 start_date DATE NOT NULL,
 end_date DATE NOT NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 0,
 UNIQUE KEY uq_semester(academic_year_id,semester_number),
 FOREIGN KEY(academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE app_settings (
 setting_key VARCHAR(100) PRIMARY KEY,
 setting_value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users(login,email,password,role,status) VALUES
('admin','admin@college.kz','$2y$12$EdLR.5F54rVObBbgvKZhs.MIX5MM5IC1vg.4nm35UOnf90MreoVjC','admin','active');

INSERT INTO subjects(name,description) VALUES
('ОП.01 — Основы программирования','Основы C++, Python и алгоритмизации'),
('ОП.02 — Алгоритмы и структуры данных','Алгоритмы, массивы, списки, деревья и оценка сложности'),
('МДК.01.01 — Базы данных','SQL, проектирование, нормализация и MySQL'),
('МДК.01.02 — Веб-разработка','HTML, CSS, JavaScript, PHP и работа с сервером'),
('МДК.02.01 — Компьютерные сети','Сетевые технологии, TCP/IP и оборудование'),
('МДК.02.02 — Операционные системы','Windows, Linux и основы администрирования'),
('МДК.03.01 — Информационная безопасность','Защита информации и безопасная разработка'),
('МДК.03.02 — Тестирование ПО','Тест-кейсы, дефекты и автоматизация тестирования'),
('ОГСЭ.01 — Математика','Алгебра, анализ и прикладная математика'),
('ОГСЭ.02 — Английский язык','Общий и профессиональный английский язык'),
('МДК.04.01 — Проектирование ПО','UML, архитектура и жизненный цикл программного продукта'),
('МДК.04.02 — Мобильная разработка','Проектирование и разработка мобильных приложений'),
('ОП.03 — Компьютерная графика','Растровая, векторная графика и основы UI'),
('ОП.04 — Экономика и менеджмент','Экономика предприятия, проекты и управление'),
('ОП.05 — Основы искусственного интеллекта','Основы машинного обучения и применения ИИ');

-- ВАЖНО:
-- 10 преподавателей из «Персонал.xlsx» и все 426 студентов из
-- «Контингент вктоу ПО.xls» автоматически импортируются при первом открытии сайта
-- из config/source_people.php. Никакие старые тестовые преподаватели/студенты не добавляются.
