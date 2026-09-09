-- =============================================================================
-- نظام إدارة المستشفى — بنية قاعدة البيانات
-- طريقة الاستخدام:  mysql -u root -p < schema.sql
-- =============================================================================

CREATE DATABASE IF NOT EXISTS hospital
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE hospital;

-- -----------------------------------------------------------------------------
-- الأقسام
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS departments (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(100) NOT NULL,
    description TEXT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_departments_name (name)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- الأطباء
-- حذف القسم بيخلي department_id = NULL بدل ما يمنع الحذف
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS doctors (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name           VARCHAR(100) NOT NULL,
    specialization VARCHAR(100) NULL,
    department_id  INT UNSIGNED NULL,
    phone          VARCHAR(20) NULL,
    email          VARCHAR(100) NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_doctors_department (department_id),
    CONSTRAINT fk_doctors_department
        FOREIGN KEY (department_id) REFERENCES departments (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- المرضى
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS patients (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(100) NOT NULL,
    gender        ENUM('male', 'female') NOT NULL DEFAULT 'male',
    date_of_birth DATE NULL,
    phone         VARCHAR(20) NULL,
    address       VARCHAR(255) NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- المواعيد
-- القيد uq_doctor_slot هو اللي بيمنع الحجز المزدوج على مستوى قاعدة البيانات،
-- عشان طلبين متزامنين ما يقدروش يتخطوا الفحص اللي في كود PHP.
--
-- العمود active_slot بيبقى 1 للموعد النشط و NULL للموعد الملغي. وبما إن قيم
-- NULL بتتعامل كقيم مختلفة في الفهارس الفريدة، فده بيسمح بإعادة حجز نفس
-- التوقيت بعد الإلغاء، ولسه بيمنع وجود موعدين نشطين في نفس التوقيت.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS appointments (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    patient_id       INT UNSIGNED NOT NULL,
    doctor_id        INT UNSIGNED NOT NULL,
    appointment_date DATETIME NOT NULL,
    status           ENUM('scheduled', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled',
    notes            TEXT NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    active_slot      TINYINT UNSIGNED AS (IF(status = 'cancelled', NULL, 1)) STORED,
    PRIMARY KEY (id),
    UNIQUE KEY uq_doctor_slot (doctor_id, appointment_date, active_slot),
    KEY idx_appointments_patient (patient_id),
    KEY idx_appointments_date (appointment_date),
    CONSTRAINT fk_appointments_patient
        FOREIGN KEY (patient_id) REFERENCES patients (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_appointments_doctor
        FOREIGN KEY (doctor_id) REFERENCES doctors (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- السجلات الطبية — سجل واحد لكل موعد
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS medical_records (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    appointment_id INT UNSIGNED NOT NULL,
    diagnosis      TEXT NULL,
    prescription   TEXT NULL,
    notes          TEXT NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_records_appointment (appointment_id),
    CONSTRAINT fk_records_appointment
        FOREIGN KEY (appointment_id) REFERENCES appointments (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- المستخدمون
-- عمود password بيخزن ناتج password_hash() مش الباسورد نفسه، وطوله 255
-- عشان يستوعب أي خوارزمية تشفير أحدث مستقبلًا.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username   VARCHAR(50) NOT NULL,
    password   VARCHAR(255) NOT NULL,
    full_name  VARCHAR(100) NULL,
    role       ENUM('admin', 'receptionist', 'doctor', 'patient') NOT NULL DEFAULT 'receptionist',
    doctor_id  INT UNSIGNED NULL,
    patient_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    KEY idx_users_doctor (doctor_id),
    KEY idx_users_patient (patient_id),
    CONSTRAINT fk_users_doctor
        FOREIGN KEY (doctor_id) REFERENCES doctors (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_users_patient
        FOREIGN KEY (patient_id) REFERENCES patients (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- حساب المدير الأول
-- اسم المستخدم: admin   /   كلمة المرور: Admin@12345
-- >>> غيّر كلمة المرور دي فورًا بعد أول تسجيل دخول <<<
-- -----------------------------------------------------------------------------
INSERT IGNORE INTO users (username, password, full_name, role)
VALUES ('admin',
        '$2y$12$bSQIdNN.fCTGJA223YaRFe6lxbEK5jksi1VZ/CATS5RYZHuKLGVXa',
        'مدير النظام',
        'admin');
