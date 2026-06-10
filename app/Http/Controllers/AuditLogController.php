<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    
    public function index(Request $request)
    {
        $query = AuditLog::with('user')->orderBy('created_at', 'desc');

        
        if ($request->has('action_type') && $request->action_type) {
            $query->where('action_type', $request->action_type);
        }

        
        if ($request->has('from_date') && $request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        
        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        $auditLogs = $query->paginate(50);
        $actionTypes = AuditLog::distinct()->pluck('action_type');
        $users = \App\Models\User::orderBy('name')->get();

        if ($request->wantsJson()) {
            return response()->json($auditLogs);
        }

        return view('AuditLogs.Index', [
            'auditLogs' => $auditLogs,
            'actionTypes' => $actionTypes,
            'users' => $users,
        ]);
    }

    
    public function show(AuditLog $auditLog)
    {
        $auditLog->load('user');

        if (request()->wantsJson()) {
            return response()->json($auditLog);
        }

        return view('AuditLogs.Show', ['auditLog' => $auditLog]);
    }
}
