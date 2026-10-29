<?php

namespace App\Http\Controllers;

use App\Models\Submission;

class ResolutionLetterController extends Controller
{
    /**
     * Generate the initial resolution letter addressed to PAIR for a submission,
     * covering the interview workflow and Meta posting billing.
     */
    public function show(Submission $submission)
    {
        $user = auth()->user();

        // Departments may only generate letters for submissions routed to their own department.
        if ($user->isDepartment()) {
            abort_unless(
                $user->department_id !== null
                    && (int) $submission->review_department_id === (int) $user->department_id,
                403
            );
        }

        $submission->loadMissing(['user.department', 'reviewDepartment']);

        return view('Submission.ResolutionLetter', [
            'submission' => $submission,
            'issuedBy' => $user,
            'issuedOn' => now(),
        ]);
    }
}
