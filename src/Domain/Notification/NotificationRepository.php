<?php

declare(strict_types=1);

namespace App\Domain\Notification;

use PDO;

final class NotificationRepository
{
    public function __construct(private readonly PDO $database) {}

    public function billingAlerts(int $userId): array
    {
        $statement = $this->database->prepare("SELECT d.id, d.numero_devis, d.destine_a, d.total_ttc,
            d.date_facturation_prevue, DATEDIFF(d.date_facturation_prevue, CURRENT_DATE) AS days_left,
            n.read_at FROM devis d
            LEFT JOIN notification_user n ON n.user_id=:user_id AND n.notification_key=CONCAT('billing:', d.id, ':', d.date_facturation_prevue)
            WHERE d.masque=0 AND d.date_facturation_prevue IS NOT NULL
              AND d.date_facturation_prevue <= DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY)
              AND n.dismissed_at IS NULL ORDER BY d.date_facturation_prevue, d.id DESC LIMIT 30");
        $statement->execute(['user_id' => $userId]);
        return array_map(static function (array $row): array {
            $days = (int) $row['days_left'];
            $row['key'] = 'billing:' . $row['id'] . ':' . $row['date_facturation_prevue'];
            $row['level'] = $days < 0 ? 'danger' : ($days <= 2 ? 'warning' : 'info');
            $row['title'] = $days < 0 ? 'Facturation en retard' : ($days === 0 ? 'Facturation prévue aujourd’hui' : 'Facturation à venir');
            $row['message'] = $row['numero_devis'] . ' · ' . trim(strtok((string) $row['destine_a'], "\n")) . ' · ' . number_format((float) $row['total_ttc'], 0, ',', ' ') . ' FCFA';
            $row['url'] = 'liste_facture.php?focus=' . (int) $row['id'];
            $row['read'] = $row['read_at'] !== null;
            return $row;
        }, $statement->fetchAll());
    }

    public function announcements(int $userId): array
    {
        $statement = $this->database->prepare("SELECT a.*, n.read_at
            FROM app_announcements a
            LEFT JOIN notification_user n ON n.user_id=:user_id
                AND n.notification_key=CONCAT('announcement:', a.id)
            WHERE a.active=1 AND a.starts_at <= NOW()
                AND (a.ends_at IS NULL OR a.ends_at >= NOW())
                AND n.dismissed_at IS NULL
            ORDER BY a.starts_at DESC, a.id DESC LIMIT 30");
        $statement->execute(['user_id' => $userId]);
        return array_map(static function (array $row): array {
            $row['key'] = 'announcement:' . $row['id'];
            $row['level'] = 'info';
            $row['type'] = 'announcement';
            $row['url'] = $row['url'] ?: '#';
            $row['read'] = $row['read_at'] !== null;
            return $row;
        }, $statement->fetchAll());
    }

    public function mark(int $userId, string $key, bool $dismiss): void
    {
        $sql = 'INSERT INTO notification_user(user_id,notification_key,read_at,dismissed_at) VALUES(:user,:key,NOW(),' . ($dismiss ? 'NOW()' : 'NULL') . ')
            ON DUPLICATE KEY UPDATE read_at=NOW()' . ($dismiss ? ', dismissed_at=NOW()' : '');
        $this->database->prepare($sql)->execute(['user' => $userId, 'key' => $key]);
    }
}
