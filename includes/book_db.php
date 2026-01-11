<?php
class BookDatabase {
    private $inventoryFile;
    private $issuedFile;

    public function __construct() {
        $this->inventoryFile = __DIR__ . '/../data/books_inventory.csv';
        $this->issuedFile = __DIR__ . '/../data/books_issued.csv';

        if (!file_exists($this->inventoryFile)) {
            $handle = fopen($this->inventoryFile, 'w');
            fputcsv($handle, ['id', 'name', 'subject', 'class', 'qty_total', 'qty_available', 'created_at']);
            fclose($handle);
        }
        if (!file_exists($this->issuedFile)) {
            $handle = fopen($this->issuedFile, 'w');
            fputcsv($handle, ['id', 'student_id', 'book_id', 'issue_date', 'return_date', 'status', 'remarks']);
            fclose($handle);
        }
    }

    // --- Inventory Methods ---

    public function getAllBooks() {
        $books = [];
        if (($handle = fopen($this->inventoryFile, "r")) !== FALSE) {
            $header = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== FALSE) {
                if (count($data) == count($header)) {
                    $books[] = array_combine($header, $data);
                }
            }
            fclose($handle);
        }
        return $books;
    }

    public function addBook($name, $subject, $class, $qty) {
        $id = uniqid();
        $createdAt = date('Y-m-d H:i:s');
        $newBook = [$id, $name, $subject, $class, $qty, $qty, $createdAt];
        
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
        if (!$book || $book['qty_available'] <= 0) {
            return false;
        }

        $id = uniqid();
        $issueDate = date('Y-m-d');
        $record = [$id, $studentId, $bookId, $issueDate, '', 'Issued', ''];

        $handle = fopen($this->issuedFile, 'a');
        fputcsv($handle, $record);
        fclose($handle);

        $this->updateBookQty($bookId, -1);
        return true;
    }

    public function returnBook($issueId, $remarks = '') {
        $rows = [];
        $header = [];
        $bookId = null;
        $updated = false;

        if (($handle = fopen($this->issuedFile, "r")) !== FALSE) {
            $header = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== FALSE) {
                // $data[0] is id, $data[2] is book_id, $data[5] is status
                if ($data[0] == $issueId && $data[5] == 'Issued') {
                    $data[4] = date('Y-m-d'); // return_date
                    $data[5] = 'Returned';    // status
                    $data[6] = $remarks;      // remarks
                    $bookId = $data[2];
                    $updated = true;
                }
                $rows[] = $data;
            }
            fclose($handle);
        }

        if ($updated && $bookId) {
            $handle = fopen($this->issuedFile, 'w');
            fputcsv($handle, $header);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
            
            $this->updateBookQty($bookId, 1);
            return true;
        }
        return false;
    }

    public function getStudentHistory($studentId) {
        $history = [];
        $books = $this->getAllBooks();
        $bookMap = [];
        foreach ($books as $b) {
            $bookMap[$b['id']] = $b;
        }

        if (($handle = fopen($this->issuedFile, "r")) !== FALSE) {
            $header = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== FALSE) {
                if ($data[1] == $studentId) { // $data[1] is student_id
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
                if ($data[5] == 'Issued') { // Only active issues
                    $record = array_combine($header, $data);
                    $record['book_details'] = isset($bookMap[$record['book_id']]) ? $bookMap[$record['book_id']] : null;
                    $issued[] = $record;
                }
            }
            fclose($handle);
        }
        return $issued;
    }

    public function getStats() {
        $books = $this->getAllBooks();
        $totalItems = 0;
        $totalAvailable = 0;
        
        foreach ($books as $b) {
            $totalItems += (int)$b['qty_total'];
            $totalAvailable += (int)$b['qty_available'];
        }

        $issuedCount = 0;
        if (($handle = fopen($this->issuedFile, "r")) !== FALSE) {
            fgetcsv($handle); // skip header
            while (($data = fgetcsv($handle)) !== FALSE) {
                if ($data[5] == 'Issued') {
                    $issuedCount++;
                }
            }
            fclose($handle);
        }

        return [
            'total_books' => $totalItems,
            'available_books' => $totalAvailable,
            'issued_books' => $issuedCount
        ];
    }
}
?>
