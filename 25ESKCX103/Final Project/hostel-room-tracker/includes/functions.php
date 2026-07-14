<?php
// Shared utility helper functions

/**
 * Sanitizes a string input to protect against XSS injections.
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Formats a currency number with the Rupee symbol.
 */
function format_currency($amount) {
    return '₹' . number_format($amount, 2);
}

/**
 * Returns the HTML for a request status badge.
 */
function get_status_badge($status) {
    $status = sanitize($status);
    switch ($status) {
        case 'Approved':
            return '<span class="badge-status badge-status-approved"><i class="bi bi-check-circle-fill"></i> Approved</span>';
        case 'Rejected':
            return '<span class="badge-status badge-status-rejected"><i class="bi bi-x-circle-fill"></i> Rejected</span>';
        case 'Cancelled':
            return '<span class="badge-status badge-status-cancelled"><i class="bi bi-slash-circle"></i> Cancelled</span>';
        case 'Pending':
        default:
            return '<span class="badge-status badge-status-pending"><i class="bi bi-hourglass-split"></i> Pending</span>';
    }
}

/**
 * Returns the HTML for a room availability status badge.
 */
function get_room_status_badge($status) {
    $status = sanitize($status);
    switch ($status) {
        case 'Available':
            return '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-10 px-3 py-2 rounded-pill"><i class="bi bi-check-circle-fill"></i> Available</span>';
        case 'Full':
            return '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10 px-3 py-2 rounded-pill"><i class="bi bi-person-fill-slash"></i> Full</span>';
        case 'Maintenance':
        default:
            return '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-10 px-3 py-2 rounded-pill"><i class="bi bi-wrench-adjustable"></i> Maintenance</span>';
    }
}

/**
 * Gets hostel gender restriction labels.
 */
function get_hostel_type_label($type) {
    switch ($type) {
        case 'boys':
            return '<span class="badge bg-primary bg-opacity-10 text-primary px-2.5 py-1.5 rounded-pill"><i class="bi bi-gender-male"></i> Boys Only</span>';
        case 'girls':
            return '<span class="badge bg-danger bg-opacity-10 text-danger px-2.5 py-1.5 rounded-pill"><i class="bi bi-gender-female"></i> Girls Only</span>';
        case 'coed':
        default:
            return '<span class="badge bg-info bg-opacity-10 text-info px-2.5 py-1.5 rounded-pill"><i class="bi bi-gender-ambiguous"></i> Co-ed</span>';
    }
}
?>
