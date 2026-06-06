<?php

function buildFeeHistoryReport(Database $db, array $params = []) {
    $month = $params['month'] ?? '';
    $class_filter = $params['class'] ?? '';
    $stage_filter = $params['stage'] ?? '';
    $search = trim($params['search'] ?? '');
    $gr_no = $params['gr_no'] ?? '';

    $classes = $db->getClasses();
    $classStageMap = [];
    $classOrderMap = [];
    foreach ($classes as $c) {
        $classStageMap[$c['class_name']] = $c['stage'] ?? 'Elementary';
        $classOrderMap[$c['class_name']] = (int)($c['sort_order'] ?? 0);
    }

    $filters = [];
    if ($month) $filters['month'] = $month;
    if ($gr_no) $filters['gr_no'] = $gr_no;

    $history = $db->getFeeCollections($filters);
    $all_students = $db->readData();
    $studentMap = [];
    foreach ($all_students as $s) {
        $studentMap[$s['gr_no']] = $s;
    }

    $feeStructure = $db->getFeeStructure();
    $display_list = [];

    if ($month && !$gr_no) {
        foreach ($all_students as $s) {
            $student_gr = $s['gr_no'];
            $student_class = $s['current_class'];

            if ($class_filter && $student_class !== $class_filter) continue;
            if (isset($s['student_status']) && $s['student_status'] === 'Alumni') continue;

            $payment = null;
            foreach ($history as $h) {
                if ($h['gr_no'] == $student_gr && $h['month_for'] == $month) {
                    $payment = $h;
                    break;
                }
            }

            $display_list[] = [
                'gr_no' => $student_gr,
                'student_name' => $s['student_name'],
                'father_name' => $s['father_name'] ?? '',
                'class' => $student_class,
                'stage' => $classStageMap[$student_class] ?? 'Elementary',
                'payment' => $payment
            ];
        }
    } else {
        foreach ($history as $h) {
            $s = $studentMap[$h['gr_no']] ?? null;
            $s_name = $s ? $s['student_name'] : 'Unknown';
            $father_name = $s ? ($s['father_name'] ?? '') : '';
            $s_class = 'N/A';
            if ($s) {
                $s_class = ($s['student_status'] ?? '') === 'Alumni'
                    ? ($s['last_class'] ?? $s['current_class'])
                    : $s['current_class'];
            }

            if ($class_filter && $s_class !== $class_filter) continue;

            $display_list[] = [
                'gr_no' => $h['gr_no'],
                'student_name' => $s_name,
                'father_name' => $father_name,
                'class' => $s_class,
                'stage' => $classStageMap[$s_class] ?? 'Elementary',
                'payment' => $h
            ];
        }
    }

    if ($stage_filter !== '') {
        $display_list = array_values(array_filter($display_list, function ($row) use ($stage_filter) {
            return ($row['stage'] ?? '') === $stage_filter;
        }));
    }

    if ($search !== '') {
        $searchLower = strtolower($search);
        $display_list = array_values(array_filter($display_list, function ($row) use ($searchLower) {
            return strpos(strtolower($row['student_name']), $searchLower) !== false
                || strpos(strtolower((string)$row['gr_no']), $searchLower) !== false;
        }));
    }

    $rows = [];
    foreach ($display_list as $row) {
        $rows[] = enrichFeeHistoryReportRow($db, $row, $feeStructure, $month);
    }

    $stageOrder = ['Pre-Primary' => 0, 'Elementary' => 1, 'College' => 2];
    usort($rows, function ($a, $b) use ($stageOrder, $classOrderMap) {
        $sA = $stageOrder[$a['stage']] ?? 99;
        $sB = $stageOrder[$b['stage']] ?? 99;
        if ($sA !== $sB) return $sA - $sB;

        $cA = $classOrderMap[$a['class']] ?? 999;
        $cB = $classOrderMap[$b['class']] ?? 999;
        if ($cA !== $cB) return $cA - $cB;

        $mCmp = strcmp($a['month_for'], $b['month_for']);
        if ($mCmp !== 0) return $mCmp;

        return strcasecmp($a['student_name'], $b['student_name']);
    });

    return $rows;
}

function enrichFeeHistoryReportRow(Database $db, array $row, array $feeStructure, $defaultMonth) {
    $p = $row['payment'];
    $classFees = $feeStructure[$row['class']] ?? ['monthly_fee' => 0];
    $assignedMonthly = (float)$classFees['monthly_fee'];
    $month_for = $p ? $p['month_for'] : ($defaultMonth ?: date('Y-m'));
    $arrears = $db->getStudentPreviousDebt($row['gr_no'], $month_for);

    if ($p) {
        $due_tuition = (isset($p['tuition_fee']) && $p['tuition_fee'] !== '' && (float)$p['tuition_fee'] > 0)
            ? (float)$p['tuition_fee'] : $assignedMonthly;
        $admission = (float)($p['admission_fee'] ?? 0);
        $exam = (float)($p['exam_fee'] ?? 0);
        $other = (float)($p['other_fee'] ?? 0);
        $discount = (float)($p['discount'] ?? 0);
        $month_fee = $due_tuition + $admission + $exam + $other - $discount;
        $total_due = $month_fee + $arrears;
        $amount_paid = (float)$p['amount_paid'];
        $remaining_debt = max(0.0, $total_due - $amount_paid);
        $month_balance = max(0.0, $month_fee - $amount_paid);

        if ($remaining_debt <= 0.01) {
            $status = 'Paid';
        } elseif ($amount_paid > 0) {
            $status = 'Partial';
        } else {
            $status = 'Unpaid';
        }

        return array_merge($row, [
            'month_for' => $month_for,
            'month_label' => date('F Y', strtotime($month_for . '-01')),
            'status' => $status,
            'tuition_fee' => $due_tuition,
            'admission_fee' => $admission,
            'exam_fee' => $exam,
            'other_fee' => $other,
            'discount' => $discount,
            'month_fee' => $month_fee,
            'arrears' => $arrears,
            'total_due' => $total_due,
            'amount_paid' => $amount_paid,
            'remaining_debt' => $remaining_debt,
            'month_balance' => $month_balance,
            'payment_method' => $p['payment_method'] ?? '',
            'payment_date' => $p['payment_date'] ?? '',
            'remarks' => $p['notes'] ?? ''
        ]);
    }

    $month_fee = $assignedMonthly;
    $total_due = $month_fee + $arrears;

    return array_merge($row, [
        'month_for' => $month_for,
        'month_label' => date('F Y', strtotime($month_for . '-01')),
        'status' => 'Unpaid',
        'tuition_fee' => $assignedMonthly,
        'admission_fee' => 0,
        'exam_fee' => 0,
        'other_fee' => 0,
        'discount' => 0,
        'month_fee' => $month_fee,
        'arrears' => $arrears,
        'total_due' => $total_due,
        'amount_paid' => 0,
        'remaining_debt' => $total_due,
        'month_balance' => $month_fee,
        'payment_method' => '',
        'payment_date' => '',
        'remarks' => ''
    ]);
}

function getFeeHistoryReportTitle(array $params) {
    $parts = [];
    if (!empty($params['month'])) {
        $parts[] = date('F Y', strtotime($params['month'] . '-01'));
    } else {
        $parts[] = 'All Time';
    }
    if (!empty($params['stage'])) $parts[] = $params['stage'];
    if (!empty($params['class'])) $parts[] = 'Class: ' . $params['class'];
    if (!empty($params['search'])) $parts[] = 'Search: ' . $params['search'];
    return implode(' | ', $parts);
}
