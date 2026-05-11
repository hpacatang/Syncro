<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    /**
     * Log an action to the audit trail
     *
     * @param string $actionType The type of action (e.g., 'login', 'feedback_submitted', 'user_created')
     * @param string|null $description Human-readable description of what happened
     * @param string|null $modelType The model type that was affected (e.g., 'Submission', 'Feedback', 'User')
     * @param int|null $modelId The ID of the affected model
     * @param array|null $changes Array of changes made (for update operations)
     * @return AuditLog
     */
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

    /**
     * Log a login action
     */
    public static function logLogin(): AuditLog
    {
        return self::log(
            'login',
            'User logged in',
            'User',
            Auth::id()
        );
    }

    /**
     * Log a logout action
     */
    public static function logLogout(?int $userId = null): AuditLog
    {
        return self::log(
            'logout',
            'User logged out',
            'User',
            $userId ?? Auth::id()
        );
    }

    /**
     * Log a feedback submission
     */
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

    /**
     * Log a revision request
     */
    public static function logRevisionRequested(int $submissionId): AuditLog
    {
        return self::log(
            'revision_requested',
            'Revision requested for submission',
            'Submission',
            $submissionId
        );
    }

    /**
     * Log a user creation
     */
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

    /**
     * Log a user update
     */
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

    /**
     * Log a user deletion
     */
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
