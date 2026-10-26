<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Notifications\DatabaseNotification;

class Notification extends DatabaseNotification
{
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }
}
