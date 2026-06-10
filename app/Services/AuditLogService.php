<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    
    public static function log(
        string $actionType,
        ?string $description = null,
        ?string $modelType = null,
        ?int $modelId = null,
        ?array $changes = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => Auth::id(),
            'action_type' => $actionType,
            'description' => $description,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'changes' => $changes,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    
    public static function logLogin(): AuditLog
    {
        return self::log(
            'login',
            'User logged in',
            'User',
            Auth::id()
        );
    }

    
    public static function logLogout(?int $userId = null): AuditLog
    {
        return self::log(
            'logout',
            'User logged out',
            'User',
            $userId ?? Auth::id()
        );
    }

    
    public static function logFeedbackSubmitted(int $submissionId, int $feedbackId): AuditLog
    {
        return self::log(
            'feedback_submitted',
            'Feedback submitted for submission',
            'Feedback',
            $feedbackId,
            ['submission_id' => $submissionId]
        );
    }

    
    public static function logRevisionRequested(int $submissionId): AuditLog
    {
        return self::log(
            'revision_requested',
            'Revision requested for submission',
            'Submission',
            $submissionId
        );
    }

    
    public static function logUserCreated(int $userId, array $userData): AuditLog
    {
        return self::log(
            'user_created',
            'New user account created',
            'User',
            $userId,
            $userData
        );
    }

    
    public static function logUserUpdated(int $userId, array $changes): AuditLog
    {
        return self::log(
            'user_updated',
            'User account updated',
            'User',
            $userId,
            $changes
        );
    }

    
    public static function logUserDeleted(int $userId, array $userData): AuditLog
    {
        return self::log(
            'user_deleted',
            'User account deleted',
            'User',
            $userId,
            $userData
        );
    }
}
