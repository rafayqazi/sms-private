<?php
require_once __DIR__ . '/db.php';

class AIContext {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getContext() {
        $context = "You are the AI Assistant for 'GBPS Ali Bux Jarwar', a primary school (Classes: Kachi to Five).\n";
        $context .= "Your name is 'ALI BUX JARWAR School AI'.\n";
        $context .= "You have access to the following real-time school data. Use it to answer user questions accurately.\n";
        $context .= "Current Date: " . date('Y-m-d') . "\n\n";

        // 1. Student Data
        $students = $this->db->readData();
        $context .= "--- STUDENT RECORDS ---\n";
        $context .= "Format: ID, GR No, Name, Father Name, Gender, Class, Status\n";
        foreach ($students as $s) {
            $context .= "{$s['id']}, {$s['gr_no']}, {$s['student_name']}, {$s['father_name']}, {$s['gender']}, {$s['current_class']}, Active\n";
        }
        $context .= "\n";

        // 2. Teacher Data
        $teachers = $this->db->getAllTeachers();
        $context .= "--- TEACHER RECORDS ---\n";
        $context .= "Format: ID, Name, Designation, Department, Contact\n";
        foreach ($teachers as $t) {
            $context .= "{$t['id']}, {$t['name']}, {$t['designation']}, {$t['department']}, {$t['contact']}\n";
        }
        $context .= "\n";

        // 3. Attendance Data (Latest)
        $stats = $this->db->getAttendanceStats();
        $context .= "--- ATTENDANCE SUMMARY (Latest) ---\n";
        $context .= "Overall: Present: {$stats['overall']['Present']}, Absent: {$stats['overall']['Absent']}, Leave: {$stats['overall']['Leave']}\n";
        $context .= "Class-wise Breakdown:\n";
        foreach ($stats['class_wise'] as $class => $data) {
            $context .= "Class $class: Present: {$data['Present']}, Absent: {$data['Absent']}, Total: {$data['Total']}\n";
        }
        $context .= "\n";

        return $context;
    }
}
?>
