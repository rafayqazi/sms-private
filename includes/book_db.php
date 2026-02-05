<?php
class BookDatabase {
    private $inventoryFile;
    private $issuedFile;
    private $damagedFile;

    public function __construct() {
        $this->inventoryFile = __DIR__ . '/../data/books_inventory.csv';
        $this->issuedFile = __DIR__ . '/../data/books_issued.csv';
        $this->damagedFile = __DIR__ . '/../data/books_damaged.csv';

        if (!file_exists($this->inventoryFile)) {
            $handle = fopen($this->inventoryFile, 'w');
            fputcsv($handle, ['id', 'name', 'subject', 'class', 'qty_total', 'qty_available', 'status', 'created_at', 'updated_at']);
            fclose($handle);
        }
        if (!file_exists($this->issuedFile)) {
            $handle = fopen($this->issuedFile, 'w');
            fputcsv($handle, ['id', 'recipient_type', 'recipient_id', 'book_id', 'issue_date', 'return_date', 'status', 'condition', 'damage_type', 'damage_remarks', 'remarks']);
            fclose($handle);
        }
        if (!file_exists($this->damagedFile)) {
            $handle = fopen($this->damagedFile, 'w');
            fputcsv($handle, ['id', 'original_book_id', 'name', 'subject', 'class', 'quantity', 'damage_date', 'damage_type', 'remarks', 'added_by']);
            fclose($handle);
        }
    }

    // --- Inventory Methods ---

