<?php

namespace App\Http\Controllers\Cashier;

use App\Models\User;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\CredentialNotification;

class CashierController extends Controller
{
    public function getCredentialRequest()
    {
        $credentialRequests = \App\Models\Request::whereDoesntHave('payment')
            ->where('request_status', 'pay')
            ->with('student.information', 'request_credential.credential', 'payment')
            ->get();

        return response()->json($credentialRequests);
    }

    public function getRequestDetail(Request $request)
    {
        $reqDetail = \App\Models\Request::where('request_number', $request->request_number)
            ->with('student.information', 'request_credential.credential', 'request_credential.credential_purpose.purpose', 'payment')
            ->first();

        return response()->json($reqDetail);
    }

    public function requestConfirm(Request $request)
    {
        $request->validate([
            'or_number' => ['required', 'digits:7']
        ]);

        Payment::create([
            'request_id' => $request->id,
            'or_number' => $request->or_number
        ]);

        $admin = User::where('role', 'admin')
            ->first();

        CredentialNotification::create([
            'user_id' => $admin->id,
            'request_id' => $request->id,
            'request_status' => 'paid',
            'notification_status' => 'unread'
        ]);

        CredentialNotification::create([
            'user_id' => $request->user_id,
            'request_id' => $request->id,
            'request_status' => 'paid',
            'notification_status' => 'unread'
        ]);
    }

    public function getPaidReport()
    {
        $credentialPaids = \App\Models\Request::whereHas('payment')
            ->with('student.information', 'request_credential.credential', 'request_credential.credential_purpose', 'payment')
            ->orderByDesc(
                \DB::table('payments')
                    ->select('created_at')
                    ->whereColumn('payments.request_id', 'requests.id')
                    ->orderBy('created_at', 'desc')
                    ->limit(1)
            )
            ->get();

        return response()->json($credentialPaids);
    }

    public function getPayNotif()
    {
        $hasNotif = \App\Models\Request::where('request_status', 'pay')
            ->doesntHave('payment')
            ->exists();

        return response()->json([
            'notif' => $hasNotif ? 'yes' : 'no'
        ]);
    }
}
