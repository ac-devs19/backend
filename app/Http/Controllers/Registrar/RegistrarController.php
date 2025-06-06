<?php

namespace App\Http\Controllers\Registrar;

use App\Models\CredentialNotification;
use App\Models\DocumentNotification;
use App\Models\User;
use App\Models\Staff;
use App\Models\Submit;
use App\Models\Student;
use App\Models\Document;
use App\Mail\PasswordMail;
use App\Models\Information;
use App\Models\StudentLink;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Exports\StudentExport;
use App\Imports\StudentImport;
use App\Models\RequestCredential;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\ValidationException;

class RegistrarController extends Controller
{
    public function importStudent(Request $request)
    {
        $request->validate([
            'import_file' => ['required', 'file', 'mimes:xlsx,xls'],
        ]);

        Excel::import(new StudentImport, $request->file('import_file'));
    }

    public function exportStudent()
    {
        $file = 'students.xlsx';

        return Excel::download(new StudentExport, $file);
    }

    public function getStudent()
    {
        $students = Student::with('information')
            ->get();

        return response()->json($students);
    }

    public function getStudentInformation(Request $request)
    {
        $student = Student::where('student_number', $request->student_number)
            ->with('information')
            ->first();

        $histories = \App\Models\Request::where('student_id', $student->id)
            ->with('request_credential.credential')
            ->whereIn('request_status', ['complete', 'cancel'])
            ->latest('updated_at')
            ->get();

        return response()->json([
            'student' => $student,
            'histories' => $histories
        ]);
    }

    public function editEmailAddress(Request $request)
    {
        $request->validate([
            'email_address' => ['required', 'email', 'unique:informations'],
        ]);

        $student = Student::where('student_number', $request->student_number)
            ->first();

        Information::where('id', $student->information_id)
            ->update([
                'email_address' => $request->email_address
            ]);

        $password = Str::random(8);

        Mail::to($request->email_address)->send(new PasswordMail($password));

        User::where('id', $student->user_id)
            ->update([
                'password' => bcrypt($password),
                'is_password_changed' => 'no'
            ]);

        User::find($student->user_id)
            ->tokens()
            ->delete();
    }

    public function addStaff(Request $request)
    {
        $request->validate([
            'last_name' => ['required'],
            'first_name' => ['required'],
            'gender' => ['required'],
            'email_address' => ['required', 'email', 'unique:informations'],
            'contact_number' => ['required'],
        ]);

        $password = Str::random(8);

        Mail::to($request->email_address)->send(new PasswordMail($password));

        $user = User::create([
            'password' => bcrypt($password),
            'role' => $request->role,
            'is_password_changed' => 'no'
        ]);

        $info = Information::create([
            'last_name' => $request->last_name,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'gender' => $request->gender,
            'email_address' => $request->email_address,
            'contact_number' => $request->contact_number
        ]);

        Staff::create([
            'user_id' => $user->id,
            'information_id' => $info->id,
            'staff_status' => $request->staff_status
        ]);
    }

    public function getStaff()
    {
        $staffs = User::with('staff.information')
            ->where('role', 'cashier')
            ->get();

        return response()->json($staffs);
    }

    public function getStaffInformation(Request $request)
    {
        $staff = Staff::where('id', $request->id)
            ->with('information')
            ->first();

        return response()->json($staff);
    }

    public function changeStaffStatus(Request $request)
    {
        Staff::where('id', $request->id)
            ->update([
                'staff_status' => $request->staff_status
            ]);

        User::find($request->user_id)
            ->tokens()
            ->delete();
    }

