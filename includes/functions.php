<?php
function formatCnic($cnic) {
    // Remove any non-numeric characters
    $cnic = preg_replace('/[^0-9]/', '', $cnic);
    
    // Check if we have exactly 13 digits
    if (strlen($cnic) == 13) {
        return substr($cnic, 0, 5) . '-' . substr($cnic, 5, 7) . '-' . substr($cnic, 12, 1);
    }
    
    // Return original if not 13 digits (or maybe partially formatted if you prefer, but requirement is strict)
    return $cnic;
}

function formatContact($contact) {
    // Remove any non-numeric characters
    $contact = preg_replace('/[^0-9]/', '', $contact);
    
    // Check if we have exactly 11 digits (e.g., 03001234567)
    if (strlen($contact) == 11) {
        return substr($contact, 0, 4) . '-' . substr($contact, 4, 7);
    }
    
    return $contact;
}
?>
