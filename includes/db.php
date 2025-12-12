<?php
date_default_timezone_set('Asia/Karachi'); // Set timezone to Pakistan/Karachi

class Database {
    private $csvFile;
    private $headers;

    public function __construct($file = null) {
        if ($file === null) {
            $file = __DIR__ . '/../data/database.csv';
        }
        $this->csvFile = $file;
        // New Schema Headers
        $this->headers = [
            'id', 'gr_no', 'student_name', 'father_name', 'gender', 'date_of_birth', 
            'admission_date', 'current_class', 'age', 'b_form_no', 'father_cnic', 
            'father_contact', 'district', 'taluka', 'school_name', 'semis_code', 
            'is_active', 'created_at', 'updated_at', 'father_cnic_front', 
            'father_cnic_back', 'b_form_img', 'profile_image', 'previous_school', 'slc_img',
            'student_status', 'is_repeater'
        ];

        if (!file_exists($this->csvFile)) {
            $this->writeData([]);
        }
    }

    private function getHeaders() {
        if (file_exists($this->csvFile) && ($handle = fopen($this->csvFile, "r")) !== FALSE) {
            $headers = fgetcsv($handle, 1000, ",");
            fclose($handle);
            return $headers;
        }
        return $this->headers;
    }

    public function readData() {
        $data = [];
        if (file_exists($this->csvFile) && ($handle = fopen($this->csvFile, "r")) !== FALSE) {
            $fileHeaders = fgetcsv($handle, 1000, ","); // Skip headers
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($row) == count($this->headers)) {
                    $data[] = array_combine($this->headers, $row);
                }
            }
            fclose($handle);
        }
        return $data;
    }

    public function writeData($data) {
        $fp = @fopen($this->csvFile, 'w');
        if ($fp === false) {
            error_log("Failed to open CSV file for writing: " . $this->csvFile);
            return false;
        }
        
        fputcsv($fp, $this->headers);
        foreach ($data as $fields) {
            // Ensure fields are in the correct order
            $row = [];
            foreach ($this->headers as $header) {
                $row[] = isset($fields[$header]) ? $fields[$header] : '';
            }
            fputcsv($fp, $row);
        }
        fclose($fp);
        return true;
    }

    public function addStudent($studentData) {
        $data = $this->readData();
        // Auto-increment ID
        $lastId = 0;
        if (!empty($data)) {
            $lastItem = end($data);
            $lastId = isset($lastItem['id']) ? (int)$lastItem['id'] : 0;
        }
        $studentData['id'] = $lastId + 1;
        
        // Set timestamps
        $now = date('Y-m-d H:i:s');
        $studentData['created_at'] = $now;
        $studentData['updated_at'] = $now;
        $studentData['is_active'] = isset($studentData['is_active']) ? $studentData['is_active'] : 1;

        // Calculate Age if DOB is present
        if (!empty($studentData['date_of_birth'])) {
            $dob = new DateTime($studentData['date_of_birth']);
            $today = new DateTime();
            $studentData['age'] = $dob->diff($today)->y;
        } else {
            $studentData['age'] = '';
        }

        $data[] = $studentData;
        $this->writeData($data);
        return true;
    }

    public function getNextGrNo() {
        $data = $this->readData();
        $maxGr = 0;
        foreach ($data as $student) {
            $gr = isset($student['gr_no']) ? (int)$student['gr_no'] : 0;
            if ($gr > $maxGr) {
                $maxGr = $gr;
            }
        }
        return $maxGr + 1;
    }

    public function updateStudent($id, $updatedData) {
        $data = $this->readData();
        $found = false;
        foreach ($data as $key => $student) {
            if ($student['id'] == $id) {
                // Merge existing data with updates
                $student = array_merge($student, $updatedData);
                
                // Update timestamp
                $student['updated_at'] = date('Y-m-d H:i:s');

                // Recalculate Age if DOB changed
                if (isset($updatedData['date_of_birth'])) {
                    $dob = new DateTime($student['date_of_birth']);
                    $today = new DateTime();
                    $student['age'] = $dob->diff($today)->y;
                }

                $data[$key] = $student;
                $found = true;
                break;
            }
        }
        if ($found) {
            $this->writeData($data);
            return true;
        }
        return false;
    }

    public function getStudent($id) {
        $data = $this->readData();
        foreach ($data as $student) {
            if ($student['id'] == $id) {
                return $student;
            }
        }
        return null;
    }
    
    public function deleteStudent($id) {
        $data = $this->readData();
        $newData = [];
        foreach ($data as $student) {
            if ($student['id'] != $id) {
                $newData[] = $student;
            }
        }
        $this->writeData($newData);
        return true;
    }
    
    
    public function searchStudents($query) {
        return $this->filterStudents(['search' => $query]);
    }

    public function filterStudents($filters = []) {
        $students = $this->readData();
        $results = [];

        // By default, exclude Alumni students unless explicitly requested
        $includeAlumni = isset($filters['include_alumni']) ? $filters['include_alumni'] : false;

        foreach ($students as $student) {
            // Skip Alumni students unless explicitly included
            if (!$includeAlumni && isset($student['student_status']) && $student['student_status'] === 'Alumni') {
                continue;
            }

            $match = true;

            // Class Filter
            if (isset($filters['class']) && !empty($filters['class'])) {
                if ($student['current_class'] != $filters['class']) {
                    $match = false;
                }
            }

            // Gender Filter
            if (isset($filters['gender']) && !empty($filters['gender'])) {
                if ($student['gender'] != $filters['gender']) {
                    $match = false;
                }
            }

            // Search Filter (Name or GR No)
            if (isset($filters['search']) && !empty($filters['search'])) {
                $searchTerm = strtolower($filters['search']);
                $nameMatch = stripos($student['student_name'], $searchTerm) !== false;
                $grMatch = stripos($student['gr_no'], $searchTerm) !== false;
                
                if (!$nameMatch && !$grMatch) {
                    $match = false;
                }
            }

            if ($match) {
                $results[] = $student;
            }
        }

        // Sorting
        $sortBy = isset($filters['sort_by']) ? $filters['sort_by'] : '';
        $order = isset($filters['order']) ? strtoupper($filters['order']) : 'ASC';

        if ($sortBy === 'gr_no') {
            usort($results, function($a, $b) use ($order) {
                // GR No might be numeric or string, treat as integer for proper numeric sorting if possible
                $valA = (int)$a['gr_no'];
                $valB = (int)$b['gr_no'];
                
                if ($valA == $valB) return 0;
                return ($order === 'ASC') ? ($valA - $valB) : ($valB - $valA);
            });
        }

        return $results;
    }

    public function getAttendance($date, $class) {
        $file = __DIR__ . '/../data/attendance.csv';
        $attendance = [];
        
        if (file_exists($file) && ($handle = fopen($file, "r")) !== FALSE) {
            $headers = fgetcsv($handle, 1000, ","); // Skip headers
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Structure: date, class, student_id, status, created_at
                if (count($row) >= 4) {
                    if ($row[0] == $date && $row[1] == $class) {
                        $attendance[$row[2]] = $row[3]; // student_id => status
                    }
                }
            }
            fclose($handle);
        }
        return $attendance;
    }

    public function saveAttendance($date, $class, $attendanceData) {
        $file = __DIR__ . '/../data/attendance.csv';
        $allAttendance = [];
        $headers = ['date', 'class', 'student_id', 'status', 'created_at'];

        // Read existing data
        if (file_exists($file) && ($handle = fopen($file, "r")) !== FALSE) {
            $fileHeaders = fgetcsv($handle, 1000, ",");
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($row) >= 4) {
                    // If not the same date/class, keep it
                    if (!($row[0] == $date && $row[1] == $class)) {
                        $allAttendance[] = $row;
                    }
                }
            }
            fclose($handle);
        }

        // Add new data
        $now = date('Y-m-d H:i:s');
        foreach ($attendanceData as $studentId => $status) {
            $allAttendance[] = [$date, $class, $studentId, $status, $now];
        }

        // Write back
        $fp = fopen($file, 'w');
        fputcsv($fp, $headers);
        foreach ($allAttendance as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        return true;
    }

    public function isGrNoExists($grNo, $excludeId = null) {
        $data = $this->readData();
        foreach ($data as $student) {
            if ($student['gr_no'] == $grNo) {
                if ($excludeId && $student['id'] == $excludeId) {
                    continue;
                }
                return true;
            }
        }
        return false;
    }

    public function isBFormNoExists($bFormNo, $excludeId = null) {
        $data = $this->readData();
        foreach ($data as $student) {
            if (isset($student['b_form_no']) && $student['b_form_no'] == $bFormNo) {
                if ($excludeId && $student['id'] == $excludeId) {
                    continue;
                }
                return $student['student_name']; // Return name of the student who has this B-Form
            }
        }
        return false;
    }

    public function isTeacherCnicExists($cnic, $excludeId = null) {
        $file = __DIR__ . '/../data/teachers.csv';
        if (!file_exists($file)) return false;

        $handle = @fopen($file, "r");
        if ($handle === false) return false;

        fgetcsv($handle); // Skip header
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // ID is index 0, CNIC is index 4
            if (count($row) > 4 && $row[4] == $cnic) {
                if ($excludeId && $row[0] == $excludeId) {
                    continue;
                }
                fclose($handle);
                return true;
            }
        }
        fclose($handle);
        return false;
    }

    // Teacher Management
    public function addTeacher($data) {
        $file = __DIR__ . '/../data/teachers.csv';
        $headers = [
            'id', 'name', 'father_name', 'gender', 'cnic', 'dob', 'age', 'email', 
            'disability', 'payment_type', 'payment_no', 'iban', 'contact', 
            'retirement_date', 'designation', 'department', 'posting', 'basic_scale', 
            'address', 'district', 'tahsil', 'profile_image', 'created_at'
        ];

        // Create file with headers if it doesn't exist
        if (!file_exists($file)) {
            $fp = fopen($file, 'w');
            fputcsv($fp, $headers);
            fclose($fp);
        }

        // Generate ID
        $id = 1;
        if (file_exists($file)) {
            $rows = file($file);
            if (count($rows) > 1) {
                $lastRow = str_getcsv(trim(end($rows)));
                if (is_numeric($lastRow[0])) {
                    $id = $lastRow[0] + 1;
                }
            }
        }

        $record = [
            $id,
            $data['name'],
            $data['father_name'],
            $data['gender'],
            $data['cnic'],
            $data['dob'],
            $data['age'],
            $data['email'],
            $data['disability'],
            $data['payment_type'],
            $data['payment_no'],
            $data['iban'],
            $data['contact'],
            $data['retirement_date'],
            $data['designation'],
            $data['department'],
            $data['posting'],
            $data['basic_scale'],
            $data['address'],
            $data['district'],
            $data['tahsil'],
            isset($data['profile_image']) ? $data['profile_image'] : '',
            date('Y-m-d H:i:s')
        ];

        $fp = @fopen($file, 'a');
        if ($fp === false) {
            die("Error: Could not open data/teachers.csv. Please make sure the file is not open in another program (like Excel) and try again.");
        }
        fputcsv($fp, $record);
        fclose($fp);
        return $id;
    }

    public function getTeacher($id) {
        $file = __DIR__ . '/../data/teachers.csv';
        if (!file_exists($file)) return null;

        $handle = fopen($file, "r");
        $headers = fgetcsv($handle, 1000, ",");
        
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if ($row[0] == $id) {
                // Adjust row to match headers length
                if (count($row) < count($headers)) {
                    $row = array_pad($row, count($headers), '');
                } elseif (count($row) > count($headers)) {
                    $row = array_slice($row, 0, count($headers));
                }
                return array_combine($headers, $row);
            }
        }
        fclose($handle);
        return null;
    }

    public function getAllTeachers() {
        $file = __DIR__ . '/../data/teachers.csv';
        $teachers = [];
        if (!file_exists($file)) return $teachers;

        $handle = fopen($file, "r");
        $headers = fgetcsv($handle, 1000, ",");
        
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($row) > 0) {
                // Adjust row to match headers length
                if (count($row) < count($headers)) {
                    $row = array_pad($row, count($headers), '');
                } elseif (count($row) > count($headers)) {
                    $row = array_slice($row, 0, count($headers));
                }
                $teachers[] = array_combine($headers, $row);
            }
        }
        fclose($handle);
        return $teachers;
    }
    public function deleteTeacher($id) {
        $file = __DIR__ . '/../data/teachers.csv';
        if (!file_exists($file)) return false;

        $rows = [];
        $handle = @fopen($file, "r");
        if ($handle === false) return false;

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if ($row[0] != $id) {
                $rows[] = $row;
            }
        }
        fclose($handle);

        $fp = @fopen($file, 'w');
        if ($fp === false) return false;

        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        return true;
    }

    public function updateTeacher($id, $data) {
        $file = __DIR__ . '/../data/teachers.csv';
        if (!file_exists($file)) return false;

        $rows = [];
        $handle = @fopen($file, "r");
        if ($handle === false) return false;

        $headers = fgetcsv($handle, 1000, ",");
        $rows[] = $headers; // Keep headers

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if ($row[0] == $id) {
                // Preserve created_at (last column)
                $createdAt = end($row);
                
                // Construct updated record
                $updatedRow = [
                    $id,
                    $data['name'],
                    $data['father_name'],
                    $data['gender'],
                    $data['cnic'],
                    $data['dob'],
                    $data['age'],
                    $data['email'],
                    $data['disability'],
                    $data['payment_type'],
                    $data['payment_no'],
                    $data['iban'],
                    $data['contact'],
                    $data['retirement_date'],
                    $data['designation'],
                    $data['department'],
                    $data['posting'],
                    $data['basic_scale'],
                    $data['address'],
                    $data['district'],
                    $data['tahsil'],
                    isset($data['profile_image']) ? $data['profile_image'] : $row[21], // Use new image or keep old
                    $createdAt
                ];
                $rows[] = $updatedRow;
            } else {
                $rows[] = $row;
            }
        }
        fclose($handle);

        $fp = @fopen($file, 'w');
        if ($fp === false) return false;

        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        return true;
    }
    public function getAttendanceStats() {
        $file = __DIR__ . '/../data/attendance.csv';
        $todayDate = date('Y-m-d');
        
        $stats = [
            'overall' => ['Present' => 0, 'Absent' => 0, 'Leave' => 0],
            'class_wise' => [],
            'is_today' => false,
            'attendance_date' => null,
            'today_date' => $todayDate
        ];

        if (!file_exists($file)) return $stats;

        // 1. Load Students to get Gender info
        $allStudents = $this->readData();
        $studentInfo = [];
        foreach ($allStudents as $s) {
            $studentInfo[$s['id']] = ['gender' => $s['gender'], 'class' => $s['current_class']];
        }

        $handle = @fopen($file, "r");
        if ($handle === false) return $stats;

        $attendanceRecords = [];
        fgetcsv($handle); // Skip header

        // 2. Read all records and find max timestamp
        $maxTimestamp = 0;
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($row) < 4) continue;
            $dateStr = $row[0];
            $timestamp = strtotime($dateStr);
            if ($timestamp === false) continue;
            
            if ($timestamp > $maxTimestamp) {
                $maxTimestamp = $timestamp;
            }

            $attendanceRecords[] = [
                'timestamp' => $timestamp,
                'normalized_date' => date('Y-m-d', $timestamp),
                'class' => $row[1],
                'student_id' => $row[2],
                'status' => $row[3]
            ];
        }
        fclose($handle);

        if ($maxTimestamp === 0) return $stats;
        $latestDate = date('Y-m-d', $maxTimestamp);
        
        // Check if latest attendance is for today
        $stats['is_today'] = ($latestDate === $todayDate);
        $stats['attendance_date'] = $latestDate;

        // 3. Initialize Class Stats with Student Counts (Male/Female)
        foreach ($studentInfo as $id => $info) {
            $class = $info['class'];
            if (!isset($stats['class_wise'][$class])) {
                $stats['class_wise'][$class] = [
                    'Present' => 0, 
                    'Absent' => 0, 
                    'Total' => 0,
                    'Male' => 0,
                    'Female' => 0
                ];
            }
            // Count total students per class by gender
            if ($info['gender'] == 'Male') $stats['class_wise'][$class]['Male']++;
            elseif ($info['gender'] == 'Female') $stats['class_wise'][$class]['Female']++;
        }

        // 4. Aggregate stats for latest date only
        foreach ($attendanceRecords as $record) {
            if ($record['normalized_date'] !== $latestDate) continue;

            $class = $record['class'];
            $status = $record['status'];

            // Overall Stats
            if ($status == 'P') $stats['overall']['Present']++;
            elseif ($status == 'A') $stats['overall']['Absent']++;
            elseif ($status == 'L') $stats['overall']['Leave']++;

            // Class-wise Stats (Attendance only)
            if (!isset($stats['class_wise'][$class])) {
                $stats['class_wise'][$class] = [
                    'Present' => 0, 'Absent' => 0, 'Total' => 0, 'Male' => 0, 'Female' => 0
                ];
            }
            
            $stats['class_wise'][$class]['Total']++;
            
            if ($status == 'P') {
                $stats['class_wise'][$class]['Present']++;
            } elseif ($status == 'A') {
                $stats['class_wise'][$class]['Absent']++;
            }
        }

        // 5. Sort Class-wise Stats by Logical Order
        $classOrder = ['Kachi', 'One', 'Two', 'Three', 'Four', 'Five'];
        $sortedClassStats = [];
        foreach ($classOrder as $className) {
            if (isset($stats['class_wise'][$className])) {
                $sortedClassStats[$className] = $stats['class_wise'][$className];
            }
        }
        // Append any remaining classes
        foreach ($stats['class_wise'] as $className => $data) {
            if (!isset($sortedClassStats[$className])) {
                $sortedClassStats[$className] = $data;
            }
        }
        $stats['class_wise'] = $sortedClassStats;

        return $stats;
    }

    public function getStudentsByAttendanceStatus($status) {
        $attendanceFile = __DIR__ . '/../data/attendance.csv';
        $students = [];
        
        if (!file_exists($attendanceFile)) return $students;

        $handle = @fopen($attendanceFile, "r");
        if ($handle === false) return $students;

        // 1. Get all attendance records
        $attendanceRecords = [];
        fgetcsv($handle); // Skip header
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($row) < 4) continue;
            // Normalize date to Y-m-d
            $dateStr = $row[0];
            $timestamp = strtotime($dateStr);
            if ($timestamp === false) continue; // Skip invalid dates
            $normalizedDate = date('Y-m-d', $timestamp);

            $attendanceRecords[] = [
                'original_date' => $dateStr,
                'normalized_date' => $normalizedDate,
                'timestamp' => $timestamp,
                'class' => $row[1],
                'student_id' => $row[2],
                'status' => $row[3]
            ];
        }
        fclose($handle);

        if (empty($attendanceRecords)) return $students;

        // 2. Find the latest date (max timestamp)
        $maxTimestamp = 0;
        foreach ($attendanceRecords as $record) {
            if ($record['timestamp'] > $maxTimestamp) {
                $maxTimestamp = $record['timestamp'];
            }
        }
        
        if ($maxTimestamp === 0) return $students;
        
        $latestDate = date('Y-m-d', $maxTimestamp);

        // 3. Filter records for latest date and status
        $targetStudentIds = [];
        foreach ($attendanceRecords as $record) {
            if ($record['normalized_date'] == $latestDate && $record['status'] == $status) {
                $targetStudentIds[] = $record['student_id'];
            }
        }

        // 4. Get Student Details
        $allStudents = $this->readData(); // Reuse existing method to get all students
        foreach ($allStudents as $student) {
            if (in_array($student['id'], $targetStudentIds)) {
                $students[] = $student;
            }
        }

        return ['date' => $latestDate, 'students' => $students];
    }

    public function backupData() {
        $backupDir = __DIR__ . '/../backups/' . date('Y-m-d_H-i-s');
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        // Backup CSVs
        $dataDir = __DIR__ . '/../data';
        $files = glob($dataDir . '/*.csv');
        foreach ($files as $file) {
            copy($file, $backupDir . '/' . basename($file));
        }
        // Backup Excel if exists
        $excelFiles = glob($dataDir . '/*.xlsx');
        foreach ($excelFiles as $file) {
            copy($file, $backupDir . '/' . basename($file));
        }

        // Backup Uploads
        $uploadsDir = __DIR__ . '/../uploads';
        $backupUploadsDir = $backupDir . '/uploads';
        if (file_exists($uploadsDir)) {
            mkdir($backupUploadsDir, 0777, true);
            $uploadFiles = glob($uploadsDir . '/*');
            foreach ($uploadFiles as $file) {
                if (is_file($file)) {
                    copy($file, $backupUploadsDir . '/' . basename($file));
                }
            }
        }
        return true;
    }

    public function resetData() {
        // 1. Truncate CSVs (Keep Headers)
        $files = [
            __DIR__ . '/../data/database.csv' => ['id', 'gr_no', 'student_name', 'father_name', 'gender', 'surname', 'religion', 'caste', 'date_of_birth', 'age', 'place_of_birth', 'b_form_no', 'admission_date', 'current_class', 'father_cnic', 'father_contact', 'district', 'taluka', 'union_council', 'school_name', 'semis_code', 'is_active', 'created_at', 'updated_at', 'father_cnic_front', 'father_cnic_back', 'b_form_img', 'profile_image', 'previous_school', 'slc_img'],
            __DIR__ . '/../data/teachers.csv' => ['id', 'name', 'father_name', 'gender', 'cnic', 'dob', 'age', 'contact', 'email', 'address', 'designation', 'department', 'posting', 'basic_scale', 'retirement_date', 'payment_type', 'payment_no', 'iban', 'profile_image'],
            __DIR__ . '/../data/attendance.csv' => ['date', 'class', 'student_id', 'status']
        ];

        foreach ($files as $file => $headers) {
            if (file_exists($file)) {
                $fp = fopen($file, 'w');
                fputcsv($fp, $headers);
                fclose($fp);
            }
        }

        // 2. Delete Uploads
        $uploadsDir = __DIR__ . '/../uploads';
        $files = glob($uploadsDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return true;
    }

    // Student Promotion Methods
    public function getStudentsByClass($class) {
        $students = $this->readData();
        return array_filter($students, function($student) use ($class) {
            return $student['current_class'] === $class && 
                   (!isset($student['student_status']) || $student['student_status'] !== 'Alumni');
        });
    }

    public function promoteStudent($id, $action) {
        $students = $this->readData();
        $classProgression = [
            'Kachi' => 'One',
            'One' => 'Two',
            'Two' => 'Three',
            'Three' => 'Four',
            'Four' => 'Five',
            'Five' => 'Alumni (Passed Students)'
        ];

        foreach ($students as &$student) {
            if ($student['id'] == $id) {
                // Set defaults for existing students without these fields
                if (!isset($student['student_status'])) {
                    $student['student_status'] = 'Active';
                }
                if (!isset($student['is_repeater'])) {
                    $student['is_repeater'] = '0';
                }

                $currentClass = $student['current_class'];
                
                if ($action === 'pass') {
                    // Promote to next class
                    if (isset($classProgression[$currentClass])) {
                        $student['current_class'] = $classProgression[$currentClass];
                        
                        // If promoted from Class Five, mark as Alumni
                        if ($currentClass === 'Five') {
                            $student['student_status'] = 'Alumni';
                        }
                    }
                    $student['is_repeater'] = '0';
                } elseif ($action === 'fail') {
                    $student['is_repeater'] = '1';
                } elseif ($action === 'stay') {
                    $student['is_repeater'] = '0';
                }
                
                $student['updated_at'] = date('Y-m-d H:i:s');
                break;
            }
        }
        return $this->writeData($students);
    }

    public function createUserRole($teacherId, $role, $username, $password, $classes) {
        $file = __DIR__ . '/../data/user_roles.csv';
        $headers = ['id', 'teacher_id', 'role', 'username', 'password_hash', 'assigned_classes', 'created_at', 'updated_at'];

        // Create file with headers if it doesn't exist
        if (!file_exists($file)) {
            $fp = fopen($file, 'w');
            fputcsv($fp, $headers);
            fclose($fp);
        }

        // Check if username already exists
        if ($this->isUsernameExists($username)) {
            return ['success' => false, 'message' => 'Username already exists'];
        }

        // Check if teacher already has a role
        if ($this->getUserRoleByTeacherId($teacherId)) {
            return ['success' => false, 'message' => 'Teacher already has a role assigned'];
        }

        // Generate ID
        $id = 1;
        if (file_exists($file)) {
            $rows = file($file);
            if (count($rows) > 1) {
                $lastRow = str_getcsv(trim(end($rows)));
                if (is_numeric($lastRow[0])) {
                    $id = $lastRow[0] + 1;
                }
            }
        }

        // Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Encode classes as JSON
        $classesJson = json_encode($classes);

        $record = [
            $id,
            $teacherId,
            $role,
            $username,
            $passwordHash,
            $classesJson,
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s')
        ];

        $fp = @fopen($file, 'a');
        if ($fp === false) {
            return ['success' => false, 'message' => 'Could not open user_roles.csv'];
        }
        fputcsv($fp, $record);
        fclose($fp);
        return ['success' => true, 'message' => 'Role assigned successfully'];
    }

    public function getUserRoleByUsername($username) {
        $file = __DIR__ . '/../data/user_roles.csv';
        if (!file_exists($file)) return null;

        $handle = fopen($file, "r");
        $headers = fgetcsv($handle, 1000, ",");
        
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($row) >= 6 && $row[3] == $username) {
                $userRole = array_combine($headers, $row);
                // Decode classes JSON
                $userRole['assigned_classes'] = json_decode($userRole['assigned_classes'], true);
                fclose($handle);
                return $userRole;
            }
        }
        fclose($handle);
        return null;
    }

    public function getUserRoleByTeacherId($teacherId) {
        $file = __DIR__ . '/../data/user_roles.csv';
        if (!file_exists($file)) return null;

        $handle = fopen($file, "r");
        $headers = fgetcsv($handle, 1000, ",");
        
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($row) >= 6 && $row[1] == $teacherId) {
                $userRole = array_combine($headers, $row);
                // Decode classes JSON
                $userRole['assigned_classes'] = json_decode($userRole['assigned_classes'], true);
                fclose($handle);
                return $userRole;
            }
        }
        fclose($handle);
        return null;
    }

    public function updateUserRole($teacherId, $role, $username, $password, $classes) {
        $file = __DIR__ . '/../data/user_roles.csv';
        if (!file_exists($file)) return ['success' => false, 'message' => 'User roles file not found'];

        $rows = [];
        $handle = @fopen($file, "r");
        if ($handle === false) return ['success' => false, 'message' => 'Could not open user_roles.csv'];

        $headers = fgetcsv($handle, 1000, ",");
        $rows[] = $headers;
        $found = false;

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if ($row[1] == $teacherId) {
                // Check if username changed and if new username exists
                if ($row[3] != $username && $this->isUsernameExists($username)) {
                    fclose($handle);
                    return ['success' => false, 'message' => 'Username already exists'];
                }

                // Update record
                $passwordHash = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : $row[4];
                $classesJson = json_encode($classes);
                
                $updatedRow = [
                    $row[0], // id
                    $teacherId,
                    $role,
                    $username,
                    $passwordHash,
                    $classesJson,
                    $row[6], // created_at
                    date('Y-m-d H:i:s') // updated_at
                ];
                $rows[] = $updatedRow;
                $found = true;
            } else {
                $rows[] = $row;
            }
        }
        fclose($handle);

        if (!$found) {
            return ['success' => false, 'message' => 'Role not found for this teacher'];
        }

        $fp = @fopen($file, 'w');
        if ($fp === false) return ['success' => false, 'message' => 'Could not write to user_roles.csv'];

        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        return ['success' => true, 'message' => 'Role updated successfully'];
    }

    public function deleteUserRole($teacherId) {
        $file = __DIR__ . '/../data/user_roles.csv';
        if (!file_exists($file)) return ['success' => false, 'message' => 'User roles file not found'];

        $rows = [];
        $handle = @fopen($file, "r");
        if ($handle === false) return ['success' => false, 'message' => 'Could not open user_roles.csv'];

        $found = false;
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if ($row[1] != $teacherId) {
                $rows[] = $row;
            } else {
                $found = true;
            }
        }
        fclose($handle);

        if (!$found) {
            return ['success' => false, 'message' => 'Role not found'];
        }

        $fp = @fopen($file, 'w');
        if ($fp === false) return ['success' => false, 'message' => 'Could not write to user_roles.csv'];

        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        return ['success' => true, 'message' => 'Role removed successfully'];
    }

    public function getAllUserRoles() {
        $file = __DIR__ . '/../data/user_roles.csv';
        $userRoles = [];
        if (!file_exists($file)) return $userRoles;

        $handle = fopen($file, "r");
        $headers = fgetcsv($handle, 1000, ",");
        
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($row) > 0) {
                $userRole = array_combine($headers, $row);
                // Decode classes JSON
                $userRole['assigned_classes'] = json_decode($userRole['assigned_classes'], true);
                $userRoles[] = $userRole;
            }
        }
        fclose($handle);
        return $userRoles;
    }

    public function isUsernameExists($username, $excludeTeacherId = null) {
        $file = __DIR__ . '/../data/user_roles.csv';
        if (!file_exists($file)) return false;

        $handle = @fopen($file, "r");
        if ($handle === false) return false;

        fgetcsv($handle); // Skip header
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($row) > 3 && $row[3] == $username) {
                if ($excludeTeacherId && $row[1] == $excludeTeacherId) {
                    continue;
                }
                fclose($handle);
                return true;
            }
        }
        fclose($handle);
        return false;
    }


    // Messaging System Methods
    public function sendMessage($senderId, $senderType, $receiverId, $message) {
        $file = __DIR__ . '/../data/messages.csv';
        $headers = ['id', 'sender_id', 'sender_type', 'receiver_id', 'message', 'created_at', 'is_read'];

        // Create file with headers if it doesn't exist
        if (!file_exists($file)) {
            $fp = fopen($file, 'w');
            fputcsv($fp, $headers);
            fclose($fp);
        }

        // Generate ID
        $id = 1;
        if (file_exists($file)) {
            $rows = file($file);
            if (count($rows) > 1) {
                $lastRow = str_getcsv(trim(end($rows)));
                if (is_numeric($lastRow[0])) {
                    $id = $lastRow[0] + 1;
                }
            }
        }

        $record = [
            $id,
            $senderId,
            $senderType, // 'admin' or 'teacher'
            $receiverId,
            $message,
            date('Y-m-d H:i:s'),
            '0' // is_read = false
        ];

        $fp = @fopen($file, 'a');
        if ($fp === false) {
            return false;
        }
        fputcsv($fp, $record);
        fclose($fp);
        return true;
    }

    public function getConversation($userId1, $userId2) {
        $file = __DIR__ . '/../data/messages.csv';
        $messages = [];
        if (!file_exists($file)) return $messages;

        $handle = fopen($file, "r");
        $headers = fgetcsv($handle, 1000, ",");
        
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($row) >= 7) {
                // Get messages between these two users (both directions)
                if (($row[1] == $userId1 && $row[3] == $userId2) || 
                    ($row[1] == $userId2 && $row[3] == $userId1)) {
                    $messages[] = array_combine($headers, $row);
                }
            }
        }
        fclose($handle);
        
        // Sort by created_at
        usort($messages, function($a, $b) {
            return strtotime($a['created_at']) - strtotime($b['created_at']);
        });
        
        return $messages;
    }

    public function getAllConversations() {
        $file = __DIR__ . '/../data/messages.csv';
        $conversations = [];
        if (!file_exists($file)) return $conversations;

        $handle = fopen($file, "r");
        $headers = fgetcsv($handle, 1000, ",");
        
        $allMessages = [];
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($row) >= 7) {
                $allMessages[] = array_combine($headers, $row);
            }
        }
        fclose($handle);

        // Group messages by teacher (sender_id when sender_type is teacher)
        $grouped = [];
        foreach ($allMessages as $msg) {
            if ($msg['sender_type'] === 'teacher') {
                $teacherId = $msg['sender_id'];
            } else {
                $teacherId = $msg['receiver_id'];
            }
            
            if (!isset($grouped[$teacherId])) {
                $grouped[$teacherId] = [];
            }
            $grouped[$teacherId][] = $msg;
        }

        // Get latest message and unread count for each conversation
        foreach ($grouped as $teacherId => $messages) {
            usort($messages, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            $unreadCount = 0;
            foreach ($messages as $msg) {
                if ($msg['is_read'] == '0' && $msg['sender_type'] === 'teacher') {
                    $unreadCount++;
                }
            }
            
            $teacher = $this->getTeacher($teacherId);
            
            $conversations[] = [
                'teacher_id' => $teacherId,
                'teacher_name' => $teacher ? $teacher['name'] : 'Unknown',
                'latest_message' => $messages[0]['message'],
                'latest_time' => $messages[0]['created_at'],
                'unread_count' => $unreadCount,
                'total_messages' => count($messages)
            ];
        }

        // Sort by latest message time
        usort($conversations, function($a, $b) {
            return strtotime($b['latest_time']) - strtotime($a['latest_time']);
        });

        return $conversations;
    }

    public function markMessagesAsRead($userId, $otherUserId) {
        $file = __DIR__ . '/../data/messages.csv';
        if (!file_exists($file)) return false;

        $rows = [];
        $handle = @fopen($file, "r");
        if ($handle === false) return false;

        $headers = fgetcsv($handle, 1000, ",");
        $rows[] = $headers;

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Mark as read if sent to userId from otherUserId
            if ($row[3] == $userId && $row[1] == $otherUserId) {
                $row[6] = '1'; // is_read = true
            }
            $rows[] = $row;
        }
        fclose($handle);

        $fp = @fopen($file, 'w');
        if ($fp === false) return false;

        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        return true;
    }

    public function deleteMessage($messageId) {
        $file = __DIR__ . '/../data/messages.csv';
        if (!file_exists($file)) return false;

        $rows = [];
        $handle = @fopen($file, "r");
        if ($handle === false) return false;

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if ($row[0] != $messageId) {
                $rows[] = $row;
            }
        }
        fclose($handle);

        $fp = @fopen($file, 'w');
        if ($fp === false) return false;

        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        return true;
    }

    public function deleteConversation($userId1, $userId2) {
        $file = __DIR__ . '/../data/messages.csv';
        if (!file_exists($file)) return false;

        $rows = [];
        $handle = @fopen($file, "r");
        if ($handle === false) return false;

        $headers = fgetcsv($handle, 1000, ",");
        $rows[] = $headers;

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Skip messages between these two users
            if (!(($row[1] == $userId1 && $row[3] == $userId2) || 
                  ($row[1] == $userId2 && $row[3] == $userId1))) {
                $rows[] = $row;
            }
        }
        fclose($handle);

        $fp = @fopen($file, 'w');
        if ($fp === false) return false;

        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        return true;
    }

    public function getUnreadMessageCount($userId) {
        $file = __DIR__ . '/../data/messages.csv';
        if (!file_exists($file)) return 0;

        $count = 0;
        $handle = @fopen($file, "r");
        if ($handle === false) return 0;

        fgetcsv($handle); // Skip header
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($row) >= 7 && $row[3] == $userId && $row[6] == '0') {
                $count++;
            }
        }
        fclose($handle);
        return $count;
    }

    // Result Management Methods
    public function addResult($resultData) {
        $file = __DIR__ . '/../data/results.csv';
        $headers = [
            'id', 'student_id', 'class', 'exam_type', 'year',
            'english', 'math', 'social_studies', 'general_science', 'mt', 'islamiyat', 'nmt', 
            'other_subjects', // New column for JSON string of extra subjects
            'total_obtained', 'total_max', 'percentage', 'grade', 'remarks', 'created_at'
        ];

        if (!file_exists($file)) {
            $fp = fopen($file, 'w');
            fputcsv($fp, $headers);
            fclose($fp);
        }

        // Generate ID
        $id = 1;
        if (file_exists($file)) {
            $rows = file($file);
            if (count($rows) > 1) {
                $lastRow = str_getcsv(trim(end($rows)));
                if (is_numeric($lastRow[0])) {
                    $id = $lastRow[0] + 1;
                }
            }
        }

        // Calculate Totals and Grade
        $marks = [
            $resultData['english'], $resultData['math'], $resultData['social_studies'], 
            $resultData['general_science'], $resultData['mt'], $resultData['islamiyat'], $resultData['nmt']
        ];
        
        // Add extra subjects to marks
        $otherSubjects = isset($resultData['other_subjects']) ? json_decode($resultData['other_subjects'], true) : [];
        if (!is_array($otherSubjects)) $otherSubjects = [];
        foreach ($otherSubjects as $subject => $mark) {
            $marks[] = $mark;
        }

        // Assuming each subject is out of 100 for now, or passed in total_max
        $totalObtained = array_sum($marks);
        $totalMax = isset($resultData['total_max']) ? $resultData['total_max'] : (count($marks) * 100);
        $percentage = ($totalMax > 0) ? ($totalObtained / $totalMax) * 100 : 0;
        $grade = $this->calculateGrade($percentage);
        $remarks = $this->calculateRemarks($percentage);

        $record = [
            $id,
            $resultData['student_id'],
            $resultData['class'],
            $resultData['exam_type'],
            $resultData['year'],
            $resultData['english'],
            $resultData['math'],
            $resultData['social_studies'],
            $resultData['general_science'],
            $resultData['mt'],
            $resultData['islamiyat'],
            $resultData['nmt'],
            isset($resultData['other_subjects']) ? $resultData['other_subjects'] : '{}',
            $totalObtained,
            $totalMax,
            round($percentage, 2),
            $grade,
            $remarks,
            date('Y-m-d H:i:s')
        ];

        // Check if result already exists for this student, exam and year, if so update it
        $existing = $this->getStudentResult($resultData['student_id'], $resultData['exam_type'], $resultData['year']);
        if ($existing) {
            return $this->updateResult($existing['id'], $resultData);
        }

        $fp = fopen($file, 'a');
        fputcsv($fp, $record);
        fclose($fp);
        return $id;
    }

    public function updateResult($id, $resultData) {
        $file = __DIR__ . '/../data/results.csv';
        if (!file_exists($file)) return false;

        $rows = [];
        $handle = fopen($file, "r");
        $headers = fgetcsv($handle, 1000, ",");
        
        // Check if headers match new schema, if not, we might need to recreate file or handle migration manually.
        // For simplicity, we assume headers are correct or we are overwriting.
        // But to be safe, let's just read all and rewrite.
        
        $rows[] = [
            'id', 'student_id', 'class', 'exam_type', 'year',
            'english', 'math', 'social_studies', 'general_science', 'mt', 'islamiyat', 'nmt', 
            'other_subjects',
            'total_obtained', 'total_max', 'percentage', 'grade', 'remarks', 'created_at'
        ];

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Handle old schema where year might be missing (index 4)
            // Old schema: id, student_id, class, exam_type, english...
            // New schema: id, student_id, class, exam_type, year, english...
            
            if ($row[0] == $id) {
                // Recalculate
                $marks = [
                    $resultData['english'], $resultData['math'], $resultData['social_studies'], 
                    $resultData['general_science'], $resultData['mt'], $resultData['islamiyat'], $resultData['nmt']
                ];
                
                $otherSubjects = isset($resultData['other_subjects']) ? json_decode($resultData['other_subjects'], true) : [];
                if (!is_array($otherSubjects)) $otherSubjects = [];
                foreach ($otherSubjects as $subject => $mark) {
                    $marks[] = $mark;
                }
                
                $totalObtained = array_sum($marks);
                $totalMax = isset($resultData['total_max']) ? $resultData['total_max'] : (count($marks) * 100);
                $percentage = ($totalMax > 0) ? ($totalObtained / $totalMax) * 100 : 0;
                $grade = $this->calculateGrade($percentage);
                $remarks = $this->calculateRemarks($percentage);

                $updatedRow = [
                    $id,
                    $resultData['student_id'],
                    $resultData['class'],
                    $resultData['exam_type'],
                    $resultData['year'],
                    $resultData['english'],
                    $resultData['math'],
                    $resultData['social_studies'],
                    $resultData['general_science'],
                    $resultData['mt'],
                    $resultData['islamiyat'],
                    $resultData['nmt'],
                    isset($resultData['other_subjects']) ? $resultData['other_subjects'] : '{}',
                    $totalObtained,
                    $totalMax,
                    round($percentage, 2),
                    $grade,
                    $remarks,
                    date('Y-m-d H:i:s') // Update timestamp
                ];
                $rows[] = $updatedRow;
            } else {
                // If it's an old row without year, we need to migrate it or keep it.
                // If count is less than new header count, insert default year
                // If count is less than new header count (19), insert default other_subjects
                if (count($row) < 19) {
                    // Insert empty JSON at index 12 (after nmt) if old row
                    // Old row has 18 columns? Wait, previous fix made it 18.
                    // New schema has 19.
                    // Check if 'other_subjects' exists in $headers is not enough because we rewrite headers.
                    // We know nmt is index 11. 
                    // Insert at 12.
                    array_splice($row, 12, 0, '{}'); 
                }
                $rows[] = $row;
            }
        }
        fclose($handle);

        $fp = fopen($file, 'w');
        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        return true;
    }

    public function deleteResult($resultId) {
        $file = __DIR__ . '/../data/results.csv';
        if (!file_exists($file)) return false;

        $rows = [];
        $handle = fopen($file, "r");
        if ($handle === false) return false;

        $found = false;
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if ($row[0] != $resultId) {
                $rows[] = $row;
            } else {
                $found = true;
            }
        }
        fclose($handle);

        if ($found) {
            $fp = fopen($file, 'w');
            foreach ($rows as $row) {
                fputcsv($fp, $row);
            }
            fclose($fp);
            return true;
        }
        return false;
    }

    public function getResults($class, $examType, $year) {
        $file = __DIR__ . '/../data/results.csv';
        $results = [];
        if (!file_exists($file)) return $results;

        $handle = fopen($file, "r");
        $headers = fgetcsv($handle, 1000, ",");
        
        // Detect if file has old schema
        $hasYear = in_array('year', $headers);

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($row) == count($headers)) {
                $data = array_combine($headers, $row);
                
                // If old schema, treat year as current year or ignore
                $rowYear = $hasYear ? $data['year'] : date('Y');
                
                if ($data['class'] == $class && $data['exam_type'] == $examType && $rowYear == $year) {
                    $results[$data['student_id']] = $data;
                }
            }
        }
        fclose($handle);
        return $results;
    }

    public function getStudentResult($studentId, $examType, $year) {
        $file = __DIR__ . '/../data/results.csv';
        if (!file_exists($file)) return null;

        $handle = fopen($file, "r");
        $headers = fgetcsv($handle, 1000, ",");
        $hasYear = in_array('year', $headers);
        
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($row) == count($headers)) {
                $data = array_combine($headers, $row);
                $rowYear = $hasYear ? $data['year'] : date('Y');

                if ($data['student_id'] == $studentId && $data['exam_type'] == $examType && $rowYear == $year) {
                    fclose($handle);
                    return $data;
                }
            }
        }
        fclose($handle);
        return null;
    }

    private function calculateGrade($percentage) {
        if ($percentage >= 80) return 'A+';
        if ($percentage >= 70) return 'A';
        if ($percentage >= 60) return 'B';
        if ($percentage >= 50) return 'C';
        if ($percentage >= 40) return 'D';
        return 'F';
    }

    private function calculateRemarks($percentage) {
        if ($percentage >= 80) return 'Excellent';
        if ($percentage >= 70) return 'Very Good';
        if ($percentage >= 60) return 'Good';
        if ($percentage >= 50) return 'Fair';
        if ($percentage >= 40) return 'Pass';
        return 'Fail';
    }

    // Dynamic Subject Configuration
    public function getSubjectConfig($class, $examType, $year) {
        $file = __DIR__ . '/../data/subject_config.json';
        if (!file_exists($file)) return [];
        
        $config = json_decode(file_get_contents($file), true);
        $key = "{$class}_{$examType}_{$year}";
        
        return isset($config[$key]) ? $config[$key] : [];
    }

    public function addSubjectConfig($class, $examType, $year, $subjectName) {
        $file = __DIR__ . '/../data/subject_config.json';
        $config = [];
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                $config = $decoded;
            }
        }
        
        $key = "{$class}_{$examType}_{$year}";
        if (!isset($config[$key])) {
            $config[$key] = [];
        }
        
        // Avoid duplicates
        if (!in_array($subjectName, $config[$key])) {
            $config[$key][] = $subjectName;
            file_put_contents($file, json_encode($config, JSON_PRETTY_PRINT));
            return true;
        }
        return false;
    }

    public function deleteSubjectConfig($class, $examType, $year, $subjectName) {
        $file = __DIR__ . '/../data/subject_config.json';
        if (!file_exists($file)) return false;
        
        $config = json_decode(file_get_contents($file), true);
        if (!is_array($config)) return false;
        
        $key = "{$class}_{$examType}_{$year}";
        if (isset($config[$key])) {
            $index = array_search($subjectName, $config[$key]);
            if ($index !== false) {
                // Remove subject
                array_splice($config[$key], $index, 1);
                
                // If empty, removing the key is cleaner but keeping it empty is fine too
                if (empty($config[$key])) {
                    unset($config[$key]);
                }
                
                file_put_contents($file, json_encode($config, JSON_PRETTY_PRINT));
                return true;
            }
        }
        return false;
    }
}
