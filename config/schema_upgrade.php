<?php
function ensure_schema(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS grades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        subject_id INT NOT NULL,
        teacher_id INT NULL,
        grade TINYINT NOT NULL,
        grade_date DATE NOT NULL,
        comment VARCHAR(255) DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_grades_student (student_id),
        INDEX idx_grades_subject (subject_id),
        CONSTRAINT fk_grades_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        CONSTRAINT fk_grades_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE RESTRICT,
        CONSTRAINT fk_grades_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        reset_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ip_address VARCHAR(45) DEFAULT '',
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $col = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='email'")->fetchColumn();
    if (!$col) $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(150) DEFAULT '' AFTER login");

    $col = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='avatar'")->fetchColumn();
    if (!$col) $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT '' AFTER email");

    $col = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='groups' AND COLUMN_NAME='curator_teacher_id'")->fetchColumn();
    if (!$col) $pdo->exec("ALTER TABLE groups ADD COLUMN curator_teacher_id INT NULL AFTER course");

    $pdo->exec("CREATE TABLE IF NOT EXISTS academic_years (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(20) NOT NULL UNIQUE,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS semesters (
        id INT AUTO_INCREMENT PRIMARY KEY,
        academic_year_id INT NOT NULL,
        name VARCHAR(50) NOT NULL,
        semester_number TINYINT NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 0,
        UNIQUE KEY uq_semester(academic_year_id,semester_number),
        FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS app_settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value TEXT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    ensure_academic_period($pdo);
    seed_subjects($pdo);
    import_source_people_once($pdo);
}

function ensure_academic_period(PDO $pdo): void {
    $year = (int)date('Y');
    $month = (int)date('n');
    $sy = $month >= 9 ? $year : $year - 1;
    $yn = $sy . '/' . ($sy + 1);

    $hasActiveYear = (int)$pdo->query("SELECT COUNT(*) FROM academic_years WHERE is_active=1")->fetchColumn();
    $st = $pdo->prepare("INSERT IGNORE INTO academic_years(name,start_date,end_date,is_active) VALUES(?,?,?,?)");
    $st->execute([$yn, $sy.'-09-01', ($sy+1).'-06-30', $hasActiveYear ? 0 : 1]);

    $q = $pdo->prepare("SELECT id FROM academic_years WHERE name=?");
    $q->execute([$yn]);
    $ay = (int)$q->fetchColumn();

    if ($ay) {
        $hasActiveSemester = (int)$pdo->query("SELECT COUNT(*) FROM semesters WHERE is_active=1")->fetchColumn();
        $firstActive = (!$hasActiveSemester && ($month >= 9 || $month == 1)) ? 1 : 0;
        $secondActive = (!$hasActiveSemester && ($month >= 2 && $month <= 6)) ? 1 : 0;

        $st = $pdo->prepare("INSERT IGNORE INTO semesters(academic_year_id,name,semester_number,start_date,end_date,is_active) VALUES(?,?,?,?,?,?)");
        $st->execute([$ay, '1 семестр', 1, $sy.'-09-01', ($sy+1).'-01-15', $firstActive]);
        $st->execute([$ay, '2 семестр', 2, ($sy+1).'-01-16', ($sy+1).'-06-30', $secondActive]);

        if (!$hasActiveSemester && !$firstActive && !$secondActive) {
            $pdo->prepare("UPDATE semesters SET is_active=1 WHERE academic_year_id=? ORDER BY semester_number LIMIT 1")->execute([$ay]);
        }
    }
}

function seed_subjects(PDO $pdo): void {
    $subjects = [
        ["ОП.01 — Основы программирования","Основы C++, Python и алгоритмизации"],
        ["ОП.02 — Алгоритмы и структуры данных","Алгоритмы, массивы, списки, деревья и оценка сложности"],
        ["МДК.01.01 — Базы данных","SQL, проектирование, нормализация и MySQL"],
        ["МДК.01.02 — Веб-разработка","HTML, CSS, JavaScript, PHP и работа с сервером"],
        ["МДК.02.01 — Компьютерные сети","Сетевые технологии, TCP/IP и оборудование"],
        ["МДК.02.02 — Операционные системы","Windows, Linux и основы администрирования"],
        ["МДК.03.01 — Информационная безопасность","Защита информации и безопасная разработка"],
        ["МДК.03.02 — Тестирование ПО","Тест-кейсы, дефекты и автоматизация тестирования"],
        ["ОГСЭ.01 — Математика","Алгебра, анализ и прикладная математика"],
        ["ОГСЭ.02 — Английский язык","Общий и профессиональный английский язык"],
        ["МДК.04.01 — Проектирование ПО","UML, архитектура и жизненный цикл программного продукта"],
        ["МДК.04.02 — Мобильная разработка","Проектирование и разработка мобильных приложений"],
        ["ОП.03 — Компьютерная графика","Растровая, векторная графика и основы UI"],
        ["ОП.04 — Экономика и менеджмент","Экономика предприятия, проекты и управление"],
        ["ОП.05 — Основы искусственного интеллекта","Основы машинного обучения и применения ИИ"],
    ];
    $st = $pdo->prepare("INSERT IGNORE INTO subjects(name,description) VALUES(?,?)");
    foreach ($subjects as $r) $st->execute($r);
}

function repair_imported_student_emails(PDO $pdo): void {
    // В исходном контингенте встречаются адреса с кириллическими символами.
    // Они сохраняются в исходном файле, но для входа/восстановления пароля
    // аккаунту нужен валидный email. Исправляем только пустые/некорректные адреса.
    $rows=$pdo->query("SELECT u.id,u.login,u.email,s.id student_id FROM users u JOIN students s ON s.user_id=u.id WHERE u.role='student'")->fetchAll();
    $uu=$pdo->prepare("UPDATE users SET email=? WHERE id=?");
    $ss=$pdo->prepare("UPDATE students SET email=? WHERE id=?");
    foreach($rows as $r){
        $email=trim((string)$r['email']);
        if($email!=='' && filter_var($email,FILTER_VALIDATE_EMAIL)) continue;
        $fallback=$r['login'].'@students.kz';
        $uu->execute([$fallback,$r['id']]);
        $ss->execute([$fallback,$r['student_id']]);
    }
}

function import_source_people_once(PDO $pdo): void {
    $version = 'source-personnel-firstcourse-20260924-v4-loginfix';
    $q = $pdo->prepare("SELECT setting_value FROM app_settings WHERE setting_key='people_dataset_version'");
    $q->execute();
    repair_imported_student_emails($pdo);
    if ($q->fetchColumn() === $version) return;

    $sourceFile = __DIR__ . '/source_people.php';
    if (!is_file($sourceFile)) {
        throw new RuntimeException('Не найден файл source_people.php с импортированным контингентом.');
    }
    $data = require $sourceFile;
    $teachers = $data['teachers'] ?? [];
    $students = $data['students'] ?? [];
    $groups = $data['groups'] ?? [];

    if (count($teachers) !== 10) {
        throw new RuntimeException('В наборе должно быть ровно 10 преподавателей.');
    }
    if (!$students) {
        throw new RuntimeException('Список студентов пуст.');
    }

    $teacherHash = '$2y$12$UKSrlJe4UCPAmu72.1hYXuAh1rv8OO2JcjhtvJ44ZAc420YfKYotq';
    $studentHash = '$2y$12$AyxiaBcBT1/veXev/190TulZfV7vjrytLE7b5L1J5GJfISCQbJT16';

    try {
        // Импорт выполняется одной транзакцией: при любой ошибке старые данные не теряются.
        // ALTER TABLE здесь намеренно не используется, потому что MySQL делает для него
        // неявный COMMIT и мог оставить базу наполовину очищенной. В интерфейсе номера
        // групп/предметов всё равно выводятся последовательно (1, 2, 3...).
        $pdo->beginTransaction();
        $pdo->exec("UPDATE groups SET curator_teacher_id=NULL");
        $pdo->exec("DELETE FROM attendance");
        $pdo->exec("DELETE FROM grades");
        $pdo->exec("DELETE FROM lessons");
        $pdo->exec("DELETE FROM students");
        $pdo->exec("DELETE FROM teachers");
        $pdo->exec("DELETE FROM users WHERE role IN ('teacher','student')");
        $pdo->exec("DELETE FROM groups");

        $groupInsert = $pdo->prepare("INSERT INTO groups(name,specialty,course) VALUES(?,?,?)");
        $groupIds = [];
        foreach ($groups as $g) {
            $groupInsert->execute([$g['name'], $g['specialty'] ?: 'Учебная группа', (int)$g['course']]);
            $groupIds[$g['name']] = (int)$pdo->lastInsertId();
        }

        $userInsert = $pdo->prepare("INSERT INTO users(login,email,password,role,status) VALUES(?,?,?,?, 'active')");
        $teacherInsert = $pdo->prepare("INSERT INTO teachers(user_id,surname,name,patronymic,phone,email) VALUES(?,?,?,?,?,?)");
        $teacherIds = [];
        $usedLogins = [];

        // Учитываем оставшиеся системные аккаунты (например, admin), чтобы импорт
        // никогда не падал на UNIQUE users.login.
        foreach ($pdo->query("SELECT login FROM users")->fetchAll(PDO::FETCH_COLUMN) as $existingLogin) {
            $usedLogins[strtolower((string)$existingLogin)] = true;
        }

        $makeUniqueLogin = static function(string $base) use (&$usedLogins): string {
            $base = trim($base);
            if ($base === '') $base = 'user';
            $candidate = $base;
            $suffix = 2;
            while (isset($usedLogins[strtolower($candidate)])) {
                $candidate = $base . $suffix;
                $suffix++;
            }
            $usedLogins[strtolower($candidate)] = true;
            return $candidate;
        };

        foreach ($teachers as $t) {
            $login = $makeUniqueLogin((string)$t['login']);
            $userInsert->execute([$login, $t['email'], $teacherHash, 'teacher']);
            $uid = (int)$pdo->lastInsertId();
            $teacherInsert->execute([$uid, $t['surname'], $t['name'], $t['patronymic'] ?? '', '', $t['email']]);
            $teacherIds[] = (int)$pdo->lastInsertId();
        }

        $studentInsert = $pdo->prepare("INSERT INTO students(user_id,group_id,surname,name,patronymic,birth_date,phone,email,status) VALUES(?,?,?,?,?,?,?,?, 'active')");
        $studentIdsByGroup = [];
        $studentRows = [];
        foreach ($students as $s) {
            if (empty($groupIds[$s['group']])) continue;
            $login = $makeUniqueLogin((string)$s['login']);
            $email = trim((string)($s['user_email'] ?? $s['email'] ?? ''));
            // Если email был автоматически построен из старого логина, синхронизируем
            // его с новым уникальным логином.
            $oldGeneratedEmail = ((string)$s['login']) . '@students.kz';
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strcasecmp($email, $oldGeneratedEmail) === 0) {
                $email = $login . '@students.kz';
            }
            $userInsert->execute([$login, $email, $studentHash, 'student']);
            $uid = (int)$pdo->lastInsertId();
            $studentInsert->execute([
                $uid,
                $groupIds[$s['group']],
                $s['surname'],
                $s['name'],
                $s['patronymic'] ?? '',
                $s['birth_date'] ?: null,
                $s['phone'] ?? '',
                $email
            ]);
            $sid = (int)$pdo->lastInsertId();
            $studentIdsByGroup[$groupIds[$s['group']]][] = $sid;
            $studentRows[] = ['id'=>$sid, 'group_id'=>$groupIds[$s['group']]];
        }

        // Кураторы: распределяем все группы между 10 преподавателями.
        if ($teacherIds) {
            $cur = $pdo->prepare("UPDATE groups SET curator_teacher_id=? WHERE id=?");
            $i = 0;
            foreach ($groupIds as $gid) {
                $cur->execute([$teacherIds[$i % count($teacherIds)], $gid]);
                $i++;
            }
        }

        seed_source_schedule_attendance_grades($pdo, $teacherIds, array_values($groupIds), $studentIdsByGroup, $studentRows);

        $set = $pdo->prepare("INSERT INTO app_settings(setting_key,setting_value) VALUES('people_dataset_version',?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
        $set->execute([$version]);
        $pdo->commit();

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function seed_source_schedule_attendance_grades(PDO $pdo, array $teacherIds, array $groupIds, array $studentIdsByGroup, array $studentRows): void {
    if (!$teacherIds || !$groupIds || !$studentRows) return;

    $subjectIds = $pdo->query("SELECT id FROM subjects ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
    if (!$subjectIds) return;

    $lessonInsert = $pdo->prepare("INSERT INTO lessons(subject_id,teacher_id,group_id,lesson_date,start_time,end_time,classroom,lesson_type) VALUES(?,?,?,?,?,?,?,?)");
    $attInsert = $pdo->prepare("INSERT INTO attendance(lesson_id,student_id,status,comment) VALUES(?,?,?,?)");

    $times = [
        ['08:30:00','10:00:00'],
        ['10:10:00','11:40:00'],
        ['12:00:00','13:30:00'],
        ['13:40:00','15:10:00'],
    ];
    $types = ['Лекция','Практика','Лабораторная'];
    $today = new DateTimeImmutable('today');

    // Шесть месяцев истории: графики и статистика сразу заполнены.
    foreach ($groupIds as $gi => $gid) {
        for ($m = 5; $m >= 0; $m--) {
            $date = $today->modify("-{$m} months")->modify('-'.(($gi + $m) % 8).' days');
            [$start,$end] = $times[($gi + $m) % count($times)];
            $subjectId = (int)$subjectIds[($gi * 2 + $m) % count($subjectIds)];
            $teacherId = (int)$teacherIds[($gi + $m) % count($teacherIds)];
            $lessonInsert->execute([$subjectId,$teacherId,$gid,$date->format('Y-m-d'),$start,$end,(string)(201 + (($gi+$m)%18)),$types[($gi+$m)%3]]);
            $lessonId = (int)$pdo->lastInsertId();

            foreach ($studentIdsByGroup[$gid] ?? [] as $si => $sid) {
                $v = ($sid + $gi + $m + $si) % 12;
                if ($v === 0) { $status='ABSENT'; $comment='Без уважительной причины'; }
                elseif ($v === 1) { $status='LATE'; $comment='Опоздание на 10 минут'; }
                elseif ($v === 2) { $status='EXCUSED'; $comment='Уважительная причина'; }
                else { $status='PRESENT'; $comment=''; }
                $attInsert->execute([$lessonId,$sid,$status,$comment]);
            }
        }

        // Ближайшие пары для поиска у преподавателей и администратора.
        foreach ([1,3,5] as $k => $days) {
            $date = $today->modify("+{$days} days");
            [$start,$end] = $times[($gi + $k) % count($times)];
            $subjectId = (int)$subjectIds[($gi * 3 + $k) % count($subjectIds)];
            $teacherId = (int)$teacherIds[($gi + $k) % count($teacherIds)];
            $lessonInsert->execute([$subjectId,$teacherId,$gid,$date->format('Y-m-d'),$start,$end,(string)(201 + (($gi+$k)%18)),$types[($gi+$k)%3]]);
        }
    }

    // По 5 оценок каждому студенту, распределённых по месяцам.
    $gradeInsert = $pdo->prepare("INSERT INTO grades(student_id,subject_id,teacher_id,grade,grade_date,comment) VALUES(?,?,?,?,?,?)");
    $comments = ['Практическая работа','Домашняя работа','Контрольная работа','Ответ на занятии','Лабораторная работа'];

    foreach ($studentRows as $idx => $st) {
        for ($m = 4; $m >= 0; $m--) {
            $date = $today->modify("-{$m} months")->modify('-'.(($idx+$m)%10).' days');
            $subjectId = (int)$subjectIds[($idx + $m*2) % count($subjectIds)];
            $teacherId = (int)$teacherIds[($st['group_id'] + $m) % count($teacherIds)];
            $grade = 3 + (($idx + $m) % 3);
            $gradeInsert->execute([$st['id'],$subjectId,$teacherId,$grade,$date->format('Y-m-d'),$comments[($idx+$m)%count($comments)]]);
        }
    }
}
