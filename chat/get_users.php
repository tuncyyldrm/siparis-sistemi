<?php
session_start();
include '../db_config.php';

// Sadece admin erişebilsin
if (!isset($_SESSION['user']) || $_SESSION['user']['cari'] !== 'PLASİYER') {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Yetkisiz erişim."]);
    exit;
}

// Sadece mesaj göndermiş veya almış kullanıcıları getir
$sql = "
SELECT u.usersId, u.username, u.cari,
       lm.message AS last_message,
       lm.created_at AS last_time,
       (
           SELECT COUNT(*) 
           FROM chat_messages um
           WHERE um.sender_id = u.cari
             AND um.receiver_id = 'ADMIN'
             AND um.role='user'
             AND um.created_at > COALESCE(
                 (SELECT created_at
                  FROM chat_messages am
                  WHERE am.sender_id = 'ADMIN' AND am.receiver_id = u.cari
                  ORDER BY created_at DESC LIMIT 1), '1970-01-01 00:00:00')
       ) AS unread_count
FROM users u
INNER JOIN (
    SELECT cm1.*
    FROM chat_messages cm1
    INNER JOIN (
        SELECT 
            CASE 
                WHEN sender_id = 'ADMIN' THEN receiver_id 
                ELSE sender_id 
            END AS user_cari,
            MAX(created_at) AS max_created
        FROM chat_messages
        GROUP BY user_cari
    ) cm2 ON (cm1.sender_id = cm2.user_cari OR cm1.receiver_id = cm2.user_cari) 
           AND cm1.created_at = cm2.max_created
) lm ON lm.sender_id = u.cari OR lm.receiver_id = u.cari
WHERE u.cari <> 'PLASİYER'
ORDER BY unread_count DESC, lm.created_at DESC
";

$result = $conn->query($sql);
$users = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = [
            "usersId" => $row['usersId'],
            "username" => $row['username'],
            "cari" => $row['cari'],
            "last_message" => $row['last_message'],
            "last_time" => $row['last_time'],
            "unread_count" => (int)$row['unread_count']
        ];
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($users, JSON_UNESCAPED_UNICODE);

$conn->close();
?>