    public function getAllBooks() {
        $books = [];
        if (($handle = fopen($this->inventoryFile, "r")) !== FALSE) {
            $header = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== FALSE) {
                // Normalize data to match header length
                $headerCount = count($header);
                $dataCount = count($data);
                
                if ($dataCount < $headerCount) {
                    $data = array_pad($data, $headerCount, '');
                } elseif ($dataCount > $headerCount) {
                    $data = array_slice($data, 0, $headerCount);
                }
                
                $books[] = array_combine($header, $data);
            }
            fclose($handle);
        }
        return $books;
    }

    public function addBook($name, $subject, $class, $qty) {
        $id = uniqid();
        $createdAt = date('Y-m-d H:i:s');
        $updatedAt = date('Y-m-d H:i:s');
        $newBook = [$id, $name, $subject, $class, $qty, $qty, 'active', $createdAt, $updatedAt];
        
        $handle = fopen($this->inventoryFile, 'a');
        fputcsv($handle, $newBook);
        fclose($handle);
        return true;
    }

    private function updateBookQty($bookId, $change) {
        $rows = [];
        $header = [];
        $updated = false;

        if (($handle = fopen($this->inventoryFile, "r")) !== FALSE) {
            $header = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== FALSE) {
                if ($data[0] == $bookId) {
                    $currentQty = (int)$data[5];
                    $data[5] = $currentQty + $change; // Update qty_available
                    $updated = true;
                }
                $rows[] = $data;
            }
            fclose($handle);
        }

        if ($updated) {
            $handle = fopen($this->inventoryFile, 'w');
            fputcsv($handle, $header);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
            return true;
        }
        return false;
    }
    
    public function getBookById($bookId) {
        if (($handle = fopen($this->inventoryFile, "r")) !== FALSE) {
            $header = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== FALSE) {
                if ($data[0] == $bookId) {
                    // Normalize data to match header length
                    $headerCount = count($header);
                    $dataCount = count($data);
                    
                    if ($dataCount < $headerCount) {
                        // Pad with empty strings
                        $data = array_pad($data, $headerCount, '');
                    } elseif ($dataCount > $headerCount) {
                        // Trim excess data
                        $data = array_slice($data, 0, $headerCount);
                    }
                    
                    fclose($handle);
                    return array_combine($header, $data);
                }
            }
            fclose($handle);
        }
        return null;
    }

    // --- Issuance Methods ---

    public function issueBook($studentId, $bookId) {
        // Check availability
        $book = $this->getBookById($bookId);
        // Backward compatibility: if status field doesn't exist, treat as active
        $status = isset($book['status']) ? $book['status'] : 'active';
        if (!$book || $book['qty_available'] <= 0 || $status != 'active') {
            return false;
        }

        $id = uniqid();
        $issueDate = date('Y-m-d');
        $record = [$id, 'student', $studentId, $bookId, $issueDate, '', 'Issued', 'normal', '', '', ''];

        $handle = fopen($this->issuedFile, 'a');
        fputcsv($handle, $record);
        fclose($handle);

        $this->updateBookQty($bookId, -1);
        return true;
    }

    public function issueBookToTeacher($teacherId, $bookId) {
        // Check availability
        $book = $this->getBookById($bookId);
        // Backward compatibility: if status field doesn't exist, treat as active
        $status = isset($book['status']) ? $book['status'] : 'active';
        if (!$book || $book['qty_available'] <= 0 || $status != 'active') {
            return false;
        }

        $id = uniqid();
        $issueDate = date('Y-m-d');
        $record = [$id, 'teacher', $teacherId, $bookId, $issueDate, '', 'Issued', 'normal', '', '', ''];

        $handle = fopen($this->issuedFile, 'a');
        fputcsv($handle, $record);
        fclose($handle);

        $this->updateBookQty($bookId, -1);
        return true;
    }

    public function issueBookBulk($classId, $bookId) {
        // Get all students in the class
        require_once __DIR__ . '/db.php';
        $db = new Database();
        $students = $db->filterStudents(['class' => $classId]);
        
        if (empty($students)) {
            return ['success' => false, 'message' => 'No students found in this class'];
        }

        // Check availability
        $book = $this->getBookById($bookId);
        // Backward compatibility: if status field doesn't exist, treat as active
        $status = isset($book['status']) ? $book['status'] : 'active';
        if (!$book || $status != 'active') {
            return ['success' => false, 'message' => 'Book not found or not active'];
        }

        // Count how many can be issued (excluding those who already have this book)
        $eligibleStudents = [];
        foreach ($students as $student) {
            $alreadyHas = false;
            if (($handle = fopen($this->issuedFile, "r")) !== FALSE) {
                $header = fgetcsv($handle);
                while (($data = fgetcsv($handle)) !== FALSE) {
                    // Check if student already has this book issued
                    if ($data[1] == 'student' && $data[2] == $student['id'] && $data[3] == $bookId && $data[6] == 'Issued') {
                        $alreadyHas = true;
                        break;
                    }
                }
                fclose($handle);
            }
            if (!$alreadyHas) {
                $eligibleStudents[] = $student;
            }
        }

        if (empty($eligibleStudents)) {
            return ['success' => false, 'message' => 'All students in this class already have this book'];
        }

        if ($book['qty_available'] < count($eligibleStudents)) {
            return ['success' => false, 'message' => 'Not enough books available. Need ' . count($eligibleStudents) . ', have ' . $book['qty_available']];
        }

        // Issue to all eligible students
        $issued = 0;
        foreach ($eligibleStudents as $student) {
            $id = uniqid();
            $issueDate = date('Y-m-d');
            $record = [$id, 'student', $student['id'], $bookId, $issueDate, '', 'Issued', 'normal', '', '', 'Bulk Issue'];

            $handle = fopen($this->issuedFile, 'a');
            fputcsv($handle, $record);
            fclose($handle);

            $this->updateBookQty($bookId, -1);
            $issued++;
        }

        return ['success' => true, 'message' => "Successfully issued books to {$issued} students", 'count' => $issued];
    }

    public function returnBook($issueId, $condition = 'normal', $damageType = '', $damageRemarks = '', $remarks = '') {
        $rows = [];
        $header = [];
        $bookId = null;
        $bookDetails = null;
        $updated = false;

        if (($handle = fopen($this->issuedFile, "r")) !== FALSE) {
            $header = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== FALSE) {
                if ($data[0] == $issueId && $data[6] == 'Issued') {
                    $data[5] = date('Y-m-d'); // return_date
                    $data[6] = 'Returned';    // status
                    $data[7] = $condition;    // condition
                    $data[8] = $damageType;   // damage_type
                    $data[9] = $damageRemarks; // damage_remarks
                    $data[10] = $remarks;     // general remarks
                    $bookId = $data[3];
                    $updated = true;
                }
                $rows[] = $data;
            }
            fclose($handle);
        }

        if ($updated && $bookId) {
            // Save updated issued file
            $handle = fopen($this->issuedFile, 'w');
            fputcsv($handle, $header);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
            
            // Get book details for damaged inventory
            $bookDetails = $this->getBookById($bookId);
            
            if ($condition === 'damaged' && $bookDetails) {
                // Move to damaged inventory - do NOT return to stock
                $this->addToDamagedInventory($bookDetails, $damageType, $damageRemarks);
            } else {
                // Normal return - return to stock
                $this->updateBookQty($bookId, 1);
            }
            
            return true;
        }
        return false;
    }

    // Add book to damaged inventory
    private function addToDamagedInventory($book, $damageType, $remarks) {
        $id = uniqid();
        $damageDate = date('Y-m-d H:i:s');
        $addedBy = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'system';
        
        $record = [
            $id,
            $book['id'],
            $book['name'],
            $book['subject'],
            $book['class'],
            1, // quantity
            $damageDate,
            $damageType,
            $remarks,
            $addedBy
        ];
        
        $handle = fopen($this->damagedFile, 'a');
        fputcsv($handle, $record);
        fclose($handle);
    }

    public function getStudentHistory($studentId) {
        return $this->getRecipientHistory('student', $studentId);
    }

    public function getTeacherHistory($teacherId) {
        return $this->getRecipientHistory('teacher', $teacherId);
    }

    public function getRecipientHistory($recipientType, $recipientId) {
        $history = [];
        $books = $this->getAllBooks();
        $bookMap = [];
        foreach ($books as $b) {
            $bookMap[$b['id']] = $b;
        }

        if (($handle = fopen($this->issuedFile, "r")) !== FALSE) {
            $header = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== FALSE) {
                // $data[1] is recipient_type, $data[2] is recipient_id
                if ($data[1] == $recipientType && $data[2] == $recipientId) {
                    // Normalize data to match header length
                    $headerCount = count($header);
                    $dataCount = count($data);
                    
                    if ($dataCount < $headerCount) {
                        $data = array_pad($data, $headerCount, '');
                    } elseif ($dataCount > $headerCount) {
                        $data = array_slice($data, 0, $headerCount);
                    }
                    
                    $record = array_combine($header, $data);
                    $record['book_details'] = isset($bookMap[$record['book_id']]) ? $bookMap[$record['book_id']] : null;
                    $history[] = $record;
                }
            }
            fclose($handle);
        }
        // Sort by issue date desc
        usort($history, function($a, $b) {
            return strtotime($b['issue_date']) - strtotime($a['issue_date']);
        });
        
        return $history;
    }

    public function getAllIssuedBooksDetails() {
        $issued = [];
        $books = $this->getAllBooks();
        $bookMap = [];
        foreach ($books as $b) {
            $bookMap[$b['id']] = $b;
        }

        if (($handle = fopen($this->issuedFile, "r")) !== FALSE) {
            $header = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== FALSE) {
                if ($data[6] == 'Issued') { // Only active issues (status is now at index 6)
                    // Normalize data to match header length
                    $headerCount = count($header);
                    $dataCount = count($data);
                    
                    if ($dataCount < $headerCount) {
                        $data = array_pad($data, $headerCount, '');
                    } elseif ($dataCount > $headerCount) {
                        $data = array_slice($data, 0, $headerCount);
                    }
                    
                    $record = array_combine($header, $data);
                    $record['book_details'] = isset($bookMap[$record['book_id']]) ? $bookMap[$record['book_id']] : null;
                    $issued[] = $record;
                }
            }
            fclose($handle);
        }
        return $issued;
    }

    // Delete book (soft delete - mark as deleted)
    public function deleteBook($bookId) {
        return $this->updateBookStatus($bookId, 'deleted');
    }

    // Move book quantity to damaged inventory
    public function moveToDamaged($bookId, $quantity, $damageType, $remarks) {
        $book = $this->getBookById($bookId);
        if (!$book || $book['qty_available'] < $quantity) {
            return false;
        }

        // Add to damaged inventory
        $id = uniqid();
        $damageDate = date('Y-m-d H:i:s');
        $addedBy = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'system';
        
        $record = [
            $id,
            $book['id'],
            $book['name'],
            $book['subject'],
            $book['class'],
            $quantity,
            $damageDate,
            $damageType,
            $remarks,
            $addedBy
        ];
        
        $handle = fopen($this->damagedFile, 'a');
        fputcsv($handle, $record);
        fclose($handle);

        // Reduce available quantity
        $this->updateBookQty($bookId, -$quantity);
        
        return true;
    }

    // Update book status
    public function updateBookStatus($bookId, $status) {
        $rows = [];
        $header = [];
        $updated = false;

        if (($handle = fopen($this->inventoryFile, "r")) !== FALSE) {
            $header = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== FALSE) {
                if ($data[0] == $bookId) {
                    $data[6] = $status; // status field
                    $data[8] = date('Y-m-d H:i:s'); // updated_at
                    $updated = true;
                }
                $rows[] = $data;
            }
            fclose($handle);
        }

        if ($updated) {
            $handle = fopen($this->inventoryFile, 'w');
            fputcsv($handle, $header);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
            return true;
        }
        return false;
    }

    // Get all damaged books
    public function getDamagedBooks() {
        $damaged = [];
        if (($handle = fopen($this->damagedFile, "r")) !== FALSE) {
            $header = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== FALSE) {
                // Normalize data to match header length
                $headerCount = count($header);
                $dataCount = count($data);
                
                if ($dataCount < $headerCount) {
                    $data = array_pad($data, $headerCount, '');
                } elseif ($dataCount > $headerCount) {
                    $data = array_slice($data, 0, $headerCount);
                }
                
                $damaged[] = array_combine($header, $data);
            }
            fclose($handle);
        }
        return $damaged;
    }

    public function getStats() {
        $books = $this->getAllBooks();
        $totalItems = 0;
        $totalAvailable = 0;
        
        foreach ($books as $b) {
            // Only count active books
            if (!isset($b['status']) || $b['status'] == 'active') {
                $totalItems += (int)$b['qty_total'];
                $totalAvailable += (int)$b['qty_available'];
            }
        }

        $issuedCount = 0;
        if (($handle = fopen($this->issuedFile, "r")) !== FALSE) {
            fgetcsv($handle); // skip header
            while (($data = fgetcsv($handle)) !== FALSE) {
                if ($data[6] == 'Issued') { // status is now at index 6
                    $issuedCount++;
                }
            }
            fclose($handle);
        }

        // Count damaged books
        $damagedCount = 0;
        $damagedBooks = $this->getDamagedBooks();
        foreach ($damagedBooks as $d) {
            $damagedCount += (int)$d['quantity'];
        }

        return [
            'total_books' => $totalItems,
            'available_books' => $totalAvailable,
            'issued_books' => $issuedCount,
            'damaged_books' => $damagedCount
        ];
    }
}
?>