    public function getRecord()
    {
        $students = Student::with('information')->get();

        $complete = [];
        $incomplete = [];

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
                $complete[] = $student;
            } else {
                $incomplete[] = $student;
            }
        }

        return response()->json([
            'complete' => $complete,
            'incomplete' => $incomplete
        ]);
    }

    public function getRequirement(Request $request)
    {
        $student = Student::where('student_number', $request->student_number)
            ->first();

        $documents = Document::where('document_type', $student->student_type)
            ->get();

        $submits = Submit::where('student_id', $student->id)
            ->with('record')
            ->get();

        return response()->json([
            'documents' => $documents,
            'submits' => $submits,
        ]);
    }

    public function getSoftCopy(Request $request)
    {
        $student = Student::where('student_number', $request->student_number)
            ->first();

        $document = Document::where('id', $request->document_id)
            ->first();

        $softCopy = Submit::where('student_id', $student->id)
            ->whereHas('record', function ($query) use ($request) {
                $query->where('document_id', $request->document_id);
            })
            ->with('record', 'pdf_record')
            ->first();

        return response()->json([
            'document' => $document,
            'soft_copy' => $softCopy
        ]);
    }

    public function confirmSubmit(Request $request)
    {
        Submit::where('id', $request->submit_id)
            ->update([
                'submit_status' => 'confirm'
            ]);

        $student = Student::where('student_number', $request->student_number)
            ->first();

        DocumentNotification::create([
            'user_id' => $student->user_id,
            'submit_id' => $request->submit_id,
            'submit_status' => 'confirm',
            'notification_status' => 'unread'
        ]);
    }

    public function declineSubmit(Request $request)
    {
        Submit::where('id', $request->submit_id)
            ->update([
                'message' => $request->others === null ? $request->message : $request->others,
                'submit_status' => 'resubmit'
            ]);

        $student = Student::where('student_number', $request->student_number)
            ->first();

        DocumentNotification::create([
            'user_id' => $student->user_id,
            'submit_id' => $request->submit_id,
            'submit_status' => 'decline',
            'notification_status' => 'unread'
        ]);
    }

    public function getCredentialRequest()
    {
        $credentialRequests = \App\Models\Request::whereNot('request_status', 'complete')
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

    public function editPage(Request $request)
    {
        RequestCredential::where('id', $request->id)
            ->update([
                'page' => $request->page
            ]);
    }

    public function requestConfirm(Request $request)
    {
        $reqCred = RequestCredential::where('request_id', $request->id)
            ->whereHas('credential')
            ->first();

        if ($reqCred->credential->on_page === 'yes' && $reqCred->page === '1') {
            throw ValidationException::withMessages([
                'message' => 'Please edit the pages.',
            ]);
        }

        \App\Models\Request::where('id', $request->id)
            ->update([
                'request_status' => 'pay'
            ]);

        CredentialNotification::create([
            'user_id' => $request->user_id,
            'request_id' => $request->id,
            'request_status' => 'confirm',
            'notification_status' => 'unread'
        ]);

        $cashier = User::where('role', 'cashier')
            ->first();

        CredentialNotification::create([
            'user_id' => $cashier->id,
            'request_id' => $request->id,
            'request_status' => 'pay',
            'notification_status' => 'unread'
        ]);
    }

    public function requestDecline(Request $request)
    {
        $request->validate([
            'message' => ['required']
        ]);

        \App\Models\Request::where('id', $request->id)
            ->update([
                'request_status' => 'cancel',
                'message' => $request->others === null ? $request->message : $request->others
            ]);

        if ($request->has('credential_id')) {
            StudentLink::where('student_id', $request->student_id)
                ->where('credential_id', $request->credential_id)
                ->delete();
        }

        CredentialNotification::create([
            'user_id' => $request->user_id,
            'request_id' => $request->id,
            'request_status' => 'decline',
            'notification_status' => 'unread'
        ]);
    }

    public function requestProcess(Request $request)
    {
        \App\Models\Request::where('id', $request->id)
            ->update([
                'request_status' => 'process'
            ]);

        CredentialNotification::create([
            'user_id' => $request->user_id,
            'request_id' => $request->id,
            'request_status' => 'process',
            'notification_status' => 'unread'
        ]);
    }

    public function requestFinish(Request $request)
    {
        \App\Models\Request::where('id', $request->id)
            ->update([
                'request_status' => 'receive'
            ]);

        CredentialNotification::create([
            'user_id' => $request->user_id,
            'request_id' => $request->id,
            'request_status' => 'receive',
            'notification_status' => 'unread'
        ]);
    }

    public function requestRelease(Request $request)
    {
        \App\Models\Request::where('id', $request->id)
            ->update([
                'request_status' => 'complete'
            ]);

        RequestCredential::where('request_id', $request->id)
            ->update([
                'request_credential_status' => 'release'
            ]);

        CredentialNotification::create([
            'user_id' => $request->user_id,
            'request_id' => $request->id,
            'request_status' => 'complete',
            'notification_status' => 'unread'
        ]);
    }

    public function getCompleteReport()
    {
        $credentialCompletes = \App\Models\Request::where('request_status', 'complete')
            ->with('student.information', 'request_credential.credential', 'request_credential.credential_purpose.purpose', 'payment')
            ->latest('updated_at')
            ->get();

        return response()->json($credentialCompletes);
    }

    public function cancelRequest(Request $request)
    {
        $request->validate([
            'password' => ['required'],
        ]);

        $user_id = $request->user()->id;

        $user = User::find($user_id);

        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'message' => 'The provided password are incorrect.',
            ]);
        }

        \App\Models\Request::where('id', $request->id)
            ->update([
                'request_status' => 'cancel',
                'message' => 'The student requested to the admin to cancel the request.'
            ]);

        RequestCredential::where('request_id', $request->id)
            ->update([
                'page' => '1'
            ]);

        if ($request->has('credential_id')) {
            StudentLink::where('student_id', $request->student_id)
                ->where('credential_id', $request->credential_id)
                ->delete();
        }

        CredentialNotification::create([
            'user_id' => $request->user_id,
            'request_id' => $request->id,
            'request_status' => 'cancel',
            'notification_status' => 'unread'
        ]);
    }

    public function getRequestNotif()
    {
        $hasNotif = \App\Models\Request::whereNotIn('request_status', ['complete', 'cancel'])
            ->exists();

        return response()->json([
            'notif' => $hasNotif ? 'yes' : 'no'
        ]);
    }

    public function getDocumentNotif(Request $request)
    {
        $user = $request->user();

        $notif = DocumentNotification::where('user_id', $user->id)
            ->with('submit.student.information', 'submit.record.document')
            ->latest()
            ->get();

        return response()->json($notif);
    }

    public function readDocumentNotif(Request $request)
    {
        DocumentNotification::where('id', $request->id)
            ->update([
                'notification_status' => 'read'
            ]);
    }

    public function getCredentialNotif(Request $request)
    {
        $user = $request->user();

        $notif = CredentialNotification::where('user_id', $user->id)
            ->with('request.student.information', 'request.request_credential.credential')
            ->latest()
            ->get();

        return response()->json($notif);
    }

    public function readCredentialNotif(Request $request)
    {
        CredentialNotification::where('id', $request->id)
            ->update([
                'notification_status' => 'read'
            ]);
    }

}
