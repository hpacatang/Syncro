<?php

namespace App\Notifications\Concerns;

use App\Models\User;

trait EnrichesNotificationPayload
{
    /**
     * Resolve sender representation dynamically.
     */
    protected function resolveSender(?User $sender = null): array
    {
        $sender ??= auth()->user();

        if (! $sender) {
            return [
                'sender_id'   => null,
                'sender_name' => 'System',
                'sender_role' => 'system',
            ];
        }

        $name = $sender->displayName();
        if ($sender->isDepartment() && $sender->department) {
            $name .= ' (' . $sender->department->displayName() . ')';
        } elseif ($sender->isPair()) {
            $name .= ' (PAIR Office)';
        } elseif ($sender->isSuperAdmin() || $sender->isAdmin()) {
            $name .= ' (PAIR Admin)';
        }

        return [
            'sender_id'   => $sender->id,
            'sender_name' => $name,
            'sender_role' => $sender->role,
        ];
    }

    /**
     * Enrich a base notification payload with sender metadata.
     */
    protected function enrichPayload(array $payload, ?User $sender = null): array
    {
        $senderData = $this->resolveSender($sender);

        return array_merge($payload, [
            'from'        => $senderData['sender_name'],
            'sender_name' => $senderData['sender_name'],
            'sender_id'   => $senderData['sender_id'],
            'sender_role' => $senderData['sender_role'],
        ]);
    }
}
