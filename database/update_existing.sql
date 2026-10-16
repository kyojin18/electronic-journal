USE electronic_journal;
CREATE TABLE IF NOT EXISTS app_settings (
 setting_key VARCHAR(100) PRIMARY KEY,
 setting_value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DELETE FROM app_settings WHERE setting_key='people_dataset_version';
-- После этого откройте сайт: config/schema_upgrade.php удалит старых преподавателей/студентов
-- и импортирует 10 преподавателей и все 426 студентов из файлов-источников.
