<?php
/* ============================================================
   منظومة المدرسة النموذجية - نظام إدارة متكامل (النسخة المطورة)
   ملف واحد PHP + MySQL - بدون روابط خارجية
   تصميم وتطوير: م. عبدالرحيم غيث الطاهر
   ============================================================ */

session_start();
date_default_timezone_set('Africa/Tripoli');

/* ---------------- إعدادات الاتصال ---------------- */
define('DB_HOST', 'localhost');
define('DB_NAME', 'school_db_v2');
define('DB_USER', 'root');
define('DB_PASS', '');

/* ---------------- الاتصال بقاعدة البيانات ---------------- */
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE " . DB_NAME);
} catch (PDOException $e) {
    die("فشل الاتصال: " . $e->getMessage());
}

/* ---------------- إنشاء الجداول تلقائياً ---------------- */
$pdo->exec("
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin','accountant','data_entry','supervisor','parent') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_code VARCHAR(20) UNIQUE,
    name VARCHAR(150) NOT NULL,
    national_id VARCHAR(30),
    phone VARCHAR(20),
    email VARCHAR(100),
    subject VARCHAR(50),
    hire_date DATE,
    salary DECIMAL(10,2) DEFAULT 0,
    address TEXT,
    work_days VARCHAR(100) DEFAULT '0,1,2,3,4',
    work_start TIME DEFAULT '08:00:00',
    work_end TIME DEFAULT '14:00:00',
    annual_leave_days INT DEFAULT 30,
    used_leave_days INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_code VARCHAR(20) UNIQUE,
    name VARCHAR(150) NOT NULL,
    national_id VARCHAR(30),
    phone VARCHAR(20),
    job_title VARCHAR(50),
    hire_date DATE,
    salary DECIMAL(10,2) DEFAULT 0,
    address TEXT,
    work_days VARCHAR(100) DEFAULT '0,1,2,3,4',
    work_start TIME DEFAULT '08:00:00',
    work_end TIME DEFAULT '14:00:00',
    annual_leave_days INT DEFAULT 30,
    used_leave_days INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS salaries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    person_type ENUM('teacher','employee') NOT NULL,
    person_id INT NOT NULL,
    month VARCHAR(20) NOT NULL,
    year INT NOT NULL,
    basic_salary DECIMAL(10,2) DEFAULT 0,
    bonus DECIMAL(10,2) DEFAULT 0,
    deduction DECIMAL(10,2) DEFAULT 0,
    absence_days INT DEFAULT 0,
    absence_deduction DECIMAL(10,2) DEFAULT 0,
    net_salary DECIMAL(10,2) DEFAULT 0,
    paid_date DATE,
    status ENUM('paid','pending') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    person_type ENUM('teacher','employee') NOT NULL,
    person_id INT NOT NULL,
    att_date DATE NOT NULL,
    check_in TIME,
    check_out TIME,
    status ENUM('present','absent','late','holiday','leave') DEFAULT 'present',
    notes VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    person_type ENUM('teacher','employee') NOT NULL,
    person_id INT NOT NULL,
    leave_type ENUM('annual','sick','emergency','unpaid') DEFAULT 'annual',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    days_count INT NOT NULL,
    reason TEXT,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    approved_by VARCHAR(100),
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS leave_balance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    person_type ENUM('teacher','employee') NOT NULL,
    person_id INT NOT NULL,
    year INT NOT NULL,
    annual_entitlement INT DEFAULT 30,
    annual_used INT DEFAULT 0,
    sick_used INT DEFAULT 0,
    emergency_used INT DEFAULT 0,
    unpaid_used INT DEFAULT 0,
    UNIQUE KEY unique_balance (person_type, person_id, year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS holidays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    holiday_name VARCHAR(100) NOT NULL,
    holiday_date DATE NOT NULL UNIQUE,
    description VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS academic_years (
    id INT AUTO_INCREMENT PRIMARY KEY,
    year_name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS grade_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level_number INT NOT NULL UNIQUE,
    level_name VARCHAR(50) NOT NULL,
    academic_year VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(80) NOT NULL,
    grade_level_id INT,
    section VARCHAR(20),
    section_name VARCHAR(100),
    academic_year VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_code VARCHAR(20) UNIQUE,
    name VARCHAR(150) NOT NULL,
    national_id VARCHAR(30),
    birth_date DATE,
    gender ENUM('male','female') DEFAULT 'male',
    class_id INT,
    academic_year VARCHAR(50),
    semester ENUM('first','second') DEFAULT 'first',
    guardian_name VARCHAR(150),
    guardian_phone VARCHAR(20),
    address TEXT,
    enroll_date DATE,
    status ENUM('active','graduated','failed','withdrawn') DEFAULT 'active',
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(50) NOT NULL,
    academic_year VARCHAR(50) NOT NULL,
    grade_level_id INT NULL,
    class_id INT NULL,
    max_grade INT DEFAULT 100,
    coursework_max INT DEFAULT 40,
    final_max INT DEFAULT 60,
    subject_type ENUM('local','international') DEFAULT 'local',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    semester ENUM('first','second') DEFAULT 'first',
    year INT,
    academic_year VARCHAR(50),
    coursework DECIMAL(5,2) DEFAULT 0,
    final_exam DECIMAL(5,2) DEFAULT 0,
    total DECIMAL(5,2) DEFAULT 0,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    person_type ENUM('student','teacher','employee') NOT NULL,
    person_id INT NOT NULL,
    note TEXT NOT NULL,
    note_date DATE,
    created_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS guardians (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    reference_code VARCHAR(20) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS timetables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grade_level_id INT NOT NULL,
    class_id INT NULL,
    section_id INT NULL,
    teacher_id INT NULL,
    subject_name VARCHAR(100) NOT NULL,
    school_name VARCHAR(100),
    day_of_week VARCHAR(20),
    period_number INT DEFAULT 1,
    start_time TIME NULL,
    end_time TIME NULL,
    notes VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS promotions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    from_class_id INT,
    to_class_id INT,
    from_grade_level INT,
    to_grade_level INT,
    academic_year VARCHAR(50),
    avg_score DECIMAL(5,2) DEFAULT 0,
    status ENUM('passed','failed') DEFAULT 'passed',
    promoted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    promoted_by VARCHAR(100),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ============================================================
   جداول نظام الأقساط الجديدة
   ============================================================ */
CREATE TABLE IF NOT EXISTS fee_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fee_name VARCHAR(100) NOT NULL,
    fee_description VARCHAR(255),
    academic_year VARCHAR(50),
    grade_level_id INT NULL,
    class_id INT NULL,
    total_amount DECIMAL(10,2) DEFAULT 0,
    installments_count INT DEFAULT 2,
    first_installment_amount DECIMAL(10,2) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS student_fees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    fee_type_id INT NOT NULL,
    academic_year VARCHAR(50) NOT NULL,
    semester ENUM('first','second') DEFAULT 'first',
    total_amount DECIMAL(10,2) DEFAULT 0,
    paid_amount DECIMAL(10,2) DEFAULT 0,
    remaining_amount DECIMAL(10,2) DEFAULT 0,
    status ENUM('pending','partial','paid','overdue') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (fee_type_id) REFERENCES fee_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS fee_installments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_fee_id INT NOT NULL,
    installment_number INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    due_date DATE NOT NULL,
    paid_date DATE NULL,
    status ENUM('pending','paid','overdue','cancelled') DEFAULT 'pending',
    payment_method ENUM('cash','bank_transfer','check','online') DEFAULT 'cash',
    receipt_number VARCHAR(50),
    notes VARCHAR(255),
    confirmed_by VARCHAR(100),
    confirmed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_fee_id) REFERENCES student_fees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS fee_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guardian_id INT NOT NULL,
    student_id INT NOT NULL,
    installment_id INT NOT NULL,
    notification_type ENUM('due_soon','overdue','payment_confirmed','new_installment') DEFAULT 'new_installment',
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (guardian_id) REFERENCES guardians(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (installment_id) REFERENCES fee_installments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS fee_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    installment_id INT NOT NULL,
    student_fee_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method ENUM('cash','bank_transfer','check','online') DEFAULT 'cash',
    receipt_number VARCHAR(50),
    received_by VARCHAR(100),
    notes VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (installment_id) REFERENCES fee_installments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_fee_id) REFERENCES student_fees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

/* ---------------- إضافة أعمدة جديدة إذا لم تكن موجودة ---------------- */
try { $pdo->exec("ALTER TABLE grades ADD COLUMN academic_year VARCHAR(50) AFTER year"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE salaries ADD COLUMN absence_days INT DEFAULT 0 AFTER deduction"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE salaries ADD COLUMN absence_deduction DECIMAL(10,2) DEFAULT 0 AFTER absence_days"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE salaries ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE classes ADD COLUMN grade_level_id INT AFTER class_name"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE classes ADD COLUMN section_name VARCHAR(100) AFTER section"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE classes ADD COLUMN academic_year VARCHAR(50) NULL AFTER section_name"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE students ADD COLUMN status ENUM('active','graduated','failed','withdrawn') DEFAULT 'active'"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE timetables ADD COLUMN class_id INT NULL AFTER grade_level_id"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE timetables ADD COLUMN section_id INT NULL AFTER class_id"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE timetables ADD COLUMN teacher_id INT NULL AFTER section_id"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE timetables ADD COLUMN period_number INT DEFAULT 1 AFTER day_of_week"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE timetables ADD COLUMN start_time TIME NULL AFTER period_number"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE timetables ADD COLUMN end_time TIME NULL AFTER start_time"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE subjects ADD COLUMN grade_level_id INT NULL AFTER academic_year"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE subjects ADD COLUMN class_id INT NULL AFTER grade_level_id"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE subjects ADD COLUMN subject_type ENUM('local','international') DEFAULT 'local' AFTER final_max"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE subjects ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE grade_levels ADD COLUMN academic_year VARCHAR(50) NULL AFTER level_name"); } catch (PDOException $e) {}

/* ---------------- إضافة عمود first_installment_amount ---------------- */
try { $pdo->exec("ALTER TABLE fee_types ADD COLUMN first_installment_amount DECIMAL(10,2) DEFAULT 0 AFTER installments_count"); } catch (PDOException $e) {}

/* ============================================================
   إنشاء البيانات الأولية - فقط مدير النظام الافتراضي
   ============================================================ */
$check = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ($check == 0) {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users(username,password,full_name,role) VALUES(?,?,?,?)")
        ->execute(['admin', $hash, 'المدير العام', 'admin']);
}

$settingsCount = $pdo->query("SELECT COUNT(*) FROM settings")->fetchColumn();
if ($settingsCount == 0) {
    $pdo->exec("INSERT INTO settings(setting_key,setting_value) VALUES
        ('absence_deduction_per_day', '50'),
        ('late_deduction_per_day', '10'),
        ('work_days_per_month', '22'),
        ('school_name', 'المدرسة النموذجية'),
        ('pass_grade', '50')");
}

$yearsCount = $pdo->query("SELECT COUNT(*) FROM academic_years")->fetchColumn();
if ($yearsCount == 0) {
    $currentYear = date('Y') . '-' . (date('Y') + 1);
    $pdo->prepare("INSERT INTO academic_years(year_name) VALUES(?)")->execute([$currentYear]);
}

/* ---------------- دوال مساعدة ---------------- */
function checkAuth() {
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['guardian_id'])) {
        header('Location: ?page=login'); exit;
    }
}
function isAdmin() { return ($_SESSION['role'] ?? '') === 'admin'; }
function isAccountant() { return in_array($_SESSION['role'] ?? '', ['admin','accountant']); }
function isDataEntry() { return in_array($_SESSION['role'] ?? '', ['admin','data_entry']); }
function isSupervisor() { return in_array($_SESSION['role'] ?? '', ['admin','supervisor']); }
function isGuardian() { return isset($_SESSION['guardian_id']); }
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function redirect($url) { header("Location: $url"); exit; }

function generateEmployeeCode($pdo, $type) {
    $table = $type === 'teacher' ? 'teachers' : 'employees';
    $prefix = $type === 'teacher' ? 'T' : 'E';
    $last = $pdo->query("SELECT employee_code FROM $table WHERE employee_code LIKE '$prefix%' ORDER BY id DESC LIMIT 1")->fetchColumn();
    if (!$last) return $prefix . '1001';
    $num = (int)substr($last, 1) + 1;
    return $prefix . $num;
}

function generateStudentCode($pdo) {
    $last = $pdo->query("SELECT student_code FROM students WHERE student_code LIKE 'S%' ORDER BY id DESC LIMIT 1")->fetchColumn();
    if (!$last) return 'S1001';
    $num = (int)substr($last, 1) + 1;
    return 'S' . $num;
}

function generateGuardianCode($pdo) {
    $last = $pdo->query("SELECT reference_code FROM guardians ORDER BY id DESC LIMIT 1")->fetchColumn();
    if (!$last) return 'G' . rand(10000, 99999);
    return 'G' . (intval(substr($last, 1)) + 1);
}

function generateReceiptNumber($pdo) {
    $last = $pdo->query("SELECT receipt_number FROM fee_payments WHERE receipt_number LIKE 'RCP%' ORDER BY id DESC LIMIT 1")->fetchColumn();
    if (!$last) return 'RCP' . date('Y') . '0001';
    $num = (int)substr($last, 7) + 1;
    return 'RCP' . date('Y') . str_pad($num, 4, '0', STR_PAD_LEFT);
}

function isWorkingDay($work_days, $date) {
    $dayOfWeek = (int)date('w', strtotime($date));
    $days = array_map('trim', explode(',', $work_days));
    return in_array((string)$dayOfWeek, $days);
}

function isHoliday($pdo, $date) {
    $stmt = $pdo->prepare("SELECT holiday_name FROM holidays WHERE holiday_date=?");
    $stmt->execute([$date]);
    return $stmt->fetch();
}

function getSetting($pdo, $key, $default = 0) {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key=?");
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    return $val !== false ? $val : $default;
}

function getAbsenceDays($pdo, $person_type, $person_id, $month, $year) {
    $monthNames = ['يناير'=>1,'فبراير'=>2,'مارس'=>3,'أبريل'=>4,'مايو'=>5,'يونيو'=>6,
                   'يوليو'=>7,'أغسطس'=>8,'سبتمبر'=>9,'أكتوبر'=>10,'نوفمبر'=>11,'ديسمبر'=>12];
    $monthNum = $monthNames[$month] ?? (int)date('m');
    $startDate = "$year-" . str_pad($monthNum, 2, '0', STR_PAD_LEFT) . "-01";
    $endDate = date('Y-m-t', strtotime($startDate));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance 
        WHERE person_type=? AND person_id=? AND att_date BETWEEN ? AND ? AND status='absent'");
    $stmt->execute([$person_type, $person_id, $startDate, $endDate]);
    $absent = $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance 
        WHERE person_type=? AND person_id=? AND att_date BETWEEN ? AND ? AND status='late'");
    $stmt->execute([$person_type, $person_id, $startDate, $endDate]);
    $late = $stmt->fetchColumn();
    return ['absent' => $absent, 'late' => $late];
}

function calculateAbsenceDeduction($pdo, $basic_salary, $absence_days, $late_days = 0) {
    $work_days_per_month = (int)getSetting($pdo, 'work_days_per_month', 22);
    $late_deduction_per_day = (float)getSetting($pdo, 'late_deduction_per_day', 10);
    $daily_rate = $work_days_per_month > 0 ? $basic_salary / $work_days_per_month : 0;
    $absence_deduction = $daily_rate * $absence_days;
    $late_deduction = $late_days * $late_deduction_per_day;
    return [
        'daily_rate' => $daily_rate,
        'absence_deduction' => $absence_deduction,
        'late_deduction' => $late_deduction,
        'total_deduction' => $absence_deduction + $late_deduction
    ];
}

function getLeaveBalance($pdo, $person_type, $person_id, $year = null) {
    if ($year === null) $year = (int)date('Y');
    $stmt = $pdo->prepare("SELECT * FROM leave_balance WHERE person_type=? AND person_id=? AND year=?");
    $stmt->execute([$person_type, $person_id, $year]);
    $balance = $stmt->fetch();
    if (!$balance) {
        $table = $person_type === 'teacher' ? 'teachers' : 'employees';
        $stmt = $pdo->prepare("SELECT annual_leave_days FROM $table WHERE id=?");
        $stmt->execute([$person_id]);
        $entitlement = $stmt->fetchColumn() ?: 30;
        $pdo->prepare("INSERT INTO leave_balance(person_type, person_id, year, annual_entitlement) VALUES(?,?,?,?)")
            ->execute([$person_type, $person_id, $year, $entitlement]);
        $stmt = $pdo->prepare("SELECT * FROM leave_balance WHERE person_type=? AND person_id=? AND year=?");
        $stmt->execute([$person_type, $person_id, $year]);
        $balance = $stmt->fetch();
    }
    return $balance;
}

function getStudentAverage($pdo, $student_id, $academic_year) {
    $stmt = $pdo->prepare("SELECT g.total, s.max_grade FROM grades g 
        JOIN subjects s ON s.id=g.subject_id 
        WHERE g.student_id=? AND g.academic_year=?");
    $stmt->execute([$student_id, $academic_year]);
    $grades = $stmt->fetchAll();
    if (!$grades) return 0;
    $sum = 0; $cnt = 0;
    foreach ($grades as $g) {
        if ($g['max_grade'] > 0) { $sum += ($g['total'] / $g['max_grade']) * 100; $cnt++; }
    }
    return $cnt ? $sum / $cnt : 0;
}

/* ============================================================
   دوال الأكاديمية الجديدة
   ============================================================ */

function getSubjectsByClass($pdo, $class_id, $type = null) {
    $sql = "SELECT s.*, gl.level_name FROM subjects s 
            LEFT JOIN grade_levels gl ON gl.id=s.grade_level_id 
            WHERE s.class_id=?";
    $params = [$class_id];
    if ($type && in_array($type, ['local', 'international'])) {
        $sql .= " AND s.subject_type=?";
        $params[] = $type;
    }
    $sql .= " ORDER BY s.subject_type, s.subject_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getTimetableByClass($pdo, $class_id) {
    $sql = "SELECT t.*, tr.name AS teacher_name, tr.employee_code AS teacher_code,
            c.class_name, c.section AS class_section
            FROM timetables t
            LEFT JOIN teachers tr ON tr.id = t.teacher_id
            LEFT JOIN classes c ON c.id = t.class_id
            WHERE t.class_id = ?
            ORDER BY FIELD(t.day_of_week,'الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'), t.period_number";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$class_id]);
    return $stmt->fetchAll();
}

function getDefaultPeriodTimes() {
    return [
        1 => ['start' => '08:00', 'end' => '08:45'],
        2 => ['start' => '08:50', 'end' => '09:35'],
        3 => ['start' => '09:40', 'end' => '10:25'],
        4 => ['start' => '10:45', 'end' => '11:30'],
        5 => ['start' => '11:35', 'end' => '12:20'],
        6 => ['start' => '12:25', 'end' => '13:10'],
        7 => ['start' => '13:15', 'end' => '14:00'],
        8 => ['start' => '14:05', 'end' => '14:50'],
    ];
}

/* ============================================================
   دوال التقييم حسب نوع المادة
   ============================================================ */

function getGradeEvaluation($percentage, $subject_type = 'local') {
    $percentage = (float)$percentage;
    
    if ($subject_type === 'international') {
        if ($percentage >= 90) {
            return ['grade' => 'A', 'color' => 'bg-success', 'passed' => true, 'label' => 'ممتاز'];
        } elseif ($percentage >= 80) {
            return ['grade' => 'B', 'color' => 'bg-success', 'passed' => true, 'label' => 'جيد جداً'];
        } elseif ($percentage >= 70) {
            return ['grade' => 'C', 'color' => 'bg-info', 'passed' => true, 'label' => 'جيد'];
        } elseif ($percentage >= 60) {
            return ['grade' => 'D', 'color' => 'bg-warning', 'passed' => true, 'label' => 'مقبول'];
        } elseif ($percentage >= 50) {
            return ['grade' => 'E', 'color' => 'bg-warning', 'passed' => true, 'label' => 'ضعيف'];
        } else {
            return ['grade' => 'F', 'color' => 'bg-danger', 'passed' => false, 'label' => 'راسب'];
        }
    } else {
        if ($percentage >= 90) {
            return ['grade' => 'ممتاز', 'color' => 'bg-success', 'passed' => true, 'label' => 'ممتاز'];
        } elseif ($percentage >= 80) {
            return ['grade' => 'جيد جداً', 'color' => 'bg-success', 'passed' => true, 'label' => 'جيد جداً'];
        } elseif ($percentage >= 70) {
            return ['grade' => 'جيد', 'color' => 'bg-info', 'passed' => true, 'label' => 'جيد'];
        } elseif ($percentage >= 60) {
            return ['grade' => 'مقبول', 'color' => 'bg-warning', 'passed' => true, 'label' => 'مقبول'];
        } elseif ($percentage >= 50) {
            return ['grade' => 'ضعيف', 'color' => 'bg-warning', 'passed' => true, 'label' => 'ضعيف'];
        } else {
            return ['grade' => 'راسب', 'color' => 'bg-danger', 'passed' => false, 'label' => 'راسب'];
        }
    }
}

function getSubjectTypeName($type) {
    return $type === 'international' ? 'دولية' : 'محلية';
}

function getSubjectTypeColor($type) {
    return $type === 'international' ? 'bg-purple' : 'bg-info';
}

/* ============================================================
   دوال نظام الأقساط
   ============================================================ */

/**
 * إنشاء أقساط لطالب - النسخة المعدلة
 * 
 * @param PDO $pdo
 * @param int $student_fee_id
 * @param float $total_amount المبلغ الإجمالي
 * @param int $installments_count عدد الأقساط
 * @param string $start_date تاريخ بداية القسط الأول
 * @param float $first_installment_amount مبلغ القسط الأول (إذا كان 0 أو فارغ، يتم التقسيم بالتساوي)
 * @return int عدد الأقساط المنشأة
 */
function createStudentInstallments($pdo, $student_fee_id, $total_amount, $installments_count, $start_date, $first_installment_amount = 0) {
    $created = 0;
    
    // التحقق من صحة البيانات
    if ($installments_count < 1) {
        $installments_count = 1;
    }
    
    // إذا كان مبلغ القسط الأول محدداً وأكبر من 0
    if ($first_installment_amount > 0 && $installments_count > 1) {
        // التأكد من أن مبلغ القسط الأول لا يتجاوز المبلغ الإجمالي
        if ($first_installment_amount > $total_amount) {
            $first_installment_amount = $total_amount;
        }
        
        $remaining_amount = $total_amount - $first_installment_amount;
        $remaining_installments = $installments_count - 1;
        $amount_per_installment = $remaining_amount / $remaining_installments;
        
        // القسط الأول
        $due_date = date('Y-m-d', strtotime($start_date));
        $pdo->prepare("INSERT INTO fee_installments(student_fee_id, installment_number, amount, due_date, status) VALUES(?,?,?,?,'pending')")
            ->execute([$student_fee_id, 1, $first_installment_amount, $due_date]);
        $created++;
        
        // باقي الأقساط
        for ($i = 2; $i <= $installments_count; $i++) {
            $due_date = date('Y-m-d', strtotime($start_date . ' +' . (($i - 1) * 30) . ' days'));
            $pdo->prepare("INSERT INTO fee_installments(student_fee_id, installment_number, amount, due_date, status) VALUES(?,?,?,?,'pending')")
                ->execute([$student_fee_id, $i, $amount_per_installment, $due_date]);
            $created++;
        }
    } else {
        // التقسيم بالتساوي (الطريقة القديمة)
        $installment_amount = $total_amount / $installments_count;
        
        for ($i = 1; $i <= $installments_count; $i++) {
            $due_date = date('Y-m-d', strtotime($start_date . ' +' . (($i - 1) * 30) . ' days'));
            $pdo->prepare("INSERT INTO fee_installments(student_fee_id, installment_number, amount, due_date, status) VALUES(?,?,?,?,'pending')")
                ->execute([$student_fee_id, $i, $installment_amount, $due_date]);
            $created++;
        }
    }
    
    return $created;
}

/**
 * إرسال إشعار لولي الأمر
 */
function sendGuardianNotification($pdo, $student_id, $installment_id, $type, $title, $message) {
    $stmt = $pdo->prepare("SELECT id FROM guardians WHERE student_id=?");
    $stmt->execute([$student_id]);
    $guardian = $stmt->fetch();
    
    if ($guardian) {
        $pdo->prepare("INSERT INTO fee_notifications(guardian_id, student_id, installment_id, notification_type, title, message) VALUES(?,?,?,?,?,?)")
            ->execute([$guardian['id'], $student_id, $installment_id, $type, $title, $message]);
        return true;
    }
    return false;
}

/**
 * الحصول على إشعارات ولي الأمر
 */
function getGuardianNotifications($pdo, $guardian_id, $unread_only = false) {
    $sql = "SELECT n.*, s.name AS student_name, fi.installment_number, fi.amount, fi.due_date, ft.fee_name
            FROM fee_notifications n
            JOIN students s ON s.id = n.student_id
            JOIN fee_installments fi ON fi.id = n.installment_id
            JOIN student_fees sf ON sf.id = fi.student_fee_id
            JOIN fee_types ft ON ft.id = sf.fee_type_id
            WHERE n.guardian_id = ?";
    
    if ($unread_only) {
        $sql .= " AND n.is_read = 0";
    }
    
    $sql .= " ORDER BY n.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$guardian_id]);
    return $stmt->fetchAll();
}

/**
 * الحصول على أقساط طالب
 */
function getStudentFees($pdo, $student_id, $academic_year = null) {
    $sql = "SELECT sf.*, ft.fee_name, ft.fee_description,
            (SELECT COUNT(*) FROM fee_installments fi WHERE fi.student_fee_id = sf.id AND fi.status = 'paid') AS paid_installments,
            (SELECT COUNT(*) FROM fee_installments fi WHERE fi.student_fee_id = sf.id) AS total_installments
            FROM student_fees sf
            JOIN fee_types ft ON ft.id = sf.fee_type_id
            WHERE sf.student_id = ?";
    $params = [$student_id];
    
    if ($academic_year) {
        $sql .= " AND sf.academic_year = ?";
        $params[] = $academic_year;
    }
    
    $sql .= " ORDER BY sf.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * الحصول على أقساط رسوم معينة
 */
function getFeeInstallments($pdo, $student_fee_id) {
    $stmt = $pdo->prepare("SELECT * FROM fee_installments WHERE student_fee_id = ? ORDER BY installment_number");
    $stmt->execute([$student_fee_id]);
    return $stmt->fetchAll();
}

/**
 * تحديث حالة الرسوم
 */
function updateStudentFeeStatus($pdo, $student_fee_id) {
    $stmt = $pdo->prepare("SELECT total_amount, 
        (SELECT COALESCE(SUM(amount), 0) FROM fee_payments WHERE student_fee_id = ?) AS paid_amount
        FROM student_fees WHERE id = ?");
    $stmt->execute([$student_fee_id, $student_fee_id]);
    $fee = $stmt->fetch();
    
    if ($fee) {
        $paid = $fee['paid_amount'];
        $total = $fee['total_amount'];
        $remaining = $total - $paid;
        
        $status = 'pending';
        if ($paid >= $total) {
            $status = 'paid';
        } elseif ($paid > 0) {
            $status = 'partial';
        }
        
        // التحقق من التأخير
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM fee_installments WHERE student_fee_id = ? AND status = 'pending' AND due_date < CURDATE()");
        $stmt->execute([$student_fee_id]);
        if ($stmt->fetchColumn() > 0) {
            $status = 'overdue';
        }
        
        $pdo->prepare("UPDATE student_fees SET paid_amount = ?, remaining_amount = ?, status = ? WHERE id = ?")
            ->execute([$paid, $remaining, $status, $student_fee_id]);
    }
}

/* ---------------- معالجة تسجيل الدخول والخروج ---------------- */
$page = $_GET['page'] ?? 'dashboard';
$tab  = $_GET['tab'] ?? 'staff';

if ($page === 'logout') {
    session_destroy();
    redirect('?page=login');
}

if ($page === 'login' && isset($_POST['guardian_login'])) {
    $phone = trim($_POST['phone'] ?? '');
    $ref   = strtoupper(trim($_POST['ref_code'] ?? ''));
    $stmt = $pdo->prepare("SELECT g.*, s.name AS student_name FROM guardians g JOIN students s ON s.id=g.student_id WHERE g.phone=? AND g.reference_code=?");
    $stmt->execute([$phone, $ref]);
    $g = $stmt->fetch();
    if ($g) {
        $_SESSION['guardian_id'] = $g['id'];
        $_SESSION['guardian_name'] = $g['full_name'];
        $_SESSION['guardian_student_id'] = $g['student_id'];
        redirect('?page=guardian_report');
    } else {
        $login_error = 'رقم الهاتف أو الكود المرجعي غير صحيح';
        $tab = 'guardian';
    }
}

if ($page === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['guardian_login'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username=?");
    $stmt->execute([$_POST['username'] ?? '']);
    $u = $stmt->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['password'])) {
        $_SESSION['user_id']   = $u['id'];
        $_SESSION['username']  = $u['username'];
        $_SESSION['full_name'] = $u['full_name'];
        $_SESSION['role']      = $u['role'];
        redirect('?page=dashboard');
    } else {
        $login_error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
        $tab = 'staff';
    }
}

if ($page !== 'login') checkAuth();

/* ============================================================
   معالجة العمليات (POST)
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $a = $_POST['action'];

    /* ------- تسجيل حضور بالكود ------- */
    if ($a === 'checkin_by_code' && isSupervisor()) {
        $code = strtoupper(trim($_POST['employee_code'] ?? ''));
        $today = date('Y-m-d');
        $now = date('H:i:s');
        $stmt = $pdo->prepare("SELECT id, name, work_days, work_start, work_end FROM teachers WHERE employee_code = ?");
        $stmt->execute([$code]);
        $person = $stmt->fetch();
        $personType = 'teacher';
        if (!$person) {
            $stmt = $pdo->prepare("SELECT id, name, work_days, work_start, work_end FROM employees WHERE employee_code = ?");
            $stmt->execute([$code]);
            $person = $stmt->fetch();
            $personType = 'employee';
        }
        if ($person) {
            $stmt = $pdo->prepare("SELECT * FROM attendance WHERE person_type=? AND person_id=? AND att_date=? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$personType, $person['id'], $today]);
            $existing = $stmt->fetch();
            if (!$existing) {
                $status = 'present';
                if ($now > $person['work_start']) $status = 'late';
                $pdo->prepare("INSERT INTO attendance(person_type, person_id, att_date, check_in, status, notes) VALUES(?,?,?,?,?,?)")
                    ->execute([$personType, $person['id'], $today, $now, $status, 'تسجيل بالكود']);
                $_SESSION['att_message'] = "✅ تم تسجيل حضور: {$person['name']} ({$code}) - الوقت: $now";
                $_SESSION['att_type'] = 'success';
            } elseif (empty($existing['check_out'])) {
                $pdo->prepare("UPDATE attendance SET check_out=?, notes=CONCAT(notes, ' - تسجيل انصراف بالكود') WHERE id=?")
                    ->execute([$now, $existing['id']]);
                $_SESSION['att_message'] = "👋 تم تسجيل انصراف: {$person['name']} ({$code}) - الوقت: $now";
                $_SESSION['att_type'] = 'info';
            } else {
                $_SESSION['att_message'] = "⚠️ تم تسجيل الحضور والانصراف مسبقاً لـ: {$person['name']} ({$code})";
                $_SESSION['att_type'] = 'warning';
            }
        } else {
            $_SESSION['att_message'] = "❌ الكود غير صحيح: $code";
            $_SESSION['att_type'] = 'danger';
        }
        redirect('?page=attendance');
    }

    /* ------- مدرسون ------- */
    if ($a === 'add_teacher' && isDataEntry()) {
        $code = generateEmployeeCode($pdo, 'teacher');
        $work_days = implode(',', $_POST['work_days'] ?? ['0']);
        $pdo->prepare("INSERT INTO teachers(employee_code,name,national_id,phone,email,subject,hire_date,salary,address,work_days,work_start,work_end,annual_leave_days) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$code,$_POST['name'],$_POST['national_id'],$_POST['phone'],$_POST['email'],$_POST['subject'],$_POST['hire_date'],$_POST['salary'],$_POST['address'],$work_days,$_POST['work_start'],$_POST['work_end'],$_POST['annual_leave_days'] ?? 30]);
        redirect('?page=teachers');
    }
    if ($a === 'edit_teacher' && isDataEntry()) {
        $work_days = implode(',', $_POST['work_days'] ?? ['0']);
        $pdo->prepare("UPDATE teachers SET name=?,national_id=?,phone=?,email=?,subject=?,hire_date=?,salary=?,address=?,work_days=?,work_start=?,work_end=?,annual_leave_days=? WHERE id=?")
            ->execute([$_POST['name'],$_POST['national_id'],$_POST['phone'],$_POST['email'],$_POST['subject'],$_POST['hire_date'],$_POST['salary'],$_POST['address'],$work_days,$_POST['work_start'],$_POST['work_end'],$_POST['annual_leave_days'],$_POST['id']]);
        redirect('?page=teachers');
    }
    if ($a === 'del_teacher' && isAdmin()) {
        $pdo->prepare("DELETE FROM teachers WHERE id=?")->execute([$_POST['id']]);
        redirect('?page=teachers');
    }

    /* ------- موظفون ------- */
    if ($a === 'add_employee' && isDataEntry()) {
        $code = generateEmployeeCode($pdo, 'employee');
        $work_days = implode(',', $_POST['work_days'] ?? ['0']);
        $pdo->prepare("INSERT INTO employees(employee_code,name,national_id,phone,job_title,hire_date,salary,address,work_days,work_start,work_end,annual_leave_days) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$code,$_POST['name'],$_POST['national_id'],$_POST['phone'],$_POST['job_title'],$_POST['hire_date'],$_POST['salary'],$_POST['address'],$work_days,$_POST['work_start'],$_POST['work_end'],$_POST['annual_leave_days'] ?? 30]);
        redirect('?page=employees');
    }
    if ($a === 'edit_employee' && isDataEntry()) {
        $work_days = implode(',', $_POST['work_days'] ?? ['0']);
        $pdo->prepare("UPDATE employees SET name=?,national_id=?,phone=?,job_title=?,hire_date=?,salary=?,address=?,work_days=?,work_start=?,work_end=?,annual_leave_days=? WHERE id=?")
            ->execute([$_POST['name'],$_POST['national_id'],$_POST['phone'],$_POST['job_title'],$_POST['hire_date'],$_POST['salary'],$_POST['address'],$work_days,$_POST['work_start'],$_POST['work_end'],$_POST['annual_leave_days'],$_POST['id']]);
        redirect('?page=employees');
    }
    if ($a === 'del_employee' && isAdmin()) {
        $pdo->prepare("DELETE FROM employees WHERE id=?")->execute([$_POST['id']]);
        redirect('?page=employees');
    }

    /* ------- رواتب ------- */
    if ($a === 'add_salary' && isAccountant()) {
        $basic = (float)($_POST['basic_salary'] ?? 0);
        $bonus = (float)($_POST['bonus'] ?? 0);
        $manual_deduction = (float)($_POST['deduction'] ?? 0);
        $absence_days = 0;
        $absence_deduction = 0;
        if (isset($_POST['auto_deduction']) && $_POST['auto_deduction'] == '1') {
            $absenceData = getAbsenceDays($pdo, $_POST['person_type'], $_POST['person_id'], $_POST['month'], $_POST['year']);
            $absence_days = $absenceData['absent'];
            $calc = calculateAbsenceDeduction($pdo, $basic, $absenceData['absent'], $absenceData['late']);
            $absence_deduction = $calc['total_deduction'];
        }
        $total_deduction = $manual_deduction + $absence_deduction;
        $net = $basic + $bonus - $total_deduction;
        $pdo->prepare("INSERT INTO salaries(person_type,person_id,month,year,basic_salary,bonus,deduction,absence_days,absence_deduction,net_salary,paid_date,status) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$_POST['person_type'],$_POST['person_id'],$_POST['month'],$_POST['year'],$basic,$bonus,$manual_deduction,$absence_days,$absence_deduction,$net,$_POST['paid_date'],$_POST['status']]);
        redirect('?page=salaries');
    }
    if ($a === 'del_salary' && isAccountant()) {
        $pdo->prepare("DELETE FROM salaries WHERE id=?")->execute([$_POST['id']]);
        redirect('?page=salaries');
    }
    if ($a === 'mark_paid' && isAccountant()) {
        $pdo->prepare("UPDATE salaries SET status='paid', paid_date=CURDATE() WHERE id=?")->execute([$_POST['id']]);
        redirect('?page=salaries');
    }
    if ($a === 'calc_auto_deduction' && isAccountant()) {
        $stmt = $pdo->prepare("SELECT * FROM salaries WHERE id=?");
        $stmt->execute([$_POST['id']]);
        $sal = $stmt->fetch();
        if ($sal) {
            $absenceData = getAbsenceDays($pdo, $sal['person_type'], $sal['person_id'], $sal['month'], $sal['year']);
            $calc = calculateAbsenceDeduction($pdo, $sal['basic_salary'], $absenceData['absent'], $absenceData['late']);
            $new_deduction = $sal['deduction'] + $calc['total_deduction'];
            $new_net = $sal['basic_salary'] + $sal['bonus'] - $new_deduction;
            $pdo->prepare("UPDATE salaries SET absence_days=?, absence_deduction=?, deduction=?, net_salary=? WHERE id=?")
                ->execute([$absenceData['absent'], $calc['total_deduction'], $new_deduction, $new_net, $sal['id']]);
        }
        redirect('?page=salaries');
    }

    /* ------- حضور ------- */
    if ($a === 'add_attendance' && isSupervisor()) {
        $pdo->prepare("INSERT INTO attendance(person_type,person_id,att_date,check_in,check_out,status,notes) VALUES(?,?,?,?,?,?,?)")
            ->execute([$_POST['person_type'],$_POST['person_id'],$_POST['att_date'],$_POST['check_in'],$_POST['check_out'],$_POST['status'],$_POST['notes']]);
        redirect('?page=attendance');
    }
    if ($a === 'del_attendance' && isSupervisor()) {
        $pdo->prepare("DELETE FROM attendance WHERE id=?")->execute([$_POST['id']]);
        redirect('?page=attendance');
    }
    if ($a === 'mark_absent' && isSupervisor()) {
        $date = $_POST['absent_date'] ?? date('Y-m-d');
        $holiday = isHoliday($pdo, $date);
        if (!$holiday) {
            $teachers = $pdo->query("SELECT id, work_days FROM teachers")->fetchAll();
            $employees = $pdo->query("SELECT id, work_days FROM employees")->fetchAll();
            foreach ($teachers as $t) {
                if (isWorkingDay($t['work_days'], $date)) {
                    $stmt = $pdo->prepare("SELECT id FROM attendance WHERE person_type='teacher' AND person_id=? AND att_date=?");
                    $stmt->execute([$t['id'], $date]);
                    if (!$stmt->fetch()) {
                        $pdo->prepare("INSERT INTO attendance(person_type,person_id,att_date,status,notes) VALUES('teacher',?,?,'absent','غياب تلقائي')")
                            ->execute([$t['id'], $date]);
                    }
                }
            }
            foreach ($employees as $e) {
                if (isWorkingDay($e['work_days'], $date)) {
                    $stmt = $pdo->prepare("SELECT id FROM attendance WHERE person_type='employee' AND person_id=? AND att_date=?");
                    $stmt->execute([$e['id'], $date]);
                    if (!$stmt->fetch()) {
                        $pdo->prepare("INSERT INTO attendance(person_type,person_id,att_date,status,notes) VALUES('employee',?,?,'absent','غياب تلقائي')")
                            ->execute([$e['id'], $date]);
                    }
                }
            }
        }
        $_SESSION['att_message'] = "✅ تم تسجيل الغياب لجميع من لم يسجل حضور بتاريخ $date";
        $_SESSION['att_type'] = 'success';
        redirect('?page=attendance');
    }

    /* ------- عطلات ------- */
    if ($a === 'add_holiday' && isSupervisor()) {
        try {
            $pdo->prepare("INSERT INTO holidays(holiday_name,holiday_date,description) VALUES(?,?,?)")
                ->execute([$_POST['holiday_name'],$_POST['holiday_date'],$_POST['description']]);
        } catch (PDOException $e) {}
        redirect('?page=holidays');
    }
    if ($a === 'del_holiday' && isSupervisor()) {
        $pdo->prepare("DELETE FROM holidays WHERE id=?")->execute([$_POST['id']]);
        redirect('?page=holidays');
    }

    /* ------- إجازات ------- */
    if ($a === 'add_leave_request' && isSupervisor()) {
        $start = $_POST['start_date'];
        $end = $_POST['end_date'];
        $days = (int)((strtotime($end) - strtotime($start)) / 86400) + 1;
        $pdo->prepare("INSERT INTO leave_requests(person_type,person_id,leave_type,start_date,end_date,days_count,reason) VALUES(?,?,?,?,?,?,?)")
            ->execute([$_POST['person_type'],$_POST['person_id'],$_POST['leave_type'],$start,$end,$days,$_POST['reason']]);
        $_SESSION['leave_message'] = "✅ تم تقديم طلب الإجازة بنجاح ($days يوم)";
        $_SESSION['leave_type_msg'] = 'success';
        redirect('?page=leaves');
    }
    if ($a === 'approve_leave' && isSupervisor()) {
        $stmt = $pdo->prepare("SELECT * FROM leave_requests WHERE id=?");
        $stmt->execute([$_POST['id']]);
        $leave = $stmt->fetch();
        if ($leave && $leave['status'] === 'pending') {
            $pdo->prepare("UPDATE leave_requests SET status='approved', approved_by=?, approved_at=NOW() WHERE id=?")
                ->execute([$_SESSION['full_name'], $leave['id']]);
            $year = (int)date('Y', strtotime($leave['start_date']));
            $balance = getLeaveBalance($pdo, $leave['person_type'], $leave['person_id'], $year);
            $field = $leave['leave_type'] . '_used';
            if (in_array($field, ['annual_used','sick_used','emergency_used','unpaid_used'])) {
                $pdo->prepare("UPDATE leave_balance SET $field = $field + ? WHERE id=?")
                    ->execute([$leave['days_count'], $balance['id']]);
            }
            $current = strtotime($leave['start_date']);
            $end = strtotime($leave['end_date']);
            while ($current <= $end) {
                $date = date('Y-m-d', $current);
                $stmt = $pdo->prepare("SELECT id FROM attendance WHERE person_type=? AND person_id=? AND att_date=?");
                $stmt->execute([$leave['person_type'], $leave['person_id'], $date]);
                if (!$stmt->fetch()) {
                    $pdo->prepare("INSERT INTO attendance(person_type,person_id,att_date,status,notes) VALUES(?,?,?,'leave','إجازة معتمدة')")
                        ->execute([$leave['person_type'], $leave['person_id'], $date]);
                }
                $current += 86400;
            }
            $_SESSION['leave_message'] = "✅ تم الموافقة على طلب الإجازة";
        }
        redirect('?page=leaves');
    }
    if ($a === 'reject_leave' && isSupervisor()) {
        $pdo->prepare("UPDATE leave_requests SET status='rejected', approved_by=?, approved_at=NOW() WHERE id=?")
            ->execute([$_SESSION['full_name'], $_POST['id']]);
        $_SESSION['leave_message'] = "❌ تم رفض طلب الإجازة";
        redirect('?page=leaves');
    }
    if ($a === 'del_leave' && isAdmin()) {
        $pdo->prepare("DELETE FROM leave_requests WHERE id=?")->execute([$_POST['id']]);
        redirect('?page=leaves');
    }

    /* ------- إعدادات ------- */
    if ($a === 'save_settings' && isAdmin()) {
        $settings = ['absence_deduction_per_day', 'late_deduction_per_day', 'work_days_per_month', 'school_name', 'pass_grade'];
        foreach ($settings as $key) {
            if (isset($_POST[$key])) {
                $stmt = $pdo->prepare("INSERT INTO settings(setting_key, setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=?");
                $stmt->execute([$key, $_POST[$key], $_POST[$key]]);
            }
        }
        redirect('?page=settings');
    }

    /* ==================================================
       إضافة سنة دراسية جديدة (مستوى)
       ================================================== */
    if ($a === 'add_grade_level_new' && isDataEntry()) {
        $level_number = (int)$_POST['level_number'];
        $level_name = trim($_POST['level_name']);
        $academic_year = trim($_POST['academic_year'] ?? '');
        
        try {
            $pdo->beginTransaction();
            
            $pdo->prepare("INSERT INTO grade_levels(level_number, level_name, academic_year) VALUES(?,?,?)")
                ->execute([$level_number, $level_name, $academic_year]);
            
            if ($academic_year) {
                $check = $pdo->prepare("SELECT id FROM academic_years WHERE year_name=?");
                $check->execute([$academic_year]);
                if (!$check->fetch()) {
                    $pdo->prepare("INSERT INTO academic_years(year_name) VALUES(?)")->execute([$academic_year]);
                }
            }
            
            $pdo->commit();
            $_SESSION['gl_msg'] = "✅ تم إضافة السنة الدراسية: $level_name ($academic_year)";
            $_SESSION['gl_type'] = 'success';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['gl_msg'] = "⚠️ خطأ: " . $e->getMessage();
            $_SESSION['gl_type'] = 'danger';
        }
        redirect('?page=grade_levels');
    }
    if ($a === 'edit_grade_level' && isDataEntry()) {
        $pdo->prepare("UPDATE grade_levels SET level_number=?, level_name=?, academic_year=? WHERE id=?")
            ->execute([(int)$_POST['level_number'], trim($_POST['level_name']), trim($_POST['academic_year'] ?? ''), $_POST['id']]);
        redirect('?page=grade_levels');
    }
    if ($a === 'del_grade_level' && isAdmin()) {
        $cnt = $pdo->prepare("SELECT COUNT(*) FROM students s JOIN classes c ON c.id=s.class_id WHERE c.grade_level_id=?");
        $cnt->execute([$_POST['id']]);
        if ($cnt->fetchColumn() > 0) {
            $_SESSION['gl_msg'] = "⚠️ لا يمكن الحذف - يوجد طلبة مرتبطون بهذا المستوى";
            $_SESSION['gl_type'] = 'danger';
        } else {
            $pdo->prepare("DELETE FROM grade_levels WHERE id=?")->execute([$_POST['id']]);
            $_SESSION['gl_msg'] = "✅ تم الحذف";
            $_SESSION['gl_type'] = 'success';
        }
        redirect('?page=grade_levels');
    }

    /* ==================================================
       إضافة فصل مع شعب متعددة
       ================================================== */
    if ($a === 'add_class_with_sections' && isDataEntry()) {
        $gl_id = (int)$_POST['grade_level_id'];
        $class_name = trim($_POST['class_name']);
        $sections_raw = trim($_POST['sections'] ?? '');
        $sections = array_filter(array_map('trim', explode(',', $sections_raw)));
        $academic_year = trim($_POST['academic_year'] ?? '');
        
        if ($class_name && count($sections) > 0) {
            $added = 0;
            foreach ($sections as $sec) {
                try {
                    $section_full_name = $class_name . ' - ' . $sec;
                    $pdo->prepare("INSERT INTO classes(class_name, grade_level_id, section, section_name, academic_year) VALUES(?,?,?,?,?)")
                        ->execute([$class_name, $gl_id, $sec, $section_full_name, $academic_year]);
                    $added++;
                } catch (PDOException $e) {}
            }
            $_SESSION['gl_msg'] = "✅ تم إضافة $added شعبة للفصل: $class_name";
            $_SESSION['gl_type'] = 'success';
        } else {
            $_SESSION['gl_msg'] = "⚠️ يرجى إدخال اسم الفصل والشعب";
            $_SESSION['gl_type'] = 'danger';
        }
        redirect("?page=classes&grade_level_id=$gl_id");
    }
    if ($a === 'add_class' && isDataEntry()) {
        $gl_id = (int)$_POST['grade_level_id'];
        $class_name = trim($_POST['class_name']);
        $section = trim($_POST['section'] ?? '');
        $section_name = $section ? $class_name . ' - ' . $section : $class_name;
        try {
            $pdo->prepare("INSERT INTO classes(class_name, grade_level_id, section, section_name) VALUES(?,?,?,?)")
                ->execute([$class_name, $gl_id, $section, $section_name]);
            $_SESSION['gl_msg'] = "✅ تم إضافة الفصل: $class_name" . ($section ? " - الشعبة: $section" : "");
            $_SESSION['gl_type'] = 'success';
        } catch (PDOException $e) {
            $_SESSION['gl_msg'] = "⚠️ خطأ: " . $e->getMessage();
            $_SESSION['gl_type'] = 'danger';
        }
        redirect("?page=classes&grade_level_id=$gl_id");
    }
    if ($a === 'edit_class' && isDataEntry()) {
        $gl_id = (int)$_POST['grade_level_id'];
        $class_name = trim($_POST['class_name']);
        $section = trim($_POST['section'] ?? '');
        $section_name = $section ? $class_name . ' - ' . $section : $class_name;
        $pdo->prepare("UPDATE classes SET class_name=?, grade_level_id=?, section=?, section_name=? WHERE id=?")
            ->execute([$class_name, $gl_id, $section, $section_name, $_POST['id']]);
        redirect("?page=classes&grade_level_id=$gl_id");
    }
    if ($a === 'del_class' && isAdmin()) {
        $gl_id = (int)($_POST['grade_level_id'] ?? 0);
        $cnt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE class_id=?");
        $cnt->execute([$_POST['id']]);
        if ($cnt->fetchColumn() > 0) {
            $_SESSION['gl_msg'] = "⚠️ لا يمكن الحذف - يوجد طلبة في هذا الفصل";
            $_SESSION['gl_type'] = 'danger';
        } else {
            $pdo->prepare("DELETE FROM classes WHERE id=?")->execute([$_POST['id']]);
            $_SESSION['gl_msg'] = "✅ تم حذف الفصل";
            $_SESSION['gl_type'] = 'success';
        }
        redirect("?page=classes&grade_level_id=$gl_id");
    }

    /* ==================================================
       إضافة مادة للفصل
       ================================================== */
    if ($a === 'add_subject_to_class' && isDataEntry()) {
        $class_id = (int)$_POST['class_id'];
        $grade_level_id = (int)$_POST['grade_level_id'];
        $subject_name = trim($_POST['subject_name']);
        $subject_type = ($_POST['subject_type'] ?? 'local') === 'international' ? 'international' : 'local';
        $academic_year = trim($_POST['academic_year']);
        $max_grade = (int)($_POST['max_grade'] ?? 100);
        $coursework_max = (int)($_POST['coursework_max'] ?? 40);
        $final_max = (int)($_POST['final_max'] ?? 60);
        
        try {
            $pdo->prepare("INSERT INTO subjects(subject_name, academic_year, grade_level_id, class_id, max_grade, coursework_max, final_max, subject_type) VALUES(?,?,?,?,?,?,?,?)")
                ->execute([$subject_name, $academic_year, $grade_level_id, $class_id, $max_grade, $coursework_max, $final_max, $subject_type]);
            
            $_SESSION['subj_msg'] = "✅ تم إضافة المادة: $subject_name (" . getSubjectTypeName($subject_type) . ")";
            $_SESSION['subj_type'] = 'success';
        } catch (PDOException $e) {
            $_SESSION['subj_msg'] = "⚠️ خطأ: " . $e->getMessage();
            $_SESSION['subj_type'] = 'danger';
        }
        redirect("?page=subjects&class_id=$class_id");
    }
    if ($a === 'edit_subject_grade' && isDataEntry()) {
        $subject_type = ($_POST['subject_type'] ?? 'local') === 'international' ? 'international' : 'local';
        $class_id = (int)$_POST['class_id'];
        $pdo->prepare("UPDATE subjects SET subject_name=?, academic_year=?, grade_level_id=?, class_id=?, max_grade=?, coursework_max=?, final_max=?, subject_type=? WHERE id=?")
            ->execute([trim($_POST['subject_name']), trim($_POST['academic_year']), (int)$_POST['grade_level_id'], $class_id, (int)$_POST['max_grade'], (int)$_POST['coursework_max'], (int)$_POST['final_max'], $subject_type, $_POST['id']]);
        $_SESSION['subj_msg'] = "✅ تم تحديث المادة";
        $_SESSION['subj_type'] = 'success';
        redirect("?page=subjects&class_id=$class_id");
    }
    if ($a === 'del_subject_grade' && isAdmin()) {
        $class_id = (int)($_POST['class_id'] ?? 0);
        $pdo->prepare("DELETE FROM subjects WHERE id=?")->execute([$_POST['id']]);
        $_SESSION['subj_msg'] = "✅ تم حذف المادة";
        $_SESSION['subj_type'] = 'success';
        redirect("?page=subjects&class_id=$class_id");
    }

    /* ==================================================
       إضافة حصة للجدول مع التوقيت
       ================================================== */
    if ($a === 'add_timetable_full' && isDataEntry()) {
        $class_id = (int)$_POST['class_id'];
        $grade_level_id = (int)$_POST['grade_level_id'];
        $teacher_id = (int)$_POST['teacher_id'];
        $subject_name = trim($_POST['subject_name']);
        $day = $_POST['day_of_week'];
        $period = (int)$_POST['period_number'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $notes = trim($_POST['notes'] ?? '');
        
        $check = $pdo->prepare("SELECT id FROM timetables WHERE class_id=? AND day_of_week=? AND period_number=?");
        $check->execute([$class_id, $day, $period]);
        
        if ($check->fetch()) {
            $_SESSION['tt_msg'] = "⚠️ يوجد حصة مسجلة مسبقاً في هذا اليوم وهذه الحصة";
            $_SESSION['tt_type'] = 'danger';
        } else {
            $pdo->prepare("INSERT INTO timetables(class_id, grade_level_id, teacher_id, subject_name, day_of_week, period_number, start_time, end_time, notes) VALUES(?,?,?,?,?,?,?,?,?)")
                ->execute([$class_id, $grade_level_id, $teacher_id, $subject_name, $day, $period, $start_time, $end_time, $notes]);
            
            $_SESSION['tt_msg'] = "✅ تم إضافة الحصة بنجاح";
            $_SESSION['tt_type'] = 'success';
        }
        redirect("?page=timetables&class_id=$class_id");
    }
    if ($a === 'edit_timetable' && isDataEntry()) {
        $class_id = (int)$_POST['class_id'];
        $grade_level_id = (int)$_POST['grade_level_id'];
        $teacher_id = (int)$_POST['teacher_id'];
        $day = $_POST['day_of_week'];
        $period = (int)$_POST['period_number'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        
        $check = $pdo->prepare("SELECT id FROM timetables WHERE class_id=? AND day_of_week=? AND period_number=? AND id<>?");
        $check->execute([$class_id, $day, $period, $_POST['id']]);
        if ($check->fetch()) {
            $_SESSION['tt_msg'] = "⚠️ يوجد تعارض مع حصة أخرى";
            $_SESSION['tt_type'] = 'danger';
        } else {
            $pdo->prepare("UPDATE timetables SET teacher_id=?, subject_name=?, day_of_week=?, period_number=?, start_time=?, end_time=?, notes=? WHERE id=?")
                ->execute([$teacher_id, $_POST['subject_name'], $day, $period, $start_time, $end_time, $_POST['notes'], $_POST['id']]);
            $_SESSION['tt_msg'] = "✅ تم تحديث الحصة";
            $_SESSION['tt_type'] = 'success';
        }
        redirect("?page=timetables&class_id=$class_id");
    }
    if ($a === 'del_timetable' && isDataEntry()) {
        $class_id = (int)($_POST['class_id'] ?? 0);
        $pdo->prepare("DELETE FROM timetables WHERE id=?")->execute([$_POST['id']]);
        redirect("?page=timetables&class_id=$class_id");
    }

    /* ==================================================
       ترقية الطلبة تلقائياً
       ================================================== */
    if ($a === 'promote_students' && isDataEntry()) {
        $from_gl = (int)$_POST['from_grade_level_id'];
        $academic_year = $_POST['academic_year'];
        $pass_grade = (float)getSetting($pdo, 'pass_grade', 50);
        
        $stmt = $pdo->prepare("SELECT * FROM grade_levels WHERE level_number = (SELECT level_number+1 FROM grade_levels WHERE id=?)");
        $stmt->execute([$from_gl]);
        $nextLevel = $stmt->fetch();
        
        if (!$nextLevel) {
            $_SESSION['promote_msg'] = "⚠️ لا يوجد مستوى أعلى للترقية إليه";
            $_SESSION['promote_type'] = 'warning';
            redirect("?page=promote&grade_level_id=$from_gl&academic_year=$academic_year");
        }
        
        $stmt = $pdo->prepare("SELECT s.*, c.section, c.id AS class_id FROM students s 
            JOIN classes c ON c.id=s.class_id 
            WHERE c.grade_level_id=? AND s.status='active'");
        $stmt->execute([$from_gl]);
        $students = $stmt->fetchAll();
        
        $promoted = 0;
        $failed = 0;
        
        foreach ($students as $st) {
            $avg = getStudentAverage($pdo, $st['id'], $academic_year);
            
            if ($avg >= $pass_grade) {
                $stmt = $pdo->prepare("SELECT id FROM classes WHERE grade_level_id=? AND section=?");
                $stmt->execute([$nextLevel['id'], $st['section']]);
                $targetClass = $stmt->fetch();
                
                if (!$targetClass) {
                    $newClassName = $nextLevel['level_name'] . ' - ' . $st['section'];
                    $pdo->prepare("INSERT INTO classes(class_name, grade_level_id, section, section_name) VALUES(?,?,?,?)")
                        ->execute([$nextLevel['level_name'], $nextLevel['id'], $st['section'], $newClassName]);
                    $targetClassId = $pdo->lastInsertId();
                } else {
                    $targetClassId = $targetClass['id'];
                }
                
                $pdo->prepare("UPDATE students SET class_id=?, academic_year=?, semester='first', status='active' WHERE id=?")
                    ->execute([$targetClassId, $_POST['new_academic_year'] ?? $academic_year, $st['id']]);
                
                $pdo->prepare("INSERT INTO promotions(student_id, from_class_id, to_class_id, from_grade_level, to_grade_level, academic_year, avg_score, status, promoted_by) VALUES(?,?,?,?,?,?,?,?,?)")
                    ->execute([$st['id'], $st['class_id'], $targetClassId, $from_gl, $nextLevel['id'], $academic_year, $avg, 'passed', $_SESSION['full_name']]);
                $promoted++;
            } else {
                $failed++;
                $pdo->prepare("INSERT INTO promotions(student_id, from_class_id, from_grade_level, academic_year, avg_score, status, promoted_by) VALUES(?,?,?,?,?,?,?)")
                    ->execute([$st['id'], $st['class_id'], $from_gl, $academic_year, $avg, 'failed', $_SESSION['full_name']]);
            }
        }
        
        $_SESSION['promote_msg'] = "✅ تم ترقية $promoted طالب، ورسوب $failed طالب (المعدل من $pass_grade)";
        $_SESSION['promote_type'] = 'success';
        redirect("?page=promote&grade_level_id=$from_gl&academic_year=$academic_year");
    }

    /* ------- طلبة ------- */
    if ($a === 'add_student' && isDataEntry()) {
        $code = generateStudentCode($pdo);
        $pdo->prepare("INSERT INTO students(student_code,name,national_id,birth_date,gender,class_id,academic_year,semester,guardian_name,guardian_phone,address,enroll_date,status) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,'active')")
            ->execute([$code,$_POST['name'],$_POST['national_id'],$_POST['birth_date'],$_POST['gender'],$_POST['class_id'] ?: null,$_POST['academic_year'],$_POST['semester'],$_POST['guardian_name'],$_POST['guardian_phone'],$_POST['address'],$_POST['enroll_date']]);
        redirect('?page=students');
    }
    if ($a === 'edit_student' && isDataEntry()) {
        $pdo->prepare("UPDATE students SET name=?,national_id=?,birth_date=?,gender=?,class_id=?,academic_year=?,semester=?,guardian_name=?,guardian_phone=?,address=?,enroll_date=?,status=? WHERE id=?")
            ->execute([$_POST['name'],$_POST['national_id'],$_POST['birth_date'],$_POST['gender'],$_POST['class_id'] ?: null,$_POST['academic_year'],$_POST['semester'],$_POST['guardian_name'],$_POST['guardian_phone'],$_POST['address'],$_POST['enroll_date'],$_POST['status'] ?? 'active',$_POST['id']]);
        redirect('?page=students');
    }
    if ($a === 'del_student' && isAdmin()) {
        $pdo->prepare("DELETE FROM students WHERE id=?")->execute([$_POST['id']]);
        redirect('?page=students');
    }

    /* ------- درجات ------- */
    if ($a === 'add_grade' && isDataEntry()) {
        $coursework = (float)($_POST['coursework'] ?? 0);
        $final = (float)($_POST['final_exam'] ?? 0);
        $total = $coursework + $final;
        $pdo->prepare("INSERT INTO grades(student_id,subject_id,semester,year,academic_year,coursework,final_exam,total) VALUES(?,?,?,?,?,?,?,?)")
            ->execute([$_POST['student_id'],$_POST['subject_id'],$_POST['semester'],$_POST['year'],$_POST['academic_year'],$coursework,$final,$total]);
        redirect('?page=grades');
    }
    if ($a === 'del_grade' && isDataEntry()) {
        $pdo->prepare("DELETE FROM grades WHERE id=?")->execute([$_POST['id']]);
        redirect('?page=grades');
    }

    /* ------- ملاحظات ------- */
    if ($a === 'add_note' && isDataEntry()) {
        $pdo->prepare("INSERT INTO notes(person_type,person_id,note,note_date,created_by) VALUES(?,?,?,?,?)")
            ->execute([$_POST['person_type'],$_POST['person_id'],$_POST['note'],$_POST['note_date'],$_SESSION['full_name'] ?? '']);
        redirect('?page=notes');
    }
    if ($a === 'del_note' && isAdmin()) {
        $pdo->prepare("DELETE FROM notes WHERE id=?")->execute([$_POST['id']]);
        redirect('?page=notes');
    }

    /* ------- أولياء الأمور ------- */
    if ($a === 'add_guardian' && isDataEntry()) {
        $code = generateGuardianCode($pdo);
        $phone = trim($_POST['phone'] ?? '');
        try {
            $stmt = $pdo->prepare("SELECT id FROM guardians WHERE phone=? AND student_id=?");
            $stmt->execute([$phone, $_POST['student_id']]);
            if ($stmt->fetch()) {
                $_SESSION['guardian_error'] = '⚠️ يوجد ولي أمر مسجل بنفس الهاتف لهذا الطالب';
            } else {
                $pdo->prepare("INSERT INTO guardians(full_name,phone,reference_code,student_id) VALUES(?,?,?,?)")
                    ->execute([$_POST['full_name'], $phone, $code, $_POST['student_id']]);
                $_SESSION['guardian_success'] = "✅ تم إضافة ولي الأمر. الكود المرجعي: $code";
            }
        } catch (PDOException $e) {
            $_SESSION['guardian_error'] = '⚠️ حدث خطأ: ' . $e->getMessage();
        }
        redirect('?page=guardians');
    }
    if ($a === 'del_guardian' && isAdmin()) {
        $pdo->prepare("DELETE FROM guardians WHERE id=?")->execute([$_POST['id']]);
        redirect('?page=guardians');
    }

    /* ------- مستخدمون ------- */
    if ($a === 'add_user' && isAdmin()) {
        $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        try {
            $pdo->prepare("INSERT INTO users(username,password,full_name,role) VALUES(?,?,?,?)")
                ->execute([$_POST['username'],$hash,$_POST['full_name'],$_POST['role']]);
        } catch (PDOException $e) {}
        redirect('?page=users');
    }
    if ($a === 'del_user' && isAdmin()) {
        if ($_POST['id'] != $_SESSION['user_id']) {
            $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$_POST['id']]);
        }
        redirect('?page=users');
    }

    /* ------- سنة دراسية ------- */
    if ($a === 'add_academic_year' && isDataEntry()) {
        try {
            $pdo->prepare("INSERT INTO academic_years(year_name) VALUES(?)")->execute([$_POST['year_name']]);
        } catch (PDOException $e) {}
        redirect('?page=students');
    }

    /* ==================================================
       نظام الأقساط - أنواع الرسوم
       ================================================== */
    if ($a === 'add_fee_type' && isDataEntry()) {
        $first_installment = (float)($_POST['first_installment_amount'] ?? 0);
        $total_amount = (float)$_POST['total_amount'];
        $installments_count = (int)($_POST['installments_count'] ?? 2);
        
        // التحقق: إذا كان مبلغ القسط الأول أكبر من الإجمالي
        if ($first_installment > $total_amount) {
            $first_installment = $total_amount;
        }
        
        $pdo->prepare("INSERT INTO fee_types(fee_name, fee_description, academic_year, grade_level_id, class_id, total_amount, installments_count, first_installment_amount) VALUES(?,?,?,?,?,?,?,?)")
            ->execute([
                trim($_POST['fee_name']),
                trim($_POST['fee_description'] ?? ''),
                trim($_POST['academic_year']),
                (int)($_POST['grade_level_id'] ?? 0) ?: null,
                (int)($_POST['class_id'] ?? 0) ?: null,
                $total_amount,
                $installments_count,
                $first_installment
            ]);
        $_SESSION['fee_msg'] = "✅ تم إضافة نوع الرسوم بنجاح";
        $_SESSION['fee_type_msg'] = 'success';
        redirect('?page=fee_types');
    }
    if ($a === 'edit_fee_type' && isDataEntry()) {
        $first_installment = (float)($_POST['first_installment_amount'] ?? 0);
        $total_amount = (float)$_POST['total_amount'];
        
        if ($first_installment > $total_amount) {
            $first_installment = $total_amount;
        }
        
        $pdo->prepare("UPDATE fee_types SET fee_name=?, fee_description=?, academic_year=?, grade_level_id=?, class_id=?, total_amount=?, installments_count=?, first_installment_amount=?, is_active=? WHERE id=?")
            ->execute([
                trim($_POST['fee_name']),
                trim($_POST['fee_description'] ?? ''),
                trim($_POST['academic_year']),
                (int)($_POST['grade_level_id'] ?? 0) ?: null,
                (int)($_POST['class_id'] ?? 0) ?: null,
                $total_amount,
                (int)($_POST['installments_count'] ?? 2),
                $first_installment,
                isset($_POST['is_active']) ? 1 : 0,
                $_POST['id']
            ]);
        $_SESSION['fee_msg'] = "✅ تم تحديث نوع الرسوم";
        $_SESSION['fee_type_msg'] = 'success';
        redirect('?page=fee_types');
    }
    if ($a === 'del_fee_type' && isAdmin()) {
        $pdo->prepare("DELETE FROM fee_types WHERE id=?")->execute([$_POST['id']]);
        $_SESSION['fee_msg'] = "✅ تم حذف نوع الرسوم";
        $_SESSION['fee_type_msg'] = 'success';
        redirect('?page=fee_types');
    }

    /* ==================================================
       نظام الأقساط - تعيين رسوم لطالب
       ================================================== */
    if ($a === 'assign_student_fee' && isDataEntry()) {
        $student_id = (int)$_POST['student_id'];
        $fee_type_id = (int)$_POST['fee_type_id'];
        $academic_year = trim($_POST['academic_year']);
        $semester = $_POST['semester'] ?? 'first';
        $start_date = $_POST['start_date'];
        $custom_amount = (float)($_POST['custom_amount'] ?? 0);
        $custom_first_installment = (float)($_POST['first_installment_amount'] ?? 0);
        
        // الحصول على تفاصيل نوع الرسوم
        $stmt = $pdo->prepare("SELECT * FROM fee_types WHERE id=?");
        $stmt->execute([$fee_type_id]);
        $fee_type = $stmt->fetch();
        
        if ($fee_type) {
            $total_amount = $custom_amount > 0 ? $custom_amount : $fee_type['total_amount'];
            $installments_count = (int)($_POST['installments_count'] ?? $fee_type['installments_count']);
            
            // تحديد مبلغ القسط الأول: إذا تم إدخاله يدوياً استخدمه، وإلا استخدم القيمة من نوع الرسوم
            $first_installment_amount = $custom_first_installment > 0 ? $custom_first_installment : (float)$fee_type['first_installment_amount'];
            
            // التحقق من صحة المبلغ
            if ($first_installment_amount > $total_amount) {
                $first_installment_amount = $total_amount;
            }
            
            try {
                $pdo->beginTransaction();
                
                // إنشاء سجل الرسوم للطالب
                $pdo->prepare("INSERT INTO student_fees(student_id, fee_type_id, academic_year, semester, total_amount, remaining_amount, status) VALUES(?,?,?,?,?,?,'pending')")
                    ->execute([$student_id, $fee_type_id, $academic_year, $semester, $total_amount, $total_amount]);
                
                $student_fee_id = $pdo->lastInsertId();
                
                // إنشاء الأقساط (مع دعم مبلغ القسط الأول المخصص)
                $created = createStudentInstallments($pdo, $student_fee_id, $total_amount, $installments_count, $start_date, $first_installment_amount);
                
                // الحصول على معلومات الطالب وولي الأمر
                $stmt = $pdo->prepare("SELECT s.name AS student_name, g.id AS guardian_id FROM students s LEFT JOIN guardians g ON g.student_id = s.id WHERE s.id = ?");
                $stmt->execute([$student_id]);
                $student_info = $stmt->fetch();
                
                // إرسال إشعارات لجميع الأقساط
                $stmt = $pdo->prepare("SELECT * FROM fee_installments WHERE student_fee_id = ? ORDER BY installment_number");
                $stmt->execute([$student_fee_id]);
                $installments = $stmt->fetchAll();
                
                foreach ($installments as $inst) {
                    if ($student_info && $student_info['guardian_id']) {
                        sendGuardianNotification(
                            $pdo,
                            $student_id,
                            $inst['id'],
                            'new_installment',
                            "قسط جديد مستحق - {$fee_type['fee_name']}",
                            "تم إضافة قسط جديد للطالب {$student_info['student_name']}. المبلغ: {$inst['amount']} - تاريخ الاستحقاق: {$inst['due_date']}"
                        );
                    }
                }
                
                $pdo->commit();
                
                // رسالة توضيحية
                $msg = "✅ تم تعيين الرسوم وإنشاء $created قسط بنجاح";
                if ($first_installment_amount > 0 && $installments_count > 1) {
                    $remaining = $total_amount - $first_installment_amount;
                    $per_installment = $remaining / ($installments_count - 1);
                    $msg .= " (القسط الأول: " . number_format($first_installment_amount, 2) . " - الباقي: " . number_format($per_installment, 2) . " × " . ($installments_count - 1) . ")";
                }
                
                $_SESSION['fee_msg'] = $msg;
                $_SESSION['fee_type_msg'] = 'success';
            } catch (PDOException $e) {
                $pdo->rollBack();
                $_SESSION['fee_msg'] = "⚠️ خطأ: " . $e->getMessage();
                $_SESSION['fee_type_msg'] = 'danger';
            }
        }
        redirect('?page=student_fees');
    }
    
    if ($a === 'assign_bulk_fees' && isDataEntry()) {
        $fee_type_id = (int)$_POST['fee_type_id'];
        $academic_year = trim($_POST['academic_year']);
        $semester = $_POST['semester'] ?? 'first';
        $start_date = $_POST['start_date'];
        $class_id = (int)($_POST['class_id'] ?? 0);
        
        // الحصول على تفاصيل نوع الرسوم
        $stmt = $pdo->prepare("SELECT * FROM fee_types WHERE id=?");
        $stmt->execute([$fee_type_id]);
        $fee_type = $stmt->fetch();
        
        if ($fee_type) {
            $sql = "SELECT id FROM students WHERE status='active' AND academic_year=?";
            $params = [$academic_year];
            
            if ($class_id) {
                $sql .= " AND class_id = ?";
                $params[] = $class_id;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $students = $stmt->fetchAll();
            
            $assigned = 0;
            foreach ($students as $student) {
                // التحقق من عدم وجود رسوم سابقة
                $stmt = $pdo->prepare("SELECT id FROM student_fees WHERE student_id=? AND fee_type_id=? AND academic_year=?");
                $stmt->execute([$student['id'], $fee_type_id, $academic_year]);
                if ($stmt->fetch()) continue;
                
                try {
                    $pdo->beginTransaction();
                    
                    $pdo->prepare("INSERT INTO student_fees(student_id, fee_type_id, academic_year, semester, total_amount, remaining_amount, status) VALUES(?,?,?,?,?,?,'pending')")
                        ->execute([$student['id'], $fee_type_id, $academic_year, $semester, $fee_type['total_amount'], $fee_type['total_amount']]);
                    
                    $student_fee_id = $pdo->lastInsertId();
                    createStudentInstallments($pdo, $student_fee_id, $fee_type['total_amount'], $fee_type['installments_count'], $start_date, $fee_type['first_installment_amount']);
                    
                    // إرسال إشعار
                    $stmt = $pdo->prepare("SELECT id FROM fee_installments WHERE student_fee_id = ? ORDER BY installment_number LIMIT 1");
                    $stmt->execute([$student_fee_id]);
                    $first_inst = $stmt->fetch();
                    
                    if ($first_inst) {
                        sendGuardianNotification(
                            $pdo,
                            $student['id'],
                            $first_inst['id'],
                            'new_installment',
                            "قسط جديد مستحق - {$fee_type['fee_name']}",
                            "تم إضافة أقساط جديدة. الرجاء مراجعة صفحة الأقساط للتفاصيل."
                        );
                    }
                    
                    $pdo->commit();
                    $assigned++;
                } catch (PDOException $e) {
                    $pdo->rollBack();
                }
            }
            
            $_SESSION['fee_msg'] = "✅ تم تعيين الرسوم لـ $assigned طالب";
            $_SESSION['fee_type_msg'] = 'success';
        }
        redirect('?page=student_fees');
    }

    /* ==================================================
       نظام الأقساط - تسجيل دفع قسط
       ================================================== */
    if ($a === 'pay_installment' && isAccountant()) {
        $installment_id = (int)$_POST['installment_id'];
        $amount = (float)$_POST['amount'];
        $payment_date = $_POST['payment_date'];
        $payment_method = $_POST['payment_method'] ?? 'cash';
        $notes = trim($_POST['notes'] ?? '');
        
        // الحصول على تفاصيل القسط
        $stmt = $pdo->prepare("SELECT fi.*, sf.student_id, sf.id AS student_fee_id, ft.fee_name, s.name AS student_name
            FROM fee_installments fi
            JOIN student_fees sf ON sf.id = fi.student_fee_id
            JOIN fee_types ft ON ft.id = sf.fee_type_id
            JOIN students s ON s.id = sf.student_id
            WHERE fi.id = ?");
        $stmt->execute([$installment_id]);
        $installment = $stmt->fetch();
        
        if ($installment) {
            try {
                $pdo->beginTransaction();
                
                $receipt_number = generateReceiptNumber($pdo);
                
                // تسجيل الدفع
                $pdo->prepare("INSERT INTO fee_payments(installment_id, student_fee_id, amount, payment_date, payment_method, receipt_number, received_by, notes) VALUES(?,?,?,?,?,?,?,?)")
                    ->execute([$installment_id, $installment['student_fee_id'], $amount, $payment_date, $payment_method, $receipt_number, $_SESSION['full_name'], $notes]);
                
                // تحديث حالة القسط
                $pdo->prepare("UPDATE fee_installments SET status='paid', paid_date=?, payment_method=?, receipt_number=?, confirmed_by=?, confirmed_at=NOW() WHERE id=?")
                    ->execute([$payment_date, $payment_method, $receipt_number, $_SESSION['full_name'], $installment_id]);
                
                // تحديث حالة الرسوم
                updateStudentFeeStatus($pdo, $installment['student_fee_id']);
                
                // إرسال إشعار لولي الأمر
                $stmt = $pdo->prepare("SELECT id FROM guardians WHERE student_id = ?");
                $stmt->execute([$installment['student_id']]);
                $guardian = $stmt->fetch();
                
                if ($guardian) {
                    sendGuardianNotification(
                        $pdo,
                        $installment['student_id'],
                        $installment_id,
                        'payment_confirmed',
                        "تم تأكيد الدفع - {$installment['fee_name']}",
                        "تم تأكيد دفع القسط رقم {$installment['installment_number']} بمبلغ {$amount}. رقم الإيصال: {$receipt_number}"
                    );
                }
                
                $pdo->commit();
                $_SESSION['fee_msg'] = "✅ تم تسجيل الدفع بنجاح. رقم الإيصال: $receipt_number";
                $_SESSION['fee_type_msg'] = 'success';
            } catch (PDOException $e) {
                $pdo->rollBack();
                $_SESSION['fee_msg'] = "⚠️ خطأ: " . $e->getMessage();
                $_SESSION['fee_type_msg'] = 'danger';
            }
        }
        redirect("?page=fee_installments&student_fee_id={$installment['student_fee_id']}");
    }

    if ($a === 'cancel_installment' && isAdmin()) {
        $installment_id = (int)$_POST['installment_id'];
        
        $stmt = $pdo->prepare("SELECT student_fee_id FROM fee_installments WHERE id=?");
        $stmt->execute([$installment_id]);
        $student_fee_id = $stmt->fetchColumn();
        
        $pdo->prepare("UPDATE fee_installments SET status='cancelled' WHERE id=?")->execute([$installment_id]);
        
        if ($student_fee_id) {
            updateStudentFeeStatus($pdo, $student_fee_id);
            redirect("?page=fee_installments&student_fee_id=$student_fee_id");
        }
        redirect('?page=student_fees');
    }

    /* ==================================================
       نظام الأقساط - معالجة إشعارات ولي الأمر
       ================================================== */
    if ($a === 'mark_notification_read' && isGuardian()) {
        $notification_id = (int)$_POST['notification_id'];
        $pdo->prepare("UPDATE fee_notifications SET is_read=1, read_at=NOW() WHERE id=? AND guardian_id=?")
            ->execute([$notification_id, $_SESSION['guardian_id']]);
        redirect('?page=guardian_notifications');
    }
    
    if ($a === 'mark_all_notifications_read' && isGuardian()) {
        $pdo->prepare("UPDATE fee_notifications SET is_read=1, read_at=NOW() WHERE guardian_id=? AND is_read=0")
            ->execute([$_SESSION['guardian_id']]);
        redirect('?page=guardian_notifications');
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>منظومة المدرسة النموذجية</title>
<style>
* { font-family: 'Segoe UI', Tahoma, Arial, sans-serif; box-sizing: border-box; margin: 0; padding: 0; }
body { background: #f4f6f9; min-height: 100vh; line-height: 1.6; color: #1e293b; }

.sidebar {
    position: fixed; top: 0; right: 0; width: 250px; height: 100vh;
    background: linear-gradient(180deg, #1e293b, #0f172a); color: #fff;
    padding: 20px 0; overflow-y: auto; z-index: 100;
    transition: right 0.3s; display: flex; flex-direction: column;
}
.sidebar .logo {
    text-align: center; font-size: 1.05rem; font-weight: bold;
    padding: 10px 15px 20px; border-bottom: 1px solid #334155;
    margin-bottom: 15px; color: #60a5fa;
}
.sidebar nav { flex: 1; }
.sidebar nav a {
    display: block; color: #cbd5e1; padding: 11px 20px; text-decoration: none;
    transition: 0.2s; border-right: 3px solid transparent; font-size: 0.9rem;
}
.sidebar nav a:hover, .sidebar nav a.active {
    background: #1e40af; color: #fff; border-right-color: #60a5fa;
}
.sidebar nav a .icon { display: inline-block; width: 22px; text-align: center; margin-left: 8px; }
.sidebar .section-title {
    padding: 10px 20px 5px; color: #64748b; font-size: 0.72rem;
    text-transform: uppercase; letter-spacing: 1px; font-weight: 600;
}
.sidebar .designer-credit {
    padding: 15px 20px; border-top: 1px solid #334155;
    text-align: center; font-size: 0.72rem; color: #94a3b8; line-height: 1.5;
}
.sidebar .designer-credit strong { color: #60a5fa; display: block; font-size: 0.78rem; }

.main-content { margin-right: 250px; min-height: 100vh; padding: 0 0 40px 0; }
.topbar {
    background: #fff; padding: 14px 25px; display: flex;
    justify-content: space-between; align-items: center;
    box-shadow: 0 2px 6px rgba(0,0,0,.06); position: sticky; top: 0; z-index: 50;
}
.content { padding: 25px; }

.stat-card {
    display: flex; align-items: center; gap: 15px; padding: 20px;
    border-radius: 15px; color: #fff; box-shadow: 0 4px 15px rgba(0,0,0,.1);
    transition: 0.3s;
}
.stat-card:hover { transform: translateY(-5px); }
.stat-card .icon { font-size: 2.3rem; opacity: 0.85; }
.stat-card h2 { margin: 0; font-size: 1.6rem; font-weight: 700; }
.stat-card span { font-size: 0.85rem; opacity: 0.9; }
.bg-grad-1 { background: linear-gradient(135deg, #667eea, #764ba2); }
.bg-grad-2 { background: linear-gradient(135deg, #f093fb, #f5576c); }
.bg-grad-3 { background: linear-gradient(135deg, #4facfe, #00f2fe); }
.bg-grad-4 { background: linear-gradient(135deg, #43e97b, #38f9d7); }
.bg-grad-5 { background: linear-gradient(135deg, #fa709a, #fee140); }
.bg-grad-6 { background: linear-gradient(135deg, #30cfd0, #330867); }
.bg-grad-7 { background: linear-gradient(135deg, #a8edea, #fed6e3); color: #1e293b !important; }
.bg-grad-8 { background: linear-gradient(135deg, #ffecd2, #fcb69f); color: #1e293b !important; }

.card { border: none; border-radius: 14px; box-shadow: 0 2px 10px rgba(0,0,0,.06); margin-bottom: 20px; background: #fff; }
.card-header { background: #fff; border-bottom: 1px solid #e5e7eb; font-weight: 700; padding: 15px 20px; border-radius: 14px 14px 0 0; }
.card-body { padding: 20px; }
.page-title { font-weight: 700; color: #1e293b; margin-bottom: 20px; font-size: 1.5rem; }
.page-title .icon { color: #3b82f6; margin-left: 10px; }

.table { width: 100%; border-collapse: collapse; }
.table th, .table td { padding: 12px 10px; text-align: right; border-bottom: 1px solid #e5e7eb; }
.table thead th { background: #f8fafc; color: #475569; font-weight: 600; border-bottom: 2px solid #e5e7eb; }
.table tbody tr:hover { background: #f8fafc; }
.table-bordered th, .table-bordered td { border: 1px solid #e5e7eb; }

.btn {
    display: inline-block; padding: 8px 16px; border: none; border-radius: 8px;
    cursor: pointer; font-size: 0.9rem; text-decoration: none;
    transition: 0.2s; text-align: center;
}
.btn:hover { opacity: 0.9; }
.btn-primary { background: #3b82f6; color: #fff; }
.btn-primary:hover { background: #2563eb; }
.btn-success { background: #10b981; color: #fff; }
.btn-danger { background: #ef4444; color: #fff; }
.btn-warning { background: #f59e0b; color: #fff; }
.btn-info { background: #06b6d4; color: #fff; }
.btn-secondary { background: #6b7280; color: #fff; }
.btn-outline-primary { background: transparent; border: 1px solid #3b82f6; color: #3b82f6; }
.btn-outline-secondary { background: transparent; border: 1px solid #6b7280; color: #6b7280; }
.btn-sm { padding: 5px 10px; font-size: 0.8rem; }
.btn-lg { padding: 12px 24px; font-size: 1rem; }
.btn-block { display: block; width: 100%; }

.form-control, .form-select {
    display: block; width: 100%; padding: 10px 12px;
    border: 1px solid #d1d5db; border-radius: 8px;
    font-size: 0.95rem; background: #fff; transition: 0.2s;
}
.form-control:focus, .form-select:focus {
    outline: none; border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59,130,246,.1);
}
.form-label { display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem; color: #374151; }
.form-check { display: inline-block; margin-left: 10px; margin-bottom: 5px; }
.form-check input { margin-left: 5px; }
textarea.form-control { resize: vertical; min-height: 60px; }

.row { display: flex; flex-wrap: wrap; margin: 0 -8px; }
.row.g-2 > * { padding: 0 8px; margin-bottom: 16px; }
.row.g-3 > * { padding: 0 10px; margin-bottom: 20px; }
.col-12 { flex: 0 0 100%; max-width: 100%; }
.col-md-2 { flex: 0 0 16.666%; max-width: 16.666%; }
.col-md-3 { flex: 0 0 25%; max-width: 25%; }
.col-md-4 { flex: 0 0 33.333%; max-width: 33.333%; }
.col-md-6 { flex: 0 0 50%; max-width: 50%; }
.col-md-8 { flex: 0 0 66.666%; max-width: 66.666%; }
.col-md-12 { flex: 0 0 100%; max-width: 100%; }
.col-sm-6 { flex: 0 0 50%; max-width: 50%; }
.col-lg-4 { flex: 0 0 33.333%; max-width: 33.333%; }

.alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 15px; border-right: 4px solid; }
.alert-info { background: #dbeafe; border-color: #3b82f6; color: #1e40af; }
.alert-warning { background: #fef3c7; border-color: #f59e0b; color: #92400e; }
.alert-danger { background: #fee2e2; border-color: #ef4444; color: #991b1b; }
.alert-success { background: #d1fae5; border-color: #10b981; color: #065f46; }

.badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 500; }
.bg-dark { background: #1e293b; color: #fff; }
.bg-primary { background: #3b82f6; color: #fff; }
.bg-success { background: #10b981; color: #fff; }
.bg-danger { background: #ef4444; color: #fff; }
.bg-warning { background: #f59e0b; color: #fff; }
.bg-info { background: #06b6d4; color: #fff; }
.bg-secondary { background: #6b7280; color: #fff; }
.bg-purple { background: #8b5cf6; color: #fff; }
.bg-orange { background: #f97316; color: #fff; }

.login-body {
    background: linear-gradient(135deg, #667eea, #764ba2);
    min-height: 100vh; display: flex; flex-direction: column;
    align-items: center; justify-content: center; padding: 20px;
}
.login-card {
    background: #fff; border-radius: 20px;
    box-shadow: 0 25px 60px rgba(0,0,0,.3);
    padding: 35px; max-width: 460px; width: 100%;
}
.login-card .logo-icon { text-align: center; margin-bottom: 15px; font-size: 3.5rem; }
.login-card h3 { text-align: center; margin-bottom: 5px; color: #1e293b; }
.login-card .subtitle { text-align: center; color: #6b7280; margin-bottom: 25px; }
.login-footer {
    text-align: center; margin-top: 25px; padding-top: 18px;
    border-top: 1px solid #e5e7eb; color: #6b7280;
    font-size: 0.82rem; line-height: 1.8;
}
.login-footer strong { color: #3b82f6; font-size: 0.9rem; }
.login-page-credit {
    color: rgba(255,255,255,.85); text-align: center;
    margin-top: 25px; font-size: 0.85rem;
    text-shadow: 0 1px 3px rgba(0,0,0,.3);
}
.login-page-credit strong { color: #fbbf24; font-size: 1rem; }

.tabs { display: flex; border-bottom: 2px solid #e5e7eb; margin-bottom: 20px; }
.tab-link {
    flex: 1; padding: 12px; background: none; border: none;
    cursor: pointer; font-size: 0.95rem; color: #6b7280;
    border-bottom: 3px solid transparent; margin-bottom: -2px;
    transition: 0.2s; text-decoration: none; text-align: center; display: block;
}
.tab-link.active { color: #3b82f6; border-bottom-color: #3b82f6; font-weight: 600; }
.tab-link.guardian-active { color: #10b981; border-bottom-color: #10b981; }
.tab-panel { display: none; }
.tab-panel.active { display: block; }

.modal-overlay {
    display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,.5); z-index: 1000; align-items: flex-start;
    justify-content: center; padding: 40px 20px; overflow-y: auto;
}
.modal-overlay.show { display: flex; }
.modal-box {
    background: #fff; border-radius: 14px; width: 100%; max-width: 600px;
    box-shadow: 0 20px 50px rgba(0,0,0,.3); animation: modalIn 0.25s;
}
.modal-box.modal-lg { max-width: 800px; }
@keyframes modalIn {
    from { transform: translateY(-30px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
.modal-header {
    padding: 15px 20px; border-bottom: 1px solid #e5e7eb;
    display: flex; justify-content: space-between; align-items: center;
}
.modal-header h5 { font-size: 1.1rem; }
.modal-close {
    background: none; border: none; font-size: 1.5rem;
    cursor: pointer; color: #6b7280; line-height: 1;
}
.modal-body { padding: 20px; }
.modal-footer {
    padding: 15px 20px; border-top: 1px solid #e5e7eb;
    display: flex; justify-content: flex-end; gap: 10px;
}

.code-input-bar {
    background: linear-gradient(135deg, #1e3a8a, #3b82f6);
    border-radius: 16px; padding: 25px; margin-bottom: 25px;
    box-shadow: 0 8px 25px rgba(59,130,246,.3);
}
.code-input-bar h4 {
    color: #fff; margin-bottom: 15px; font-size: 1.2rem;
    display: flex; align-items: center; gap: 10px;
}
.code-input-bar .input-group { display: flex; gap: 10px; }
.code-input-bar input {
    flex: 1; padding: 15px 20px; font-size: 1.2rem;
    border: 3px solid rgba(255,255,255,.3); border-radius: 12px;
    background: rgba(255,255,255,.95); text-align: center;
    font-weight: bold; letter-spacing: 2px; text-transform: uppercase;
    transition: 0.2s;
}
.code-input-bar input:focus {
    outline: none; border-color: #fbbf24;
    box-shadow: 0 0 0 4px rgba(251,191,36,.3);
}
.code-input-bar button {
    padding: 15px 30px; font-size: 1.1rem; border-radius: 12px;
    background: #fbbf24; color: #1e293b; font-weight: bold;
    border: none; cursor: pointer; transition: 0.2s; white-space: nowrap;
}
.code-input-bar button:hover { background: #f59e0b; transform: translateY(-2px); }
.code-input-bar .hint {
    color: rgba(255,255,255,.8); font-size: 0.85rem;
    margin-top: 12px; display: flex; align-items: center; gap: 8px;
}

.d-flex { display: flex; }
.justify-content-between { justify-content: space-between; }
.align-items-center { align-items: center; }
.align-items-end { align-items: flex-end; }
.text-center { text-align: center; }
.text-muted { color: #6b7280; }
.text-primary { color: #3b82f6; }
.text-success { color: #10b981; }
.text-danger { color: #ef4444; }
.text-warning { color: #f59e0b; }
.fw-bold { font-weight: 700; }
.mb-0 { margin-bottom: 0; }
.mb-1 { margin-bottom: 5px; }
.mb-2 { margin-bottom: 10px; }
.mb-3 { margin-bottom: 15px; }
.mb-4 { margin-bottom: 25px; }
.mt-2 { margin-top: 10px; }
.mt-3 { margin-top: 15px; }
.mt-4 { margin-top: 25px; }
.ms-2 { margin-right: 10px; }
.py-4 { padding: 25px 0; }
.small { font-size: 0.85rem; }
.table-responsive { overflow-x: auto; }
.list-group { list-style: none; }
.list-group-item {
    padding: 12px 15px; border: 1px solid #e5e7eb;
    border-radius: 8px; margin-bottom: 8px; background: #fff;
}
code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-size: 0.9em; color: #dc2626; }
.container { max-width: 1100px; margin: 0 auto; padding: 0 20px; }
.d-grid { display: grid; }
.gap-1 { gap: 5px; }
.gap-2 { gap: 10px; }
.flex-fill { flex: 1; }
.h-100 { height: 100%; }

.guardian-header {
    background: linear-gradient(135deg, #10b981, #059669);
    color: #fff; padding: 30px; border-radius: 16px;
    margin-bottom: 25px; box-shadow: 0 8px 25px rgba(16,185,129,.3);
}
.guardian-header h2 { margin-bottom: 5px; }
.guardian-header p { opacity: 0.9; }

.main-footer {
    text-align: center; padding: 20px; color: #6b7280;
    font-size: 0.85rem; border-top: 1px solid #e5e7eb; margin-top: 30px;
}
.main-footer strong { color: #3b82f6; }

@media print {
    .sidebar, .topbar, .no-print, .main-footer, .code-input-bar { display: none !important; }
    .main-content { margin: 0 !important; }
    .card { box-shadow: none !important; border: 1px solid #ddd !important; }
    body { background: #fff !important; }
    .content { padding: 0 !important; }
    .print-header { display: block !important; }
    .table th, .table td { border: 1px solid #333 !important; }
    .badge { border: 1px solid #333 !important; color: #000 !important; background: #fff !important; }
}

.print-header {
    display: none; text-align: center; margin-bottom: 20px;
    padding-bottom: 15px; border-bottom: 3px double #1e293b;
}
.print-header h2 { color: #1e293b; margin-bottom: 5px; }
.print-header h3 { color: #3b82f6; margin-bottom: 5px; }
.print-header p { color: #6b7280; font-size: 0.9rem; }

.report-filter {
    background: #f8fafc; border: 1px solid #e5e7eb;
    border-radius: 12px; padding: 20px; margin-bottom: 20px;
}

.leave-balance-card {
    text-align: center; padding: 20px; border-radius: 12px;
    border: 1px solid #e5e7eb; background: #fff;
}
.leave-balance-card .number { font-size: 2.5rem; font-weight: 700; }
.leave-balance-card .label { font-size: 0.9rem; color: #6b7280; }
.leave-balance-card.annual .number { color: #3b82f6; }
.leave-balance-card.sick .number { color: #ef4444; }
.leave-balance-card.emergency .number { color: #f59e0b; }
.leave-balance-card.unpaid .number { color: #6b7280; }

.level-card {
    background: #fff; border-radius: 14px; padding: 20px;
    border: 1px solid #e5e7eb; transition: 0.2s; position: relative;
    text-decoration: none; color: inherit; display: block;
}
.level-card:hover {
    transform: translateY(-4px); box-shadow: 0 8px 25px rgba(59,130,246,.15);
    border-color: #3b82f6;
}
.level-card .level-num {
    display: inline-block; width: 50px; height: 50px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #fff; border-radius: 12px; text-align: center;
    line-height: 50px; font-size: 1.5rem; font-weight: bold;
    margin-bottom: 12px;
}
.level-card h4 { margin-bottom: 5px; color: #1e293b; }
.level-card .stats { display: flex; gap: 15px; margin-top: 10px; font-size: 0.85rem; color: #6b7280; flex-wrap: wrap; }
.level-card .stats span { display: flex; align-items: center; gap: 4px; }

.tt-grid { width: 100%; border-collapse: collapse; }
.tt-grid th, .tt-grid td {
    border: 1px solid #cbd5e1; padding: 8px; text-align: center; vertical-align: middle;
}
.tt-grid thead th { background: #1e293b; color: #fff; font-size: 0.9rem; }
.tt-grid tbody td:first-child { background: #f1f5f9; font-weight: bold; width: 90px; }
.tt-cell {
    min-width: 130px; min-height: 60px; padding: 6px 4px;
    border-radius: 8px; background: #f8fafc;
}
.tt-cell .subject {
    display: block; font-weight: 700; color: #1e3a8a; font-size: 0.9rem;
    margin-bottom: 3px;
}
.tt-cell .teacher { display: block; color: #475569; font-size: 0.78rem; }
.tt-cell .class { display: block; color: #0891b2; font-size: 0.72rem; margin-top: 3px; }
.tt-empty { color: #cbd5e1; font-size: 1.5rem; }

.subject-card {
    background: #fff; border-radius: 12px; padding: 15px;
    border: 1px solid #e5e7eb; transition: 0.2s;
}
.subject-card:hover {
    border-color: #3b82f6; box-shadow: 0 4px 12px rgba(59,130,246,.12);
}
.subject-card .subject-name { font-weight: 700; color: #1e293b; font-size: 1.05rem; margin-bottom: 8px; }
.subject-card .subject-info { font-size: 0.85rem; color: #6b7280; line-height: 1.8; }

.grade-badge-local {
    display: inline-block; padding: 4px 12px; border-radius: 20px;
    font-size: 0.8rem; font-weight: 600;
}
.grade-badge-international {
    display: inline-block; padding: 4px 12px; border-radius: 20px;
    font-size: 0.9rem; font-weight: 700; min-width: 35px; text-align: center;
}
.subject-type-badge {
    display: inline-block; padding: 3px 10px; border-radius: 20px;
    font-size: 0.75rem; font-weight: 600;
}
.subject-type-local { background: #dbeafe; color: #1e40af; }
.subject-type-international { background: #ede9fe; color: #6d28d9; }

.grade-scale-table {
    width: 100%; border-collapse: collapse; margin-top: 10px;
}
.grade-scale-table th, .grade-scale-table td {
    border: 1px solid #e5e7eb; padding: 8px 12px; text-align: center;
}
.grade-scale-table th { background: #f8fafc; font-weight: 600; }

.section-card {
    background: #fff; border-radius: 14px; padding: 20px;
    border-right: 5px solid #10b981; box-shadow: 0 2px 10px rgba(0,0,0,.06);
    transition: 0.2s;
}
.section-card:hover {
    transform: translateY(-3px); box-shadow: 0 8px 20px rgba(16,185,129,.15);
}

/* ============================================================
   أنماط نظام الأقساط الجديدة
   ============================================================ */
.fee-card {
    background: #fff; border-radius: 14px; padding: 20px;
    border: 1px solid #e5e7eb; transition: 0.2s;
}
.fee-card:hover {
    transform: translateY(-3px); box-shadow: 0 8px 20px rgba(59,130,246,.12);
}
.fee-card .fee-title { font-weight: 700; color: #1e293b; font-size: 1.1rem; margin-bottom: 10px; }
.fee-card .fee-amount { font-size: 1.5rem; font-weight: 700; color: #3b82f6; }
.fee-card .fee-info { font-size: 0.85rem; color: #6b7280; margin-top: 8px; }

.installment-card {
    background: #fff; border-radius: 12px; padding: 15px;
    border-right: 4px solid #f59e0b; margin-bottom: 12px;
}
.installment-card.paid { border-right-color: #10b981; }
.installment-card.overdue { border-right-color: #ef4444; }
.installment-card.pending { border-right-color: #f59e0b; }

.notification-card {
    background: #fff; border-radius: 12px; padding: 15px;
    border-right: 4px solid #3b82f6; margin-bottom: 12px;
    transition: 0.2s;
}
.notification-card:hover { background: #f8fafc; }
.notification-card.unread { background: #eff6ff; border-right-color: #f59e0b; }
.notification-card .notif-title { font-weight: 700; color: #1e293b; margin-bottom: 5px; }
.notification-card .notif-message { color: #475569; font-size: 0.9rem; }
.notification-card .notif-date { color: #9ca3af; font-size: 0.8rem; margin-top: 8px; }

.payment-receipt {
    background: #fff; border: 2px dashed #10b981; border-radius: 12px;
    padding: 20px; margin-top: 15px;
}
.payment-receipt .receipt-header {
    text-align: center; border-bottom: 1px solid #e5e7eb;
    padding-bottom: 10px; margin-bottom: 15px;
}
.payment-receipt .receipt-number {
    font-size: 1.2rem; font-weight: 700; color: #10b981;
}

.fee-progress {
    height: 8px; background: #e5e7eb; border-radius: 4px;
    overflow: hidden; margin: 8px 0;
}
.fee-progress .bar {
    height: 100%; background: linear-gradient(90deg, #10b981, #34d399);
    border-radius: 4px; transition: width 0.3s;
}
.fee-progress .bar.overdue { background: linear-gradient(90deg, #ef4444, #f87171); }

.installment-preview {
    background: #f0fdf4; border: 1px solid #86efac; border-radius: 10px;
    padding: 15px; margin-top: 15px;
}
.installment-preview h6 { color: #166534; margin-bottom: 10px; }
.installment-preview .preview-item {
    display: flex; justify-content: space-between; padding: 5px 0;
    border-bottom: 1px dashed #d1fae5; font-size: 0.9rem;
}
.installment-preview .preview-item:last-child { border-bottom: none; }
.installment-preview .preview-item.first { font-weight: 700; color: #166534; }
.installment-preview .preview-item span:last-child { font-weight: 600; }

@media (max-width: 768px) {
    .sidebar { right: -250px; }
    .sidebar.open { right: 0; }
    .main-content { margin-right: 0; }
    .col-md-2, .col-md-3, .col-md-4, .col-md-6, .col-md-8, .col-lg-4 { flex: 0 0 100%; max-width: 100%; }
    .col-sm-6 { flex: 0 0 50%; max-width: 50%; }
    .sidebar-toggle { display: inline-block !important; }
    .code-input-bar .input-group { flex-direction: column; }
}
.sidebar-toggle {
    display: none; background: #f3f4f6; border: 1px solid #d1d5db;
    padding: 5px 10px; border-radius: 6px; cursor: pointer; margin-left: 10px;
}
</style>
</head>
<body>

<?php if ($page === 'login'): ?>

<div class="login-body">
  <div class="login-card">
    <div class="logo-icon">🏫</div>
    <h3>المدرسة النموذجية</h3>
    <p class="subtitle">نظام إدارة متكامل</p>

    <?php if (!empty($login_error)): ?>
      <div class="alert alert-danger">⚠️ <?= h($login_error) ?></div>
    <?php endif; ?>

    <div class="tabs">
      <a href="?page=login&tab=staff" class="tab-link <?= $tab === 'staff' ? 'active' : '' ?>">👨‍💼 دخول الموظفين</a>
      <a href="?page=login&tab=guardian" class="tab-link <?= $tab === 'guardian' ? 'guardian-active' : '' ?>">👪 دخول أولياء الأمور</a>
    </div>

    <div class="tab-panel <?= $tab === 'staff' ? 'active' : '' ?>">
      <form method="post" action="?page=login&tab=staff">
        <div class="mb-3">
          <label class="form-label">👤 اسم المستخدم</label>
          <input type="text" name="username" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">🔒 كلمة المرور</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <button class="btn btn-primary btn-lg btn-block">🚪 تسجيل الدخول</button>
      </form>
    </div>

    <div class="tab-panel <?= $tab === 'guardian' ? 'active' : '' ?>">
      <form method="post" action="?page=login&tab=guardian">
        <input type="hidden" name="guardian_login" value="1">
        <div class="mb-3">
          <label class="form-label">📞 رقم الهاتف</label>
          <input type="text" name="phone" class="form-control" required placeholder="مثال: 0912345678" autocomplete="off">
        </div>
        <div class="mb-3">
          <label class="form-label">🔑 الكود المرجعي</label>
          <input type="text" name="ref_code" class="form-control" required placeholder="مثال: G10001" autocomplete="off" style="text-transform: uppercase;">
        </div>
        <button class="btn btn-success btn-lg btn-block">🚪 دخول ولي الأمر</button>
      </form>
      <div class="alert alert-info mt-3 mb-0 small">
        ℹ️ للحصول على بيانات الدخول (رقم الهاتف والكود المرجعي)، يرجى التواصل مع إدارة المدرسة.
      </div>
    </div>

    <div class="login-footer">
      🎨 تصميم وتطوير<br>
      <strong>م. عبدالرحيم غيث الطاهر</strong>
    </div>
  </div>

  <div class="login-page-credit">
    منظومة المدرسة النموذجية &copy; <?= date('Y') ?> — <strong>م. عبدالرحيم غيث الطاهر</strong>
  </div>
</div>

<?php elseif (isGuardian()): ?>

<div class="container py-4">
  <?php
  $sid = $_SESSION['guardian_student_id'];
  $stmt = $pdo->prepare("SELECT s.*, c.class_name, c.section FROM students s LEFT JOIN classes c ON c.id=s.class_id WHERE s.id=?");
  $stmt->execute([$sid]);
  $student = $stmt->fetch();

  if (!$student) {
      echo '<div class="alert alert-danger">⚠️ لا يوجد طالب مرتبط بحسابك. تواصل مع إدارة المدرسة.</div>';
      echo '<a href="?page=logout" class="btn btn-danger">🚪 خروج</a>';
      exit;
  }

  $stmt = $pdo->prepare("SELECT DISTINCT academic_year FROM grades WHERE student_id=? AND academic_year IS NOT NULL ORDER BY academic_year DESC");
  $stmt->execute([$sid]);
  $student_years = $stmt->fetchAll(PDO::FETCH_COLUMN);

  $selected_year = $_GET['year'] ?? ($student_years[0] ?? $student['academic_year']);
  $selected_semester = $_GET['semester'] ?? 'all';

  $stmt = $pdo->prepare("SELECT g.*, sub.subject_name, sub.max_grade, sub.subject_type 
      FROM grades g JOIN subjects sub ON sub.id=g.subject_id 
      WHERE g.student_id=? AND g.academic_year=?" . ($selected_semester !== 'all' ? " AND g.semester=?" : "") . "
      ORDER BY sub.subject_name");
  if ($selected_semester !== 'all') {
      $stmt->execute([$sid, $selected_year, $selected_semester]);
  } else {
      $stmt->execute([$sid, $selected_year]);
  }
  $grades = $stmt->fetchAll();

  $stmt = $pdo->prepare("SELECT * FROM notes WHERE person_type='student' AND person_id=? ORDER BY note_date DESC");
  $stmt->execute([$sid]);
  $notes = $stmt->fetchAll();

  $avg = 0;
  if (count($grades)) {
      $sum = 0; $cnt = 0;
      foreach($grades as $g){ if($g['max_grade']>0){ $sum += ($g['total']/$g['max_grade'])*100; $cnt++; } }
      $avg = $cnt ? $sum/$cnt : 0;
  }
  
  // الحصول على الأقساط والإشعارات
  $student_fees = getStudentFees($pdo, $sid);
  $notifications = getGuardianNotifications($pdo, $_SESSION['guardian_id']);
  $unread_count = 0;
  foreach ($notifications as $n) {
      if (!$n['is_read']) $unread_count++;
  }
  
  // حساب إجمالي المتبقي
  $total_remaining = 0;
  foreach ($student_fees as $fee) {
      $total_remaining += $fee['remaining_amount'];
  }
  ?>

  <div class="guardian-header">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <h2>👪 مرحباً، <?= h($_SESSION['guardian_name']) ?></h2>
        <p>مرحباً بك في بوابة أولياء الأمور - المدرسة النموذجية</p>
      </div>
      <div class="text-center">
        <?php if ($unread_count > 0): ?>
          <a href="?page=guardian_notifications" class="btn btn-lg" style="background:#fff;color:#059669;font-weight:bold;position:relative;">
            🔔 الإشعارات
            <span class="badge bg-danger" style="position:absolute;top:-5px;left:-5px;"><?= $unread_count ?></span>
          </a>
        <?php else: ?>
          <a href="?page=guardian_notifications" class="btn btn-lg" style="background:#fff;color:#059669;font-weight:bold;">🔔 الإشعارات</a>
        <?php endif; ?>
        <a href="?page=logout" class="btn btn-lg ms-2" style="background:#fff;color:#059669;font-weight:bold;">🚪 خروج</a>
      </div>
    </div>
  </div>

  <!-- ملخص الأقساط -->
  <?php if ($total_remaining > 0): ?>
  <div class="alert alert-warning">
    <strong>⚠️ تنبيه:</strong> يوجد مبلغ متبقي من الأقساط بقيمة <strong><?= number_format($total_remaining, 2) ?></strong>. يرجى مراجعة قسم الأقساط أدناه.
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header">🎓 بيانات الطالب: <?= h($student['name']) ?></div>
    <div class="card-body">
      <div class="row mb-3">
        <div class="col-md-4"><strong>كود الطالب:</strong> <span class="badge bg-dark"><?= h($student['student_code']) ?></span></div>
        <div class="col-md-4"><strong>الصف:</strong> <?= h($student['class_name'] ?? '-') ?></div>
        <div class="col-md-4"><strong>السنة الدراسية:</strong> <?= h($student['academic_year']) ?></div>
        <div class="col-md-4"><strong>الفصل:</strong> <?= $student['semester']=='first'?'الأول':'الثاني' ?></div>
        <div class="col-md-4"><strong>الجنس:</strong> <?= $student['gender']=='male'?'ذكر':'أنثى' ?></div>
        <div class="col-md-4"><strong>الحالة:</strong> <span class="badge bg-success"><?= $student['status'] ?? 'نشط' ?></span></div>
      </div>

      <!-- قسم الأقساط -->
      <?php if ($student_fees): ?>
      <h5 class="mt-4">💰 الأقساط المالية</h5>
      <div class="row g-3 mb-4">
        <?php foreach ($student_fees as $fee): 
          $progress = $fee['total_amount'] > 0 ? ($fee['paid_amount'] / $fee['total_amount']) * 100 : 0;
          $status_map = [
              'pending' => ['bg-warning', 'معلق'],
              'partial' => ['bg-info', 'جزئي'],
              'paid' => ['bg-success', 'مدفوع'],
              'overdue' => ['bg-danger', 'متأخر']
          ];
          $st = $status_map[$fee['status']] ?? ['bg-secondary', $fee['status']];
        ?>
        <div class="col-md-6">
          <div class="fee-card">
            <div class="d-flex justify-content-between align-items-center">
              <div class="fee-title"><?= h($fee['fee_name']) ?></div>
              <span class="badge <?= $st[0] ?>"><?= $st[1] ?></span>
            </div>
            <div class="fee-amount"><?= number_format($fee['total_amount'], 2) ?></div>
            <div class="fee-info">
              <div>المدفوع: <?= number_format($fee['paid_amount'], 2) ?></div>
              <div>المتبقي: <?= number_format($fee['remaining_amount'], 2) ?></div>
              <div>الأقساط: <?= $fee['paid_installments'] ?> / <?= $fee['total_installments'] ?></div>
            </div>
            <div class="fee-progress">
              <div class="bar <?= $fee['status'] === 'overdue' ? 'overdue' : '' ?>" style="width: <?= $progress ?>%"></div>
            </div>
            <a href="?page=guardian_fee_details&student_fee_id=<?= $fee['id'] ?>" class="btn btn-sm btn-primary mt-2">📋 عرض التفاصيل</a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <h5 class="mt-4">📋 الدرجات - اختر السنة الدراسية</h5>
      <div class="report-filter">
        <form method="get" class="row g-2 align-items-end">
          <input type="hidden" name="page" value="guardian_report">
          <div class="col-md-4">
            <label class="form-label">السنة الدراسية</label>
            <select name="year" class="form-select">
              <?php foreach($student_years as $y): ?>
                <option value="<?= h($y) ?>" <?= $selected_year==$y?'selected':'' ?>><?= h($y) ?></option>
              <?php endforeach; ?>
              <?php if (empty($student_years)): ?>
                <option value="<?= h($student['academic_year']) ?>"><?= h($student['academic_year']) ?></option>
              <?php endif; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">الفصل</label>
            <select name="semester" class="form-select">
              <option value="all" <?= $selected_semester=='all'?'selected':'' ?>>الكل</option>
              <option value="first" <?= $selected_semester=='first'?'selected':'' ?>>الأول</option>
              <option value="second" <?= $selected_semester=='second'?'selected':'' ?>>الثاني</option>
            </select>
          </div>
          <div class="col-md-4">
            <button class="btn btn-primary btn-block">🔍 عرض</button>
          </div>
        </form>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered">
          <thead>
            <tr><th>المادة</th><th>النوع</th><th>الفصل</th><th>أعمال السنة</th><th>الامتحان النهائي</th><th>المجموع</th><th>من</th><th>النسبة</th><th>التقدير</th></tr>
          </thead>
          <tbody>
            <?php if (!$grades): ?><tr><td colspan="9" class="text-center text-muted">لا توجد درجات لهذه السنة</td></tr><?php endif; ?>
            <?php foreach($grades as $g):
              $pct = $g['max_grade']>0 ? ($g['total']/$g['max_grade'])*100 : 0;
              $subject_type = $g['subject_type'] ?? 'local';
              $evaluation = getGradeEvaluation($pct, $subject_type);
            ?>
              <tr>
                <td><?= h($g['subject_name']) ?></td>
                <td>
                  <span class="subject-type-badge <?= $subject_type === 'international' ? 'subject-type-international' : 'subject-type-local' ?>">
                    <?= getSubjectTypeName($subject_type) ?>
                  </span>
                </td>
                <td><?= $g['semester']=='first'?'الأول':'الثاني' ?></td>
                <td><?= number_format($g['coursework'],2) ?></td>
                <td><?= number_format($g['final_exam'],2) ?></td>
                <td><strong><?= number_format($g['total'],2) ?></strong></td>
                <td><?= $g['max_grade'] ?></td>
                <td><?= number_format($pct,2) ?>%</td>
                <td>
                  <span class="badge <?= $evaluation['color'] ?>">
                    <?= $evaluation['grade'] ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr><th colspan="5" style="text-align:left">المعدل العام:</th><th colspan="4"><?= number_format($avg,2) ?>%</th></tr>
          </tfoot>
        </table>
      </div>

      <div class="alert alert-info mt-3">
        <strong>النتيجة النهائية:</strong>
        <?php if ($avg>=90): ?> <span class="text-success">ممتاز</span>
        <?php elseif ($avg>=80): ?> <span class="text-success">جيد جداً</span>
        <?php elseif ($avg>=70): ?> <span class="text-primary">جيد</span>
        <?php elseif ($avg>=60): ?> <span class="text-warning">مقبول</span>
        <?php elseif ($avg>=50): ?> <span class="text-warning">ضعيف</span>
        <?php else: ?> <span class="text-danger">راسب</span>
        <?php endif; ?>
      </div>

      <h5 class="mt-4">💬 الملاحظات والسلوك</h5>
      <?php if (!$notes): ?>
        <p class="text-muted">لا توجد ملاحظات.</p>
      <?php else: ?>
        <ul class="list-group">
          <?php foreach($notes as $n): ?>
            <li class="list-group-item">
              <small class="text-muted"><?= h($n['note_date']) ?> - بواسطة: <?= h($n['created_by']) ?></small><br>
              <?= h($n['note']) ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>

  <div class="main-footer">
    🎨 تصميم وتطوير: <strong>م. عبدالرحيم غيث الطاهر</strong>
  </div>
</div>

<?php
/* ============================================================
   صفحة إشعارات ولي الأمر
   ============================================================ */
elseif ($page === 'guardian_notifications' && isGuardian()):
    $notifications = getGuardianNotifications($pdo, $_SESSION['guardian_id']);
    $unread_count = 0;
    foreach ($notifications as $n) {
        if (!$n['is_read']) $unread_count++;
    }
?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="page-title mb-0"><span class="icon">🔔</span> الإشعارات</h3>
        <div>
            <?php if ($unread_count > 0): ?>
            <form method="post" style="display:inline">
                <input type="hidden" name="action" value="mark_all_notifications_read">
                <button class="btn btn-success">✅ تعليم الكل كمقروء</button>
            </form>
            <?php endif; ?>
            <a href="?page=guardian_report" class="btn btn-primary">← رجوع للبوابة</a>
        </div>
    </div>

    <?php if (!$notifications): ?>
        <div class="alert alert-info">لا توجد إشعارات حالياً.</div>
    <?php else: ?>
        <?php foreach ($notifications as $n): ?>
        <div class="notification-card <?= !$n['is_read'] ? 'unread' : '' ?>">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="notif-title">
                        <?= !$n['is_read'] ? '🔵 ' : '⚪ ' ?>
                        <?= h($n['title']) ?>
                    </div>
                    <div class="notif-message"><?= h($n['message']) ?></div>
                    <div class="notif-date">
                        📅 <?= h($n['created_at']) ?>
                        <?php if ($n['fee_name']): ?>
                            | <?= h($n['fee_name']) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (!$n['is_read']): ?>
                <form method="post" style="display:inline">
                    <input type="hidden" name="action" value="mark_notification_read">
                    <input type="hidden" name="notification_id" value="<?= $n['id'] ?>">
                    <button class="btn btn-sm btn-outline-primary">✔️ مقروء</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="main-footer">
        🎨 تصميم وتطوير: <strong>م. عبدالرحيم غيث الطاهر</strong>
    </div>
</div>

<?php
/* ============================================================
   صفحة تفاصيل أقساط ولي الأمر
   ============================================================ */
elseif ($page === 'guardian_fee_details' && isGuardian()):
    $student_fee_id = (int)($_GET['student_fee_id'] ?? 0);
    
    // التحقق من ملكية الرسوم
    $stmt = $pdo->prepare("SELECT sf.*, ft.fee_name, ft.fee_description, s.name AS student_name
        FROM student_fees sf
        JOIN fee_types ft ON ft.id = sf.fee_type_id
        JOIN students s ON s.id = sf.student_id
        WHERE sf.id = ? AND sf.student_id = ?");
    $stmt->execute([$student_fee_id, $_SESSION['guardian_student_id']]);
    $student_fee = $stmt->fetch();
    
    if (!$student_fee) {
        echo '<div class="alert alert-danger">⚠️ الرسوم غير موجودة أو غير مصرح لك بعرضها.</div>';
        echo '<a href="?page=guardian_report" class="btn btn-primary">← رجوع</a>';
        exit;
    }
    
    $installments = getFeeInstallments($pdo, $student_fee_id);
    $payments = $pdo->prepare("SELECT * FROM fee_payments WHERE student_fee_id = ? ORDER BY payment_date DESC");
    $payments->execute([$student_fee_id]);
    $payments_list = $payments->fetchAll();
?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="page-title mb-0"><span class="icon">💰</span> تفاصيل الأقساط</h3>
        <a href="?page=guardian_report" class="btn btn-secondary">← رجوع للبوابة</a>
    </div>

    <div class="card mb-4">
        <div class="card-header">📋 معلومات الرسوم</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4"><strong>نوع الرسوم:</strong> <?= h($student_fee['fee_name']) ?></div>
                <div class="col-md-4"><strong>الطالب:</strong> <?= h($student_fee['student_name']) ?></div>
                <div class="col-md-4"><strong>السنة الدراسية:</strong> <?= h($student_fee['academic_year']) ?></div>
                <div class="col-md-4"><strong>إجمالي المبلغ:</strong> <?= number_format($student_fee['total_amount'], 2) ?></div>
                <div class="col-md-4"><strong>المدفوع:</strong> <span class="text-success"><?= number_format($student_fee['paid_amount'], 2) ?></span></div>
                <div class="col-md-4"><strong>المتبقي:</strong> <span class="text-danger"><?= number_format($student_fee['remaining_amount'], 2) ?></span></div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">📅 الأقساط</div>
        <div class="card-body">
            <?php foreach ($installments as $inst): 
                $status_map = [
                    'pending' => ['bg-warning', 'معلق', 'installment-card pending'],
                    'paid' => ['bg-success', 'مدفوع', 'installment-card paid'],
                    'overdue' => ['bg-danger', 'متأخر', 'installment-card overdue'],
                    'cancelled' => ['bg-secondary', 'ملغي', 'installment-card']
                ];
                $st = $status_map[$inst['status']] ?? ['bg-secondary', $inst['status'], 'installment-card'];
            ?>
            <div class="<?= $st[2] ?>">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>القسط رقم <?= $inst['installment_number'] ?></strong>
                        <span class="badge <?= $st[0] ?> ms-2"><?= $st[1] ?></span>
                    </div>
                    <div class="fw-bold"><?= number_format($inst['amount'], 2) ?></div>
                </div>
                <div class="small text-muted mt-2">
                    📅 تاريخ الاستحقاق: <?= h($inst['due_date']) ?>
                    <?php if ($inst['paid_date']): ?>
                        | ✅ تاريخ الدفع: <?= h($inst['paid_date']) ?>
                    <?php endif; ?>
                    <?php if ($inst['receipt_number']): ?>
                        | 🧾 رقم الإيصال: <?= h($inst['receipt_number']) ?>
                    <?php endif; ?>
                </div>
                <?php if ($inst['status'] === 'pending' || $inst['status'] === 'overdue'): ?>
                    <div class="mt-2">
                        <a href="?page=guardian_fee_details&student_fee_id=<?= $student_fee_id ?>&pay_installment=<?= $inst['id'] ?>" class="btn btn-sm btn-success">💳 دفع القسط</a>
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($payments_list): ?>
    <div class="card">
        <div class="card-header">🧾 سجل المدفوعات</div>
        <div class="card-body table-responsive">
            <table class="table">
                <thead>
                    <tr><th>#</th><th>المبلغ</th><th>تاريخ الدفع</th><th>طريقة الدفع</th><th>رقم الإيصال</th><th>ملاحظات</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($payments_list as $i => $p): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= number_format($p['amount'], 2) ?></td>
                        <td><?= h($p['payment_date']) ?></td>
                        <td>
                            <?php 
                            $methods = ['cash'=>'نقدي','bank_transfer'=>'تحويل بنكي','check'=>'شيك','online'=>'دفع إلكتروني'];
                            echo $methods[$p['payment_method']] ?? $p['payment_method'];
                            ?>
                        </td>
                        <td><span class="badge bg-dark"><?= h($p['receipt_number']) ?></span></td>
                        <td><?= h($p['notes']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="main-footer">
        🎨 تصميم وتطوير: <strong>م. عبدالرحيم غيث الطاهر</strong>
    </div>
</div>

<?php else: ?>

<aside class="sidebar" id="sidebar">
  <div class="logo">🏫 المدرسة النموذجية</div>
  <nav>
    <a href="?page=dashboard" class="<?= $page=='dashboard'?'active':'' ?>"><span class="icon">📊</span> لوحة التحكم</a>
    <?php if (isDataEntry()): ?>
    <a href="?page=teachers" class="<?= $page=='teachers'?'active':'' ?>"><span class="icon">👨‍🏫</span> المدرسون</a>
    <a href="?page=employees" class="<?= $page=='employees'?'active':'' ?>"><span class="icon">👔</span> الموظفون</a>
    <?php endif; ?>
    <?php if (isAccountant()): ?>
    <a href="?page=salaries" class="<?= $page=='salaries'?'active':'' ?>"><span class="icon">💰</span> الرواتب</a>
    <?php endif; ?>
    <?php if (isSupervisor()): ?>
    <a href="?page=attendance" class="<?= $page=='attendance'?'active':'' ?>"><span class="icon">⏰</span> الحضور والانصراف</a>
    <a href="?page=attendance_report" class="<?= $page=='attendance_report'?'active':'' ?>"><span class="icon">📊</span> تقرير الحضور</a>
    <a href="?page=employee_attendance_report" class="<?= $page=='employee_attendance_report'?'active':'' ?>"><span class="icon">📋</span> تقرير موظف</a>
    <a href="?page=leaves" class="<?= $page=='leaves'?'active':'' ?>"><span class="icon">🏖️</span> الإجازات</a>
    <a href="?page=holidays" class="<?= $page=='holidays'?'active':'' ?>"><span class="icon">📅</span> العطلات الرسمية</a>
    <?php endif; ?>

    <?php if (isDataEntry()): ?>
    <div class="section-title">📚 الأكاديمية</div>
    <a href="?page=grade_levels" class="<?= $page=='grade_levels'?'active':'' ?>"><span class="icon">🎯</span> السنوات الدراسية</a>
    <a href="?page=classes" class="<?= in_array($page, ['classes','class_sections'])?'active':'' ?>"><span class="icon">🏛️</span> الفصول والشعب</a>
    <a href="?page=subjects" class="<?= $page=='subjects'?'active':'' ?>"><span class="icon">📖</span> المواد الدراسية</a>
    <a href="?page=timetables" class="<?= $page=='timetables'?'active':'' ?>"><span class="icon">📅</span> الجدول الدراسي</a>
    <a href="?page=promote" class="<?= $page=='promote'?'active':'' ?>"><span class="icon">⬆️</span> ترقية الطلبة</a>

    <div class="section-title">👥 الأفراد</div>
    <a href="?page=students" class="<?= $page=='students'?'active':'' ?>"><span class="icon">🎓</span> الطلبة</a>
    <a href="?page=grades" class="<?= $page=='grades'?'active':'' ?>"><span class="icon">📋</span> الدرجات</a>
    <a href="?page=student_report" class="<?= $page=='student_report'?'active':'' ?>"><span class="icon">📄</span> كشف الدرجات</a>
    <a href="?page=guardians" class="<?= $page=='guardians'?'active':'' ?>"><span class="icon">👪</span> أولياء الأمور</a>
    <a href="?page=notes" class="<?= $page=='notes'?'active':'' ?>"><span class="icon">💬</span> الملاحظات</a>
    <a href="?page=reports" class="<?= $page=='reports'?'active':'' ?>"><span class="icon">📈</span> تقارير الطلبة</a>
    <?php endif; ?>

    <?php if (isAccountant()): ?>
    <div class="section-title">💰 المالية والأقساط</div>
    <a href="?page=fee_types" class="<?= $page=='fee_types'?'active':'' ?>"><span class="icon">📋</span> أنواع الرسوم</a>
    <a href="?page=student_fees" class="<?= $page=='student_fees'?'active':'' ?>"><span class="icon">💳</span> أقساط الطلبة</a>
    <a href="?page=fee_installments" class="<?= $page=='fee_installments'?'active':'' ?>"><span class="icon">📅</span> الأقساط المستحقة</a>
    <a href="?page=fee_report" class="<?= $page=='fee_report'?'active':'' ?>"><span class="icon">📊</span> تقرير الأقساط</a>
    <?php endif; ?>

    <?php if (isAdmin()): ?>
    <div class="section-title">⚙️ النظام</div>
    <a href="?page=users" class="<?= $page=='users'?'active':'' ?>"><span class="icon">⚙️</span> المستخدمون</a>
    <a href="?page=settings" class="<?= $page=='settings'?'active':'' ?>"><span class="icon">🔧</span> الإعدادات</a>
    <?php endif; ?>
    <a href="?page=logout" style="color:#f87171"><span class="icon">🚪</span> تسجيل الخروج</a>
  </nav>
  <div class="designer-credit">
    🎨 تصميم وتطوير<br>
    <strong>م. عبدالرحيم غيث الطاهر</strong>
  </div>
</aside>

<div class="main-content">
  <div class="topbar">
    <div>
      <button class="sidebar-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">☰</button>
      <strong class="ms-2">👤 مرحباً، <?= h($_SESSION['full_name']) ?></strong>
      <?php
      $roleLabels = ['admin'=>'مدير عام','accountant'=>'محاسب','data_entry'=>'مدخل بيانات','supervisor'=>'مشرف'];
      ?>
      <span class="badge bg-primary ms-2"><?= $roleLabels[$_SESSION['role']] ?? $_SESSION['role'] ?></span>
    </div>
    <div class="text-muted small">📅 <?= date('Y-m-d') ?></div>
  </div>

  <div class="content">
  <?php

  /* ------------ لوحة التحكم ------------ */
  if ($page === 'dashboard'):
      $teachers  = $pdo->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
      $employees = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
      $students  = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
      $today     = date('Y-m-d');
      $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE att_date=? AND status='present'");
      $stmt->execute([$today]);
      $presentToday = $stmt->fetchColumn();
      $totalSalaries = $pdo->query("SELECT COALESCE(SUM(net_salary),0) FROM salaries WHERE status='paid'")->fetchColumn();
      $holiday = isHoliday($pdo, $today);
      $guardiansCount = $pdo->query("SELECT COUNT(*) FROM guardians")->fetchColumn();
      $pendingLeaves = $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status='pending'")->fetchColumn();
      $gradeLevelsCount = $pdo->query("SELECT COUNT(*) FROM grade_levels")->fetchColumn();
      $classesCount = $pdo->query("SELECT COUNT(*) FROM classes")->fetchColumn();
      $subjectsCount = $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
      
      // إحصائيات الأقساط
      $totalFees = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM student_fees")->fetchColumn();
      $totalPaidFees = $pdo->query("SELECT COALESCE(SUM(paid_amount),0) FROM student_fees")->fetchColumn();
      $totalRemainingFees = $pdo->query("SELECT COALESCE(SUM(remaining_amount),0) FROM student_fees")->fetchColumn();
      $overdueInstallments = $pdo->query("SELECT COUNT(*) FROM fee_installments WHERE status='pending' AND due_date < CURDATE()")->fetchColumn();
  ?>
    <h3 class="page-title"><span class="icon">📊</span> لوحة التحكم</h3>

    <?php if ($holiday): ?>
      <div class="alert alert-info">📅 اليوم عطلة رسمية: <strong><?= h($holiday['holiday_name']) ?></strong></div>
    <?php endif; ?>

    <?php if ($pendingLeaves > 0 && isSupervisor()): ?>
      <div class="alert alert-warning">🏖️ يوجد <strong><?= $pendingLeaves ?></strong> طلب إجازة بانتظار الموافقة. <a href="?page=leaves">عرض الطلبات</a></div>
    <?php endif; ?>
    
    <?php if ($overdueInstallments > 0 && isAccountant()): ?>
      <div class="alert alert-danger">💰 يوجد <strong><?= $overdueInstallments ?></strong> قسط متأخر. <a href="?page=fee_installments&filter=overdue">عرض الأقساط المتأخرة</a></div>
    <?php endif; ?>

    <div class="row g-3 mb-3">
      <div class="col-md-3 col-sm-6">
        <div class="stat-card bg-grad-1">
          <span class="icon">👨‍🏫</span>
          <div><h2><?= $teachers ?></h2><span>المدرسون</span></div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="stat-card bg-grad-2">
          <span class="icon">👔</span>
          <div><h2><?= $employees ?></h2><span>الموظفون</span></div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="stat-card bg-grad-3">
          <span class="icon">🎓</span>
          <div><h2><?= $students ?></h2><span>الطلبة</span></div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="stat-card bg-grad-4">
          <span class="icon">⏰</span>
          <div><h2><?= $presentToday ?></h2><span>الحاضرون اليوم</span></div>
        </div>
      </div>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-md-3 col-sm-6">
        <div class="stat-card bg-grad-6">
          <span class="icon">💰</span>
          <div><h2><?= number_format($totalSalaries,2) ?></h2><span>الرواتب المدفوعة</span></div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="stat-card bg-grad-5">
          <span class="icon">👪</span>
          <div><h2><?= $guardiansCount ?></h2><span>أولياء الأمور</span></div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="stat-card bg-grad-7">
          <span class="icon">🏖️</span>
          <div><h2><?= $pendingLeaves ?></h2><span>إجازات معلقة</span></div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="stat-card bg-grad-8">
          <span class="icon">📖</span>
          <div><h2><?= $subjectsCount ?></h2><span>المواد الدراسية</span></div>
        </div>
      </div>
    </div>
    
    <?php if (isAccountant()): ?>
    <div class="row g-3 mb-4">
      <div class="col-md-3 col-sm-6">
        <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669);">
          <span class="icon">💵</span>
          <div><h2><?= number_format($totalFees, 2) ?></h2><span>إجمالي الرسوم</span></div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="stat-card" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);">
          <span class="icon">✅</span>
          <div><h2><?= number_format($totalPaidFees, 2) ?></h2><span>المدفوع من الرسوم</span></div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
          <span class="icon">⏳</span>
          <div><h2><?= number_format($totalRemainingFees, 2) ?></h2><span>المتبقي من الرسوم</span></div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="stat-card" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
          <span class="icon">⚠️</span>
          <div><h2><?= $overdueInstallments ?></h2><span>أقساط متأخرة</span></div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <div class="card mt-4">
      <div class="card-header">📊 نظام التقييم المعتمد</div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <h6 class="fw-bold text-primary">🇱🇾 المواد المحلية</h6>
            <table class="grade-scale-table">
              <thead>
                <tr><th>النسبة</th><th>التقدير</th></tr>
              </thead>
              <tbody>
                <tr><td>90 - 100%</td><td><span class="badge bg-success">ممتاز</span></td></tr>
                <tr><td>80 - 89%</td><td><span class="badge bg-success">جيد جداً</span></td></tr>
                <tr><td>70 - 79%</td><td><span class="badge bg-info">جيد</span></td></tr>
                <tr><td>60 - 69%</td><td><span class="badge bg-warning">مقبول</span></td></tr>
                <tr><td>50 - 59%</td><td><span class="badge bg-warning">ضعيف</span></td></tr>
                <tr><td>أقل من 50%</td><td><span class="badge bg-danger">راسب</span></td></tr>
              </tbody>
            </table>
          </div>
          <div class="col-md-6">
            <h6 class="fw-bold" style="color:#8b5cf6;">🌍 المواد الدولية</h6>
            <table class="grade-scale-table">
              <thead>
                <tr><th>النسبة</th><th>التقدير</th></tr>
              </thead>
              <tbody>
                <tr><td>90 - 100%</td><td><span class="badge bg-success">A</span></td></tr>
                <tr><td>80 - 89%</td><td><span class="badge bg-success">B</span></td></tr>
                <tr><td>70 - 79%</td><td><span class="badge bg-info">C</span></td></tr>
                <tr><td>60 - 69%</td><td><span class="badge bg-warning">D</span></td></tr>
                <tr><td>50 - 59%</td><td><span class="badge bg-warning">E</span></td></tr>
                <tr><td>أقل من 50%</td><td><span class="badge bg-danger">F</span></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  <?php
  /* ============================================================
     أنواع الرسوم - إدارة أنواع الرسوم الدراسية
     ============================================================ */
  elseif ($page === 'fee_types' && isAccountant()):
      $fee_msg = $_SESSION['fee_msg'] ?? '';
      $fee_type_msg = $_SESSION['fee_type_msg'] ?? 'info';
      unset($_SESSION['fee_msg'], $_SESSION['fee_type_msg']);
      
      $grade_levels = $pdo->query("SELECT * FROM grade_levels ORDER BY level_number")->fetchAll();
      $classes = $pdo->query("SELECT c.*, gl.level_name FROM classes c LEFT JOIN grade_levels gl ON gl.id=c.grade_level_id ORDER BY gl.level_number, c.class_name")->fetchAll();
      $years = $pdo->query("SELECT * FROM academic_years ORDER BY id DESC")->fetchAll();
      
      $rows = $pdo->query("SELECT ft.*, gl.level_name, c.class_name,
          (SELECT COUNT(*) FROM student_fees sf WHERE sf.fee_type_id = ft.id) AS students_count
          FROM fee_types ft
          LEFT JOIN grade_levels gl ON gl.id = ft.grade_level_id
          LEFT JOIN classes c ON c.id = ft.class_id
          ORDER BY ft.id DESC")->fetchAll();
  ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="page-title mb-0"><span class="icon">📋</span> أنواع الرسوم الدراسية</h3>
      <button class="btn btn-primary" onclick="openModal('addFeeTypeModal')">➕ إضافة نوع رسوم</button>
    </div>

    <?php if ($fee_msg): ?><div class="alert alert-<?= h($fee_type_msg) ?>"><?= h($fee_msg) ?></div><?php endif; ?>

    <div class="alert alert-info">
      <strong>ℹ️ أنواع الرسوم:</strong> حدد أنواع الرسوم الدراسية (مثل: رسوم دراسية، رسوم مواصلات، رسوم أنشطة) مع المبلغ الإجمالي وعدد الأقساط.<br>
      <strong>💡 ميزة التقسيم المخصص:</strong> يمكنك تحديد مبلغ القسط الأول يدوياً (مثلاً 1000 دينار) وسيتم تقسيم الباقي على الأقساط المتبقية بالتساوي.
    </div>

    <div class="card">
      <div class="card-body table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>#</th><th>اسم الرسوم</th><th>السنة الدراسية</th><th>المستوى</th><th>الفصل</th>
              <th>المبلغ الإجمالي</th><th>تقسيم الأقساط</th><th>عدد الطلبة</th><th>الحالة</th><th>إجراءات</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$rows): ?><tr><td colspan="10" class="text-center text-muted py-4">لا توجد أنواع رسوم. اضغط "إضافة نوع رسوم" للبدء.</td></tr><?php endif; ?>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><?= $r['id'] ?></td>
                <td>
                  <strong><?= h($r['fee_name']) ?></strong>
                  <?php if ($r['fee_description']): ?>
                    <br><small class="text-muted"><?= h($r['fee_description']) ?></small>
                  <?php endif; ?>
                </td>
                <td><?= h($r['academic_year']) ?></td>
                <td><?= h($r['level_name'] ?? 'الكل') ?></td>
                <td><?= h($r['class_name'] ?? 'الكل') ?></td>
                <td><strong class="text-primary"><?= number_format($r['total_amount'], 2) ?></strong></td>
                <td>
                  <span class="badge bg-info"><?= $r['installments_count'] ?> قسط</span>
                  <?php if ($r['first_installment_amount'] > 0 && $r['installments_count'] > 1): ?>
                    <br><small class="text-success">
                      الأول: <?= number_format($r['first_installment_amount'], 0) ?> |
                      الباقي: <?= number_format(($r['total_amount'] - $r['first_installment_amount']) / ($r['installments_count'] - 1), 0) ?> × <?= $r['installments_count'] - 1 ?>
                    </small>
                  <?php endif; ?>
                </td>
                <td><span class="badge bg-secondary"><?= $r['students_count'] ?> طالب</span></td>
                <td>
                  <?php if ($r['is_active']): ?>
                    <span class="badge bg-success">نشط</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">غير نشط</span>
                  <?php endif; ?>
                </td>
                <td>
                  <button class="btn btn-sm btn-warning" onclick='editFeeType(<?= json_encode($r, JSON_UNESCAPED_UNICODE) ?>)'>✏️</button>
                  <?php if (isAdmin()): ?>
                  <form method="post" style="display:inline" onsubmit="return confirm('حذف نوع الرسوم؟ سيتم حذف جميع الأقساط المرتبطة.')">
                    <input type="hidden" name="action" value="del_fee_type">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <button class="btn btn-sm btn-danger">🗑️</button>
                  </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php
    $glOptions = ''; foreach($grade_levels as $gl){ $glOptions .= "<option value='{$gl['id']}'>".h($gl['level_name'])."</option>"; }
    $classOptions = ''; foreach($classes as $c){ $classOptions .= "<option value='{$c['id']}'>".h($c['class_name'])." ".($c['section'] ? '- '.h($c['section']) : '')."</option>"; }
    $yearOptions = ''; foreach($years as $y){ $yearOptions .= "<option value='{$y['year_name']}'>{$y['year_name']}</option>"; }
    ?>

    <div class="modal-overlay" id="addFeeTypeModal">
      <div class="modal-box modal-lg">
        <form method="post" id="addFeeTypeForm">
          <input type="hidden" name="action" value="add_fee_type">
          <div class="modal-header"><h5>📋 إضافة نوع رسوم</h5><button type="button" class="modal-close" onclick="closeModal('addFeeTypeModal')">×</button></div>
          <div class="modal-body">
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label">اسم الرسوم *</label>
                <input name="fee_name" class="form-control" required placeholder="مثال: رسوم دراسية">
              </div>
              <div class="col-md-6">
                <label class="form-label">السنة الدراسية *</label>
                <select name="academic_year" class="form-select" required><?= $yearOptions ?></select>
              </div>
              <div class="col-md-6">
                <label class="form-label">المستوى (اختياري)</label>
                <select name="grade_level_id" class="form-select">
                  <option value="">جميع المستويات</option>
                  <?= $glOptions ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">الفصل (اختياري)</label>
                <select name="class_id" class="form-select">
                  <option value="">جميع الفصول</option>
                  <?= $classOptions ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">المبلغ الإجمالي *</label>
                <input type="number" step="0.01" name="total_amount" id="add_ft_total" class="form-control" required placeholder="0.00" oninput="updateInstallmentPreview('add')">
              </div>
              <div class="col-md-4">
                <label class="form-label">عدد الأقساط *</label>
                <input type="number" name="installments_count" id="add_ft_count" class="form-control" value="2" min="1" max="12" oninput="updateInstallmentPreview('add')">
                <small class="text-muted">عدد الدفعات لكل فصل دراسي</small>
              </div>
              <div class="col-md-4">
                <label class="form-label">مبلغ القسط الأول (اختياري)</label>
                <input type="number" step="0.01" name="first_installment_amount" id="add_ft_first" class="form-control" value="0" placeholder="اتركه 0 للتقسيم المتساوي" oninput="updateInstallmentPreview('add')">
                <small class="text-success">💡 مثال: 1000 دينار</small>
              </div>
              <div class="col-12" id="add_ft_preview"></div>
              <div class="col-12">
                <label class="form-label">وصف الرسوم</label>
                <textarea name="fee_description" class="form-control" rows="2" placeholder="وصف اختياري..."></textarea>
              </div>
            </div>
          </div>
          <div class="modal-footer"><button class="btn btn-primary">حفظ</button></div>
        </form>
      </div>
    </div>

    <div class="modal-overlay" id="editFeeTypeModal">
      <div class="modal-box modal-lg">
        <form method="post" id="editFeeTypeForm">
          <input type="hidden" name="action" value="edit_fee_type">
          <input type="hidden" name="id" id="eft_id">
          <div class="modal-header"><h5>✏️ تعديل نوع الرسوم</h5><button type="button" class="modal-close" onclick="closeModal('editFeeTypeModal')">×</button></div>
          <div class="modal-body">
            <div class="row g-2">
              <div class="col-md-6"><label class="form-label">اسم الرسوم *</label><input name="fee_name" id="eft_name" class="form-control" required></div>
              <div class="col-md-6">
                <label class="form-label">السنة الدراسية *</label>
                <select name="academic_year" id="eft_year" class="form-select" required><?= $yearOptions ?></select>
              </div>
              <div class="col-md-6">
                <label class="form-label">المستوى</label>
                <select name="grade_level_id" id="eft_gl" class="form-select"><option value="">جميع المستويات</option><?= $glOptions ?></select>
              </div>
              <div class="col-md-6">
                <label class="form-label">الفصل</label>
                <select name="class_id" id="eft_class" class="form-select"><option value="">جميع الفصول</option><?= $classOptions ?></select>
              </div>
              <div class="col-md-4">
                <label class="form-label">المبلغ الإجمالي *</label>
                <input type="number" step="0.01" name="total_amount" id="eft_total" class="form-control" required oninput="updateInstallmentPreview('edit')">
              </div>
              <div class="col-md-4">
                <label class="form-label">عدد الأقساط *</label>
                <input type="number" name="installments_count" id="eft_count" class="form-control" min="1" max="12" oninput="updateInstallmentPreview('edit')">
              </div>
              <div class="col-md-4">
                <label class="form-label">مبلغ القسط الأول</label>
                <input type="number" step="0.01" name="first_installment_amount" id="eft_first" class="form-control" value="0" placeholder="0 = تقسيم متساوي" oninput="updateInstallmentPreview('edit')">
                <small class="text-success">💡 مثال: 1000 دينار</small>
              </div>
              <div class="col-12" id="eft_preview"></div>
              <div class="col-md-6">
                <label class="form-label">الحالة</label>
                <select name="is_active" id="eft_active" class="form-select">
                  <option value="1">نشط</option>
                  <option value="0">غير نشط</option>
                </select>
              </div>
              <div class="col-12"><label class="form-label">وصف الرسوم</label><textarea name="fee_description" id="eft_desc" class="form-control" rows="2"></textarea></div>
            </div>
          </div>
          <div class="modal-footer"><button class="btn btn-primary">تحديث</button></div>
        </form>
      </div>
    </div>

    <script>
    function updateInstallmentPreview(prefix){
      const totalInput = document.getElementById(prefix === 'add' ? 'add_ft_total' : 'eft_total');
      const countInput = document.getElementById(prefix === 'add' ? 'add_ft_count' : 'eft_count');
      const firstInput = document.getElementById(prefix === 'add' ? 'add_ft_first' : 'eft_first');
      const previewDiv = document.getElementById(prefix === 'add' ? 'add_ft_preview' : 'eft_preview');
      
      const total = parseFloat(totalInput.value) || 0;
      const count = parseInt(countInput.value) || 1;
      const first = parseFloat(firstInput.value) || 0;
      
      if (total <= 0) { previewDiv.innerHTML = ''; return; }
      
      let html = '<div class="installment-preview"><h6>📊 معاينة توزيع الأقساط:</h6>';
      
      if (first > 0 && count > 1 && first < total) {
        const remaining = total - first;
        const perInstallment = remaining / (count - 1);
        
        html += `<div class="preview-item first"><span>القسط 1 (الأول):</span><span>${first.toFixed(2)} د.ل</span></div>`;
        for (let i = 2; i <= count; i++) {
          html += `<div class="preview-item"><span>القسط ${i}:</span><span>${perInstallment.toFixed(2)} د.ل</span></div>`;
        }
        html += `<div class="preview-item" style="border-top:2px solid #166534; margin-top:8px; padding-top:8px; font-weight:700;">
          <span>الإجمالي:</span><span>${total.toFixed(2)} د.ل</span></div>`;
      } else if (first >= total && count > 1) {
        html += `<div class="alert alert-warning mb-0">⚠️ مبلغ القسط الأول (${first}) أكبر من أو يساوي المبلغ الإجمالي (${total}). سيتم التقسيم بالتساوي.</div>`;
        const perInstallment = total / count;
        for (let i = 1; i <= count; i++) {
          html += `<div class="preview-item"><span>القسط ${i}:</span><span>${perInstallment.toFixed(2)} د.ل</span></div>`;
        }
      } else {
        const perInstallment = total / count;
        for (let i = 1; i <= count; i++) {
          html += `<div class="preview-item"><span>القسط ${i}:</span><span>${perInstallment.toFixed(2)} د.ل</span></div>`;
        }
      }
      
      html += '</div>';
      previewDiv.innerHTML = html;
    }
    
    function editFeeType(d){
      document.getElementById('eft_id').value = d.id;
      document.getElementById('eft_name').value = d.fee_name || '';
      document.getElementById('eft_year').value = d.academic_year || '';
      document.getElementById('eft_gl').value = d.grade_level_id || '';
      document.getElementById('eft_class').value = d.class_id || '';
      document.getElementById('eft_total').value = d.total_amount || 0;
      document.getElementById('eft_count').value = d.installments_count || 2;
      document.getElementById('eft_first').value = d.first_installment_amount || 0;
      document.getElementById('eft_active').value = d.is_active ? '1' : '0';
      document.getElementById('eft_desc').value = d.fee_description || '';
      updateInstallmentPreview('edit');
      openModal('editFeeTypeModal');
    }
    </script>

  <?php
  /* ============================================================
     أقساط الطلبة - تعيين وإدارة أقساط الطلبة
     ============================================================ */
  elseif ($page === 'student_fees' && isAccountant()):
      $fee_msg = $_SESSION['fee_msg'] ?? '';
      $fee_type_msg = $_SESSION['fee_type_msg'] ?? 'info';
      unset($_SESSION['fee_msg'], $_SESSION['fee_type_msg']);
      
      $fee_types = $pdo->query("SELECT * FROM fee_types WHERE is_active = 1 ORDER BY fee_name")->fetchAll();
      $students = $pdo->query("SELECT s.*, c.class_name, c.section FROM students s LEFT JOIN classes c ON c.id=s.class_id WHERE s.status='active' ORDER BY s.name")->fetchAll();
      $years = $pdo->query("SELECT * FROM academic_years ORDER BY id DESC")->fetchAll();
      $classes = $pdo->query("SELECT c.*, gl.level_name FROM classes c LEFT JOIN grade_levels gl ON gl.id=c.grade_level_id ORDER BY gl.level_number, c.class_name")->fetchAll();
      
      $filter_year = $_GET['academic_year'] ?? '';
      $filter_class = (int)($_GET['class_id'] ?? 0);
      
      $sql = "SELECT sf.*, ft.fee_name, s.name AS student_name, s.student_code, c.class_name, c.section
          FROM student_fees sf
          JOIN fee_types ft ON ft.id = sf.fee_type_id
          JOIN students s ON s.id = sf.student_id
          LEFT JOIN classes c ON c.id = s.class_id
          WHERE 1=1";
      $params = [];
      
      if ($filter_year) {
          $sql .= " AND sf.academic_year = ?";
          $params[] = $filter_year;
      }
      if ($filter_class) {
          $sql .= " AND s.class_id = ?";
          $params[] = $filter_class;
      }
      
      $sql .= " ORDER BY sf.id DESC";
      $stmt = $pdo->prepare($sql);
      $stmt->execute($params);
      $student_fees = $stmt->fetchAll();
  ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="page-title mb-0"><span class="icon">💳</span> أقساط الطلبة</h3>
      <div>
        <button class="btn btn-outline-primary" onclick="openModal('bulkAssignModal')">📋 تعيين جماعي</button>
        <button class="btn btn-primary" onclick="openModal('assignFeeModal')">➕ تعيين رسوم لطالب</button>
      </div>
    </div>

    <?php if ($fee_msg): ?><div class="alert alert-<?= h($fee_type_msg) ?>"><?= h($fee_msg) ?></div><?php endif; ?>

    <div class="report-filter">
      <form method="get" class="row g-2 align-items-end">
        <input type="hidden" name="page" value="student_fees">
        <div class="col-md-4">
          <label class="form-label">السنة الدراسية</label>
          <select name="academic_year" class="form-select">
            <option value="">الكل</option>
            <?php foreach ($years as $y): ?>
              <option value="<?= h($y['year_name']) ?>" <?= $filter_year == $y['year_name'] ? 'selected' : '' ?>><?= h($y['year_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">الفصل</label>
          <select name="class_id" class="form-select">
            <option value="">الكل</option>
            <?php foreach ($classes as $c): ?>
              <option value="<?= $c['id'] ?>" <?= $filter_class == $c['id'] ? 'selected' : '' ?>>
                <?= h($c['class_name']) ?> <?= $c['section'] ? '- '.h($c['section']) : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <button class="btn btn-primary btn-block">🔍 عرض</button>
        </div>
      </form>
    </div>

    <div class="card">
      <div class="card-body table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>#</th><th>الطالب</th><th>الفصل</th><th>نوع الرسوم</th><th>السنة</th>
              <th>المبلغ الإجمالي</th><th>المدفوع</th><th>المتبقي</th><th>الأقساط</th><th>الحالة</th><th>إجراءات</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$student_fees): ?><tr><td colspan="11" class="text-center text-muted py-4">لا توجد أقساط مسجلة</td></tr><?php endif; ?>
            <?php foreach ($student_fees as $sf): 
              $status_map = [
                  'pending' => ['bg-warning', 'معلق'],
                  'partial' => ['bg-info', 'جزئي'],
                  'paid' => ['bg-success', 'مدفوع'],
                  'overdue' => ['bg-danger', 'متأخر']
              ];
              $st = $status_map[$sf['status']] ?? ['bg-secondary', $sf['status']];
            ?>
              <tr>
                <td><?= $sf['id'] ?></td>
                <td>
                  <?= h($sf['student_name']) ?>
                  <br><small class="text-muted"><?= h($sf['student_code']) ?></small>
                </td>
                <td><?= h($sf['class_name'] ?? '-') ?> <?= $sf['section'] ? '- '.h($sf['section']) : '' ?></td>
                <td><?= h($sf['fee_name']) ?></td>
                <td><?= h($sf['academic_year']) ?></td>
                <td><strong><?= number_format($sf['total_amount'], 2) ?></strong></td>
                <td class="text-success"><?= number_format($sf['paid_amount'], 2) ?></td>
                <td class="text-danger"><?= number_format($sf['remaining_amount'], 2) ?></td>
                <td>
                  <?php 
                  $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM fee_installments WHERE student_fee_id = ? AND status = 'paid'");
                  $stmt2->execute([$sf['id']]);
                  $paid_count = $stmt2->fetchColumn();
                  $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM fee_installments WHERE student_fee_id = ?");
                  $stmt2->execute([$sf['id']]);
                  $total_count = $stmt2->fetchColumn();
                  ?>
                  <span class="badge bg-primary"><?= $paid_count ?> / <?= $total_count ?></span>
                </td>
                <td><span class="badge <?= $st[0] ?>"><?= $st[1] ?></span></td>
                <td>
                  <a href="?page=fee_installments&student_fee_id=<?= $sf['id'] ?>" class="btn btn-sm btn-info" title="عرض الأقساط">📅</a>
                  <a href="?page=student_report&student_id=<?= $sf['student_id'] ?>" class="btn btn-sm btn-primary" title="ملف الطالب">👤</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php
    $studentOptions = ''; foreach($students as $s){ $studentOptions .= "<option value='{$s['id']}'>".h($s['name'])." (".h($s['student_code']).") - ".h($s['class_name'] ?? '')."</option>"; }
    $feeTypeOptions = ''; foreach($fee_types as $ft){ $feeTypeOptions .= "<option value='{$ft['id']}' data-amount='{$ft['total_amount']}' data-installments='{$ft['installments_count']}' data-first='{$ft['first_installment_amount']}'>".h($ft['fee_name'])." - ".number_format($ft['total_amount'],2)."</option>"; }
    $yearOptions2 = ''; foreach($years as $y){ $yearOptions2 .= "<option value='{$y['year_name']}'>{$y['year_name']}</option>"; }
    ?>

    <!-- Modal تعيين رسوم لطالب -->
    <div class="modal-overlay" id="assignFeeModal">
      <div class="modal-box modal-lg">
        <form method="post">
          <input type="hidden" name="action" value="assign_student_fee">
          <div class="modal-header"><h5>💳 تعيين رسوم لطالب</h5><button type="button" class="modal-close" onclick="closeModal('assignFeeModal')">×</button></div>
          <div class="modal-body">
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label">الطالب *</label>
                <select name="student_id" class="form-select" required><?= $studentOptions ?></select>
              </div>
              <div class="col-md-6">
                <label class="form-label">نوع الرسوم *</label>
                <select name="fee_type_id" id="assign_fee_type" class="form-select" required onchange="updateFeeDetails()"><?= $feeTypeOptions ?></select>
              </div>
              <div class="col-md-6">
                <label class="form-label">السنة الدراسية *</label>
                <select name="academic_year" class="form-select" required><?= $yearOptions2 ?></select>
              </div>
              <div class="col-md-6">
                <label class="form-label">الفصل الدراسي *</label>
                <select name="semester" class="form-select" required>
                  <option value="first">الأول</option>
                  <option value="second">الثاني</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">المبلغ الإجمالي *</label>
                <input type="number" step="0.01" name="custom_amount" id="assign_amount" class="form-control" required oninput="updateAssignPreview()">
              </div>
              <div class="col-md-4">
                <label class="form-label">عدد الأقساط *</label>
                <input type="number" name="installments_count" id="assign_installments" class="form-control" min="1" max="12" required oninput="updateAssignPreview()">
              </div>
              <div class="col-md-4">
                <label class="form-label">مبلغ القسط الأول</label>
                <input type="number" step="0.01" name="first_installment_amount" id="assign_first" class="form-control" value="0" placeholder="0 = تقسيم متساوي" oninput="updateAssignPreview()">
                <small class="text-success">💡 مثال: 1000 دينار</small>
              </div>
              <div class="col-12" id="assign_preview"></div>
              <div class="col-md-6">
                <label class="form-label">تاريخ بداية القسط الأول *</label>
                <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
              </div>
            </div>
            <div class="alert alert-info mt-3 mb-0">
              ℹ️ سيتم إنشاء الأقساط تلقائياً وإرسال إشعارات لولي الأمر.
            </div>
          </div>
          <div class="modal-footer"><button class="btn btn-primary">حفظ</button></div>
        </form>
      </div>
    </div>

    <!-- Modal التعيين الجماعي -->
    <div class="modal-overlay" id="bulkAssignModal">
      <div class="modal-box modal-lg">
        <form method="post">
          <input type="hidden" name="action" value="assign_bulk_fees">
          <div class="modal-header"><h5>📋 تعيين جماعي للرسوم</h5><button type="button" class="modal-close" onclick="closeModal('bulkAssignModal')">×</button></div>
          <div class="modal-body">
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label">نوع الرسوم *</label>
                <select name="fee_type_id" class="form-select" required><?= $feeTypeOptions ?></select>
              </div>
              <div class="col-md-6">
                <label class="form-label">السنة الدراسية *</label>
                <select name="academic_year" class="form-select" required><?= $yearOptions2 ?></select>
              </div>
              <div class="col-md-6">
                <label class="form-label">الفصل الدراسي *</label>
                <select name="semester" class="form-select" required>
                  <option value="first">الأول</option>
                  <option value="second">الثاني</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">تاريخ بداية القسط الأول *</label>
                <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">الفصل (اختياري)</label>
                <select name="class_id" class="form-select">
                  <option value="">جميع الفصول</option>
                  <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= h($c['class_name']) ?> <?= $c['section'] ? '- '.h($c['section']) : '' ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="alert alert-warning mt-3 mb-0">
              ⚠️ سيتم تعيين الرسوم لجميع الطلبة النشطين في السنة الدراسية المحددة (والفصل إن تم اختياره). الطلبة الذين لديهم رسوم مسجلة مسبقاً سيتم تخطيهم.<br>
              <strong>💡 ملاحظة:</strong> سيتم استخدام إعدادات القسط الأول من نوع الرسوم المحدد.
            </div>
          </div>
          <div class="modal-footer"><button class="btn btn-primary">تعيين جماعي</button></div>
        </form>
      </div>
    </div>

    <script>
    function updateFeeDetails(){
      const sel = document.getElementById('assign_fee_type');
      const opt = sel.options[sel.selectedIndex];
      if(opt){
        document.getElementById('assign_amount').value = opt.dataset.amount || 0;
        document.getElementById('assign_installments').value = opt.dataset.installments || 2;
        document.getElementById('assign_first').value = opt.dataset.first || 0;
        updateAssignPreview();
      }
    }
    
    function updateAssignPreview(){
      const total = parseFloat(document.getElementById('assign_amount').value) || 0;
      const count = parseInt(document.getElementById('assign_installments').value) || 1;
      const first = parseFloat(document.getElementById('assign_first').value) || 0;
      const previewDiv = document.getElementById('assign_preview');
      
      if (total <= 0) { previewDiv.innerHTML = ''; return; }
      
      let html = '<div class="installment-preview"><h6>📊 معاينة توزيع الأقساط:</h6>';
      
      if (first > 0 && count > 1 && first < total) {
        const remaining = total - first;
        const perInstallment = remaining / (count - 1);
        
        html += `<div class="preview-item first"><span>القسط 1 (الأول):</span><span>${first.toFixed(2)} د.ل</span></div>`;
        for (let i = 2; i <= count; i++) {
          html += `<div class="preview-item"><span>القسط ${i}:</span><span>${perInstallment.toFixed(2)} د.ل</span></div>`;
        }
        html += `<div class="preview-item" style="border-top:2px solid #166534; margin-top:8px; padding-top:8px; font-weight:700;">
          <span>الإجمالي:</span><span>${total.toFixed(2)} د.ل</span></div>`;
      } else {
        const perInstallment = total / count;
        for (let i = 1; i <= count; i++) {
          html += `<div class="preview-item"><span>القسط ${i}:</span><span>${perInstallment.toFixed(2)} د.ل</span></div>`;
        }
      }
      
      html += '</div>';
      previewDiv.innerHTML = html;
    }
    
    updateFeeDetails();
    </script>

  <?php
  /* ============================================================
     الأقساط المستحقة - عرض وإدارة الأقساط
     ============================================================ */
  elseif ($page === 'fee_installments' && isAccountant()):
      $student_fee_id = (int)($_GET['student_fee_id'] ?? 0);
      $filter = $_GET['filter'] ?? 'all';
      
      $fee_msg = $_SESSION['fee_msg'] ?? '';
      $fee_type_msg = $_SESSION['fee_type_msg'] ?? 'info';
      unset($_SESSION['fee_msg'], $_SESSION['fee_type_msg']);
      
      // التحقق من وجود student_fee_id
      $student_fee = null;
      $installments = [];
      $student = null;
      
      if ($student_fee_id) {
          $stmt = $pdo->prepare("SELECT sf.*, ft.fee_name, s.name AS student_name, s.student_code, c.class_name, c.section, g.id AS guardian_id
              FROM student_fees sf
              JOIN fee_types ft ON ft.id = sf.fee_type_id
              JOIN students s ON s.id = sf.student_id
              LEFT JOIN classes c ON c.id = s.class_id
              LEFT JOIN guardians g ON g.student_id = s.id
              WHERE sf.id = ?");
          $stmt->execute([$student_fee_id]);
          $student_fee = $stmt->fetch();
          
          if ($student_fee) {
              $installments = getFeeInstallments($pdo, $student_fee_id);
          }
      }
      
      // إذا لم يتم تحديد student_fee_id، عرض كل الأقساط
      $all_installments = [];
      if (!$student_fee_id) {
          $sql = "SELECT fi.*, sf.total_amount, ft.fee_name, s.name AS student_name, s.student_code, sf.id AS sf_id
              FROM fee_installments fi
              JOIN student_fees sf ON sf.id = fi.student_fee_id
              JOIN fee_types ft ON ft.id = sf.fee_type_id
              JOIN students s ON s.id = sf.student_id
              WHERE 1=1";
          $params = [];
          
          if ($filter === 'overdue') {
              $sql .= " AND fi.status = 'pending' AND fi.due_date < CURDATE()";
          } elseif ($filter === 'pending') {
              $sql .= " AND fi.status = 'pending'";
          } elseif ($filter === 'paid') {
              $sql .= " AND fi.status = 'paid'";
          }
          
          $sql .= " ORDER BY fi.due_date ASC, fi.id DESC LIMIT 200";
          $stmt = $pdo->prepare($sql);
          $stmt->execute($params);
          $all_installments = $stmt->fetchAll();
      }
  ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="page-title mb-0">
        <span class="icon">📅</span> الأقساط المستحقة
        <?php if ($student_fee): ?>
          - <?= h($student_fee['student_name']) ?> (<?= h($student_fee['fee_name']) ?>)
        <?php endif; ?>
      </h3>
      <div>
        <?php if ($student_fee): ?>
          <a href="?page=student_fees" class="btn btn-secondary">← رجوع لأقساط الطلبة</a>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($fee_msg): ?><div class="alert alert-<?= h($fee_type_msg) ?>"><?= h($fee_msg) ?></div><?php endif; ?>

    <?php if ($student_fee): ?>
      <!-- عرض أقساط طالب معين -->
      <div class="card mb-4">
        <div class="card-header">📋 معلومات الرسوم</div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-3"><strong>الطالب:</strong> <?= h($student_fee['student_name']) ?></div>
            <div class="col-md-3"><strong>الفصل:</strong> <?= h($student_fee['class_name'] ?? '-') ?> <?= $student_fee['section'] ? '- '.h($student_fee['section']) : '' ?></div>
            <div class="col-md-3"><strong>نوع الرسوم:</strong> <?= h($student_fee['fee_name']) ?></div>
            <div class="col-md-3"><strong>السنة:</strong> <?= h($student_fee['academic_year']) ?></div>
            <div class="col-md-3"><strong>الإجمالي:</strong> <?= number_format($student_fee['total_amount'], 2) ?></div>
            <div class="col-md-3"><strong>المدفوع:</strong> <span class="text-success"><?= number_format($student_fee['paid_amount'], 2) ?></span></div>
            <div class="col-md-3"><strong>المتبقي:</strong> <span class="text-danger"><?= number_format($student_fee['remaining_amount'], 2) ?></span></div>
            <div class="col-md-3">
              <?php 
              $status_map = [
                  'pending' => ['bg-warning', 'معلق'],
                  'partial' => ['bg-info', 'جزئي'],
                  'paid' => ['bg-success', 'مدفوع'],
                  'overdue' => ['bg-danger', 'متأخر']
              ];
              $st = $status_map[$student_fee['status']] ?? ['bg-secondary', $student_fee['status']];
              ?>
              <strong>الحالة:</strong> <span class="badge <?= $st[0] ?>"><?= $st[1] ?></span>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">📅 قائمة الأقساط</div>
        <div class="card-body">
          <?php foreach ($installments as $inst): 
              $status_map = [
                  'pending' => ['bg-warning', 'معلق', 'installment-card pending'],
                  'paid' => ['bg-success', 'مدفوع', 'installment-card paid'],
                  'overdue' => ['bg-danger', 'متأخر', 'installment-card overdue'],
                  'cancelled' => ['bg-secondary', 'ملغي', 'installment-card']
              ];
              $st = $status_map[$inst['status']] ?? ['bg-secondary', $inst['status'], 'installment-card'];
              $is_overdue = $inst['status'] === 'pending' && $inst['due_date'] < date('Y-m-d');
          ?>
          <div class="<?= $is_overdue ? 'installment-card overdue' : $st[2] ?>">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <strong>القسط رقم <?= $inst['installment_number'] ?></strong>
                <?php if ($inst['installment_number'] == 1): ?>
                  <span class="badge bg-primary ms-1">القسط الأول</span>
                <?php endif; ?>
                <span class="badge <?= $is_overdue ? 'bg-danger' : $st[0] ?> ms-2">
                  <?= $is_overdue ? 'متأخر' : $st[1] ?>
                </span>
                <div class="small text-muted mt-2">
                  📅 تاريخ الاستحقاق: <?= h($inst['due_date']) ?>
                  <?php if ($inst['paid_date']): ?>
                    | ✅ تاريخ الدفع: <?= h($inst['paid_date']) ?>
                  <?php endif; ?>
                  <?php if ($inst['receipt_number']): ?>
                    | 🧾 رقم الإيصال: <?= h($inst['receipt_number']) ?>
                  <?php endif; ?>
                </div>
              </div>
              <div class="text-left">
                <div class="fw-bold" style="font-size: 1.2rem;"><?= number_format($inst['amount'], 2) ?></div>
                <?php if ($inst['status'] === 'pending' || $is_overdue): ?>
                <button class="btn btn-sm btn-success mt-2" onclick='openPayModal(<?= $inst['id'] ?>, <?= $inst['amount'] ?>, "القسط رقم <?= $inst['installment_number'] ?>")'>
                  💳 تسجيل دفع
                </button>
                <?php endif; ?>
                <?php if ($inst['status'] === 'pending' && isAdmin()): ?>
                <form method="post" style="display:inline" onsubmit="return confirm('إلغاء هذا القسط؟')">
                  <input type="hidden" name="action" value="cancel_installment">
                  <input type="hidden" name="installment_id" value="<?= $inst['id'] ?>">
                  <button class="btn btn-sm btn-danger mt-2">❌ إلغاء</button>
                </form>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Modal تسجيل دفع -->
      <div class="modal-overlay" id="payInstallmentModal">
        <div class="modal-box">
          <form method="post">
            <input type="hidden" name="action" value="pay_installment">
            <input type="hidden" name="installment_id" id="pay_installment_id">
            <div class="modal-header"><h5>💳 تسجيل دفع قسط</h5><button type="button" class="modal-close" onclick="closeModal('payInstallmentModal')">×</button></div>
            <div class="modal-body">
              <div class="alert alert-info">
                <strong>القسط:</strong> <span id="pay_installment_desc"></span><br>
                <strong>المبلغ المستحق:</strong> <span id="pay_installment_amount"></span>
              </div>
              <div class="row g-2">
                <div class="col-md-6">
                  <label class="form-label">المبلغ المدفوع *</label>
                  <input type="number" step="0.01" name="amount" id="pay_amount" class="form-control" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">تاريخ الدفع *</label>
                  <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">طريقة الدفع *</label>
                  <select name="payment_method" class="form-select" required>
                    <option value="cash">نقدي</option>
                    <option value="bank_transfer">تحويل بنكي</option>
                    <option value="check">شيك</option>
                    <option value="online">دفع إلكتروني</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">ملاحظات</label>
                  <input name="notes" class="form-control" placeholder="اختياري">
                </div>
              </div>
            </div>
            <div class="modal-footer"><button class="btn btn-success">💳 تأكيد الدفع</button></div>
          </form>
        </div>
      </div>

      <script>
      function openPayModal(id, amount, desc){
        document.getElementById('pay_installment_id').value = id;
        document.getElementById('pay_installment_desc').textContent = desc;
        document.getElementById('pay_installment_amount').textContent = amount;
        document.getElementById('pay_amount').value = amount;
        openModal('payInstallmentModal');
      }
      </script>

    <?php else: ?>
      <!-- عرض كل الأقساط -->
      <div class="report-filter">
        <form method="get" class="row g-2 align-items-end">
          <input type="hidden" name="page" value="fee_installments">
          <div class="col-md-8">
            <label class="form-label">تصفية</label>
            <select name="filter" class="form-select" onchange="this.form.submit()">
              <option value="all" <?= $filter=='all'?'selected':'' ?>>الكل</option>
              <option value="pending" <?= $filter=='pending'?'selected':'' ?>>المعلقة</option>
              <option value="overdue" <?= $filter=='overdue'?'selected':'' ?>>المتأخرة</option>
              <option value="paid" <?= $filter=='paid'?'selected':'' ?>>المدفوعة</option>
            </select>
          </div>
          <div class="col-md-4">
            <button class="btn btn-primary btn-block">🔍 عرض</button>
          </div>
        </form>
      </div>

      <div class="card">
        <div class="card-body table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>#</th><th>الطالب</th><th>نوع الرسوم</th><th>القسط</th>
                <th>المبلغ</th><th>تاريخ الاستحقاق</th><th>الحالة</th><th>إجراءات</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$all_installments): ?><tr><td colspan="8" class="text-center text-muted py-4">لا توجد أقساط</td></tr><?php endif; ?>
              <?php foreach ($all_installments as $inst): 
                $is_overdue = $inst['status'] === 'pending' && $inst['due_date'] < date('Y-m-d');
                $status_map = [
                    'pending' => $is_overdue ? ['bg-danger', 'متأخر'] : ['bg-warning', 'معلق'],
                    'paid' => ['bg-success', 'مدفوع'],
                    'overdue' => ['bg-danger', 'متأخر'],
                    'cancelled' => ['bg-secondary', 'ملغي']
                ];
                $st = $status_map[$inst['status']] ?? ['bg-secondary', $inst['status']];
              ?>
                <tr>
                  <td><?= $inst['id'] ?></td>
                  <td>
                    <?= h($inst['student_name']) ?>
                    <br><small class="text-muted"><?= h($inst['student_code']) ?></small>
                  </td>
                  <td><?= h($inst['fee_name']) ?></td>
                  <td>
                    <span class="badge bg-info">قسط <?= $inst['installment_number'] ?></span>
                    <?php if ($inst['installment_number'] == 1): ?>
                      <span class="badge bg-primary">الأول</span>
                    <?php endif; ?>
                  </td>
                  <td><strong><?= number_format($inst['amount'], 2) ?></strong></td>
                  <td><?= h($inst['due_date']) ?></td>
                  <td><span class="badge <?= $st[0] ?>"><?= $st[1] ?></span></td>
                  <td>
                    <a href="?page=fee_installments&student_fee_id=<?= $inst['sf_id'] ?>" class="btn btn-sm btn-primary">📋 عرض</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

  <?php
  /* ============================================================
     تقرير الأقساط
     ============================================================ */
  elseif ($page === 'fee_report' && isAccountant()):
      $filter_year = $_GET['academic_year'] ?? '';
      $filter_status = $_GET['status'] ?? 'all';
      $date_from = $_GET['date_from'] ?? date('Y-m-01');
      $date_to = $_GET['date_to'] ?? date('Y-m-d');
      
      $years = $pdo->query("SELECT * FROM academic_years ORDER BY id DESC")->fetchAll();
      
      // إحصائيات عامة
      $stats_sql = "SELECT 
          COUNT(*) AS total_fees,
          COALESCE(SUM(total_amount), 0) AS total_amount,
          COALESCE(SUM(paid_amount), 0) AS paid_amount,
          COALESCE(SUM(remaining_amount), 0) AS remaining_amount
          FROM student_fees WHERE 1=1";
      $stats_params = [];
      if ($filter_year) {
          $stats_sql .= " AND academic_year = ?";
          $stats_params[] = $filter_year;
      }
      $stmt = $pdo->prepare($stats_sql);
      $stmt->execute($stats_params);
      $stats = $stmt->fetch();
      
      // المدفوعات في الفترة
      $payments_sql = "SELECT fp.*, fi.installment_number, ft.fee_name, s.name AS student_name
          FROM fee_payments fp
          JOIN fee_installments fi ON fi.id = fp.installment_id
          JOIN student_fees sf ON sf.id = fp.student_fee_id
          JOIN fee_types ft ON ft.id = sf.fee_type_id
          JOIN students s ON s.id = sf.student_id
          WHERE fp.payment_date BETWEEN ? AND ?";
      $payments_params = [$date_from, $date_to];
      
      if ($filter_year) {
          $payments_sql .= " AND sf.academic_year = ?";
          $payments_params[] = $filter_year;
      }
      
      $payments_sql .= " ORDER BY fp.payment_date DESC";
      $stmt = $pdo->prepare($payments_sql);
      $stmt->execute($payments_params);
      $payments = $stmt->fetchAll();
      
      $total_period_payments = 0;
      foreach ($payments as $p) {
          $total_period_payments += $p['amount'];
      }
  ?>
    <div class="print-header">
      <h2>🏫 المدرسة النموذجية</h2>
      <h3>📊 تقرير الأقساط والمدفوعات</h3>
      <p>الفترة: من <?= h($date_from) ?> إلى <?= h($date_to) ?></p>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
      <h3 class="page-title mb-0"><span class="icon">📊</span> تقرير الأقساط</h3>
      <button onclick="window.print()" class="btn btn-primary">🖨️ طباعة التقرير</button>
    </div>

    <div class="report-filter no-print">
      <form method="get" class="row g-2 align-items-end">
        <input type="hidden" name="page" value="fee_report">
        <div class="col-md-3">
          <label class="form-label">السنة الدراسية</label>
          <select name="academic_year" class="form-select">
            <option value="">الكل</option>
            <?php foreach ($years as $y): ?>
              <option value="<?= h($y['year_name']) ?>" <?= $filter_year == $y['year_name'] ? 'selected' : '' ?>><?= h($y['year_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">من تاريخ</label>
          <input type="date" name="date_from" class="form-control" value="<?= h($date_from) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">إلى تاريخ</label>
          <input type="date" name="date_to" class="form-control" value="<?= h($date_to) ?>">
        </div>
        <div class="col-md-3">
          <button class="btn btn-primary btn-block">🔍 عرض</button>
        </div>
      </form>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-3 col-sm-6">
        <div class="stat-card" style="background: linear-gradient(135deg, #667eea, #764ba2);">
          <span class="icon">📋</span>
          <div><h2><?= $stats['total_fees'] ?></h2><span>عدد الرسوم</span></div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669);">
          <span class="icon">💵</span>
          <div><h2><?= number_format($stats['total_amount'], 0) ?></h2><span>إجمالي الرسوم</span></div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="stat-card" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);">
          <span class="icon">✅</span>
          <div><h2><?= number_format($stats['paid_amount'], 0) ?></h2><span>المدفوع</span></div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
          <span class="icon">⏳</span>
          <div><h2><?= number_format($stats['remaining_amount'], 0) ?></h2><span>المتبقي</span></div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header">
        💳 المدفوعات في الفترة من <?= h($date_from) ?> إلى <?= h($date_to) ?>
        <span class="badge bg-success ms-2">الإجمالي: <?= number_format($total_period_payments, 2) ?></span>
      </div>
      <div class="card-body table-responsive">
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>#</th><th>الطالب</th><th>نوع الرسوم</th><th>القسط</th>
              <th>المبلغ</th><th>تاريخ الدفع</th><th>طريقة الدفع</th><th>رقم الإيصال</th><th>بواسطة</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$payments): ?><tr><td colspan="9" class="text-center text-muted py-4">لا توجد مدفوعات في هذه الفترة</td></tr><?php endif; ?>
            <?php foreach ($payments as $i => $p): 
              $methods = ['cash'=>'نقدي','bank_transfer'=>'تحويل بنكي','check'=>'شيك','online'=>'دفع إلكتروني'];
            ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= h($p['student_name']) ?></td>
                <td><?= h($p['fee_name']) ?></td>
                <td>
                  <span class="badge bg-info">قسط <?= $p['installment_number'] ?></span>
                  <?php if ($p['installment_number'] == 1): ?>
                    <span class="badge bg-primary">الأول</span>
                  <?php endif; ?>
                </td>
                <td><strong><?= number_format($p['amount'], 2) ?></strong></td>
                <td><?= h($p['payment_date']) ?></td>
                <td><?= $methods[$p['payment_method']] ?? $p['payment_method'] ?></td>
                <td><span class="badge bg-dark"><?= h($p['receipt_number']) ?></span></td>
                <td><?= h($p['received_by']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  <?php
  /* ============================================================
     1) السنوات الدراسية - إضافة سنة دراسية جديدة
     ============================================================ */
  elseif ($page === 'grade_levels' && isDataEntry()):
      $gl_msg = $_SESSION['gl_msg'] ?? '';
      $gl_type = $_SESSION['gl_type'] ?? 'info';
      unset($_SESSION['gl_msg'], $_SESSION['gl_type']);
      
      $academic_years = $pdo->query("SELECT * FROM academic_years ORDER BY id DESC")->fetchAll();
      
      $rows = $pdo->query("SELECT g.*, 
          (SELECT COUNT(*) FROM classes c WHERE c.grade_level_id=g.id) AS classes_count,
          (SELECT COUNT(DISTINCT c.class_name) FROM classes c WHERE c.grade_level_id=g.id) AS unique_classes_count,
          (SELECT COUNT(*) FROM students s JOIN classes c ON c.id=s.class_id WHERE c.grade_level_id=g.id) AS students_count,
          (SELECT COUNT(*) FROM subjects sub WHERE sub.grade_level_id=g.id) AS subjects_count
          FROM grade_levels g ORDER BY g.level_number")->fetchAll();
  ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="page-title mb-0"><span class="icon">🎯</span> السنوات الدراسية</h3>
      <button class="btn btn-primary" onclick="openModal('addGLModal')">➕ إضافة سنة دراسية</button>
    </div>

    <?php if ($gl_msg): ?><div class="alert alert-<?= h($gl_type) ?>"><?= h($gl_msg) ?></div><?php endif; ?>

    <div class="alert alert-info">
      <strong>ℹ️ السنوات الدراسية:</strong> كل سنة دراسية تمثل مستوى تعليمياً مع السنة الدراسية بالعام (مثل 2026-2027). اضغط على أي سنة لعرض الفصول والشعب التابعة لها.
    </div>

    <div class="row g-3">
      <?php if (!$rows): ?>
        <div class="col-12"><div class="alert alert-warning">لا توجد سنوات دراسية. اضغط "إضافة سنة دراسية" للبدء.</div></div>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <div class="col-md-4 col-sm-6">
          <div class="level-card">
            <a href="?page=classes&grade_level_id=<?= $r['id'] ?>" style="text-decoration:none; color:inherit; display:block;">
              <div class="level-num"><?= $r['level_number'] ?></div>
              <h4><?= h($r['level_name']) ?></h4>
              <?php if ($r['academic_year']): ?>
                <div class="small" style="color: #3b82f6; font-weight: 600;">📅 السنة الدراسية: <?= h($r['academic_year']) ?></div>
              <?php endif; ?>
              <div class="stats">
                <span>🏛️ <?= $r['unique_classes_count'] ?> فصل</span>
                <span>👥 <?= $r['classes_count'] ?> شعبة</span>
                <span>🎓 <?= $r['students_count'] ?> طالب</span>
                <span>📖 <?= $r['subjects_count'] ?> مادة</span>
              </div>
            </a>
            <div class="mt-3 d-flex gap-2">
              <a href="?page=classes&grade_level_id=<?= $r['id'] ?>" class="btn btn-sm btn-primary">📂 عرض الفصول</a>
              <button class="btn btn-sm btn-warning" onclick='editGL(<?= json_encode($r, JSON_UNESCAPED_UNICODE) ?>)'>✏️</button>
              <?php if (isAdmin()): ?>
              <form method="post" style="display:inline" onsubmit="return confirm('حذف السنة الدراسية؟')">
                <input type="hidden" name="action" value="del_grade_level">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <button class="btn btn-sm btn-danger">🗑️</button>
              </form>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="modal-overlay" id="addGLModal">
      <div class="modal-box">
        <form method="post">
          <input type="hidden" name="action" value="add_grade_level_new">
          <div class="modal-header">
            <h5>➕ إضافة سنة دراسية جديدة</h5>
            <button type="button" class="modal-close" onclick="closeModal('addGLModal')">×</button>
          </div>
          <div class="modal-body">
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label">رقم المستوى *</label>
                <input type="number" name="level_number" class="form-control" required min="1" placeholder="مثال: 1">
              </div>
              <div class="col-md-6">
                <label class="form-label">اسم المستوى *</label>
                <input name="level_name" class="form-control" required placeholder="مثال: الصف الأول">
              </div>
              <div class="col-md-12">
                <label class="form-label">السنة الدراسية بالعام *</label>
                <input name="academic_year" class="form-control" placeholder="مثال: 2026-2027" value="<?= date('Y') ?>-<?= date('Y')+1 ?>" required>
                <small class="text-muted">أدخل السنة الدراسية بصيغة سنة-سنة (مثل: 2026-2027)</small>
              </div>
            </div>
            <div class="alert alert-info small mt-3 mb-0">
              ℹ️ سيتم إنشاء السنة الدراسية تلقائياً إذا لم تكن موجودة.
            </div>
          </div>
          <div class="modal-footer"><button class="btn btn-primary">حفظ</button></div>
        </form>
      </div>
    </div>

    <div class="modal-overlay" id="editGLModal">
      <div class="modal-box">
        <form method="post">
          <input type="hidden" name="action" value="edit_grade_level">
          <input type="hidden" name="id" id="egl_id">
          <div class="modal-header">
            <h5>✏️ تعديل السنة الدراسية</h5>
            <button type="button" class="modal-close" onclick="closeModal('editGLModal')">×</button>
          </div>
          <div class="modal-body">
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label">رقم المستوى *</label>
                <input type="number" name="level_number" id="egl_number" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">اسم المستوى *</label>
                <input name="level_name" id="egl_name" class="form-control" required>
              </div>
              <div class="col-md-12">
                <label class="form-label">السنة الدراسية بالعام</label>
                <input name="academic_year" id="egl_year" class="form-control" placeholder="مثال: 2026-2027">
              </div>
            </div>
          </div>
          <div class="modal-footer"><button class="btn btn-primary">تحديث</button></div>
        </form>
      </div>
    </div>

    <script>
    function editGL(d){
      document.getElementById('egl_id').value=d.id;
      document.getElementById('egl_number').value=d.level_number;
      document.getElementById('egl_name').value=d.level_name;
      document.getElementById('egl_year').value=d.academic_year||'';
      openModal('editGLModal');
    }
    </script>

  <?php
  /* ============================================================
     2) الفصول والشعب - عرض الفصول مع إمكانية إضافة شعب
     ============================================================ */
  elseif ($page === 'classes' && isDataEntry()):
      $gl_id = (int)($_GET['grade_level_id'] ?? 0);
      $gl_msg = $_SESSION['gl_msg'] ?? '';
      $gl_type = $_SESSION['gl_type'] ?? 'info';
      unset($_SESSION['gl_msg'], $_SESSION['gl_type']);
      
      $grade_levels = $pdo->query("SELECT * FROM grade_levels ORDER BY level_number")->fetchAll();
      $academic_years = $pdo->query("SELECT * FROM academic_years ORDER BY id DESC")->fetchAll();
      $currentLevel = null;
      
      if ($gl_id) {
          $stmt = $pdo->prepare("SELECT * FROM grade_levels WHERE id=?");
          $stmt->execute([$gl_id]);
          $currentLevel = $stmt->fetch();
      }
      
      $classes = [];
      if ($gl_id) {
          $stmt = $pdo->prepare("SELECT c.class_name, c.grade_level_id,
              COUNT(DISTINCT c.id) AS sections_count,
              COUNT(DISTINCT s.id) AS students_count,
              GROUP_CONCAT(DISTINCT c.section ORDER BY c.section SEPARATOR ',') AS sections_list
              FROM classes c 
              LEFT JOIN students s ON s.class_id=c.id
              WHERE c.grade_level_id=? 
              GROUP BY c.class_name, c.grade_level_id
              ORDER BY c.class_name");
          $stmt->execute([$gl_id]);
          $classes = $stmt->fetchAll();
      }
  ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="page-title mb-0">
        <span class="icon">🏛️</span> الفصول والشعب
        <?php if ($currentLevel): ?> - <?= h($currentLevel['level_name']) ?>
          <?php if ($currentLevel['academic_year']): ?>
            <small class="text-muted">(<?= h($currentLevel['academic_year']) ?>)</small>
          <?php endif; ?>
        <?php endif; ?>
      </h3>
      <div>
        <?php if ($gl_id): ?>
          <button class="btn btn-success" onclick="openModal('addClassWithSectionsModal')">➕ إضافة فصل مع شعب</button>
        <?php endif; ?>
        <a href="?page=grade_levels" class="btn btn-secondary">← رجوع للسنوات</a>
      </div>
    </div>

    <?php if ($gl_msg): ?><div class="alert alert-<?= h($gl_type) ?>"><?= h($gl_msg) ?></div><?php endif; ?>

    <div class="report-filter">
      <form method="get" class="row g-2 align-items-end">
        <input type="hidden" name="page" value="classes">
        <div class="col-md-8">
          <label class="form-label">اختر السنة الدراسية</label>
          <select name="grade_level_id" class="form-select" onchange="this.form.submit()">
            <option value="">-- اختر --</option>
            <?php foreach ($grade_levels as $gl): ?>
              <option value="<?= $gl['id'] ?>" <?= $gl_id==$gl['id']?'selected':'' ?>>
                رقم <?= $gl['level_number'] ?> - <?= h($gl['level_name']) ?>
                <?= $gl['academic_year'] ? ' ('.h($gl['academic_year']).')' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <button class="btn btn-primary btn-block">🔍 عرض</button>
        </div>
      </form>
    </div>

    <?php if ($gl_id && $currentLevel): ?>
      <div class="row g-3">
        <?php if (!$classes): ?>
          <div class="col-12"><div class="alert alert-warning">لا توجد فصول. اضغط "إضافة فصل مع شعب".</div></div>
        <?php endif; ?>
        <?php foreach ($classes as $c): ?>
          <div class="col-md-6 col-lg-4">
            <div class="card h-100" style="border-right: 5px solid #3b82f6;">
              <div class="card-body">
                <h5 class="mb-2">🏛️ <?= h($c['class_name']) ?></h5>
                <div class="mb-2">
                  <span class="badge bg-primary"><?= $c['students_count'] ?> طالب</span>
                  <span class="badge bg-info"><?= $c['sections_count'] ?> شعبة</span>
                </div>
                <div class="small text-muted mb-2">
                  الشعب: <?= h($c['sections_list'] ?: '-') ?>
                </div>
                <div class="d-flex gap-1 mt-3 flex-wrap">
                  <a href="?page=class_sections&class_name=<?= urlencode($c['class_name']) ?>&grade_level_id=<?= $gl_id ?>" class="btn btn-sm btn-primary flex-fill">
                    👥 عرض الشعب
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- Modal إضافة فصل مع شعب -->
    <div class="modal-overlay" id="addClassWithSectionsModal">
      <div class="modal-box">
        <form method="post">
          <input type="hidden" name="action" value="add_class_with_sections">
          <input type="hidden" name="grade_level_id" value="<?= $gl_id ?>">
          <div class="modal-header">
            <h5>➕ إضافة فصل مع شعب</h5>
            <button type="button" class="modal-close" onclick="closeModal('addClassWithSectionsModal')">×</button>
          </div>
          <div class="modal-body">
            <div class="row g-2">
              <div class="col-md-12">
                <label class="form-label">اسم الفصل *</label>
                <input name="class_name" class="form-control" required placeholder="مثال: الصف الأول">
              </div>
              <div class="col-md-12">
                <label class="form-label">الشعب (مفصولة بفاصلة) *</label>
                <input name="sections" class="form-control" required placeholder="مثال: أ,ب,ج,د">
                <small class="text-muted">سيتم إنشاء شعبة لكل اسم: الصف الأول - أ، الصف الأول - ب ...</small>
              </div>
              <div class="col-md-12">
                <label class="form-label">السنة الدراسية</label>
                <select name="academic_year" class="form-select">
                  <?php foreach ($academic_years as $y): ?>
                    <option value="<?= h($y['year_name']) ?>"><?= h($y['year_name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>
          <div class="modal-footer"><button class="btn btn-primary">حفظ</button></div>
        </form>
      </div>
    </div>

  <?php
  /* ============================================================
     3) عرض الشعب التابعة لفصل معين
     ============================================================ */
  elseif ($page === 'class_sections' && isDataEntry()):
      $class_name = $_GET['class_name'] ?? '';
      $gl_id = (int)($_GET['grade_level_id'] ?? 0);
      
      $sections = $pdo->prepare("SELECT c.*, 
          (SELECT COUNT(*) FROM students s WHERE s.class_id=c.id) AS students_count,
          (SELECT COUNT(*) FROM subjects sub WHERE sub.class_id=c.id) AS subjects_count
          FROM classes c 
          WHERE c.class_name=? AND c.grade_level_id=? 
          ORDER BY c.section");
      $sections->execute([$class_name, $gl_id]);
      $sections_list = $sections->fetchAll();
      
      $currentLevel = null;
      if ($gl_id) {
          $stmt = $pdo->prepare("SELECT * FROM grade_levels WHERE id=?");
          $stmt->execute([$gl_id]);
          $currentLevel = $stmt->fetch();
      }
  ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="page-title mb-0">
        <span class="icon">👥</span> شعب <?= h($class_name) ?>
        <?php if ($currentLevel): ?> - <?= h($currentLevel['level_name']) ?>
          <?php if ($currentLevel['academic_year']): ?>
            <small class="text-muted">(<?= h($currentLevel['academic_year']) ?>)</small>
          <?php endif; ?>
        <?php endif; ?>
      </h3>
      <a href="?page=classes&grade_level_id=<?= $gl_id ?>" class="btn btn-secondary">← رجوع للفصول</a>
    </div>

    <div class="alert alert-info">
      ℹ️ اختر الشعبة للدخول إلى موادها الدراسية وجدولها الدراسي.
    </div>

    <div class="row g-3">
      <?php if (!$sections_list): ?>
        <div class="col-12"><div class="alert alert-warning">لا توجد شعب لهذا الفصل.</div></div>
      <?php endif; ?>
      <?php foreach ($sections_list as $s): ?>
        <div class="col-md-4 col-sm-6">
          <div class="section-card">
            <div class="text-center">
              <div style="font-size: 2.5rem; font-weight: bold; color: #10b981; line-height: 1;">
                <?= h($s['section'] ?: '—') ?>
              </div>
              <h5 class="mt-2"><?= h($s['class_name']) ?></h5>
              <?php if ($s['section']): ?>
                <div class="text-muted small">الشعبة: <?= h($s['section']) ?></div>
              <?php endif; ?>
              <div class="mt-3">
                <span class="badge bg-primary"><?= $s['students_count'] ?> طالب</span>
                <span class="badge bg-info"><?= $s['subjects_count'] ?> مادة</span>
              </div>
            </div>
            <div class="mt-3 d-grid gap-2">
              <a href="?page=class_students&class_id=<?= $s['id'] ?>" class="btn btn-sm btn-success">🎓 عرض الطلاب</a>
              <a href="?page=subjects&class_id=<?= $s['id'] ?>" class="btn btn-sm btn-primary">📖 المواد الدراسية</a>
              <a href="?page=timetables&class_id=<?= $s['id'] ?>" class="btn btn-sm btn-info">📅 الجدول الدراسي</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

  <?php
  /* ============================================================
     4) المواد الدراسية - مقسمة محلية/دولية حسب الفصل
     ============================================================ */
  elseif ($page === 'subjects' && isDataEntry()):
      $class_id = (int)($_GET['class_id'] ?? 0);
      $academic_years = $pdo->query("SELECT * FROM academic_years ORDER BY id DESC")->fetchAll();
      
      $all_classes = $pdo->query("SELECT c.*, gl.level_name FROM classes c 
          LEFT JOIN grade_levels gl ON gl.id=c.grade_level_id 
          ORDER BY gl.level_number, c.class_name, c.section")->fetchAll();
      
      $currentClass = null;
      $local_subjects = [];
      $international_subjects = [];
      
      if ($class_id) {
          $stmt = $pdo->prepare("SELECT c.*, gl.level_name, gl.id AS gl_id FROM classes c 
              LEFT JOIN grade_levels gl ON gl.id=c.grade_level_id WHERE c.id=?");
          $stmt->execute([$class_id]);
          $currentClass = $stmt->fetch();
          
          if ($currentClass) {
              $local_subjects = getSubjectsByClass($pdo, $class_id, 'local');
              $international_subjects = getSubjectsByClass($pdo, $class_id, 'international');
          }
      }
      
      $subj_msg = $_SESSION['subj_msg'] ?? '';
      $subj_type = $_SESSION['subj_type'] ?? 'info';
      unset($_SESSION['subj_msg'], $_SESSION['subj_type']);
  ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="page-title mb-0">
        <span class="icon">📖</span> المواد الدراسية
        <?php if ($currentClass): ?>
          - <?= h($currentClass['class_name']) ?>
        <?php endif; ?>
      </h3>
      <div>
        <?php if ($class_id): ?>
          <button class="btn btn-primary" onclick="openModal('addSubjectModal')">➕ إضافة مادة</button>
        <?php endif; ?>
        <?php if ($currentClass): ?>
          <a href="?page=class_sections&class_name=<?= urlencode($currentClass['class_name']) ?>&grade_level_id=<?= $currentClass['gl_id'] ?>" class="btn btn-secondary">← رجوع للشعب</a>
        <?php else: ?>
          <a href="?page=classes" class="btn btn-secondary">← رجوع</a>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($subj_msg): ?><div class="alert alert-<?= h($subj_type) ?>"><?= h($subj_msg) ?></div><?php endif; ?>

    <div class="report-filter">
      <form method="get" class="row g-2 align-items-end">
        <input type="hidden" name="page" value="subjects">
        <div class="col-md-8">
          <label class="form-label">اختر الفصل/الشعبة</label>
          <select name="class_id" class="form-select" onchange="this.form.submit()">
            <option value="">-- اختر الفصل --</option>
            <?php foreach ($all_classes as $c): ?>
              <option value="<?= $c['id'] ?>" <?= $class_id==$c['id']?'selected':'' ?>>
                <?= h($c['class_name']) ?> <?= $c['section'] ? '- '.h($c['section']) : '' ?> (<?= h($c['level_name'] ?? '') ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <button class="btn btn-primary btn-block">🔍 عرض المواد</button>
        </div>
      </form>
    </div>

    <?php if ($class_id && $currentClass): ?>
      <!-- المواد المحلية -->
      <div class="card mb-4">
        <div class="card-header" style="background: linear-gradient(135deg, #3b82f6, #1e40af); color: #fff;">
          🏠 المواد المحلية (<?= count($local_subjects) ?>)
        </div>
        <div class="card-body">
          <?php if (!$local_subjects): ?>
            <p class="text-muted">لا توجد مواد محلية لهذا الفصل.</p>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($local_subjects as $sub): ?>
                <div class="col-md-4 col-sm-6">
                  <div class="subject-card" style="border-right: 4px solid #3b82f6;">
                    <div class="subject-name">📖 <?= h($sub['subject_name']) ?></div>
                    <div class="subject-info">
                      <div>📅 السنة: <?= h($sub['academic_year']) ?></div>
                      <div>🎯 الدرجة العظمى: <?= $sub['max_grade'] ?></div>
                      <div>📝 أعمال السنة: <?= $sub['coursework_max'] ?></div>
                      <div>📋 النهائي: <?= $sub['final_max'] ?></div>
                    </div>
                    <div class="mt-2 d-flex gap-1">
                      <button class="btn btn-sm btn-warning" onclick='editSubject(<?= json_encode($sub, JSON_UNESCAPED_UNICODE) ?>)'>✏️</button>
                      <?php if (isAdmin()): ?>
                      <form method="post" style="display:inline" onsubmit="return confirm('حذف المادة؟')">
                        <input type="hidden" name="action" value="del_subject_grade">
                        <input type="hidden" name="id" value="<?= $sub['id'] ?>">
                        <input type="hidden" name="class_id" value="<?= $class_id ?>">
                        <button class="btn btn-sm btn-danger">🗑️</button>
                      </form>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- المواد الدولية -->
      <div class="card">
        <div class="card-header" style="background: linear-gradient(135deg, #8b5cf6, #6d28d9); color: #fff;">
          🌍 المواد الدولية (<?= count($international_subjects) ?>)
        </div>
        <div class="card-body">
          <?php if (!$international_subjects): ?>
            <p class="text-muted">لا توجد مواد دولية لهذا الفصل.</p>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($international_subjects as $sub): ?>
                <div class="col-md-4 col-sm-6">
                  <div class="subject-card" style="border-right: 4px solid #8b5cf6;">
                    <div class="subject-name">🌍 <?= h($sub['subject_name']) ?></div>
                    <div class="subject-info">
                      <div>📅 السنة: <?= h($sub['academic_year']) ?></div>
                      <div>🎯 الدرجة العظمى: <?= $sub['max_grade'] ?></div>
                      <div>📝 أعمال السنة: <?= $sub['coursework_max'] ?></div>
                      <div>📋 النهائي: <?= $sub['final_max'] ?></div>
                    </div>
                    <div class="mt-2 d-flex gap-1">
                      <button class="btn btn-sm btn-warning" onclick='editSubject(<?= json_encode($sub, JSON_UNESCAPED_UNICODE) ?>)'>✏️</button>
                      <?php if (isAdmin()): ?>
                      <form method="post" style="display:inline" onsubmit="return confirm('حذف المادة؟')">
                        <input type="hidden" name="action" value="del_subject_grade">
                        <input type="hidden" name="id" value="<?= $sub['id'] ?>">
                        <input type="hidden" name="class_id" value="<?= $class_id ?>">
                        <button class="btn btn-sm btn-danger">🗑️</button>
                      </form>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php else: ?>
      <div class="alert alert-info">اختر فصلاً من القائمة أعلاه لعرض مواده الدراسية.</div>
    <?php endif; ?>

    <!-- Modal إضافة مادة -->
    <div class="modal-overlay" id="addSubjectModal">
      <div class="modal-box">
        <form method="post">
          <input type="hidden" name="action" value="add_subject_to_class">
          <input type="hidden" name="class_id" value="<?= $class_id ?>">
          <input type="hidden" name="grade_level_id" value="<?= $currentClass['gl_id'] ?? 0 ?>">
          <div class="modal-header">
            <h5>📖 إضافة مادة دراسية</h5>
            <button type="button" class="modal-close" onclick="closeModal('addSubjectModal')">×</button>
          </div>
          <div class="modal-body">
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label">اسم المادة *</label>
                <input name="subject_name" class="form-control" required placeholder="مثال: الرياضيات">
              </div>
              <div class="col-md-6">
                <label class="form-label">نوع المادة *</label>
                <select name="subject_type" class="form-select" required>
                  <option value="local">🏠 محلية (راسب - ممتاز)</option>
                  <option value="international">🌍 دولية (A - F)</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">السنة الدراسية *</label>
                <select name="academic_year" class="form-select" required>
                  <?php foreach ($academic_years as $y): ?>
                    <option value="<?= h($y['year_name']) ?>"><?= h($y['year_name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">الدرجة العظمى</label>
                <input type="number" name="max_grade" class="form-control" value="100">
              </div>
              <div class="col-md-6">
                <label class="form-label">أعمال السنة</label>
                <input type="number" name="coursework_max" class="form-control" value="40">
              </div>
              <div class="col-md-6">
                <label class="form-label">الامتحان النهائي</label>
                <input type="number" name="final_max" class="form-control" value="60">
              </div>
            </div>
          </div>
          <div class="modal-footer"><button class="btn btn-primary">حفظ</button></div>
        </form>
      </div>
    </div>

    <!-- Modal تعديل مادة -->
    <div class="modal-overlay" id="editSubjectModal">
      <div class="modal-box">
        <form method="post">
          <input type="hidden" name="action" value="edit_subject_grade">
          <input type="hidden" name="id" id="esub_id">
          <input type="hidden" name="class_id" value="<?= $class_id ?>">
          <input type="hidden" name="grade_level_id" value="<?= $currentClass['gl_id'] ?? 0 ?>">
          <div class="modal-header">
            <h5>✏️ تعديل المادة</h5>
            <button type="button" class="modal-close" onclick="closeModal('editSubjectModal')">×</button>
          </div>
          <div class="modal-body">
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label">اسم المادة *</label>
                <input name="subject_name" id="esub_name" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">نوع المادة *</label>
                <select name="subject_type" id="esub_type" class="form-select" required>
                  <option value="local">🏠 محلية</option>
                  <option value="international">🌍 دولية</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">السنة الدراسية *</label>
                <input name="academic_year" id="esub_year" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">الدرجة العظمى</label>
                <input type="number" name="max_grade" id="esub_max" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label">أعمال السنة</label>
                <input type="number" name="coursework_max" id="esub_cw" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label">الامتحان النهائي</label>
                <input type="number" name="final_max" id="esub_final" class="form-control">
              </div>
            </div>
          </div>
          <div class="modal-footer"><button class="btn btn-primary">تحديث</button></div>
        </form>
      </div>
    </div>

    <script>
    function editSubject(d){
      document.getElementById('esub_id').value=d.id;
      document.getElementById('esub_name').value=d.subject_name||'';
      document.getElementById('esub_type').value=d.subject_type||'local';
      document.getElementById('esub_year').value=d.academic_year||'';
      document.getElementById('esub_max').value=d.max_grade||100;
      document.getElementById('esub_cw').value=d.coursework_max||40;
      document.getElementById('esub_final').value=d.final_max||60;
      openModal('editSubjectModal');
    }
    </script>

  <?php
  /* ============================================================
     5) الجدول الدراسي - حسب الفصل مع اختيار المدرس ورقم الحصة والتوقيت
     ============================================================ */
  elseif ($page === 'timetables' && isDataEntry()):
      $class_id = (int)($_GET['class_id'] ?? 0);
      
      $all_classes = $pdo->query("SELECT c.*, gl.level_name FROM classes c 
          LEFT JOIN grade_levels gl ON gl.id=c.grade_level_id 
          ORDER BY gl.level_number, c.class_name, c.section")->fetchAll();
      $teachers_list = $pdo->query("SELECT id, name, employee_code, subject FROM teachers ORDER BY name")->fetchAll();
      
      $all_subjects_by_class = [];
      $subjects_raw = $pdo->query("SELECT id, subject_name, class_id, subject_type, academic_year FROM subjects ORDER BY subject_name")->fetchAll();
      foreach ($subjects_raw as $sub) {
          $all_subjects_by_class[$sub['class_id']][] = $sub;
      }
      
      $currentClass = null;
      $timetables = [];
      $periodTimes = getDefaultPeriodTimes();
      
      if ($class_id) {
          $stmt = $pdo->prepare("SELECT c.*, gl.level_name, gl.id AS gl_id FROM classes c 
              LEFT JOIN grade_levels gl ON gl.id=c.grade_level_id WHERE c.id=?");
          $stmt->execute([$class_id]);
          $currentClass = $stmt->fetch();
          
          if ($currentClass) {
              $timetables = getTimetableByClass($pdo, $class_id);
          }
      }
      
      $school_name = getSetting($pdo, 'school_name', 'المدرسة النموذجية');
      $days = ['الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
      
      $grid = [];
      $maxPeriod = 0;
      foreach ($timetables as $t) {
          $day = $t['day_of_week'] ?: 'غير محدد';
          $period = (int)$t['period_number'];
          if (!isset($grid[$day])) $grid[$day] = [];
          $grid[$day][$period] = $t;
          if ($period > $maxPeriod) $maxPeriod = $period;
      }
      $periodsCount = max($maxPeriod, 6);
      
      $tt_msg = $_SESSION['tt_msg'] ?? '';
      $tt_type = $_SESSION['tt_type'] ?? 'info';
      unset($_SESSION['tt_msg'], $_SESSION['tt_type']);
  ?>
    <div class="print-header">
      <h2>🏫 <?= h($school_name) ?></h2>
      <h3>📅 الجدول الدراسي الأسبوعي</h3>
      <?php if ($currentClass): ?>
        <p>الفصل: <?= h($currentClass['class_name']) ?> - <?= h($currentClass['level_name'] ?? '') ?></p>
      <?php endif; ?>
      <p>التاريخ: <?= date('Y-m-d') ?></p>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
      <h3 class="page-title mb-0">
        <span class="icon">📅</span> الجدول الدراسي
        <?php if ($currentClass): ?> - <?= h($currentClass['class_name']) ?><?php endif; ?>
      </h3>
      <div>
        <?php if ($class_id): ?>
          <button class="btn btn-primary" onclick="openModal('addTTModal')">➕ إضافة حصة</button>
          <button onclick="window.print()" class="btn btn-info">🖨️ طباعة</button>
        <?php endif; ?>
        <?php if ($currentClass): ?>
          <a href="?page=class_sections&class_name=<?= urlencode($currentClass['class_name']) ?>&grade_level_id=<?= $currentClass['gl_id'] ?>" class="btn btn-secondary">← رجوع</a>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($tt_msg): ?><div class="alert alert-<?= h($tt_type) ?>"><?= h($tt_msg) ?></div><?php endif; ?>

    <div class="report-filter no-print">
      <form method="get" class="row g-2 align-items-end">
        <input type="hidden" name="page" value="timetables">
        <div class="col-md-8">
          <label class="form-label">اختر الفصل/الشعبة</label>
          <select name="class_id" class="form-select" onchange="this.form.submit()">
            <option value="">-- اختر الفصل --</option>
            <?php foreach ($all_classes as $c): ?>
              <option value="<?= $c['id'] ?>" <?= $class_id==$c['id']?'selected':'' ?>>
                <?= h($c['class_name']) ?> <?= $c['section'] ? '- '.h($c['section']) : '' ?> (<?= h($c['level_name'] ?? '') ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <button class="btn btn-primary btn-block">🔍 عرض الجدول</button>
        </div>
      </form>
    </div>

    <?php if ($class_id && $currentClass): ?>
      <?php if (!$timetables): ?>
        <div class="alert alert-warning">لا توجد حصص مسجلة لهذا الفصل. اضغط "إضافة حصة" للبدء.</div>
      <?php else: ?>
        <div class="card">
          <div class="card-header">📅 الجدول الأسبوعي - <?= h($currentClass['class_name']) ?></div>
          <div class="card-body table-responsive">
            <table class="tt-grid">
              <thead>
                <tr>
                  <th style="width:100px;">اليوم</th>
                  <?php for ($p = 1; $p <= $periodsCount; $p++): ?>
                    <th>
                      الحصة <?= $p ?><br>
                      <small style="font-weight: normal; font-size: 0.7rem;">
                        <?= $periodTimes[$p]['start'] ?? '' ?> - <?= $periodTimes[$p]['end'] ?? '' ?>
                      </small>
                    </th>
                  <?php endfor; ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($days as $day): ?>
                  <tr>
                    <td><strong><?= $day ?></strong></td>
                    <?php for ($p = 1; $p <= $periodsCount; $p++): ?>
                      <td style="padding: 4px;">
                        <?php if (!empty($grid[$day][$p])): $entry = $grid[$day][$p]; ?>
                          <div class="tt-cell">
                            <span class="subject">📚 <?= h($entry['subject_name']) ?></span>
                            <span class="teacher">👨‍🏫 <?= h($entry['teacher_name'] ?? 'غير محدد') ?></span>
                            <?php if ($entry['start_time']): ?>
                              <span class="teacher" style="color:#10b981;">⏰ <?= h($entry['start_time']) ?> - <?= h($entry['end_time']) ?></span>
                            <?php endif; ?>
                            <div class="no-print mt-2">
                              <form method="post" style="display:inline" onsubmit="return confirm('حذف هذه الحصة؟')">
                                <input type="hidden" name="action" value="del_timetable">
                                <input type="hidden" name="id" value="<?= $entry['id'] ?>">
                                <input type="hidden" name="class_id" value="<?= $class_id ?>">
                                <button class="btn btn-sm btn-danger" style="padding:2px 6px; font-size:0.7rem;">🗑️</button>
                              </form>
                            </div>
                          </div>
                        <?php else: ?>
                          <span class="tt-empty">—</span>
                        <?php endif; ?>
                      </td>
                    <?php endfor; ?>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card">
          <div class="card-header">📋 قائمة الحصص التفصيلية</div>
          <div class="card-body table-responsive">
            <table class="table table-bordered">
              <thead>
                <tr>
                  <th>#</th><th>اليوم</th><th>الحصة</th><th>التوقيت</th><th>المادة</th><th>المدرس</th><th class="no-print">إجراءات</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($timetables as $i => $t): ?>
                  <tr>
                    <td><?= $i+1 ?></td>
                    <td><span class="badge bg-primary"><?= h($t['day_of_week'] ?: '-') ?></span></td>
                    <td><span class="badge bg-info">الحصة <?= $t['period_number'] ?></span></td>
                    <td>
                      <?php if ($t['start_time']): ?>
                        <span class="badge bg-success"><?= h($t['start_time']) ?> - <?= h($t['end_time']) ?></span>
                      <?php else: ?>-<?php endif; ?>
                    </td>
                    <td><strong><?= h($t['subject_name']) ?></strong></td>
                    <td><?= h($t['teacher_name'] ?? '-') ?> <small class="text-muted">(<?= h($t['teacher_code'] ?? '') ?>)</small></td>
                    <td class="no-print">
                      <form method="post" style="display:inline" onsubmit="return confirm('حذف هذه الحصة؟')">
                        <input type="hidden" name="action" value="del_timetable">
                        <input type="hidden" name="id" value="<?= $t['id'] ?>">
                        <input type="hidden" name="class_id" value="<?= $class_id ?>">
                        <button class="btn btn-sm btn-danger">🗑️</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>
    <?php else: ?>
      <div class="alert alert-info">اختر فصلاً من القائمة أعلاه لعرض جدوله الدراسي.</div>
    <?php endif; ?>

    <!-- Modal إضافة حصة -->
    <div class="modal-overlay" id="addTTModal">
      <div class="modal-box modal-lg">
        <form method="post">
          <input type="hidden" name="action" value="add_timetable_full">
          <input type="hidden" name="class_id" value="<?= $class_id ?>">
          <input type="hidden" name="grade_level_id" value="<?= $currentClass['gl_id'] ?? 0 ?>">
          <div class="modal-header">
            <h5>➕ إضافة حصة للجدول</h5>
            <button type="button" class="modal-close" onclick="closeModal('addTTModal')">×</button>
          </div>
          <div class="modal-body">
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label">المدرس *</label>
                <select name="teacher_id" class="form-select" required>
                  <option value="">-- اختر المدرس --</option>
                  <?php foreach ($teachers_list as $t): ?>
                    <option value="<?= $t['id'] ?>">
                      <?= h($t['name']) ?> <?= $t['subject'] ? '('.h($t['subject']).')' : '' ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">المادة *</label>
                <select name="subject_name" id="tt_subject" class="form-select" required>
                  <option value="">-- اختر المادة --</option>
                  <?php 
                  $class_subjects = $all_subjects_by_class[$class_id] ?? [];
                  foreach ($class_subjects as $sub): ?>
                    <option value="<?= h($sub['subject_name']) ?>">
                      <?= h($sub['subject_name']) ?> 
                      <?= $sub['subject_type'] === 'international' ? '🌍' : '🏠' ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <?php if (empty($class_subjects)): ?>
                  <small class="text-danger">⚠️ لا توجد مواد مسجلة لهذا الفصل. قم بإضافة المواد أولاً.</small>
                <?php endif; ?>
              </div>
              <div class="col-md-6">
                <label class="form-label">اليوم *</label>
                <select name="day_of_week" class="form-select" required>
                  <option value="">-- اختر --</option>
                  <?php foreach ($days as $d): ?><option value="<?= $d ?>"><?= $d ?></option><?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">رقم الحصة *</label>
                <select name="period_number" id="tt_period" class="form-select" required onchange="updatePeriodTimes()">
                  <?php for ($p = 1; $p <= 8; $p++): ?>
                    <option value="<?= $p ?>">الحصة <?= $p ?></option>
                  <?php endfor; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">وقت البداية *</label>
                <input type="time" name="start_time" id="tt_start" class="form-control" required value="08:00">
              </div>
              <div class="col-md-6">
                <label class="form-label">وقت النهاية *</label>
                <input type="time" name="end_time" id="tt_end" class="form-control" required value="08:45">
              </div>
              <div class="col-12">
                <label class="form-label">ملاحظات</label>
                <input name="notes" class="form-control" placeholder="اختياري">
              </div>
            </div>
          </div>
          <div class="modal-footer"><button class="btn btn-primary">حفظ</button></div>
        </form>
      </div>
    </div>

    <script>
    const periodTimes = <?= json_encode($periodTimes) ?>;
    const subjectsByClass = <?= json_encode($all_subjects_by_class, JSON_UNESCAPED_UNICODE) ?>;
    
    function updatePeriodTimes(){
        const p = document.getElementById('tt_period').value;
        if(periodTimes[p]){
            document.getElementById('tt_start').value = periodTimes[p].start;
            document.getElementById('tt_end').value = periodTimes[p].end;
        }
    }
    
    function loadSubjectsForClass(className){
        const classSelect = document.querySelector('select[name="class_id"]');
        if (!classSelect) return;
        
        const classId = classSelect.value;
        const subjectSelect = document.getElementById('tt_subject');
        if (!subjectSelect) return;
        
        const subjects = subjectsByClass[classId] || [];
        subjectSelect.innerHTML = '<option value="">-- اختر المادة --</option>';
        subjects.forEach(sub => {
            const typeIcon = sub.subject_type === 'international' ? '🌍' : '🏠';
            subjectSelect.innerHTML += `<option value="${sub.subject_name}">${sub.subject_name} ${typeIcon}</option>`;
        });
        
        if (subjects.length === 0) {
            subjectSelect.innerHTML = '<option value="">لا توجد مواد مسجلة لهذا الفصل</option>';
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const originalOpenModal = window.openModal;
        window.openModal = function(id) {
            if (id === 'addTTModal') {
                loadSubjectsForClass();
            }
            originalOpenModal(id);
        };
    });
    </script>

  <?php
  /* ============================================================
     عرض طلاب الفصل
     ============================================================ */
  elseif ($page === 'class_students' && isDataEntry()):
      $class_id = (int)($_GET['class_id'] ?? 0);
      $stmt = $pdo->prepare("SELECT c.*, gl.level_name, gl.level_number 
          FROM classes c LEFT JOIN grade_levels gl ON gl.id=c.grade_level_id WHERE c.id=?");
      $stmt->execute([$class_id]);
      $class = $stmt->fetch();

      $students = [];
      if ($class) {
          $stmt = $pdo->prepare("SELECT s.* FROM students s WHERE s.class_id=? ORDER BY s.name");
          $stmt->execute([$class_id]);
          $students = $stmt->fetchAll();
      }

      $school_name = getSetting($pdo, 'school_name', 'المدرسة النموذجية');
  ?>
    <div class="print-header">
      <h2>🏫 <?= h($school_name) ?></h2>
      <h3>📋 كشف بيانات طلاب الفصل</h3>
      <p><?= h($class['class_name'] ?? '-') ?> <?= $class['section'] ? '- '.h($class['section']) : '' ?></p>
      <p>عدد الطلاب: <?= count($students) ?> - التاريخ: <?= date('Y-m-d') ?></p>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
      <h3 class="page-title mb-0">
        <span class="icon">👥</span> طلاب الفصل: <?= h($class['class_name'] ?? '-') ?> <?= $class['section'] ? '- '.h($class['section']) : '' ?>
      </h3>
      <div>
        <button onclick="window.print()" class="btn btn-primary">🖨️ طباعة كشف البيانات</button>
        <a href="?page=class_sections&class_name=<?= urlencode($class['class_name']) ?>&grade_level_id=<?= $class['grade_level_id'] ?>" class="btn btn-secondary">← رجوع</a>
      </div>
    </div>

    <?php if (!$class): ?>
      <div class="alert alert-danger">⚠️ الفصل غير موجود</div>
    <?php else: ?>
      <div class="row g-3 mb-3 no-print">
        <div class="col-md-3 col-sm-6">
          <div class="stat-card bg-grad-1">
            <span class="icon">🎓</span>
            <div><h2><?= count($students) ?></h2><span>عدد الطلاب</span></div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="stat-card bg-grad-3">
            <span class="icon">♂️</span>
            <div><h2><?= count(array_filter($students, fn($s) => $s['gender']=='male')) ?></h2><span>ذكور</span></div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="stat-card bg-grad-5">
            <span class="icon">♀️</span>
            <div><h2><?= count(array_filter($students, fn($s) => $s['gender']=='female')) ?></h2><span>إناث</span></div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="stat-card bg-grad-4">
            <span class="icon">📊</span>
            <div><h2><?= h($class['level_name'] ?? '-') ?></h2><span>المستوى</span></div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-body table-responsive">
          <table class="table table-bordered">
            <thead>
              <tr>
                <th>#</th><th>الكود</th><th>الاسم</th><th>الرقم الوطني</th><th>تاريخ الميلاد</th>
                <th>الجنس</th><th>السنة الدراسية</th><th>ولي الأمر</th><th>الهاتف</th><th>الحالة</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$students): ?>
                <tr><td colspan="10" class="text-center text-muted py-4">لا يوجد طلاب في هذا الفصل</td></tr>
              <?php endif; ?>
              <?php foreach ($students as $i => $s): ?>
                <tr>
                  <td><?= $i+1 ?></td>
                  <td><span class="badge bg-dark"><?= h($s['student_code']) ?></span></td>
                  <td><?= h($s['name']) ?></td>
                  <td><?= h($s['national_id']) ?></td>
                  <td><?= h($s['birth_date']) ?></td>
                  <td><?= $s['gender']=='male'?'ذكر':'أنثى' ?></td>
                  <td><?= h($s['academic_year']) ?></td>
                  <td><?= h($s['guardian_name']) ?></td>
                  <td><?= h($s['guardian_phone']) ?></td>
                  <td>
                    <?php 
                    $statusMap = ['active'=>['bg-success','نشط'],'graduated'=>['bg-primary','متخرج'],'failed'=>['bg-danger','راسب'],'withdrawn'=>['bg-secondary','منسحب']];
                    $st = $statusMap[$s['status'] ?? 'active'] ?? ['bg-secondary','-'];
                    ?>
                    <span class="badge <?= $st[0] ?>"><?= $st[1] ?></span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

  <?php
  /* ============================================================
     ترقية الطلبة
     ============================================================ */
  elseif ($page === 'promote' && isDataEntry()):
      $gl_id = (int)($_GET['grade_level_id'] ?? 0);
      $academic_year = $_GET['academic_year'] ?? '';
      $grade_levels = $pdo->query("SELECT * FROM grade_levels ORDER BY level_number")->fetchAll();
      $academic_years = $pdo->query("SELECT year_name FROM academic_years ORDER BY id DESC")->fetchAll();
      $pass_grade = (float)getSetting($pdo, 'pass_grade', 50);

      $promote_msg = $_SESSION['promote_msg'] ?? '';
      $promote_type = $_SESSION['promote_type'] ?? 'info';
      unset($_SESSION['promote_msg'], $_SESSION['promote_type']);

      $students_info = [];
      $currentLevel = null;
      $nextLevel = null;

      if ($gl_id && $academic_year) {
          $stmt = $pdo->prepare("SELECT * FROM grade_levels WHERE id=?");
          $stmt->execute([$gl_id]);
          $currentLevel = $stmt->fetch();

          $stmt = $pdo->prepare("SELECT * FROM grade_levels WHERE level_number = (SELECT level_number+1 FROM grade_levels WHERE id=?)");
          $stmt->execute([$gl_id]);
          $nextLevel = $stmt->fetch();

          $stmt = $pdo->prepare("SELECT s.*, c.class_name, c.section AS sec 
              FROM students s JOIN classes c ON c.id=s.class_id 
              WHERE c.grade_level_id=? AND s.status='active' ORDER BY s.name");
          $stmt->execute([$gl_id]);
          $students_list = $stmt->fetchAll();

          foreach ($students_list as $s) {
              $avg = getStudentAverage($pdo, $s['id'], $academic_year);
              $students_info[] = [
                  'student' => $s,
                  'avg' => $avg,
                  'passed' => $avg >= $pass_grade
              ];
          }
      }
  ?>
    <h3 class="page-title"><span class="icon">⬆️</span> ترقية الطلبة للسنة التالية</h3>

    <?php if ($promote_msg): ?><div class="alert alert-<?= h($promote_type) ?>"><?= h($promote_msg) ?></div><?php endif; ?>

    <div class="alert alert-info">
      <strong>ℹ️ آلية الترقية:</strong><br>
      • يتم حساب معدل الطالب من درجاته في السنة المحددة<br>
      • الطالب يعتبر ناجحاً إذا كان معدله ≥ <strong><?= $pass_grade ?>%</strong><br>
      • عند الترقية ينتقل الطالب تلقائياً للفصل المقابل (نفس الشعبة) في المستوى التالي
    </div>

    <div class="report-filter">
      <form method="get" class="row g-2 align-items-end">
        <input type="hidden" name="page" value="promote">
        <div class="col-md-4">
          <label class="form-label">المستوى الحالي (من)</label>
          <select name="grade_level_id" class="form-select" required>
            <option value="">-- اختر --</option>
            <?php foreach ($grade_levels as $gl): ?>
              <option value="<?= $gl['id'] ?>" <?= $gl_id==$gl['id']?'selected':'' ?>>
                رقم <?= $gl['level_number'] ?> - <?= h($gl['level_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">السنة الدراسية المنجزة</label>
          <select name="academic_year" class="form-select" required>
            <option value="">-- اختر --</option>
            <?php foreach ($academic_years as $y): ?>
              <option value="<?= h($y['year_name']) ?>" <?= $academic_year==$y['year_name']?'selected':'' ?>>
                <?= h($y['year_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <button class="btn btn-primary btn-block">🔍 عرض الطلبة</button>
        </div>
      </form>
    </div>

    <?php if ($gl_id && $academic_year && $currentLevel): ?>
      <div class="card mb-3">
        <div class="card-header">
          🎯 ترقية من: <?= h($currentLevel['level_name']) ?>
          <?php if ($nextLevel): ?>
            → إلى: <span class="text-success"><?= h($nextLevel['level_name']) ?></span>
          <?php else: ?>
            <span class="text-danger">(لا يوجد مستوى أعلى)</span>
          <?php endif; ?>
        </div>
      </div>

      <?php if (!$nextLevel): ?>
        <div class="alert alert-warning">⚠️ لا يمكن الترقية - هذا آخر مستوى في النظام.</div>
      <?php else: ?>
        <?php
        $passed = array_filter($students_info, fn($x) => $x['passed']);
        $failed = array_filter($students_info, fn($x) => !$x['passed']);
        ?>
        <div class="row g-3 mb-3">
          <div class="col-md-3 col-sm-6"><div class="stat-card bg-grad-1"><span class="icon">👥</span><div><h2><?= count($students_info) ?></h2><span>إجمالي الطلبة</span></div></div></div>
          <div class="col-md-3 col-sm-6"><div class="stat-card bg-grad-4"><span class="icon">✅</span><div><h2><?= count($passed) ?></h2><span>ناجح</span></div></div></div>
          <div class="col-md-3 col-sm-6"><div class="stat-card bg-grad-2"><span class="icon">❌</span><div><h2><?= count($failed) ?></h2><span>راسب</span></div></div></div>
          <div class="col-md-3 col-sm-6"><div class="stat-card bg-grad-5"><span class="icon">📊</span><div><h2><?= $pass_grade ?>%</h2><span>درجة النجاح</span></div></div></div>
        </div>

        <?php if (count($passed) > 0): ?>
          <div class="alert alert-success">
            <form method="post" onsubmit="return confirm('سيتم ترقية جميع الطلبة الناجحين (<?= count($passed) ?>) إلى <?= h($nextLevel['level_name']) ?>. متابعة؟')" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
              <div>✅ يوجد <strong><?= count($passed) ?></strong> طالب ناجح جاهز للترقية</div>
              <input type="hidden" name="action" value="promote_students">
              <input type="hidden" name="from_grade_level_id" value="<?= $gl_id ?>">
              <input type="hidden" name="academic_year" value="<?= h($academic_year) ?>">
              <input type="hidden" name="new_academic_year" value="<?= h($academic_year) ?>">
              <button class="btn btn-success btn-lg">⬆️ ترقية الطلبة الناجحين</button>
            </form>
          </div>
        <?php endif; ?>

        <div class="card">
          <div class="card-header">📋 قائمة الطلبة والمعدلات</div>
          <div class="card-body table-responsive">
            <table class="table table-bordered">
              <thead>
                <tr><th>#</th><th>الكود</th><th>الاسم</th><th>الفصل الحالي</th><th>المعدل</th><th>الحالة</th></tr>
              </thead>
              <tbody>
                <?php if (!$students_info): ?><tr><td colspan="6" class="text-center text-muted py-4">لا يوجد طلبة</td></tr><?php endif; ?>
                <?php foreach ($students_info as $i => $info): ?>
                  <tr>
                    <td><?= $i+1 ?></td>
                    <td><span class="badge bg-dark"><?= h($info['student']['student_code']) ?></span></td>
                    <td><?= h($info['student']['name']) ?></td>
                    <td><?= h($info['student']['class_name']) ?></td>
                    <td><strong class="<?= $info['passed']?'text-success':'text-danger' ?>"><?= number_format($info['avg'],2) ?>%</strong></td>
                    <td>
                      <?php if ($info['passed']): ?>
                        <span class="badge bg-success">✅ ناجح</span>
                      <?php else: ?>
                        <span class="badge bg-danger">❌ راسب</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>
    <?php endif; ?>

  <?php
  /* ------------ المدرسون ------------ */
  elseif ($page === 'teachers' && isDataEntry()):
      $rows = $pdo->query("SELECT * FROM teachers ORDER BY id DESC")->fetchAll();
      $daysNames = ['الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
  ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="page-title mb-0"><span class="icon">👨‍🏫</span> إدارة المدرسين</h3>
      <button class="btn btn-primary" onclick="openModal('addTeacherModal')">➕ إضافة مدرس</button>
    </div>

    <div class="card">
      <div class="card-body table-responsive">
        <table class="table">
          <thead>
            <tr><th>الكود</th><th>الاسم</th><th>الهاتف</th><th>المادة</th><th>الراتب</th><th>أيام العمل</th><th>التوقيت</th><th>رصيد الإجازات</th><th>إجراءات</th></tr>
          </thead>
          <tbody>
            <?php if (!$rows): ?><tr><td colspan="9" class="text-center text-muted py-4">لا توجد بيانات</td></tr><?php endif; ?>
            <?php foreach($rows as $r):
              $wd = array_map('trim', explode(',', $r['work_days']));
              $wdNames = [];
              foreach($wd as $d) if(isset($daysNames[(int)$d])) $wdNames[] = $daysNames[(int)$d];
              $balance = getLeaveBalance($pdo, 'teacher', $r['id']);
              $remaining = $balance['annual_entitlement'] - $balance['annual_used'];
            ?>
              <tr>
                <td><span class="badge bg-dark"><?= h($r['employee_code']) ?></span></td>
                <td><?= h($r['name']) ?></td>
                <td><?= h($r['phone']) ?></td>
                <td><span class="badge bg-info"><?= h($r['subject']) ?></span></td>
                <td><?= number_format($r['salary'],2) ?></td>
                <td><small><?= implode('، ', $wdNames) ?></small></td>
                <td><small><?= h($r['work_start']) ?> - <?= h($r['work_end']) ?></small></td>
                <td><span class="badge <?= $remaining > 5 ? 'bg-success' : 'bg-warning' ?>"><?= $remaining ?> / <?= $balance['annual_entitlement'] ?> يوم</span></td>
                <td>
                  <a href="?page=leaves&person_type=teacher&person_id=<?= $r['id'] ?>" class="btn btn-sm btn-info" title="الإجازات">🏖️</a>
                  <button class="btn btn-sm btn-warning" onclick='editTeacher(<?= json_encode($r, JSON_UNESCAPED_UNICODE) ?>)'>✏️</button>
                  <?php if (isAdmin()): ?>
                  <form method="post" style="display:inline" onsubmit="return confirm('حذف هذا المدرس؟')">
                    <input type="hidden" name="action" value="del_teacher">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <button class="btn btn-sm btn-danger">🗑️</button>
                  </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php
    $daysCheckbox = '';
    foreach($daysNames as $i=>$dn) $daysCheckbox .= "<div class='form-check'><input type='checkbox' name='work_days[]' value='$i' id='wd_t_$i'><label for='wd_t_$i'>$dn</label></div>";
    ?>

    <div class="modal-overlay" id="addTeacherModal">
      <div class="modal-box modal-lg">
        <form method="post">
          <input type="hidden" name="action" value="add_teacher">
          <div class="modal-header"><h5>➕ إضافة مدرس</h5><button type="button" class="modal-close" onclick="closeModal('addTeacherModal')">×</button></div>
          <div class="modal-body">
            <div class="row g-2">
              <div class="col-md-6"><label class="form-label">الاسم الرباعي *</label><input name="name" class="form-control" required></div>
              <div class="col-md-6"><label class="form-label">الرقم الوطني</label><input name="national_id" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">الهاتف</label><input name="phone" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">البريد</label><input type="email" name="email" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">المادة</label><input name="subject" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">تاريخ التعيين</label><input type="date" name="hire_date" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">الراتب الشهري</label><input type="number" step="0.01" name="salary" class="form-control" value="0"></div>
              <div class="col-md-6"><label class="form-label">الرصيد السنوي للإجازات (يوم)</label><input type="number" name="annual_leave_days" class="form-control" value="30"></div>
              <div class="col-md-6"><label class="form-label">وقت بدء العمل</label><input type="time" name="work_start" class="form-control" value="08:00"></div>
              <div class="col-md-6"><label class="form-label">وقت نهاية العمل</label><input type="time" name="work_end" class="form-control" value="14:00"></div>
              <div class="col-12"><label class="form-label fw-bold">أيام العمل</label><?= $daysCheckbox ?></div>
              <div class="col-12"><label class="form-label">العنوان</label><textarea name="address" class="form-control" rows="2"></textarea></div>
            </div>
          </div>
          <div class="modal-footer"><button class="btn btn-primary">حفظ</button></div>
        </form>
      </div>
    </div>

    <div class="modal-overlay" id="editTeacherModal">
      <div class="modal-box modal-lg">
        <form method="post">
          <input type="hidden" name="action" value="edit_teacher">
          <input type="hidden" name="id" id="et_id">
          <div class="modal-header"><h5>✏️ تعديل مدرس</h5><button type="button" class="modal-close" onclick="closeModal('editTeacherModal')">×</button></div>
          <div class="modal-body">
            <div class="row g-2">
              <div class="col-md-6"><label class="form-label">الاسم</label><input name="name" id="et_name" class="form-control" required></div>
              <div class="col-md-6"><label class="form-label">الرقم الوطني</label><input name="national_id" id="et_national_id" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">الهاتف</label><input name="phone" id="et_phone" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">البريد</label><input name="email" id="et_email" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">المادة</label><input name="subject" id="et_subject" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">تاريخ التعيين</label><input type="date" name="hire_date" id="et_hire_date" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">الراتب</label><input type="number" step="0.01" name="salary" id="et_salary" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">الرصيد السنوي</label><input type="number" name="annual_leave_days" id="et_annual_leave_days" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">بدء العمل</label><input type="time" name="work_start" id="et_work_start" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">نهاية العمل</label><input type="time" name="work_end" id="et_work_end" class="form-control"></div>
              <div class="col-12" id="et_work_days_container"><label class="form-label fw-bold">أيام العمل</label></div>
              <div class="col-12"><label class="form-label">العنوان</label><textarea name="address" id="et_address" class="form-control" rows="2"></textarea></div>
            </div>
          </div>
          <div class="modal-footer"><button class="btn btn-primary">تحديث</button></div>
        </form>
      </div>
    </div>

    <script>
    const daysNamesJs = <?= json_encode($daysNames) ?>;
    function editTeacher(d){
      document.getElementById('et_id').value=d.id;
      document.getElementById('et_name').value=d.name||'';
      document.getElementById('et_national_id').value=d.national_id||'';
      document.getElementById('et_phone').value=d.phone||'';
      document.getElementById('et_email').value=d.email||'';
      document.getElementById('et_subject').value=d.subject||'';
      document.getElementById('et_hire_date').value=d.hire_date||'';
      document.getElementById('et_salary').value=d.salary||0;
      document.getElementById('et_annual_leave_days').value=d.annual_leave_days||30;
      document.getElementById('et_work_start').value=d.work_start||'08:00';
      document.getElementById('et_work_end').value=d.work_end||'14:00';
      document.getElementById('et_address').value=d.address||'';
      const container = document.getElementById('et_work_days_container');
      const wd = (d.work_days||'').split(',').map(x=>x.trim());
      let html = '<label class="form-label fw-bold">أيام العمل</label>';
      daysNamesJs.forEach((name,i)=>{
        const checked = wd.includes(String(i)) ? 'checked' : '';
        html += `<div class='form-check'><input type='checkbox' name='work_days[]' value='${i}' id='wd_e_${i}' ${checked}><label for='wd_e_${i}'>${name}</label></div>`;
      });
      container.innerHTML = html;
      openModal('editTeacherModal');
    }
    </script>

  <?php
  /* ------------ الموظفون ------------ */
  elseif ($page === 'employees' && isDataEntry()):
      $rows = $pdo->query("SELECT * FROM employees ORDER BY id DESC")->fetchAll();
      $daysNames = ['الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
  ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="page-title mb-0"><span class="icon">👔</span> إدارة الموظفين</h3>
      <button class="btn btn-primary" onclick="openModal('addEmpModal')">➕ إضافة موظف</button>
    </div>

    <div class="card"><div class="card-body table-responsive">
      <table class="table">
        <thead><tr><th>الكود</th><th>الاسم</th><th>الهاتف</th><th>الوظيفة</th><th>الراتب</th><th>أيام العمل</th><th>التوقيت</th><th>رصيد الإجازات</th><th>إجراءات</th></tr></thead>
        <tbody>
          <?php if (!$rows): ?><tr><td colspan="9" class="text-center text-muted py-4">لا توجد بيانات</td></tr><?php endif; ?>
          <?php foreach($rows as $r):
            $wd = array_map('trim', explode(',', $r['work_days']));
            $wdNames = [];
            foreach($wd as $d) if(isset($daysNames[(int)$d])) $wdNames[] = $daysNames[(int)$d];
            $balance = getLeaveBalance($pdo, 'employee', $r['id']);
            $remaining = $balance['annual_entitlement'] - $balance['annual_used'];
          ?>
            <tr>
              <td><span class="badge bg-dark"><?= h($r['employee_code']) ?></span></td>
              <td><?= h($r['name']) ?></td>
              <td><?= h($r['phone']) ?></td>
              <td><span class="badge bg-secondary"><?= h($r['job_title']) ?></span></td>
              <td><?= number_format($r['salary'],2) ?></td>
              <td><small><?= implode('، ', $wdNames) ?></small></td>
              <td><small><?= h($r['work_start']) ?> - <?= h($r['work_end']) ?></small></td>
              <td><span class="badge <?= $remaining > 5 ? 'bg-success' : 'bg-warning' ?>"><?= $remaining ?> / <?= $balance['annual_entitlement'] ?> يوم</span></td>
              <td>
                <a href="?page=leaves&person_type=employee&person_id=<?= $r['id'] ?>" class="btn btn-sm btn-info" title="الإجازات">🏖️</a>
                <button class="btn btn-sm btn-warning" onclick='editEmp(<?= json_encode($r, JSON_UNESCAPED_UNICODE) ?>)'>✏️</button>
                <?php if (isAdmin()): ?>
                <form method="post" style="display:inline" onsubmit="return confirm('حذف هذا الموظف؟')">
                  <input type="hidden" name="action" value="del_employee">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>">
                  <button class="btn btn-sm btn-danger">🗑️</button>
                </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div></div>

    <?php
    $daysCheckbox = '';
    foreach($daysNames as $i=>$dn) $daysCheckbox .= "<div class='form-check'><input type='checkbox' name='work_days[]' value='$i' id='wd_e_add_$i'><label for='wd_e_add_$i'>$dn</label></div>";
    ?>

    <div class="modal-overlay" id="addEmpModal">
      <div class="modal-box modal-lg">
        <form method="post">
          <input type="hidden" name="action" value="add_employee">
          <div class="modal-header"><h5>➕ إضافة موظف</h5><button type="button" class="modal-close" onclick="closeModal('addEmpModal')">×</button></div>
          <div class="modal-body">
            <div class="row g-2">
              <div class="col-md-6"><label class="form-label">الاسم الرباعي *</label><input name="name" class="form-control" required></div>
              <div class="col-md-6"><label class="form-label">الرقم الوطني</label><input name="national_id" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">الهاتف</label><input name="phone" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">الوظيفة</label><input name="job_title" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">تاريخ التعيين</label><input type="date" name="hire_date" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">الراتب</label><input type="number" step="0.01" name="salary" class="form-control" value="0"></div>
              <div class="col-md-6"><label class="form-label">الرصيد السنوي</label><input type="number" name="annual_leave_days" class="form-control" value="30"></div>
              <div class="col-md-6"><label class="form-label">بدء العمل</label><input type="time" name="work_start" class="form-control" value="08:00"></div>
              <div class="col-md-6"><label class="form-label">نهاية العمل</label><input type="time" name="work_end" class="form-control" value="14:00"></div>
              <div class="col-12"><label class="form-label fw-bold">أيام العمل</label><?= $daysCheckbox ?></div>
              <div class="col-12"><label class="form-label">العنوان</label><textarea name="address" class="form-control" rows="2"></textarea></div>
            </div>
          </div>
          <div class="modal-footer"><button class="btn btn-primary">حفظ</button></div>
        </form>
      </div>
    </div>

    <div class="modal-overlay" id="editEmpModal">
      <div class="modal-box modal-lg">
        <form method="post">
          <input type="hidden" name="action" value="edit_employee">
          <input type="hidden" name="id" id="ee_id">
          <div class="modal-header"><h5>✏️ تعديل موظف</h5><button type="button" class="modal-close" onclick="closeModal('editEmpModal')">×</button></div>
          <div class="modal-body">
            <div class="row g-2">
              <div class="col-md-6"><label class="form-label">الاسم</label><input name="name" id="ee_name" class="form-control" required></div>
              <div class="col-md-6"><label class="form-label">الرقم الوطني</label><input name="national_id" id="ee_national_id" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">الهاتف</label><input name="phone" id="ee_phone" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">الوظيفة</label><input name="job_title" id="ee_job_title" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">تاريخ التعيين</label><input type="date" name="hire_date" id="ee_hire_date" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">الراتب</label><input type="number" step="0.01" name="salary" id="ee_salary" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">الرصيد السنوي</label><input type="number" name="annual_leave_days" id="ee_annual_leave_days" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">بدء العمل</label><input type="time" name="work_start" id="ee_work_start" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">نهاية العمل</label><input type="time" name="work_end" id="ee_work_end" class="form-control"></div>
              <div class="col-12" id="ee_work_days_container"><label class="form-label fw-bold">أيام العمل</label></div>
              <div class="col-12"><label class="form-label">العنوان</label><textarea name="address" id="ee_address" class="form-control" rows="2"></textarea></div>
            </div>
          </div>
          <div class="modal-footer"><button class="btn btn-primary">تحديث</button></div>
        </form>
      </div>
    </div>

    <script>
    const daysNamesJsEmp = <?= json_encode($daysNames) ?>;
    function editEmp(d){
      document.getElementById('ee_id').value=d.id;
      document.getElementById('ee_name').value=d.name||'';
      document.getElementById('ee_national_id').value=d.national_id||'';
      document.getElementById('ee_phone').value=d.phone||'';
      document.getElementById('ee_job_title').value=d.job_title||'';
      document.getElementById('ee_hire_date').value=d.hire_date||'';
      document.getElementById('ee_salary').value=d.salary||0;
      document.getElementById('ee_annual_leave_days').value=d.annual_leave_days||30;
      document.getElementById('ee_work_start').value=d.work_start||'08:00';
      document.getElementById('ee_work_end').value=d.work_end||'14:00';
      document.getElementById('ee_address').value=d.address||'';
      const container = document.getElementById('ee_work_days_container');
      const wd = (d.work_days||'').split(',').map(x=>x.trim());
      let html = '<label class="form-label fw-bold">أيام العمل</label>';
      daysNamesJsEmp.forEach((name,i)=>{
        const checked = wd.includes(String(i)) ? 'checked' : '';
        html += `<div class='form-check'><input type='checkbox' name='work_days[]' value='${i}' id='wd_ee_${i}' ${checked}><label for='wd_ee_${i}'>${name}</label></div>`;
      });
      container.innerHTML = html;
      openModal('editEmpModal');
    }
    </script>

  <?php
  /* ------------ الرواتب ------------ */
  elseif ($page === 'salaries' && isAccountant()):
      $teachers  = $pdo->query("SELECT id,name,salary,employee_code FROM teachers")->fetchAll();
      $employees = $pdo->query("SELECT id,name,salary,employee_code FROM employees")->fetchAll();
      $rows = $pdo->query("SELECT s.*, 
          CASE s.person_type 
            WHEN 'teacher' THEN (SELECT name FROM teachers WHERE id=s.person_id)
            ELSE (SELECT name FROM employees WHERE id=s.person_id) END AS person_name
          FROM salaries s ORDER BY s.id DESC")->fetchAll();
      $months = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
      $work_days_per_month = getSetting($pdo, 'work_days_per_month', 22);
      $late_deduction = getSetting($pdo, 'late_deduction_per_day', 10);
  ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="page-title mb-0"><span class="icon">💰</span> إدارة الرواتب</h3>
      <button class="btn btn-primary" onclick="openModal('addSalaryModal')">➕ صرف راتب</button>
    </div>

    <div class="alert alert-info">
      <strong>ℹ️ نظام الخصم التلقائي:</strong><br>
      • أيام العمل في الشهر: <strong><?= $work_days_per_month ?></strong> يوم<br>
      • خصم التأخير: <strong><?= $late_deduction ?></strong> لكل يوم تأخير<br>
      • خصم الغياب = (الراتب الأساسي ÷ <?= $work_days_per_month ?>) × عدد أيام الغياب
    </div>

    <div class="card"><div class="card-body table-responsive">
      <table class="table">
        <thead><tr><th>#</th><th>النوع</th><th>الاسم</th><th>الشهر</th><th>السنة</th><th>الأساسي</th><th>مكافأة</th><th>غياب</th><th>خصم غياب</th><th>خصم يدوي</th><th>الصافي</th><th>الحالة</th><th>إجراءات</th></tr></thead>
        <tbody>
          <?php if (!$rows): ?><tr><td colspan="13" class="text-center text-muted py-4">لا توجد بيانات</td></tr><?php endif; ?>
          <?php foreach($rows as $r): ?>
            <tr>
              <td><?= $r['id'] ?></td>
              <td><span class="badge <?= $r['person_type']=='teacher'?'bg-info':'bg-secondary' ?>"><?= $r['person_type']=='teacher'?'مدرس':'موظف' ?></span></td>
              <td><?= h($r['person_name'] ?? '-') ?></td>
              <td><?= h($r['month']) ?></td>
              <td><?= h($r['year']) ?></td>
              <td><?= number_format($r['basic_salary'],2) ?></td>
              <td class="text-success">+<?= number_format($r['bonus'],2) ?></td>
              <td><?= $r['absence_days'] ?? 0 ?></td>
              <td class="text-danger">-<?= number_format($r['absence_deduction'] ?? 0,2) ?></td>
              <td class="text-danger">-<?= number_format($r['deduction'],2) ?></td>
              <td><strong><?= number_format($r['net_salary'],2) ?></strong></td>
              <td>
                <?php if($r['status']=='paid'): ?><span class="badge bg-success">مدفوع</span>
                <?php else: ?><span class="badge bg-warning">معلق</span><?php endif; ?>
              </td>
              <td>
                <?php if(($r['absence_deduction'] ?? 0) == 0): ?>
                <form method="post" style="display:inline" title="حساب الخصم التلقائي">
                  <input type="hidden" name="action" value="calc_auto_deduction">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>">
                  <button class="btn btn-sm btn-warning">🔄</button>
                </form>
                <?php endif; ?>
                <?php if($r['status']=='pending'): ?>
                <form method="post" style="display:inline">
                  <input type="hidden" name="action" value="mark_paid">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>">
                  <button class="btn btn-sm btn-success">✓</button>
                </form>
                <?php endif; ?>
                <form method="post" style="display:inline" onsubmit="return confirm('حذف الراتب؟')">
                  <input type="hidden" name="action" value="del_salary">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>">
                  <button class="btn btn-sm btn-danger">🗑️</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div></div>

    <div class="modal-overlay" id="addSalaryModal">
      <div class="modal-box">
        <form method="post">
          <input type="hidden" name="action" value="add_salary">
          <div class="modal-header"><h5>💰 صرف راتب</h5><button type="button" class="modal-close" onclick="closeModal('addSalaryModal')">×</button></div>
          <div class="modal-body">
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label">النوع</label>
                <select name="person_type" id="sal_type" class="form-select" onchange="toggleSalPerson()" required>
                  <option value="teacher">مدرس</option>
                  <option value="employee">موظف</option>
                </select>
              </div>
              <div class="col-md-6"><label class="form-label">الشخص</label><select name="person_id" id="sal_person" class="form-select" required></select></div>
              <div class="col-md-6">
                <label class="form-label">الشهر</label>
                <select name="month" class="form-select" required>
                  <?php foreach($months as $m): ?><option value="<?= $m ?>"><?= $m ?></option><?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6"><label class="form-label">السنة</label><input type="number" name="year" class="form-control" value="<?= date('Y') ?>" required></div>
              <div class="col-md-6"><label class="form-label">الراتب الأساسي</label><input type="number" step="0.01" name="basic_salary" id="sal_basic" class="form-control" required></div>
              <div class="col-md-6"><label class="form-label">مكافأة</label><input type="number" step="0.01" name="bonus" class="form-control" value="0"></div>
              <div class="col-md-6"><label class="form-label">خصم يدوي</label><input type="number" step="0.01" name="deduction" class="form-control" value="0"></div>
              <div class="col-md-6"><label class="form-label">تاريخ الدفع</label><input type="date" name="paid_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
              <div class="col-md-6">
                <label class="form-label">الحالة</label>
                <select name="status" class="form-select"><option value="paid">مدفوع</option><option value="pending">معلق</option></select>
              </div>
              <div class="col-12">
                <div class="form-check">
                  <input type="checkbox" name="auto_deduction" value="1" id="auto_ded" checked>
                  <label for="auto_ded" class="fw-bold">🔄 حساب الخصم التلقائي بناءً على أيام الغياب والتأخير</label>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer"><button class="btn btn-primary">حفظ</button></div>
        </form>
      </div>
    </div>

    <script>
    const teachersData = <?= json_encode($teachers, JSON_UNESCAPED_UNICODE) ?>;
    const employeesData = <?= json_encode($employees, JSON_UNESCAPED_UNICODE) ?>;
    function toggleSalPerson(){
      const t = document.getElementById('sal_type').value;
      const sel = document.getElementById('sal_person');
      const data = t === 'teacher' ? teachersData : employeesData;
      sel.innerHTML = '';
      data.forEach(p => { sel.innerHTML += `<option value="${p.id}" data-salary="${p.salary}">${p.name} (${p.employee_code})</option>`; });
      updateSalBasic();
    }
    function updateSalBasic(){
      const sel = document.getElementById('sal_person');
      const opt = sel.options[sel.selectedIndex];
      if(opt) document.getElementById('sal_basic').value = opt.dataset.salary || 0;
    }
    document.getElementById('sal_person').addEventListener('change', updateSalBasic);
    toggleSalPerson();
    </script>

  <?php
  /* ------------ الإجازات ------------ */
  elseif ($page === 'leaves' && isSupervisor()):
      $person_type = $_GET['person_type'] ?? 'teacher';
      $person_id = (int)($_GET['person_id'] ?? 0);
      $filter_status = $_GET['status'] ?? 'all';

      $teachers = $pdo->query("SELECT id,name,employee_code FROM teachers ORDER BY name")->fetchAll();
      $employees = $pdo->query("SELECT id,name,employee_code FROM employees ORDER BY name")->fetchAll();

      $sql = "SELECT lr.*, 
          CASE lr.person_type 
            WHEN 'teacher' THEN (SELECT name FROM teachers WHERE id=lr.person_id)
            ELSE (SELECT name FROM employees WHERE id=lr.person_id) END AS person_name,
          CASE lr.person_type 
            WHEN 'teacher' THEN (SELECT employee_code FROM teachers WHERE id=lr.person_id)
            ELSE (SELECT employee_code FROM employees WHERE id=lr.person_id) END AS person_code
          FROM leave_requests lr WHERE 1=1";
      $params = [];
      if ($person_type && $person_id) {
          $sql .= " AND lr.person_type=? AND lr.person_id=?";
          $params[] = $person_type;
          $params[] = $person_id;
      }
      if ($filter_status !== 'all') {
          $sql .= " AND lr.status=?";
          $params[] = $filter_status;
      }
      $sql .= " ORDER BY lr.id DESC";
      $stmt = $pdo->prepare($sql);
      $stmt->execute($params);
      $leave_requests = $stmt->fetchAll();

      $balance = null;
      $person_name = '';
      if ($person_id) {
          $balance = getLeaveBalance($pdo, $person_type, $person_id);
          $table = $person_type === 'teacher' ? 'teachers' : 'employees';
          $stmt = $pdo->prepare("SELECT name FROM $table WHERE id=?");
          $stmt->execute([$person_id]);
          $person_name = $stmt->fetchColumn();
      }
      $leave_message = $_SESSION['leave_message'] ?? '';
      $leave_type_msg = $_SESSION['leave_type_msg'] ?? 'info';
      unset($_SESSION['leave_message'], $_SESSION['leave_type_msg']);
      $leaveTypes = ['annual'=>'سنوية','sick'=>'مرضية','emergency'=>'طارئة','unpaid'=>'بدون راتب'];
  ?>
    <h3 class="page-title"><span class="icon">🏖️</span> نظام الإجازات</h3>

    <?php if ($leave_message): ?><div class="alert alert-<?= h($leave_type_msg) ?>"><?= h($leave_message) ?></div><?php endif; ?>

    <div class="report-filter">
      <form method="get" class="row g-2 align-items-end">
        <input type="hidden" name="page" value="leaves">
        <div class="col-md-3">
          <label class="form-label">النوع</label>
          <select name="person_type" class="form-select">
            <option value="">الكل</option>
            <option value="teacher" <?= $person_type=='teacher'?'selected':'' ?>>مدرس</option>
            <option value="employee" <?= $person_type=='employee'?'selected':'' ?>>موظف</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">الحالة</label>
          <select name="status" class="form-select">
            <option value="all" <?= $filter_status=='all'?'selected':'' ?>>الكل</option>
            <option value="pending" <?= $filter_status=='pending'?'selected':'' ?>>معلقة</option>
            <option value="approved" <?= $filter_status=='approved'?'selected':'' ?>>موافق عليها</option>
            <option value="rejected" <?= $filter_status=='rejected'?'selected':'' ?>>مرفوضة</option>
          </select>
        </div>
        <div class="col-md-3"><button class="btn btn-primary btn-block">🔍 عرض</button></div>
        <div class="col-md-3"><button type="button" class="btn btn-success btn-block" onclick="openModal('addLeaveModal')">➕ طلب إجازة جديد</button></div>
      </form>
    </div>

    <?php if ($balance): ?>
    <div class="card mb-4">
      <div class="card-header">🏖️ رصيد إجازات: <?= h($person_name) ?></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-3">
            <div class="leave-balance-card annual">
              <div class="number"><?= $balance['annual_entitlement'] - $balance['annual_used'] ?></div>
              <div class="label">سنوية متبقية</div>
              <small class="text-muted">(من <?= $balance['annual_entitlement'] ?>)</small>
            </div>
          </div>
          <div class="col-md-3"><div class="leave-balance-card sick"><div class="number"><?= $balance['sick_used'] ?></div><div class="label">مرضية مستخدمة</div></div></div>
          <div class="col-md-3"><div class="leave-balance-card emergency"><div class="number"><?= $balance['emergency_used'] ?></div><div class="label">طارئة مستخدمة</div></div></div>
          <div class="col-md-3"><div class="leave-balance-card unpaid"><div class="number"><?= $balance['unpaid_used'] ?></div><div class="label">بدون راتب مستخدمة</div></div></div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header">📋 طلبات الإجازة</div>
      <div class="card-body table-responsive">
        <table class="table">
          <thead>
            <tr><th>#</th><th>النوع</th><th>الاسم</th><th>نوع الإجازة</th><th>من</th><th>إلى</th><th>الأيام</th><th>السبب</th><th>الحالة</th><th>بواسطة</th><th>إجراءات</th></tr>
          </thead>
          <tbody>
            <?php if (!$leave_requests): ?><tr><td colspan="11" class="text-center text-muted py-4">لا توجد طلبات إجازة</td></tr><?php endif; ?>
            <?php foreach($leave_requests as $lr):
              $statusMap = ['pending'=>['bg-warning','معلقة'],'approved'=>['bg-success','موافق عليها'],'rejected'=>['bg-danger','مرفوضة']];
              $st = $statusMap[$lr['status']] ?? ['bg-secondary','-'];
            ?>
              <tr>
                <td><?= $lr['id'] ?></td>
                <td><span class="badge <?= $lr['person_type']=='teacher'?'bg-info':'bg-secondary' ?>"><?= $lr['person_type']=='teacher'?'مدرس':'موظف' ?></span></td>
                <td><?= h($lr['person_name']) ?> <small class="text-muted">(<?= h($lr['person_code']) ?>)</small></td>
                <td><span class="badge bg-purple"><?= $leaveTypes[$lr['leave_type']] ?? $lr['leave_type'] ?></span></td>
                <td><?= h($lr['start_date']) ?></td>
                <td><?= h($lr['end_date']) ?></td>
                <td><strong><?= $lr['days_count'] ?></strong> يوم</td>
                <td><small><?= h($lr['reason']) ?></small></td>
                <td><span class="badge <?= $st[0] ?>"><?= $st[1] ?></span></td>
                <td><small><?= h($lr['approved_by']) ?></small></td>
                <td>
                  <?php if ($lr['status'] === 'pending'): ?>
                    <form method="post" style="display:inline">
                      <input type="hidden" name="action" value="approve_leave">
                      <input type="hidden" name="id" value="<?= $lr['id'] ?>">
                      <button class="btn btn-sm btn-success" title="موافقة">✓</button>
                    </form>
                    <form method="post" style="display:inline">
                      <input type="hidden" name="action" value="reject_leave">
                      <input type="hidden" name="id" value="<?= $lr['id'] ?>">
                      <button class="btn btn-sm btn-danger" title="رفض">✗</button>
                    </form>
                  <?php endif; ?>
                  <?php if (isAdmin()): ?>
                    <form method="post" style="display:inline" onsubmit="return confirm('حذف الطلب؟')">
                      <input type="hidden" name="action" value="del_leave">
                      <input type="hidden" name="id" value="<?= $lr['id'] ?>">
                      <button class="btn btn-sm btn-secondary">🗑️</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="modal-overlay" id="addLeaveModal">
      <div class="modal-box">
        <form method="post">
          <input type="hidden" name="action" value="add_leave_request">
          <div class="modal-header"><h5>🏖️ طلب إجازة جديد</h5><button type="button" class="modal-close" onclick="closeModal('addLeaveModal')">×</button></div>
          <div class="modal-body">
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label">النوع *</label>
                <select name="person_type" id="leave_type_person" class="form-select" onchange="loadLeavePersons()" required>
                  <option value="teacher">مدرس</option>
                  <option value="employee">موظف</option>
                </select>
              </div>
              <div class="col-md-6"><label class="form-label">الشخص *</label><select name="person_id" id="leave_person" class="form-select" required></select></div>
              <div class="col-md-6">
                <label class="form-label">نوع الإجازة *</label>
                <select name="leave_type" class="form-select" required>
                  <option value="annual">سنوية</option>
                  <option value="sick">مرضية</option>
                  <option value="emergency">طارئة</option>
                  <option value="unpaid">بدون راتب</option>
                </select>
              </div>
              <div class="col-md-6"><label class="form-label">عدد الأيام (يُحسب تلقائياً)</label><input type="text" id="leave_days_display" class="form-control" readonly value="1"></div>
              <div class="col-md-6"><label class="form-label">من تاريخ *</label><input type="date" name="start_date" id="leave_start" class="form-control" value="<?= date('Y-m-d') ?>" required onchange="calcLeaveDays()"></div>
              <div class="col-md-6"><label class="form-label">إلى تاريخ *</label><input type="date" name="end_date" id="leave_end" class="form-control" value="<?= date('Y-m-d') ?>" required onchange="calcLeaveDays()"></div>
              <div class="col-12"><label class="form-label">السبب</label><textarea name="reason" class="form-control" rows="2"></textarea></div>
            </div>
          </div>
          <div class="modal-footer"><button class="btn btn-primary">تقديم الطلب</button></div>
        </form>
      </div>
    </div>

    <script>
    const leaveTeachers = <?= json_encode($teachers, JSON_UNESCAPED_UNICODE) ?>;
    const leaveEmployees = <?= json_encode($employees, JSON_UNESCAPED_UNICODE) ?>;
    function loadLeavePersons(){
      const t = document.getElementById('leave_type_person').value;
      const sel = document.getElementById('leave_person');
      const data = t === 'teacher' ? leaveTeachers : leaveEmployees;
      sel.innerHTML = '';
      data.forEach(p => { sel.innerHTML += `<option value="${p.id}">${p.name} (${p.employee_code})</option>`; });
    }
    function calcLeaveDays(){
      const start = document.getElementById('leave_start').value;
      const end = document.getElementById('leave_end').value;
      if (start && end) {
        const diff = (new Date(end) - new Date(start)) / 86400000 + 1;
        document.getElementById('leave_days_display').value = diff > 0 ? diff : 0;
      }
    }
    loadLeavePersons();
    </script>

  <?php
  /* ------------ الحضور والانصراف ------------ */
  elseif ($page === 'attendance' && isSupervisor()):
      $teachers  = $pdo->query("SELECT id,name,employee_code,work_days,work_start,work_end FROM teachers ORDER BY name")->fetchAll();
      $employees = $pdo->query("SELECT id,name,employee_code,work_days,work_start,work_end FROM employees ORDER BY name")->fetchAll();
      $rows = $pdo->query("SELECT a.*, 
          CASE a.person_type 
            WHEN 'teacher' THEN (SELECT name FROM teachers WHERE id=a.person_id)
            ELSE (SELECT name FROM employees WHERE id=a.person_id) END AS person_name,
          CASE a.person_type 
            WHEN 'teacher' THEN (SELECT employee_code FROM teachers WHERE id=a.person_id)
            ELSE (SELECT employee_code FROM employees WHERE id=a.person_id) END AS person_code
          FROM attendance a ORDER BY a.att_date DESC, a.id DESC LIMIT 300")->fetchAll();

      $att_message = $_SESSION['att_message'] ?? '';
      $att_type = $_SESSION['att_type'] ?? 'info';
      unset($_SESSION['att_message'], $_SESSION['att_type']);
  ?>
    <h3 class="page-title"><span class="icon">⏰</span> سجل الحضور والانصراف</h3>

    <div class="code-input-bar">
      <h4>📟 تسجيل الحضور / الانصراف بالكود</h4>
      <form method="post">
        <input type="hidden" name="action" value="checkin_by_code">
        <div class="input-group">
          <input type="text" name="employee_code" id="code_input" placeholder="أدخل كود الموظف (مثال: T1001)" autocomplete="off" autofocus required>
          <button type="submit">⏎ تسجيل</button>
        </div>
      </form>
      <div class="hint">💡 عند إدخال الكود لأول مرة يسجل حضور، وعند إدخاله مرة أخرى يسجل انصراف</div>
    </div>

    <?php if ($att_message): ?><div class="alert alert-<?= h($att_type) ?>"><?= h($att_message) ?></div><?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
      <span></span>
      <div>
        <form method="post" style="display:inline" onsubmit="return confirm('سيتم تسجيل غياب لجميع من لم يسجل حضور. متابعة؟')">
          <input type="hidden" name="action" value="mark_absent">
          <input type="hidden" name="absent_date" value="<?= date('Y-m-d') ?>">
          <button class="btn btn-warning">📝 تسجيل غياب اليوم</button>
        </form>
        <a href="?page=attendance_report" class="btn btn-info">📊 تقرير يومي</a>
        <a href="?page=employee_attendance_report" class="btn btn-info">📋 تقرير موظف</a>
        <button class="btn btn-outline-primary" onclick="openModal('addAttModal')">➕ تسجيل يدوي</button>
      </div>
    </div>

    <div class="card"><div class="card-body table-responsive">
      <table class="table">
        <thead><tr><th>#</th><th>التاريخ</th><th>الكود</th><th>النوع</th><th>الاسم</th><th>الحضور</th><th>الانصراف</th><th>الحالة</th><th>ملاحظات</th><th></th></tr></thead>
        <tbody>
          <?php if (!$rows): ?><tr><td colspan="10" class="text-center text-muted py-4">لا توجد بيانات</td></tr><?php endif; ?>
          <?php foreach($rows as $r):
            $statusMap = ['present'=>['bg-success','حاضر'],'absent'=>['bg-danger','غائب'],'late'=>['bg-warning','متأخر'],'holiday'=>['bg-info','عطلة'],'leave'=>['bg-secondary','إجازة']];
            $s = $statusMap[$r['status']] ?? ['bg-secondary','-'];
          ?>
            <tr>
              <td><?= $r['id'] ?></td>
              <td><?= h($r['att_date']) ?></td>
              <td><span class="badge bg-dark"><?= h($r['person_code']) ?></span></td>
              <td><span class="badge <?= $r['person_type']=='teacher'?'bg-info':'bg-secondary' ?>"><?= $r['person_type']=='teacher'?'مدرس':'موظف' ?></span></td>
              <td><?= h($r['person_name'] ?? '-') ?></td>
              <td><?= h($r['check_in']) ?></td>
              <td><?= h($r['check_out']) ?></td>
              <td><span class="badge <?= $s[0] ?>"><?= $s[1] ?></span></td>
              <td><small><?= h($r['notes']) ?></small></td>
              <td>
                <form method="post" style="display:inline" onsubmit="return confirm('حذف السجل؟')">
                  <input type="hidden" name="action" value="del_attendance">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>">
                  <button class="btn btn-sm btn-danger">🗑️</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div></div>

    <div class="modal-overlay" id="addAttModal">
      <div class="modal-box">
        <form method="post">
          <input type="hidden" name="action" value="add_attendance">
          <div class="modal-header"><h5>⏰ تسجيل حضور يدوي</h5><button type="button" class="modal-close" onclick="closeModal('addAttModal')">×</button></div>
          <div class="modal-body">
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label">النوع</label>
                <select name="person_type" id="att_type" class="form-select" onchange="toggleAttPerson()">
                  <option value="teacher">مدرس</option><option value="employee">موظف</option>
                </select>
              </div>
              <div class="col-md-6"><label class="form-label">الشخص</label><select name="person_id" id="att_person" class="form-select" required></select></div>
              <div class="col-md-6"><label class="form-label">التاريخ</label><input type="date" name="att_date" id="att_date" class="form-control" value="<?= date('Y-m-d') ?>" required onchange="checkWorkDay()"></div>
              <div class="col-md-6">
                <label class="form-label">الحالة</label>
                <select name="status" class="form-select">
                  <option value="present">حاضر</option><option value="late">متأخر</option>
                  <option value="absent">غائب</option><option value="leave">إجازة</option>
                </select>
              </div>
              <div class="col-md-6"><label class="form-label">وقت الحضور</label><input type="time" name="check_in" class="form-control" value="<?= date('H:i') ?>"></div>
              <div class="col-md-6"><label class="form-label">وقت الانصراف</label><input type="time" name="check_out" class="form-control"></div>
              <div class="col-12"><label class="form-label">ملاحظات</label><input name="notes" class="form-control"></div>
              <div class="col-12"><div id="workDayAlert"></div></div>
            </div>
          </div>
          <div class="modal-footer"><button class="btn btn-primary">حفظ</button></div>
        </form>
      </div>
    </div>

    <script>
    const attTeachers = <?= json_encode($teachers, JSON_UNESCAPED_UNICODE) ?>;
    const attEmployees = <?= json_encode($employees, JSON_UNESCAPED_UNICODE) ?>;
    const holidaysList = <?= json_encode($pdo->query("SELECT holiday_date,holiday_name FROM holidays")->fetchAll(), JSON_UNESCAPED_UNICODE) ?>;

    function toggleAttPerson(){
      const t = document.getElementById('att_type').value;
      const sel = document.getElementById('att_person');
      const data = t === 'teacher' ? attTeachers : attEmployees;
      sel.innerHTML = '';
      data.forEach(p => sel.innerHTML += `<option value="${p.id}" data-wd="${p.work_days}">${p.name} (${p.employee_code})</option>`);
      checkWorkDay();
    }

    function checkWorkDay(){
      const sel = document.getElementById('att_person');
      const opt = sel.options[sel.selectedIndex];
      const dateVal = document.getElementById('att_date').value;
      const alertBox = document.getElementById('workDayAlert');
      alertBox.innerHTML = '';
      if(!opt || !dateVal) return;
      const hol = holidaysList.find(h => h.holiday_date === dateVal);
      if(hol){
        alertBox.innerHTML = `<div class="alert alert-info">📅 هذا اليوم عطلة رسمية: <strong>${hol.holiday_name}</strong></div>`;
        return;
      }
      const wd = (opt.dataset.wd||'').split(',').map(x=>x.trim());
      const dayOfWeek = new Date(dateVal).getDay();
      if(!wd.includes(String(dayOfWeek))){
        alertBox.innerHTML = `<div class="alert alert-warning">⚠️ تنبيه: هذا الموظف ليس في يوم عمله حسب الجدول المحدد.</div>`;
      }
    }
    document.getElementById('att_person').addEventListener('change', checkWorkDay);
    toggleAttPerson();
    </script>

  <?php
  /* ------------ تقرير الحضور اليومي ------------ */
  elseif ($page === 'attendance_report' && isSupervisor()):
      $report_date = $_GET['report_date'] ?? date('Y-m-d');
      $filter_type = $_GET['filter_type'] ?? 'all';

      $sql = "SELECT a.*, 
          CASE a.person_type 
            WHEN 'teacher' THEN (SELECT name FROM teachers WHERE id=a.person_id)
            ELSE (SELECT name FROM employees WHERE id=a.person_id) END AS person_name,
          CASE a.person_type 
            WHEN 'teacher' THEN (SELECT employee_code FROM teachers WHERE id=a.person_id)
            ELSE (SELECT employee_code FROM employees WHERE id=a.person_id) END AS person_code,
          CASE a.person_type 
            WHEN 'teacher' THEN (SELECT subject FROM teachers WHERE id=a.person_id)
            ELSE (SELECT job_title FROM employees WHERE id=a.person_id) END AS job_info
          FROM attendance a 
          WHERE a.att_date = ?";
      $params = [$report_date];
      if ($filter_type !== 'all') { $sql .= " AND a.person_type = ?"; $params[] = $filter_type; }
      $sql .= " ORDER BY a.person_type, person_name";
      $stmt = $pdo->prepare($sql);
      $stmt->execute($params);
      $attendance_rows = $stmt->fetchAll();

      $total_present = 0; $total_absent = 0; $total_late = 0; $total_leave = 0;
      foreach ($attendance_rows as $row) {
          switch ($row['status']) {
              case 'present': $total_present++; break;
              case 'absent': $total_absent++; break;
              case 'late': $total_late++; break;
              case 'leave': $total_leave++; break;
          }
      }
      $all_teachers = $pdo->query("SELECT id, name, employee_code, subject FROM teachers ORDER BY name")->fetchAll();
      $all_employees = $pdo->query("SELECT id, name, employee_code, job_title FROM employees ORDER BY name")->fetchAll();
      $attended_ids = [];
      foreach ($attendance_rows as $row) { $attended_ids[$row['person_type']][] = $row['person_id']; }
      $absent_teachers = []; $absent_employees = [];
      if ($filter_type === 'all' || $filter_type === 'teacher') {
          foreach ($all_teachers as $t) {
              if (!isset($attended_ids['teacher']) || !in_array($t['id'], $attended_ids['teacher'])) $absent_teachers[] = $t;
          }
      }
      if ($filter_type === 'all' || $filter_type === 'employee') {
          foreach ($all_employees as $e) {
              if (!isset($attended_ids['employee']) || !in_array($e['id'], $attended_ids['employee'])) $absent_employees[] = $e;
          }
      }
      $holiday = isHoliday($pdo, $report_date);
      $dayNames = ['الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
      $dayOfWeek = $dayNames[(int)date('w', strtotime($report_date))];
  ?>
    <div class="print-header">
      <h2>🏫 المدرسة النموذجية</h2>
      <h3>📊 تقرير الحضور والانصراف اليومي</h3>
      <p>التاريخ: <?= h($report_date) ?> (<?= $dayOfWeek ?>)</p>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
      <h3 class="page-title mb-0"><span class="icon">📊</span> تقرير الحضور والانصراف اليومي</h3>
      <button onclick="window.print()" class="btn btn-primary">🖨️ طباعة التقرير</button>
    </div>

    <?php if ($holiday): ?><div class="alert alert-info">📅 هذا اليوم عطلة رسمية: <strong><?= h($holiday['holiday_name']) ?></strong></div><?php endif; ?>

    <div class="report-filter no-print">
      <form method="get" class="row g-2 align-items-end">
        <input type="hidden" name="page" value="attendance_report">
        <div class="col-md-4"><label class="form-label">التاريخ</label><input type="date" name="report_date" class="form-control" value="<?= h($report_date) ?>"></div>
        <div class="col-md-4">
          <label class="form-label">النوع</label>
          <select name="filter_type" class="form-select">
            <option value="all" <?= $filter_type=='all'?'selected':'' ?>>الكل</option>
            <option value="teacher" <?= $filter_type=='teacher'?'selected':'' ?>>المدرسون فقط</option>
            <option value="employee" <?= $filter_type=='employee'?'selected':'' ?>>الموظفون فقط</option>
          </select>
        </div>
        <div class="col-md-4"><button class="btn btn-primary btn-block">🔍 عرض التقرير</button></div>
      </form>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-3 col-sm-6"><div class="stat-card bg-grad-4"><span class="icon">✅</span><div><h2><?= $total_present ?></h2><span>حاضر</span></div></div></div>
      <div class="col-md-3 col-sm-6"><div class="stat-card bg-grad-2"><span class="icon">❌</span><div><h2><?= $total_absent ?></h2><span>غائب (مسجل)</span></div></div></div>
      <div class="col-md-3 col-sm-6"><div class="stat-card bg-grad-5"><span class="icon">⏰</span><div><h2><?= $total_late ?></h2><span>متأخر</span></div></div></div>
      <div class="col-md-3 col-sm-6"><div class="stat-card bg-grad-6"><span class="icon">🏖️</span><div><h2><?= $total_leave ?></h2><span>إجازة</span></div></div></div>
    </div>

    <div class="card">
      <div class="card-header">📋 سجل الحضور المسجل</div>
      <div class="card-body table-responsive">
        <table class="table table-bordered">
          <thead>
            <tr><th>#</th><th>الكود</th><th>النوع</th><th>الاسم</th><th>التخصص/الوظيفة</th><th>الحضور</th><th>الانصراف</th><th>الحالة</th><th>ملاحظات</th></tr>
          </thead>
          <tbody>
            <?php if (!$attendance_rows): ?><tr><td colspan="9" class="text-center text-muted py-4">لا توجد سجلات حضور لهذا اليوم</td></tr><?php endif; ?>
            <?php foreach($attendance_rows as $i => $r):
              $statusMap = ['present'=>['bg-success','حاضر'],'absent'=>['bg-danger','غائب'],'late'=>['bg-warning','متأخر'],'holiday'=>['bg-info','عطلة'],'leave'=>['bg-secondary','إجازة']];
              $s = $statusMap[$r['status']] ?? ['bg-secondary','-'];
            ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td><span class="badge bg-dark"><?= h($r['person_code']) ?></span></td>
                <td><span class="badge <?= $r['person_type']=='teacher'?'bg-info':'bg-secondary' ?>"><?= $r['person_type']=='teacher'?'مدرس':'موظف' ?></span></td>
                <td><?= h($r['person_name'] ?? '-') ?></td>
                <td><?= h($r['job_info'] ?? '-') ?></td>
                <td><?= h($r['check_in']) ?></td>
                <td><?= h($r['check_out']) ?></td>
                <td><span class="badge <?= $s[0] ?>"><?= $s[1] ?></span></td>
                <td><small><?= h($r['notes']) ?></small></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php if ($absent_teachers || $absent_employees): ?>
    <div class="card">
      <div class="card-header">⚠️ لم يسجلوا حضور اليوم (<?= count($absent_teachers) + count($absent_employees) ?>)</div>
      <div class="card-body table-responsive">
        <table class="table table-bordered">
          <thead><tr><th>#</th><th>الكود</th><th>النوع</th><th>الاسم</th><th>التخصص/الوظيفة</th></tr></thead>
          <tbody>
            <?php $idx = 1; foreach($absent_teachers as $t): ?>
              <tr><td><?= $idx++ ?></td><td><span class="badge bg-dark"><?= h($t['employee_code']) ?></span></td><td><span class="badge bg-info">مدرس</span></td><td><?= h($t['name']) ?></td><td><?= h($t['subject']) ?></td></tr>
            <?php endforeach; ?>
            <?php foreach($absent_employees as $e): ?>
              <tr><td><?= $idx++ ?></td><td><span class="badge bg-dark"><?= h($e['employee_code']) ?></span></td><td><span class="badge bg-secondary">موظف</span></td><td><?= h($e['name']) ?></td><td><?= h($e['job_title']) ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <div class="main-footer" style="margin-top:30px;">
      <p>تم إنشاء التقرير بتاريخ: <?= date('Y-m-d H:i:s') ?></p>
      <p>🎨 تصميم وتطوير: <strong>م. عبدالرحيم غيث الطاهر</strong></p>
    </div>

  <?php
  /* ------------ تقرير حضور موظف ------------ */
  elseif ($page === 'employee_attendance_report' && isSupervisor()):
      $person_type = $_GET['person_type'] ?? 'teacher';
      $person_id = (int)($_GET['person_id'] ?? 0);
      $date_from = $_GET['date_from'] ?? date('Y-m-01');
      $date_to = $_GET['date_to'] ?? date('Y-m-d');

      $teachers = $pdo->query("SELECT id, name, employee_code, subject FROM teachers ORDER BY name")->fetchAll();
      $employees = $pdo->query("SELECT id, name, employee_code, job_title FROM employees ORDER BY name")->fetchAll();

      $person = null;
      $attendance_rows = [];
      $summary = ['present'=>0,'absent'=>0,'late'=>0,'leave'=>0,'total'=>0];
      if ($person_id) {
          if ($person_type === 'teacher') {
              $stmt = $pdo->prepare("SELECT id, name, employee_code, subject AS job_info, work_days, work_start, work_end FROM teachers WHERE id=?");
          } else {
              $stmt = $pdo->prepare("SELECT id, name, employee_code, job_title AS job_info, work_days, work_start, work_end FROM employees WHERE id=?");
          }
          $stmt->execute([$person_id]);
          $person = $stmt->fetch();
          if ($person) {
              $stmt = $pdo->prepare("SELECT * FROM attendance WHERE person_type=? AND person_id=? AND att_date BETWEEN ? AND ? ORDER BY att_date DESC");
              $stmt->execute([$person_type, $person_id, $date_from, $date_to]);
              $attendance_rows = $stmt->fetchAll();
              foreach ($attendance_rows as $row) {
                  $summary['total']++;
                  switch ($row['status']) {
                      case 'present': $summary['present']++; break;
                      case 'absent': $summary['absent']++; break;
                      case 'late': $summary['late']++; break;
                      case 'leave': $summary['leave']++; break;
                  }
              }
          }
      }
      $dayNames = ['الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
  ?>
    <div class="print-header">
      <h2>🏫 المدرسة النموذجية</h2>
      <h3>📋 تقرير الحضور والانصراف الفردي</h3>
      <?php if ($person): ?>
        <p><?= h($person['name']) ?> - <?= h($person['employee_code']) ?></p>
        <p>الفترة: من <?= h($date_from) ?> إلى <?= h($date_to) ?></p>
      <?php endif; ?>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
      <h3 class="page-title mb-0"><span class="icon">📋</span> تقرير حضور موظف / مدرس</h3>
      <?php if ($person): ?><button onclick="window.print()" class="btn btn-primary">🖨️ طباعة التقرير</button><?php endif; ?>
    </div>

    <div class="report-filter no-print">
      <form method="get" class="row g-2 align-items-end">
        <input type="hidden" name="page" value="employee_attendance_report">
        <div class="col-md-3">
          <label class="form-label">النوع</label>
          <select name="person_type" id="rep_person_type" class="form-select" onchange="loadReportPersons()">
            <option value="teacher" <?= $person_type=='teacher'?'selected':'' ?>>مدرس</option>
            <option value="employee" <?= $person_type=='employee'?'selected':'' ?>>موظف</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">الشخص</label>
          <select name="person_id" id="rep_person_id" class="form-select"><option value="">-- اختر --</option></select>
        </div>
        <div class="col-md-2"><label class="form-label">من تاريخ</label><input type="date" name="date_from" class="form-control" value="<?= h($date_from) ?>"></div>
        <div class="col-md-2"><label class="form-label">إلى تاريخ</label><input type="date" name="date_to" class="form-control" value="<?= h($date_to) ?>"></div>
        <div class="col-md-2"><button class="btn btn-primary btn-block">🔍 عرض</button></div>
      </form>
    </div>

    <?php if ($person): ?>
      <div class="card mb-4">
        <div class="card-header">👤 معلومات <?= $person_type=='teacher'?'المدرس':'الموظف' ?></div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-3"><strong>الاسم:</strong> <?= h($person['name']) ?></div>
            <div class="col-md-3"><strong>الكود:</strong> <span class="badge bg-dark"><?= h($person['employee_code']) ?></span></div>
            <div class="col-md-3"><strong>النوع:</strong> <span class="badge <?= $person_type=='teacher'?'bg-info':'bg-secondary' ?>"><?= $person_type=='teacher'?'مدرس':'موظف' ?></span></div>
            <div class="col-md-3"><strong><?= $person_type=='teacher'?'المادة':'الوظيفة' ?>:</strong> <?= h($person['job_info']) ?></div>
          </div>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6"><div class="stat-card bg-grad-4"><span class="icon">✅</span><div><h2><?= $summary['present'] ?></h2><span>حاضر</span></div></div></div>
        <div class="col-md-3 col-sm-6"><div class="stat-card bg-grad-2"><span class="icon">❌</span><div><h2><?= $summary['absent'] ?></h2><span>غائب</span></div></div></div>
        <div class="col-md-3 col-sm-6"><div class="stat-card bg-grad-5"><span class="icon">⏰</span><div><h2><?= $summary['late'] ?></h2><span>متأخر</span></div></div></div>
        <div class="col-md-3 col-sm-6"><div class="stat-card bg-grad-6"><span class="icon">🏖️</span><div><h2><?= $summary['leave'] ?></h2><span>إجازة</span></div></div></div>
      </div>

      <div class="card">
        <div class="card-header">📅 سجل الحضور والانصراف</div>
        <div class="card-body table-responsive">
          <table class="table table-bordered">
            <thead><tr><th>#</th><th>التاريخ</th><th>اليوم</th><th>الحضور</th><th>الانصراف</th><th>الحالة</th><th>ملاحظات</th></tr></thead>
            <tbody>
              <?php if (!$attendance_rows): ?><tr><td colspan="7" class="text-center text-muted py-4">لا توجد سجلات في هذه الفترة</td></tr><?php endif; ?>
              <?php foreach($attendance_rows as $i => $r):
                $statusMap = ['present'=>['bg-success','حاضر'],'absent'=>['bg-danger','غائب'],'late'=>['bg-warning','متأخر'],'holiday'=>['bg-info','عطلة'],'leave'=>['bg-secondary','إجازة']];
                $s = $statusMap[$r['status']] ?? ['bg-secondary','-'];
                $dayName = $dayNames[(int)date('w', strtotime($r['att_date']))];
              ?>
                <tr>
                  <td><?= $i+1 ?></td>
                  <td><?= h($r['att_date']) ?></td>
                  <td><?= $dayName ?></td>
                  <td><?= h($r['check_in'] ?: '-') ?></td>
                  <td><?= h($r['check_out'] ?: '-') ?></td>
                  <td><span class="badge <?= $s[0] ?>"><?= $s[1] ?></span></td>
                  <td><small><?= h($r['notes']) ?></small></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

    <script>
    const repTeachers = <?= json_encode($teachers, JSON_UNESCAPED_UNICODE) ?>;
    const repEmployees = <?= json_encode($employees, JSON_UNESCAPED_UNICODE) ?>;
    const selectedPersonId = <?= $person_id ?>;
    function loadReportPersons(){
      const type = document.getElementById('rep_person_type').value;
      const sel = document.getElementById('rep_person_id');
      const data = type === 'teacher' ? repTeachers : repEmployees;
      sel.innerHTML = '<option value="">-- اختر --</option>';
      data.forEach(p => {
        const selected = (p.id == selectedPersonId) ? 'selected' : '';
        sel.innerHTML += `<option value="${p.id}" ${selected}>${p.name} (${p.employee_code})</option>`;
      });
    }
    loadReportPersons();
    </script>

  <?php
  /* ------------ العطلات ------------ */
  elseif ($page === 'holidays' && isSupervisor()):
      $rows = $pdo->query("SELECT * FROM holidays ORDER BY holiday_date DESC")->fetchAll();
  ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="page-title mb-0"><span class="icon">📅</span> العطلات الرسمية</h3>
      <button class="btn btn-primary" onclick="openModal('addHolidayModal')">➕ إضافة عطلة</button>
    </div>
    <div class="card"><div class="card-body table-responsive">
      <table class="table">
        <thead><tr><th>#</th><th>اسم العطلة</th><th>التاريخ</th><th>الوصف</th><th></th></tr></thead>
        <tbody>
          <?php if (!$rows): ?><tr><td colspan="5" class="text-center text-muted py-4">لا توجد بيانات</td></tr><?php endif; ?>
          <?php foreach($rows as $r): ?>
            <tr>
              <td><?= $r['id'] ?></td>
              <td><span class="badge bg-info"><?= h($r['holiday_name']) ?></span></td>
              <td><?= h($r['holiday_date']) ?></td>
              <td><?= h($r['description']) ?></td>
              <td>
                <form method="post" style="display:inline" onsubmit="return confirm('حذف العطلة؟')">
                  <input type="hidden" name="action" value="del_holiday">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>">
                  <button class="btn btn-sm btn-danger">🗑️</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div></div>

    <div class="modal-overlay" id="addHolidayModal">
      <div class="modal-box">
        <form method="post">
          <input type="hidden" name="action" value="add_holiday">
          <div class="modal-header"><h5>📅 إضافة عطلة</h5><button type="button" class="modal-close" onclick="closeModal('addHolidayModal')">×</button></div>
          <div class="modal-body">
            <div class="mb-2"><label class="form-label">اسم العطلة *</label><input name="holiday_name" class="form-control" required></div>
            <div class="mb-2"><label class="form-label">التاريخ *</label><input type="date" name="holiday_date" class="form-control" required></div>
            <div class="mb-2"><label class="form-label">الوصف</label><input name="description" class="form-control"></div>
          </div>
          <div class="modal-footer"><button class="btn btn-primary">حفظ</button></div>
        </form>
      </div>
    </div>

  <?php
  /* ------------ الطلبة ------------ */
  elseif ($page === 'students' && isDataEntry()):
      $grade_levels = $pdo->query("SELECT * FROM grade_levels ORDER BY level_number")->fetchAll();
      $classes = $pdo->query("SELECT c.*, gl.level_name FROM classes c LEFT JOIN grade_levels gl ON gl.id=c.grade_level_id ORDER BY gl.level_number, c.class_name, c.section")->fetchAll();
      $years = $pdo->query("SELECT * FROM academic_years ORDER BY id DESC")->fetchAll();
      $rows = $pdo->query("SELECT s.*, c.class_name, c.section FROM students s LEFT JOIN classes c ON c.id=s.class_id ORDER BY s.id DESC")->fetchAll();
  ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="page-title mb-0"><span class="icon">🎓</span> إدارة الطلبة</h3>
      <div>
        <button class="btn btn-outline-secondary" onclick="openModal('addYearModal')">➕ سنة دراسية</button>
        <button class="btn btn-primary" onclick="openModal('addStdModal')">➕ إضافة طالب</button>
      </div>
    </div>

    <div class="card"><div class="card-body table-responsive">
      <table class="table">
        <thead><tr><th>الكود</th><th>الاسم</th><th>الجنس</th><th>الصف</th><th>الشعبة</th><th>السنة الدراسية</th><th>الفصل</th><th>ولي الأمر</th><th>الهاتف</th><th>الحالة</th><th>إجراءات</th></tr></thead>
        <tbody>
          <?php if (!$rows): ?><tr><td colspan="11" class="text-center text-muted py-4">لا توجد بيانات</td></tr><?php endif; ?>
          <?php foreach($rows as $r): ?>
            <tr>
              <td><span class="badge bg-dark"><?= h($r['student_code']) ?></span></td>
              <td><?= h($r['name']) ?></td>
              <td><?= $r['gender']=='male' ? 'ذكر' : 'أنثى' ?></td>
              <td><?= h($r['class_name'] ?? '-') ?></td>
              <td><?= h($r['section'] ?? '-') ?></td>
              <td><?= h($r['academic_year']) ?></td>
              <td><?= $r['semester']=='first'?'الأول':'الثاني' ?></td>
              <td><?= h($r['guardian_name']) ?></td>
              <td><?= h($r['guardian_phone']) ?></td>
              <td>
                <?php 
                $statusMap = ['active'=>['bg-success','نشط'],'graduated'=>['bg-primary','متخرج'],'failed'=>['bg-danger','راسب'],'withdrawn'=>['bg-secondary','منسحب']];
                $st = $statusMap[$r['status'] ?? 'active'] ?? ['bg-secondary','-'];
                ?>
                <span class="badge <?= $st[0] ?>"><?= $st[1] ?></span>
              </td>
              <td>
                <a href="?page=student_report&student_id=<?= $r['id'] ?>" class="btn btn-sm btn-success" title="كشف الدرجات">📄</a>
                <a href="?page=reports&student_id=<?= $r['id'] ?>" class="btn btn-sm btn-info" title="تقرير">📈</a>
                <button class="btn btn-sm btn-warning" onclick='editStd(<?= json_encode($r, JSON_UNESCAPED_UNICODE) ?>)'>✏️</button>
                <?php if (isAdmin()): ?>
                <form method="post" style="display:inline" onsubmit="return confirm('حذف الطالب؟')">
                  <input type="hidden" name="action" value="del_student">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>">
                  <button class="btn btn-sm btn-danger">🗑️</button>
                </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div></div>

    <?php
    $classOptions = ''; foreach($classes as $c){ $classOptions .= "<option value='{$c['id']}'>".h($c['class_name'])." ".($c['section'] ? '- '.h($c['section']) : '')."</option>"; }
    $yearOptions = ''; foreach($years as $y){ $yearOptions .= "<option value='{$y['year_name']}'>{$y['year_name']}</option>"; }
    ?>

    <div class="modal-overlay" id="addStdModal">
      <div class="modal-box modal-lg">
        <form method="post">
          <input type="hidden" name="action" value="add_student">
          <div class="modal-header"><h5>➕ إضافة طالب</h5><button type="button" class="modal-close" onclick="closeModal('addStdModal')">×</button></div>
          <div class="modal-body">
            <div class="row g-2">
              <div class="col-md-6"><label class="form-label">الاسم الرباعي *</label><input name="name" class="form-control" required></div>
              <div class="col-md-6"><label class="form-label">الرقم الوطني</label><input name="national_id" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">تاريخ الميلاد</label><input type="date" name="birth_date" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">الجنس</label><select name="gender" class="form-select"><option value="male">ذكر</option><option value="female">أنثى</option></select></div>
              <div class="col-md-6"><label class="form-label">الفصل</label><select name="class_id" class="form-select"><option value="">-- اختر --</option><?= $classOptions ?></select></div>
              <div class="col-md-6"><label class="form-label">السنة الدراسية</label><select name="academic_year" class="form-select"><?= $yearOptions ?></select></div>
              <div class="col-md-6"><label class="form-label">الفصل الدراسي</label><select name="semester" class="form-select"><option value="first">الأول</option><option value="second">الثاني</option></select></div>
              <div class="col-md-6"><label class="form-label">تاريخ التسجيل</label><input type="date" name="enroll_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
              <div class="col-md-6"><label class="form-label">ولي الأمر</label><input name="guardian_name" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">هاتف ولي الأمر</label><input name="guardian_phone" class="form-control"></div>
              <div class="col-12"><label class="form-label">العنوان</label><textarea name="address" class="form-control" rows="2"></textarea></div>
            </div>
          </div>
          <div class="modal-footer"><button class="btn btn-primary">حفظ</button></div>
        </form>
      </div>
    </div>

    <div class="modal-overlay" id="editStdModal">
      <div class="modal-box modal-lg">
        <form method="post">
          <input type="hidden" name="action" value="edit_student">
          <input type="hidden" name="id" id="es_id">
          <div class="modal-header"><h5>✏️ تعديل طالب</h5><button type="button" class="modal-close" onclick="closeModal('editStdModal')">×</button></div>
          <div class="modal-body">
            <div class="row g-2">
              <div class="col-md-6"><label class="form-label">الاسم</label><input name="name" id="es_name" class="form-control" required></div>
              <div class="col-md-6"><label class="form-label">الرقم الوطني</label><input name="national_id" id="es_national_id" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">تاريخ الميلاد</label><input type="date" name="birth_date" id="es_birth_date" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">الجنس</label><select name="gender" id="es_gender" class="form-select"><option value="male">ذكر</option><option value="female">أنثى</option></select></div>
              <div class="col-md-6"><label class="form-label">الفصل</label><select name="class_id" id="es_class_id" class="form-select"><option value="">-- اختر --</option><?= $classOptions ?></select></div>
              <div class="col-md-6"><label class="form-label">السنة الدراسية</label><select name="academic_year" id="es_academic_year" class="form-select"><?= $yearOptions ?></select></div>
              <div class="col-md-6"><label class="form-label">الفصل الدراسي</label><select name="semester" id="es_semester" class="form-select"><option value="first">الأول</option><option value="second">الثاني</option></select></div>
              <div class="col-md-6"><label class="form-label">تاريخ التسجيل</label><input type="date" name="enroll_date" id="es_enroll_date" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">ولي الأمر</label><input name="guardian_name" id="es_guardian_name" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">هاتف ولي الأمر</label><input name="guardian_phone" id="es_guardian_phone" class="form-control"></div>
              <div class="col-md-6">
                <label class="form-label">الحالة</label>
                <select name="status" id="es_status" class="form-select">
                  <option value="active">نشط</option>
                  <option value="graduated">متخرج</option>
                  <option value="failed">راسب</option>
                  <option value="withdrawn">منسحب</option>
                </select>
              </div>
              <div class="col-12"><label class="form-label">العنوان</label><textarea name="address" id="es_address" class="form-control" rows="2"></textarea></div>
            </div>
          </div>
          <div class="modal-footer"><button class="btn btn-primary">تحديث</button></div>
        </form>
      </div>
    </div>

    <div class="modal-overlay" id="addYearModal">
      <div class="modal-box">
        <form method="post">
          <input type="hidden" name="action" value="add_academic_year">
          <div class="modal-header"><h5>📅 إضافة سنة دراسية</h5><button type="button" class="modal-close" onclick="closeModal('addYearModal')">×</button></div>
          <div class="modal-body">
            <div class="mb-2"><label class="form-label">اسم السنة (مثال: 2025-2026)</label><input name="year_name" class="form-control" required></div>
          </div>
          <div class="modal-footer"><button class="btn btn-primary">حفظ</button></div>
        </form>
      </div>
    </div>

    <script>
    function editStd(d){
      document.getElementById('es_id').value=d.id;
      document.getElementById('es_name').value=d.name||'';
      document.getElementById('es_national_id').value=d.national_id||'';
      document.getElementById('es_birth_date').value=d.birth_date||'';
      document.getElementById('es_gender').value=d.gender||'male';
      document.getElementById('es_class_id').value=d.class_id||'';
      document.getElementById('es_academic_year').value=d.academic_year||'';
      document.getElementById('es_semester').value=d.semester||'first';
      document.getElementById('es_enroll_date').value=d.enroll_date||'';
      document.getElementById('es_guardian_name').value=d.guardian_name||'';
      document.getElementById('es_guardian_phone').value=d.guardian_phone||'';
      document.getElementById('es_status').value=d.status||'active';
      document.getElementById('es_address').value=d.address||'';
      openModal('editStdModal');
    }
    </script>

  <?php
  /* ------------ الدرجات ------------ */
  elseif ($page === 'grades' && isDataEntry()):
      $students = $pdo->query("SELECT id,name,academic_year,student_code FROM students ORDER BY name")->fetchAll();
      $subjects = $pdo->query("SELECT sub.*, gl.level_name FROM subjects sub LEFT JOIN grade_levels gl ON gl.id=sub.grade_level_id ORDER BY sub.academic_year, sub.subject_name")->fetchAll();
      $years = $pdo->query("SELECT * FROM academic_years ORDER BY id DESC")->fetchAll();
      $rows = $pdo->query("SELECT g.*, s.name AS student_name, sub.subject_name, sub.max_grade, sub.subject_type
          FROM grades g JOIN students s ON s.id=g.student_id JOIN subjects sub ON sub.id=g.subject_id
          ORDER BY g.id DESC")->fetchAll();
  ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="page-title mb-0"><span class="icon">📋</span> إدارة الدرجات</h3>
      <button class="btn btn-primary" onclick="openModal('addGradeModal')">➕ إضافة درجة</button>
    </div>

    <div class="card"><div class="card-body table-responsive">
      <table class="table">
        <thead><tr><th>#</th><th>الطالب</th><th>المادة</th><th>نوع المادة</th><th>السنة الدراسية</th><th>أعمال السنة</th><th>الامتحان النهائي</th><th>المجموع</th><th>من</th><th>التقدير</th><th></th></tr></thead>
        <tbody>
          <?php if (!$rows): ?><tr><td colspan="11" class="text-center text-muted py-4">لا توجد بيانات</td></tr><?php endif; ?>
          <?php foreach($rows as $r):
            $pct = $r['max_grade']>0 ? ($r['total']/$r['max_grade'])*100 : 0;
            $subject_type = $r['subject_type'] ?? 'local';
            $evaluation = getGradeEvaluation($pct, $subject_type);
          ?>
            <tr>
              <td><?= $r['id'] ?></td>
              <td><?= h($r['student_name']) ?></td>
              <td><?= h($r['subject_name']) ?></td>
              <td>
                <span class="subject-type-badge <?= $subject_type === 'international' ? 'subject-type-international' : 'subject-type-local' ?>">
                  <?= getSubjectTypeName($subject_type) ?>
                </span>
              </td>
              <td><?= h($r['academic_year'] ?? '-') ?></td>
              <td><?= number_format($r['coursework'],2) ?></td>
              <td><?= number_format($r['final_exam'],2) ?></td>
              <td><strong><?= number_format($r['total'],2) ?></strong></td>
              <td><?= $r['max_grade'] ?></td>
              <td>
                <span class="badge <?= $evaluation['color'] ?>">
                  <?= $evaluation['grade'] ?>
                </span>
              </td>
              <td>
                <form method="post" style="display:inline" onsubmit="return confirm('حذف الدرجة؟')">
                  <input type="hidden" name="action" value="del_grade">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>">
                  <button class="btn btn-sm btn-danger">🗑️</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div></div>

    <?php
    $stdOpt = ''; foreach($students as $s){ $stdOpt .= "<option value='{$s['id']}' data-year='{$s['academic_year']}'>".h($s['name'])." (".h($s['student_code']).")</option>"; }
    $yearOpt = ''; foreach($years as $y){ $yearOpt .= "<option value='{$y['year_name']}'>{$y['year_name']}</option>"; }
    $subjectsJson = json_encode($subjects, JSON_UNESCAPED_UNICODE);
    ?>

    <div class="modal-overlay" id="addGradeModal">
      <div class="modal-box">
        <form method="post">
          <input type="hidden" name="action" value="add_grade">
          <div class="modal-header"><h5>➕ إضافة درجة</h5><button type="button" class="modal-close" onclick="closeModal('addGradeModal')">×</button></div>
          <div class="modal-body">
            <div class="row g-2">
              <div class="col-md-12"><label class="form-label">الطالب *</label><select name="student_id" id="gr_student" class="form-select" required onchange="loadSubjects()"><?= $stdOpt ?></select></div>
              <div class="col-md-12"><label class="form-label">المادة *</label><select name="subject_id" id="gr_subject" class="form-select" required></select></div>
              <div class="col-md-6"><label class="form-label">أعمال السنة (من 40)</label><input type="number" step="0.01" name="coursework" class="form-control" value="0" max="40"></div>
              <div class="col-md-6"><label class="form-label">الامتحان النهائي (من 60)</label><input type="number" step="0.01" name="final_exam" class="form-control" value="0" max="60"></div>
              <div class="col-md-6"><label class="form-label">الفصل</label><select name="semester" class="form-select"><option value="first">الأول</option><option value="second">الثاني</option></select></div>
              <div class="col-md-6"><label class="form-label">السنة</label><input type="number" name="year" class="form-control" value="<?= date('Y') ?>"></div>
              <div class="col-md-12"><label class="form-label">السنة الدراسية</label><select name="academic_year" class="form-select"><?= $yearOpt ?></select></div>
            </div>
          </div>
          <div class="modal-footer"><button class="btn btn-primary">حفظ</button></div>
        </form>
      </div>
    </div>

    <script>
    const subjectsList = <?= $subjectsJson ?>;
    function loadSubjects(){
      const sel = document.getElementById('gr_student');
      const opt = sel.options[sel.selectedIndex];
      const year = opt ? opt.dataset.year : '';
      const subSel = document.getElementById('gr_subject');
      subSel.innerHTML = '';
      subjectsList.filter(s => s.academic_year === year).forEach(s => {
        const typeLabel = s.subject_type === 'international' ? ' [دولية]' : ' [محلية]';
        subSel.innerHTML += `<option value="${s.id}">${s.subject_name} (${s.academic_year})${typeLabel}</option>`;
      });
      if(subSel.innerHTML === '') subSel.innerHTML = '<option value="">لا توجد مواد لهذه السنة</option>';
      const yearSelect = document.querySelector('select[name="academic_year"]');
      if (yearSelect && year) {
        for (let opt of yearSelect.options) {
          if (opt.value === year) { opt.selected = true; break; }
        }
      }
    }
    document.getElementById('gr_student').addEventListener('change', loadSubjects);
    loadSubjects();
    </script>

  <?php
  /* ------------ كشف الدرجات ------------ */
  elseif ($page === 'student_report' && isDataEntry()):
      $students = $pdo->query("SELECT s.*, c.class_name, c.section FROM students s LEFT JOIN classes c ON c.id=s.class_id ORDER BY s.name")->fetchAll();
      $sid = (int)($_GET['student_id'] ?? 0);
      $student = null;
      $all_grades_by_year = [];
      $student_years = [];
      $notes = [];

      if ($sid) {
          $stmt = $pdo->prepare("SELECT s.*, c.class_name, c.section FROM students s LEFT JOIN classes c ON c.id=s.class_id WHERE s.id=?");
          $stmt->execute([$sid]);
          $student = $stmt->fetch();
          if ($student) {
              $stmt = $pdo->prepare("SELECT DISTINCT academic_year FROM grades WHERE student_id=? AND academic_year IS NOT NULL ORDER BY academic_year DESC");
              $stmt->execute([$sid]);
              $student_years = $stmt->fetchAll(PDO::FETCH_COLUMN);
              foreach ($student_years as $year) {
                  $stmt = $pdo->prepare("SELECT g.*, sub.subject_name, sub.max_grade, sub.subject_type 
                      FROM grades g JOIN subjects sub ON sub.id=g.subject_id 
                      WHERE g.student_id=? AND g.academic_year=? 
                      ORDER BY g.semester, sub.subject_name");
                  $stmt->execute([$sid, $year]);
                  $all_grades_by_year[$year] = $stmt->fetchAll();
              }
              $stmt = $pdo->prepare("SELECT * FROM notes WHERE person_type='student' AND person_id=? ORDER BY note_date DESC");
              $stmt->execute([$sid]);
              $notes = $stmt->fetchAll();
          }
      }
  ?>
    <h3 class="page-title no-print"><span class="icon">📄</span> كشف الدرجات</h3>

    <div class="card no-print">
      <div class="card-body">
        <form method="get" class="row g-2">
          <input type="hidden" name="page" value="student_report">
          <div class="col-md-8">
            <label class="form-label">اختر الطالب</label>
            <select name="student_id" class="form-select" onchange="this.form.submit()">
              <option value="">-- اختر طالباً --</option>
              <?php foreach($students as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $sid==$s['id']?'selected':'' ?>>
                  <?= h($s['name']) ?> (<?= h($s['student_code']) ?>) - <?= h($s['class_name'] ?? '') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4 d-flex align-items-end">
            <button class="btn btn-primary btn-block">🔍 عرض كشف الدرجات</button>
          </div>
        </form>
      </div>
    </div>

    <?php if ($student): ?>
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>🎓 كشف درجات: <?= h($student['name']) ?> (<?= h($student['student_code']) ?>)</span>
          <button onclick="window.print()" class="btn btn-sm btn-outline-primary no-print">🖨️ طباعة</button>
        </div>
        <div class="card-body">
          <div class="print-header">
            <h2>🏫 المدرسة النموذجية</h2>
            <h3>📄 كشف الدرجات الرسمي</h3>
            <p><?= h($student['name']) ?> - <?= h($student['student_code']) ?></p>
          </div>

          <div class="row mb-3">
            <div class="col-md-4"><strong>الصف:</strong> <?= h($student['class_name'] ?? '-') ?></div>
            <div class="col-md-4"><strong>السنة الدراسية:</strong> <?= h($student['academic_year']) ?></div>
            <div class="col-md-4"><strong>ولي الأمر:</strong> <?= h($student['guardian_name']) ?></div>
          </div>

          <?php if (empty($all_grades_by_year)): ?>
            <div class="alert alert-warning">⚠️ لا توجد درجات مسجلة لهذا الطالب بعد.</div>
          <?php else: ?>
            <?php foreach($all_grades_by_year as $year => $grades):
              $year_avg = 0;
              if (count($grades)) {
                  $sum = 0; $cnt = 0;
                  foreach($grades as $g){ if($g['max_grade']>0){ $sum += ($g['total']/$g['max_grade'])*100; $cnt++; } }
                  $year_avg = $cnt ? $sum/$cnt : 0;
              }
            ?>
              <div class="card mb-4">
                <div class="card-header" style="background: linear-gradient(135deg, #667eea, #764ba2); color: #fff;">
                  📅 السنة الدراسية: <?= h($year) ?>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-bordered">
                      <thead>
                        <tr><th>المادة</th><th>النوع</th><th>الفصل</th><th>أعمال السنة</th><th>الامتحان النهائي</th><th>المجموع</th><th>من</th><th>النسبة</th><th>التقدير</th></tr>
                      </thead>
                      <tbody>
                        <?php foreach($grades as $g):
                          $pct = $g['max_grade']>0 ? ($g['total']/$g['max_grade'])*100 : 0;
                          $subject_type = $g['subject_type'] ?? 'local';
                          $evaluation = getGradeEvaluation($pct, $subject_type);
                        ?>
                          <tr>
                            <td><?= h($g['subject_name']) ?></td>
                            <td>
                              <span class="subject-type-badge <?= $subject_type === 'international' ? 'subject-type-international' : 'subject-type-local' ?>">
                                <?= getSubjectTypeName($subject_type) ?>
                              </span>
                            </td>
                            <td><?= $g['semester']=='first'?'الأول':'الثاني' ?></td>
                            <td><?= number_format($g['coursework'],2) ?></td>
                            <td><?= number_format($g['final_exam'],2) ?></td>
                            <td><strong><?= number_format($g['total'],2) ?></strong></td>
                            <td><?= $g['max_grade'] ?></td>
                            <td><?= number_format($pct,2) ?>%</td>
                            <td>
                              <span class="badge <?= $evaluation['color'] ?>">
                                <?= $evaluation['grade'] ?>
                              </span>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                      <tfoot>
                        <tr><th colspan="5" style="text-align:left">معدل السنة:</th><th colspan="4"><?= number_format($year_avg,2) ?>%</th></tr>
                      </tfoot>
                    </table>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>

          <?php if ($notes): ?>
            <h5 class="mt-4">💬 الملاحظات والسلوك</h5>
            <ul class="list-group">
              <?php foreach($notes as $n): ?>
                <li class="list-group-item">
                  <small class="text-muted"><?= h($n['note_date']) ?> - <?= h($n['created_by']) ?></small><br>
                  <?= h($n['note']) ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

  <?php
  /* ------------ الملاحظات ------------ */
  elseif ($page === 'notes' && isDataEntry()):
      $teachers = $pdo->query("SELECT id,name FROM teachers ORDER BY name")->fetchAll();
      $employees = $pdo->query("SELECT id,name FROM employees ORDER BY name")->fetchAll();
      $students = $pdo->query("SELECT id,name FROM students ORDER BY name")->fetchAll();
      $rows = $pdo->query("SELECT n.*, 
        CASE n.person_type 
          WHEN 'student' THEN (SELECT name FROM students WHERE id=n.person_id)
          WHEN 'teacher' THEN (SELECT name FROM teachers WHERE id=n.person_id)
          ELSE (SELECT name FROM employees WHERE id=n.person_id) END AS person_name
        FROM notes n ORDER BY n.id DESC")->fetchAll();
  ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="page-title mb-0"><span class="icon">💬</span> الملاحظات</h3>
      <button class="btn btn-primary" onclick="openModal('addNoteModal')">➕ إضافة ملاحظة</button>
    </div>

    <div class="card"><div class="card-body table-responsive">
      <table class="table">
        <thead><tr><th>#</th><th>النوع</th><th>الاسم</th><th>الملاحظة</th><th>التاريخ</th><th>بواسطة</th><th></th></tr></thead>
        <tbody>
          <?php if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-4">لا توجد بيانات</td></tr><?php endif; ?>
          <?php foreach($rows as $r):
            $typeLabels = ['student'=>'طالب','teacher'=>'مدرس','employee'=>'موظف'];
            $badgeColors = ['student'=>'bg-success','teacher'=>'bg-info','employee'=>'bg-secondary'];
          ?>
            <tr>
              <td><?= $r['id'] ?></td>
              <td><span class="badge <?= $badgeColors[$r['person_type']] ?>"><?= $typeLabels[$r['person_type']] ?></span></td>
              <td><?= h($r['person_name'] ?? '-') ?></td>
              <td><?= h($r['note']) ?></td>
              <td><?= h($r['note_date']) ?></td>
              <td><?= h($r['created_by']) ?></td>
              <td>
                <?php if (isAdmin()): ?>
                <form method="post" style="display:inline" onsubmit="return confirm('حذف الملاحظة؟')">
                  <input type="hidden" name="action" value="del_note">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>">
                  <button class="btn btn-sm btn-danger">🗑️</button>
                </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div></div>

    <div class="modal-overlay" id="addNoteModal">
      <div class="modal-box">
        <form method="post">
          <input type="hidden" name="action" value="add_note">
          <div class="modal-header"><h5>💬 إضافة ملاحظة</h5><button type="button" class="modal-close" onclick="closeModal('addNoteModal')">×</button></div>
          <div class="modal-body">
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label">نوع الشخص *</label>
                <select name="person_type" id="note_type" class="form-select" onchange="loadNotePersons()" required>
                  <option value="student">طالب</option>
                  <option value="teacher">مدرس</option>
                  <option value="employee">موظف</option>
                </select>
              </div>
              <div class="col-md-6"><label class="form-label">الاسم *</label><select name="person_id" id="note_person" class="form-select" required></select></div>
              <div class="col-md-6"><label class="form-label">تاريخ الملاحظة</label><input type="date" name="note_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
              <div class="col-12"><label class="form-label">الملاحظة *</label><textarea name="note" class="form-control" rows="3" required></textarea></div>
            </div>
          </div>
          <div class="modal-footer"><button class="btn btn-primary">حفظ</button></div>
        </form>
      </div>
    </div>

    <script>
    const noteStudents = <?= json_encode($students, JSON_UNESCAPED_UNICODE) ?>;
    const noteTeachers = <?= json_encode($teachers, JSON_UNESCAPED_UNICODE) ?>;
    const noteEmployees = <?= json_encode($employees, JSON_UNESCAPED_UNICODE) ?>;
    function loadNotePersons(){
      const t = document.getElementById('note_type').value;
      const sel = document.getElementById('note_person');
      const data = t === 'student' ? noteStudents : (t === 'teacher' ? noteTeachers : noteEmployees);
      sel.innerHTML = '';
      data.forEach(p => sel.innerHTML += `<option value="${p.id}">${p.name}</option>`);
    }
    loadNotePersons();
    </script>

  <?php
  /* ------------ أولياء الأمور ------------ */
  elseif ($page === 'guardians' && isDataEntry()):
      $students = $pdo->query("SELECT id,name,student_code FROM students ORDER BY name")->fetchAll();
      $rows = $pdo->query("SELECT g.*, s.name AS student_name, s.student_code FROM guardians g JOIN students s ON s.id=g.student_id ORDER BY g.id DESC")->fetchAll();

      $guardian_success = $_SESSION['guardian_success'] ?? '';
      $guardian_error = $_SESSION['guardian_error'] ?? '';
      unset($_SESSION['guardian_success'], $_SESSION['guardian_error']);
  ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="page-title mb-0"><span class="icon">👪</span> أولياء الأمور</h3>
      <button class="btn btn-primary" onclick="openModal('addGuardianModal')">➕ إضافة ولي أمر</button>
    </div>

    <?php if ($guardian_success): ?><div class="alert alert-success"><?= h($guardian_success) ?></div><?php endif; ?>
    <?php if ($guardian_error): ?><div class="alert alert-danger"><?= h($guardian_error) ?></div><?php endif; ?>

    <div class="alert alert-info">
      <strong>ℹ️ كيف يدخل ولي الأمر؟</strong><br>
      1. يفتح صفحة تسجيل الدخول<br>
      2. يختار تبويب <strong>"دخول أولياء الأمور"</strong><br>
      3. يُدخل <strong>رقم الهاتف</strong> + <strong>الكود المرجعي</strong> الظاهر في الجدول أدناه
    </div>

    <div class="card"><div class="card-body table-responsive">
      <table class="table">
        <thead><tr><th>#</th><th>الاسم</th><th>الهاتف</th><th>الكود المرجعي</th><th>الطالب</th><th>كود الطالب</th><th>إجراءات</th></tr></thead>
        <tbody>
          <?php if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-4">لا توجد بيانات</td></tr><?php endif; ?>
          <?php foreach($rows as $r): ?>
            <tr>
              <td><?= $r['id'] ?></td>
              <td><?= h($r['full_name']) ?></td>
              <td><?= h($r['phone']) ?></td>
              <td><span class="badge bg-warning"><?= h($r['reference_code']) ?></span></td>
              <td><?= h($r['student_name']) ?></td>
              <td><span class="badge bg-dark"><?= h($r['student_code']) ?></span></td>
              <td>
                <button class="btn btn-sm btn-info" onclick="alert('📞 الهاتف: <?= h($r['phone']) ?>\n🔑 الكود المرجعي: <?= h($r['reference_code']) ?>')">📋</button>
                <?php if (isAdmin()): ?>
                <form method="post" style="display:inline" onsubmit="return confirm('حذف ولي الأمر؟')">
                  <input type="hidden" name="action" value="del_guardian">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>">
                  <button class="btn btn-sm btn-danger">🗑️</button>
                </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div></div>

    <?php $stdOpt = ''; foreach($students as $s){ $stdOpt .= "<option value='{$s['id']}'>".h($s['name'])." (".h($s['student_code']).")</option>"; } ?>

    <div class="modal-overlay" id="addGuardianModal">
      <div class="modal-box">
        <form method="post">
          <input type="hidden" name="action" value="add_guardian">
          <div class="modal-header"><h5>👪 إضافة ولي أمر</h5><button type="button" class="modal-close" onclick="closeModal('addGuardianModal')">×</button></div>
          <div class="modal-body">
            <div class="mb-2"><label class="form-label">الاسم الثلاثي *</label><input name="full_name" class="form-control" required></div>
            <div class="mb-2"><label class="form-label">رقم الهاتف *</label><input name="phone" class="form-control" required placeholder="مثال: 0912345678"></div>
            <div class="mb-2"><label class="form-label">الطالب *</label><select name="student_id" class="form-select" required><?= $stdOpt ?></select></div>
            <div class="alert alert-info small mb-0">ℹ️ سيتم إنشاء كود مرجعي تلقائياً. أعطِ هذا الكود لولي الأمر ليتمكن من الدخول.</div>
          </div>
          <div class="modal-footer"><button class="btn btn-primary">حفظ</button></div>
        </form>
      </div>
    </div>

  <?php
  /* ------------ تقارير الطلبة ------------ */
  elseif ($page === 'reports' && isDataEntry()):
      $students = $pdo->query("SELECT s.*, c.class_name, c.section FROM students s LEFT JOIN classes c ON c.id=s.class_id ORDER BY s.name")->fetchAll();
      $sid = (int)($_GET['student_id'] ?? 0);
      $student = null; $grades = []; $avg = 0; $notes = [];

      if ($sid) {
          $stmt = $pdo->prepare("SELECT s.*, c.class_name, c.section FROM students s LEFT JOIN classes c ON c.id=s.class_id WHERE s.id=?");
          $stmt->execute([$sid]);
          $student = $stmt->fetch();

          $stmt = $pdo->prepare("SELECT g.*, sub.subject_name, sub.max_grade, sub.subject_type FROM grades g 
              JOIN subjects sub ON sub.id=g.subject_id WHERE g.student_id=? ORDER BY sub.subject_name");
          $stmt->execute([$sid]);
          $grades = $stmt->fetchAll();

          $stmt = $pdo->prepare("SELECT * FROM notes WHERE person_type='student' AND person_id=? ORDER BY note_date DESC");
          $stmt->execute([$sid]);
          $notes = $stmt->fetchAll();

          if (count($grades)) {
              $sum = 0; $cnt = 0;
              foreach($grades as $g){ if($g['max_grade']>0){ $sum += ($g['total']/$g['max_grade'])*100; $cnt++; } }
              $avg = $cnt ? $sum/$cnt : 0;
          }
      }
  ?>
    <h3 class="page-title"><span class="icon">📈</span> تقارير الطلبة</h3>

    <div class="card no-print">
      <div class="card-body">
        <form method="get" class="row g-2">
          <input type="hidden" name="page" value="reports">
          <div class="col-md-8">
            <label class="form-label">اختر الطالب</label>
            <select name="student_id" class="form-select" onchange="this.form.submit()">
              <option value="">-- اختر طالباً --</option>
              <?php foreach($students as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $sid==$s['id']?'selected':'' ?>>
                  <?= h($s['name']) ?> (<?= h($s['student_code']) ?>) - <?= h($s['class_name'] ?? '') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4 d-flex align-items-end">
            <button class="btn btn-primary btn-block">🔍 عرض التقرير</button>
          </div>
        </form>
      </div>
    </div>

    <?php if ($student): ?>
      <div class="card">
        <div class="card-header d-flex justify-content-between">
          <span>🎓 كشف درجات: <?= h($student['name']) ?> (<?= h($student['student_code']) ?>)</span>
          <button onclick="window.print()" class="btn btn-sm btn-outline-primary no-print">🖨️ طباعة</button>
        </div>
        <div class="card-body">
          <div class="row mb-3">
            <div class="col-md-6"><strong>الصف:</strong> <?= h($student['class_name'] ?? '-') ?></div>
            <div class="col-md-6"><strong>السنة الدراسية:</strong> <?= h($student['academic_year']) ?></div>
            <div class="col-md-6"><strong>الفصل:</strong> <?= $student['semester']=='first'?'الأول':'الثاني' ?></div>
            <div class="col-md-6"><strong>ولي الأمر:</strong> <?= h($student['guardian_name']) ?></div>
          </div>
          <div class="table-responsive">
            <table class="table table-bordered">
              <thead>
                <tr><th>المادة</th><th>النوع</th><th>أعمال السنة</th><th>الامتحان النهائي</th><th>المجموع</th><th>من</th><th>النسبة</th><th>التقدير</th></tr>
              </thead>
              <tbody>
                <?php if (!$grades): ?><tr><td colspan="8" class="text-center text-muted">لا توجد درجات مسجلة</td></tr><?php endif; ?>
                <?php foreach($grades as $g):
                  $pct = $g['max_grade']>0 ? ($g['total']/$g['max_grade'])*100 : 0;
                  $subject_type = $g['subject_type'] ?? 'local';
                  $evaluation = getGradeEvaluation($pct, $subject_type);
                ?>
                  <tr>
                    <td><?= h($g['subject_name']) ?></td>
                    <td>
                      <span class="subject-type-badge <?= $subject_type === 'international' ? 'subject-type-international' : 'subject-type-local' ?>">
                        <?= getSubjectTypeName($subject_type) ?>
                      </span>
                    </td>
                    <td><?= number_format($g['coursework'],2) ?></td>
                    <td><?= number_format($g['final_exam'],2) ?></td>
                    <td><strong><?= number_format($g['total'],2) ?></strong></td>
                    <td><?= $g['max_grade'] ?></td>
                    <td><?= number_format($pct,2) ?>%</td>
                    <td>
                      <span class="badge <?= $evaluation['color'] ?>">
                        <?= $evaluation['grade'] ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr><th colspan="4" style="text-align:left">المعدل العام:</th><th colspan="4"><?= number_format($avg,2) ?>%</th></tr>
              </tfoot>
            </table>
          </div>
          <div class="alert alert-info mt-3">
            <strong>النتيجة النهائية:</strong>
            <?php if ($avg>=90): ?> <span class="text-success">ممتاز</span>
            <?php elseif ($avg>=80): ?> <span class="text-success">جيد جداً</span>
            <?php elseif ($avg>=70): ?> <span class="text-primary">جيد</span>
            <?php elseif ($avg>=60): ?> <span class="text-warning">مقبول</span>
            <?php elseif ($avg>=50): ?> <span class="text-warning">ضعيف</span>
            <?php else: ?> <span class="text-danger">راسب</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

  <?php
  /* ------------ المستخدمون ------------ */
  elseif ($page === 'users' && isAdmin()):
      $rows = $pdo->query("SELECT id,username,full_name,role,created_at FROM users ORDER BY id")->fetchAll();
      $roleLabels = ['admin'=>'مدير عام','accountant'=>'محاسب','data_entry'=>'مدخل بيانات','supervisor'=>'مشرف','parent'=>'ولي أمر'];
  ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="page-title mb-0"><span class="icon">⚙️</span> إدارة المستخدمين</h3>
      <button class="btn btn-primary" onclick="openModal('addUserModal')">➕ إضافة مستخدم</button>
    </div>

    <div class="card"><div class="card-body table-responsive">
      <table class="table">
        <thead><tr><th>#</th><th>اسم المستخدم</th><th>الاسم الكامل</th><th>الدور</th><th>تاريخ الإنشاء</th><th>إجراءات</th></tr></thead>
        <tbody>
          <?php foreach($rows as $r): ?>
            <tr>
              <td><?= $r['id'] ?></td>
              <td><code><?= h($r['username']) ?></code></td>
              <td><?= h($r['full_name']) ?></td>
              <td><span class="badge bg-primary"><?= $roleLabels[$r['role']] ?? $r['role'] ?></span></td>
              <td><?= h($r['created_at']) ?></td>
              <td>
                <?php if ($r['id'] != $_SESSION['user_id']): ?>
                  <form method="post" style="display:inline" onsubmit="return confirm('حذف المستخدم؟')">
                    <input type="hidden" name="action" value="del_user">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <button class="btn btn-sm btn-danger">🗑️</button>
                  </form>
                <?php else: ?>
                  <span class="badge bg-secondary">(أنت)</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div></div>

    <div class="modal-overlay" id="addUserModal">
      <div class="modal-box">
        <form method="post">
          <input type="hidden" name="action" value="add_user">
          <div class="modal-header"><h5>⚙️ إضافة مستخدم</h5><button type="button" class="modal-close" onclick="closeModal('addUserModal')">×</button></div>
          <div class="modal-body">
            <div class="mb-2"><label class="form-label">اسم المستخدم *</label><input name="username" class="form-control" required></div>
            <div class="mb-2"><label class="form-label">الاسم الكامل *</label><input name="full_name" class="form-control" required></div>
            <div class="mb-2"><label class="form-label">كلمة المرور *</label><input type="password" name="password" class="form-control" required></div>
            <div class="mb-2">
              <label class="form-label">الدور</label>
              <select name="role" class="form-select">
                <option value="admin">مدير عام</option>
                <option value="accountant">محاسب</option>
                <option value="data_entry">مدخل بيانات</option>
                <option value="supervisor">مشرف</option>
              </select>
            </div>
          </div>
          <div class="modal-footer"><button class="btn btn-primary">حفظ</button></div>
        </form>
      </div>
    </div>

  <?php
  /* ------------ الإعدادات ------------ */
  elseif ($page === 'settings' && isAdmin()):
  ?>
    <h3 class="page-title"><span class="icon">🔧</span> إعدادات النظام</h3>

    <div class="card">
      <div class="card-header">⚙️ الإعدادات العامة</div>
      <div class="card-body">
        <form method="post">
          <input type="hidden" name="action" value="save_settings">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">اسم المدرسة</label>
              <input name="school_name" class="form-control" value="<?= h(getSetting($pdo, 'school_name', 'المدرسة النموذجية')) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">عدد أيام العمل في الشهر</label>
              <input type="number" name="work_days_per_month" class="form-control" value="<?= getSetting($pdo, 'work_days_per_month', 22) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">خصم التأخير (لكل يوم)</label>
              <input type="number" step="0.01" name="late_deduction_per_day" class="form-control" value="<?= getSetting($pdo, 'late_deduction_per_day', 10) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">خصم الغياب (لكل يوم)</label>
              <input type="number" step="0.01" name="absence_deduction_per_day" class="form-control" value="<?= getSetting($pdo, 'absence_deduction_per_day', 50) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">درجة النجاح (%)</label>
              <input type="number" step="0.01" name="pass_grade" class="form-control" value="<?= getSetting($pdo, 'pass_grade', 50) ?>">
              <small class="text-muted">يُستخدم في ترقية الطلبة تلقائياً</small>
            </div>
            <div class="col-12">
              <button class="btn btn-primary">💾 حفظ الإعدادات</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="alert alert-info">
      <strong>ℹ️ كيفية عمل الخصم التلقائي:</strong><br>
      • عند صرف راتب جديد، يتم حساب أيام الغياب والتأخير تلقائياً من سجل الحضور<br>
      • خصم الغياب = (الراتب الأساسي ÷ عدد أيام العمل في الشهر) × عدد أيام الغياب<br>
      • خصم التأخير = عدد أيام التأخير × مبلغ الخصم لكل يوم<br>
      • يمكن أيضاً حساب الخصم لراتب موجود بالضغط على زر 🔄 في صفحة الرواتب
    </div>

  <?php
  /* ------------ صفحة غير موجودة ------------ */
  else: ?>
    <div class="alert alert-warning">⚠️ الصفحة غير موجودة أو غير مصرح بها.</div>
  <?php endif; ?>

  <div class="main-footer">
    🎨 تصميم وتطوير: <strong>م. عبدالرحيم غيث الطاهر</strong> &copy; <?= date('Y') ?>
  </div>

  </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('show');
    }
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.show').forEach(m => m.classList.remove('show'));
    }
});
</script>
<?php endif; ?>

</body>
</html>