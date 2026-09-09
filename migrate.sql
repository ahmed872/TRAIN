-- =============================================================================
-- ترقية قاعدة بيانات موجودة بالفعل إلى البنية الجديدة.
--
-- استخدم الملف ده لو عندك داتابيز "hospital" شغالة من قبل وفيها بيانات.
-- (لو بتعمل تنصيب جديد من الصفر استخدم schema.sql بدل الملف ده.)
--
--     mysql -u root -p hospital < migrate.sql
--
-- الملف آمن للتشغيل أكتر من مرة: بيتخطى أي خطوة متعملة قبل كده.
-- وبيبدأ بفحص البيانات المكررة قبل ما يعدّل أي حاجة، فلو فيه مشكلة
-- بيقف من غير ما يغيّر حاجة خالص.
-- =============================================================================

-- الرسائل والبيانات بالعربي، فلازم نضبط ترميز الاتصال قبل أي حاجة.
SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS hospital_migrate;

DELIMITER $$

CREATE PROCEDURE hospital_migrate()
BEGIN
    DECLARE db     VARCHAR(64) DEFAULT DATABASE();
    DECLARE cnt    INT DEFAULT 0;
    DECLARE msg    VARCHAR(500);

    -- =========================================================================
    -- 1) فحص قبل التعديل: القيود الفريدة الجديدة مش هتتقبل لو فيه بيانات مكررة
    -- =========================================================================

    -- مواعيد نشطة (غير ملغية) لنفس الطبيب في نفس التوقيت
    SELECT COUNT(*) INTO cnt FROM (
        SELECT doctor_id FROM appointments
        WHERE status <> 'cancelled'
        GROUP BY doctor_id, appointment_date
        HAVING COUNT(*) > 1
    ) AS dups;

    IF cnt > 0 THEN
        SET msg = CONCAT('الترقية توقفت: فيه ', cnt,
            ' حالة حجز مزدوج لنفس الطبيب في نفس التوقيت. راجع الاستعلام المكتوب في آخر الملف، ',
            'الغِ أو عدّل المواعيد الزيادة، وبعدين شغّل الملف تاني. لم يتم تغيير أي شيء.');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = msg;
    END IF;

    -- أكتر من سجل طبي لنفس الموعد
    SELECT COUNT(*) INTO cnt FROM (
        SELECT appointment_id FROM medical_records
        GROUP BY appointment_id HAVING COUNT(*) > 1
    ) AS dups;

    IF cnt > 0 THEN
        SET msg = CONCAT('الترقية توقفت: فيه ', cnt,
            ' موعد له أكثر من سجل طبي. ادمج أو احذف السجلات الزيادة وشغّل الملف تاني. ',
            'لم يتم تغيير أي شيء.');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = msg;
    END IF;

    -- أقسام بنفس الاسم
    SELECT COUNT(*) INTO cnt FROM (
        SELECT name FROM departments GROUP BY name HAVING COUNT(*) > 1
    ) AS dups;

    IF cnt > 0 THEN
        SET msg = CONCAT('الترقية توقفت: فيه ', cnt,
            ' اسم قسم مكرر. وحّد الأسماء وشغّل الملف تاني. لم يتم تغيير أي شيء.');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = msg;
    END IF;

    -- صفوف يتيمة تمنع إضافة المفاتيح الأجنبية
    SELECT COUNT(*) INTO cnt FROM appointments a
        LEFT JOIN patients p ON a.patient_id = p.id WHERE p.id IS NULL;
    IF cnt > 0 THEN
        SET msg = CONCAT('الترقية توقفت: فيه ', cnt,
            ' موعد مربوط بمريض غير موجود. نظّف الصفوف دي وشغّل الملف تاني. لم يتم تغيير أي شيء.');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = msg;
    END IF;

    SELECT COUNT(*) INTO cnt FROM appointments a
        LEFT JOIN doctors d ON a.doctor_id = d.id WHERE d.id IS NULL;
    IF cnt > 0 THEN
        SET msg = CONCAT('الترقية توقفت: فيه ', cnt,
            ' موعد مربوط بطبيب غير موجود. نظّف الصفوف دي وشغّل الملف تاني. لم يتم تغيير أي شيء.');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = msg;
    END IF;

    SELECT COUNT(*) INTO cnt FROM medical_records m
        LEFT JOIN appointments a ON m.appointment_id = a.id WHERE a.id IS NULL;
    IF cnt > 0 THEN
        SET msg = CONCAT('الترقية توقفت: فيه ', cnt,
            ' سجل طبي مربوط بموعد غير موجود. نظّف الصفوف دي وشغّل الملف تاني. لم يتم تغيير أي شيء.');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = msg;
    END IF;

    -- =========================================================================
    -- 2) عمود users.patient_id — ده اللي كان بيسبب خطأ "Unknown column"
    -- =========================================================================
    SELECT COUNT(*) INTO cnt FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'patient_id';
    IF cnt = 0 THEN
        ALTER TABLE users ADD COLUMN patient_id INT NULL AFTER doctor_id;
        SELECT 'تمت إضافة العمود users.patient_id' AS step;
    END IF;

    -- =========================================================================
    -- 3) دور "patient" لازم يكون ضمن القيم المسموحة
    -- =========================================================================
    SELECT COUNT(*) INTO cnt FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role'
          AND COLUMN_TYPE LIKE '%patient%';
    IF cnt = 0 THEN
        ALTER TABLE users MODIFY COLUMN role
            ENUM('admin','receptionist','doctor','patient') NOT NULL DEFAULT 'receptionist';
        SELECT 'تمت إضافة دور patient لعمود users.role' AS step;
    END IF;

    -- =========================================================================
    -- 4) منع الحجز المزدوج على مستوى قاعدة البيانات
    --    active_slot = 1 للموعد النشط و NULL للملغي، وبما إن NULL بتتعامل
    --    كقيمة مختلفة في الفهرس الفريد فالخانة بترجع متاحة بعد الإلغاء.
    -- =========================================================================
    SELECT COUNT(*) INTO cnt FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = db AND TABLE_NAME = 'appointments' AND COLUMN_NAME = 'active_slot';
    IF cnt = 0 THEN
        ALTER TABLE appointments
            ADD COLUMN active_slot TINYINT UNSIGNED AS (IF(status = 'cancelled', NULL, 1)) STORED;
        SELECT 'تمت إضافة العمود appointments.active_slot' AS step;
    END IF;

    SELECT COUNT(*) INTO cnt FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = db AND TABLE_NAME = 'appointments' AND INDEX_NAME = 'uq_doctor_slot';
    IF cnt = 0 THEN
        ALTER TABLE appointments
            ADD UNIQUE KEY uq_doctor_slot (doctor_id, appointment_date, active_slot);
        SELECT 'تمت إضافة قيد منع الحجز المزدوج' AS step;
    END IF;

    -- =========================================================================
    -- 5) سجل طبي واحد لكل موعد
    -- =========================================================================
    SELECT COUNT(*) INTO cnt FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = db AND TABLE_NAME = 'medical_records' AND INDEX_NAME = 'uq_records_appointment';
    IF cnt = 0 THEN
        ALTER TABLE medical_records ADD UNIQUE KEY uq_records_appointment (appointment_id);
        SELECT 'تمت إضافة قيد سجل طبي واحد لكل موعد' AS step;
    END IF;

    -- =========================================================================
    -- 6) أسماء الأقسام لا تتكرر
    -- =========================================================================
    SELECT COUNT(*) INTO cnt FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = db AND TABLE_NAME = 'departments' AND INDEX_NAME = 'uq_departments_name';
    IF cnt = 0 THEN
        ALTER TABLE departments ADD UNIQUE KEY uq_departments_name (name);
        SELECT 'تمت إضافة قيد عدم تكرار اسم القسم' AS step;
    END IF;

    -- =========================================================================
    -- 7) المفاتيح الأجنبية — من غيرها الحذف بيسيب صفوف يتيمة
    -- =========================================================================
    SELECT COUNT(*) INTO cnt FROM information_schema.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA = db AND TABLE_NAME = 'doctors' AND CONSTRAINT_NAME = 'fk_doctors_department';
    IF cnt = 0 THEN
        ALTER TABLE doctors ADD CONSTRAINT fk_doctors_department
            FOREIGN KEY (department_id) REFERENCES departments (id)
            ON DELETE SET NULL ON UPDATE CASCADE;
        SELECT 'تمت إضافة العلاقة doctors -> departments' AS step;
    END IF;

    SELECT COUNT(*) INTO cnt FROM information_schema.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA = db AND TABLE_NAME = 'appointments' AND CONSTRAINT_NAME = 'fk_appointments_patient';
    IF cnt = 0 THEN
        ALTER TABLE appointments ADD CONSTRAINT fk_appointments_patient
            FOREIGN KEY (patient_id) REFERENCES patients (id)
            ON DELETE RESTRICT ON UPDATE CASCADE;
        SELECT 'تمت إضافة العلاقة appointments -> patients' AS step;
    END IF;

    SELECT COUNT(*) INTO cnt FROM information_schema.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA = db AND TABLE_NAME = 'appointments' AND CONSTRAINT_NAME = 'fk_appointments_doctor';
    IF cnt = 0 THEN
        ALTER TABLE appointments ADD CONSTRAINT fk_appointments_doctor
            FOREIGN KEY (doctor_id) REFERENCES doctors (id)
            ON DELETE RESTRICT ON UPDATE CASCADE;
        SELECT 'تمت إضافة العلاقة appointments -> doctors' AS step;
    END IF;

    SELECT COUNT(*) INTO cnt FROM information_schema.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA = db AND TABLE_NAME = 'medical_records' AND CONSTRAINT_NAME = 'fk_records_appointment';
    IF cnt = 0 THEN
        ALTER TABLE medical_records ADD CONSTRAINT fk_records_appointment
            FOREIGN KEY (appointment_id) REFERENCES appointments (id)
            ON DELETE CASCADE ON UPDATE CASCADE;
        SELECT 'تمت إضافة العلاقة medical_records -> appointments' AS step;
    END IF;

    SELECT COUNT(*) INTO cnt FROM information_schema.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA = db AND TABLE_NAME = 'users' AND CONSTRAINT_NAME = 'fk_users_doctor';
    IF cnt = 0 THEN
        ALTER TABLE users ADD CONSTRAINT fk_users_doctor
            FOREIGN KEY (doctor_id) REFERENCES doctors (id)
            ON DELETE SET NULL ON UPDATE CASCADE;
        SELECT 'تمت إضافة العلاقة users -> doctors' AS step;
    END IF;

    SELECT COUNT(*) INTO cnt FROM information_schema.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA = db AND TABLE_NAME = 'users' AND CONSTRAINT_NAME = 'fk_users_patient';
    IF cnt = 0 THEN
        ALTER TABLE users ADD CONSTRAINT fk_users_patient
            FOREIGN KEY (patient_id) REFERENCES patients (id)
            ON DELETE SET NULL ON UPDATE CASCADE;
        SELECT 'تمت إضافة العلاقة users -> patients' AS step;
    END IF;

    -- =========================================================================
    -- 8) اسم المستخدم لا يتكرر
    -- =========================================================================
    SELECT COUNT(*) INTO cnt FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'username' AND NON_UNIQUE = 0;
    IF cnt = 0 THEN
        ALTER TABLE users ADD UNIQUE KEY uq_users_username (username);
        SELECT 'تمت إضافة قيد عدم تكرار اسم المستخدم' AS step;
    END IF;

    SELECT 'تمت الترقية بنجاح' AS result;
END$$

DELIMITER ;

CALL hospital_migrate();
DROP PROCEDURE hospital_migrate;

-- =============================================================================
-- لو الترقية وقفت بسبب حجز مزدوج، الاستعلام ده بيوريك المواعيد المتعارضة:
--
--   SELECT doctor_id, appointment_date, COUNT(*) AS total,
--          GROUP_CONCAT(id) AS appointment_ids
--   FROM appointments WHERE status <> 'cancelled'
--   GROUP BY doctor_id, appointment_date HAVING COUNT(*) > 1;
-- =============================================================================
