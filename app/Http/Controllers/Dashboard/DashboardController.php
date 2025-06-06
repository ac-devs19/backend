<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Staff;
use App\Models\Submit;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function getUserCount()
    {
        $student = Student::count();
        $staff = Staff::whereHas('user', function ($query) {
            $query->where('role', 'cashier');
        })
            ->count();

        return response()->json([
            'student' => $student,
            'staff' => $staff,
        ]);
    }

    public function getRecordCount()
    {
        $students = Student::with('information')->get();

        $complete = 0;
        $incomplete = 0;

        foreach ($students as $student) {
            $requiredDocuments = Document::where('document_type', $student->student_type)
                ->pluck('id')
                ->toArray();

            $submittedDocuments = Submit::where('student_id', $student->id)
                ->where('submit_status', 'confirm')
                ->with('record')
                ->get()
                ->flatMap(function ($submit) {
                    return $submit->record->pluck('document_id');
                })
                ->toArray();

            if (empty(array_diff($requiredDocuments, $submittedDocuments))) {
                $complete++;
            } else {
                $incomplete++;
            }
        }

        return response()->json([
            'complete' => $complete,
            'incomplete' => $incomplete
        ]);
    }

    public function getRequestCount(Request $request)
    {
        $monthlyCounts = \App\Models\Request::select(DB::raw('MONTH(updated_at) as month'), DB::raw('COUNT(*) as count'))
            ->where('request_status', 'complete')
            ->whereYear('updated_at', $request->year)
            ->groupBy(DB::raw('MONTH(updated_at)'))
            ->orderBy(DB::raw('MONTH(updated_at)'))
            ->pluck('count', 'month');

        $monthlyData = array_fill(1, 12, 0);
        foreach ($monthlyCounts as $month => $count) {
            $monthlyData[$month] = $count;
        }

        return response()->json(array_values($monthlyData));
    }

    public function getPaidCount()
    {
        $paid = Payment::count();

        return response()->json($paid);
    }

    public function getSalesCount(Request $request)
    {
        $salesData = \App\Models\Request::whereHas('payment', function ($query) use ($request) {
            $query->whereYear('created_at', $request->year);
        })->with(['request_credential.credential_purpose'])
            ->get()
            ->map(function ($request) {
                $credentialAmount = (float) $request->request_credential->credential_amount;
                $page = (int) $request->request_credential->page;

                $totalAmount = $request->request_credential->credential_purpose->reduce(function ($sum, $purpose) use ($credentialAmount, $page) {
                    return $sum + ($credentialAmount * (int) $purpose->quantity * $page);
                }, 0);

                return [
                    'month' => Carbon::parse($request->created_at)->format('m'),
                    'amount' => $totalAmount,
                ];
            });

        $monthlySales = $salesData->groupBy('month')->map(function ($monthData) {
            return $monthData->sum('amount');
        })->toArray();

        $monthlySales = array_replace(array_fill(1, 12, 0), $monthlySales);

        return response()->json(array_values($monthlySales));
    }
}
