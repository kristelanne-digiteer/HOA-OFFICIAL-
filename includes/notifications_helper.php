<?php
/**
 * Pinagsasama-sama ang mga recent updates mula sa iba't-ibang table
 * (announcements, document/rental/payment status, SOA dues) para sa
 * isang user, may stable na 'key' bawat isa para magamit sa
 * "Clear" / dismiss feature (parang sa notifications ng phone).
 *
 * @return array list of ['key','icon','message','time']
 */
function getUserNotifications($conn, $user_id, $masterlist_id) {
    $notifications = [];

    // 1. Mga bagong HOA announcement (lahat ng users makikita ito)
    $ann_q = mysqli_query($conn, "
        SELECT announcement_id, title, created_at
        FROM announcements
        ORDER BY created_at DESC
        LIMIT 5
    ");
    while ($row = mysqli_fetch_assoc($ann_q)) {
        $notifications[] = [
            'key'     => 'ann_' . $row['announcement_id'],
            'icon'    => '📢',
            'message' => 'Announcement: ' . $row['title'],
            'time'    => $row['created_at'],
        ];
    }

    // 2. Status updates sa document requests niya (approved/rejected/completed)
    $doc_q = mysqli_query($conn, "
        SELECT request_id, document_type, status, date_requested
        FROM document_requests
        WHERE user_id='$user_id' AND status != 'pending'
        ORDER BY date_requested DESC
        LIMIT 5
    ");
    while ($row = mysqli_fetch_assoc($doc_q)) {
        $notifications[] = [
            'key'     => 'doc_' . $row['request_id'],
            'icon'    => '📄',
            'message' => 'Your ' . $row['document_type'] . ' request is now ' . ucfirst($row['status']),
            'time'    => $row['date_requested'],
        ];
    }

    // 3. Status updates sa rental requests niya
    $rent_q = mysqli_query($conn, "
        SELECT rental_id, status, created_at
        FROM rental_requests
        WHERE user_id='$user_id' AND status != 'pending'
        ORDER BY created_at DESC
        LIMIT 5
    ");
    while ($row = mysqli_fetch_assoc($rent_q)) {
        $notifications[] = [
            'key'     => 'rental_' . $row['rental_id'],
            'icon'    => '🏠',
            'message' => 'Your rental request #' . $row['rental_id'] . ' is now ' . ucfirst($row['status']),
            'time'    => $row['created_at'],
        ];
    }

    // 4. Status updates sa payments niya (approved/rejected)
    $pay_q = mysqli_query($conn, "
        SELECT payment_id, amount_paid, status, payment_date
        FROM payments
        WHERE user_id='$user_id' AND status != 'pending'
        ORDER BY payment_date DESC
        LIMIT 5
    ");
    while ($row = mysqli_fetch_assoc($pay_q)) {
        $notifications[] = [
            'key'     => 'pay_' . $row['payment_id'],
            'icon'    => '💸',
            'message' => 'Your payment of ₱' . number_format($row['amount_paid'], 2) . ' was ' . ucfirst($row['status']),
            'time'    => $row['payment_date'],
        ];
    }

    // 5. Paalala kung may unpaid/overdue dues
    if ($masterlist_id) {
        $soa_q = mysqli_query($conn, "
            SELECT soa_id, billing_month, total_balance, due_date, status
            FROM soa_records
            WHERE homeowner_id='$masterlist_id' AND status IN ('unpaid','overdue')
            ORDER BY due_date ASC
            LIMIT 3
        ");
        while ($row = mysqli_fetch_assoc($soa_q)) {
            $label = $row['status'] === 'overdue'
                ? 'is overdue'
                : 'is due on ' . date('M d, Y', strtotime($row['due_date']));
            $notifications[] = [
                'key'     => 'soa_' . $row['soa_id'],
                'icon'    => '⚠️',
                'message' => 'Dues for ' . $row['billing_month'] . ' (₱' . number_format($row['total_balance'], 2) . ') ' . $label,
                'time'    => $row['due_date'],
            ];
        }
    }

    // 6. Reminder ng mga events na "na-save" ng user (event_reminders table)
    //    Lalabas lang ito kapag dumating na ang araw ng event (event_date = today)
    //    — parang pop-up notification na "nangyari/dumating na" ang event.
    $event_rem_q = mysqli_query($conn, "
        SELECT e.event_id, e.title, e.event_date, e.event_time
        FROM event_reminders r
        INNER JOIN events e ON r.event_id = e.event_id
        WHERE r.user_id='$user_id' AND e.event_date = CURDATE()
    ");
    while ($row = mysqli_fetch_assoc($event_rem_q)) {
        $time_part = $row['event_time'] ? ' at ' . date('h:i A', strtotime($row['event_time'])) : '';
        $notifications[] = [
            'key'     => 'event_' . $row['event_id'],
            'icon'    => '🔔',
            'message' => 'Reminder: "' . $row['title'] . '" is today' . $time_part,
            'time'    => $row['event_date'] . ' ' . ($row['event_time'] ?? '00:00:00'),
        ];
    }

    return $notifications;
}
?>