<?php

namespace App\Http\Controllers\Student;

use App\Models\Link;
use App\Models\User;
use App\Models\Record;
use App\Models\Submit;
use App\Models\Student;
use App\Models\Document;
use App\Models\PdfRecord;
use App\Models\StudentLink;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\CredentialPurpose;
use App\Models\RequestCredential;
use App\Http\Controllers\Controller;
use App\Models\DocumentNotification;
use Illuminate\Support\Facades\File;
use App\Models\CredentialNotification;

class StudentController extends Controller
{
    public function getRequirement(Request $request)
    {
        $user = $request->user();

        $student = Student::where('user_id', $user->id)
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

    public function submitRequirement(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png']
        ]);

        $student = Student::where('user_id', $user->id)->first();

        $submit = Submit::create([
            'student_id' => $student->id,
            'submit_status' => 'review',
        ]);

        $imagePaths = [];

        foreach ($request->file('images') as $image) {
            $filename = uniqid() . '_' . date('Y-m-d') . '.' . $image->getClientOriginalExtension();
            $imagePath = "uploads/students/documents/images/";
            $image->move(public_path($imagePath), $filename);

            $fullPath = $imagePath . $filename;
            $imagePaths[] = $fullPath;

            Record::create([
                'submit_id' => $submit->id,
                'document_id' => $request->document_id,
                'uri' => $fullPath,
            ]);
        }

        $pdfDirectory = public_path("uploads/students/documents/pdf/");
        if (!File::exists($pdfDirectory)) {
            File::makeDirectory($pdfDirectory, 0755, true);
        }

        $pdfHtml = '<div style="text-align:center; margin:20px;">';
        foreach ($imagePaths as $path) {
            $pdfHtml .= "<div style='page-break-inside:avoid; margin-bottom:20px;'>";
            $pdfHtml .= "<img src='" . public_path($path) . "' style='max-width:100%; max-height:100%; object-fit:contain; display:block; margin:auto;'/>";
            $pdfHtml .= "</div>";
        }
        $pdfHtml .= '</div>';


        $pdf = Pdf::loadHTML($pdfHtml);
        $pdfPath = "uploads/students/documents/pdf/" . uniqid() . '_' . date('Y-m-d') . ".pdf";
        $pdf->save($pdfDirectory . basename($pdfPath));

        PdfRecord::create([
            'submit_id' => $submit->id,
            'document_id' => $request->document_id,
            'pdf' => $pdfPath,
        ]);

        $admin = User::where('role', 'admin')->first();

        DocumentNotification::create([
            'user_id' => $admin->id,
            'submit_id' => $submit->id,
            'submit_status' => 'submit',
            'notification_status' => 'unread'
        ]);
    }

    public function resubmitRequirement(Request $request)
    {
        $request->validate([
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:10240']
        ]);

        $records = Record::where('submit_id', $request->submit_id)
            ->get();

        foreach ($records as $record) {
            $imagePath = public_path($record->uri);
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        Record::where('submit_id', $request->submit_id)
            ->delete();

        $pdf = PdfRecord::where('submit_id', $request->submit_id)
            ->first();

        $pdfPath = public_path($pdf->pdf);
        if (file_exists($pdfPath)) {
            unlink($pdfPath);
        }

        PdfRecord::where('submit_id', $request->submit_id)
            ->delete();

        Submit::where('id', $request->submit_id)
            ->update([
                'submit_status' => 'review',
                'message' => null
            ]);

        $imagePaths = [];

        foreach ($request->file('images') as $image) {
            $filename = uniqid() . '_' . date('Y-m-d') . '.' . $image->getClientOriginalExtension();
            $imagePath = "uploads/students/documents/images/";
            $image->move(public_path($imagePath), $filename);

            $fullPath = $imagePath . $filename;
            $imagePaths[] = $fullPath;

            Record::create([
                'submit_id' => $request->submit_id,
                'document_id' => $request->document_id,
                'uri' => $fullPath,
            ]);
        }

        $pdfDirectory = public_path("uploads/students/documents/pdf/");
        if (!File::exists($pdfDirectory)) {
            File::makeDirectory($pdfDirectory, 0755, true);
        }

        $pdfHtml = '<div style="text-align:center; margin:20px;">';
        foreach ($imagePaths as $index => $path) {
            $pdfHtml .= "<img src='" . public_path($path) . "' style='max-width:100%; max-height:100%; object-fit:contain;'/>";
            if (($index + 1) % 20 === 0) {
                $pdfHtml .= '<div style="page-break-after:always;"/>';
            }
        }
        $pdfHtml .= '</div>';

        $pdf = Pdf::loadHTML($pdfHtml);
        $pdfPath = "uploads/students/documents/pdf/" . uniqid() . '_' . date('Y-m-d') . ".pdf";
        $pdf->save($pdfDirectory . basename($pdfPath));

        PdfRecord::create([
            'submit_id' => $request->submit_id,
            'document_id' => $request->document_id,
            'pdf' => $pdfPath,
        ]);

        $admin = User::where('role', 'admin')
            ->first();

        DocumentNotification::create([
            'user_id' => $admin->id,
            'submit_id' => $request->submit_id,
            'submit_status' => 'resubmit',
            'notification_status' => 'unread'
        ]);
    }

    public function getSoftCopy(Request $request)
    {
        $user = $request->user();

        $student = Student::where('user_id', $user->id)
            ->first();

        $softCopies = Submit::where('student_id', $student->id)
            ->whereHas('record', function ($query) use ($request) {
                $query->where('document_id', $request->document_id);
            })
            ->with('record')
            ->first();

        return response()->json($softCopies);
    }

    public function getRecordStatus(Request $request)
    {
        $user = $request->user();

        $student = Student::where('user_id', $user->id)
            ->first();

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

        $status = null;

        empty(array_diff($requiredDocuments, $submittedDocuments)) ? $status = 'complete' : $status = 'incomplete';

        return response()->json($status);
    }

    public function requestCredential(Request $request)
    {
        $user = $request->user();

        $student = Student::where('user_id', $user->id)
            ->first();

        foreach ($request->checkOutData as $data) {
            $studentRequest = \App\Models\Request::create([
                'student_id' => $student->id,
                'request_number' => rand(1000000000, 9999999999),
                'request_status' => 'review'
            ]);

            $requestCredential = RequestCredential::create([
                'credential_id' => $data['credentialId'],
                'request_id' => $studentRequest->id,
                'credential_amount' => $data['credentialAmount']
            ]);

            foreach ($data['selectedPurposes'] as $purposeId) {
                $quantity = $data['quantities'][$purposeId] ?? 1;
                CredentialPurpose::create([
                    'purpose_id' => $purposeId,
                    'request_credential_id' => $requestCredential->id,
                    'quantity' => $quantity
                ]);
            }

            $link = Link::where('credential_id', $data['credentialId'])
                ->first();

            if ($link) {
                StudentLink::create([
                    'credential_id' => $link->credential_id,
                    'student_id' => $student->id
                ]);
            }

            $admin = User::where('role', 'admin')
                ->first();

            CredentialNotification::create([
                'user_id' => $admin->id,
                'request_id' => $studentRequest->id,
                'request_status' => 'request',
                'notification_status' => 'unread'
            ]);
        }
    }

    public function requestAgainCredential(Request $request)
    {
        $user = $request->user();

        $student = Student::where('user_id', $user->id)
            ->first();

        $req = \App\Models\Request::create([
            'student_id' => $student->id,
            'request_number' => rand(1000000000, 9999999999),
            'request_status' => 'review'
        ]);

        $reqCred = RequestCredential::create([
            'credential_id' => $request->credential_id,
            'request_id' => $req->id,
            'credential_amount' => $request->amount,
            'page' => $request->page
        ]);

        foreach ($request->credPurpose as $credPurpose) {
            CredentialPurpose::create([
                'purpose_id' => $credPurpose['purpose_id'],
                'request_credential_id' => $reqCred->id,
                'quantity' => $credPurpose['quantity']
            ]);
        }

        $link = Link::where('credential_id', $request->credential_id)
            ->first();

        if ($link) {
            StudentLink::create([
                'credential_id' => $link->credential_id,
                'student_id' => $student->id
            ]);
        }

        // \App\Models\Request::where('id', $request->req_id)
        //     ->delete();

        $admin = User::where('role', 'admin')
            ->first();

        CredentialNotification::create([
            'user_id' => $admin->id,
            'request_id' => $req->id,
            'request_status' => 'request',
            'notification_status' => 'unread'
        ]);
    }

    public function getRequestCount(Request $request)
    {
        $user = $request->user();

        $student = Student::where('user_id', $user->id)
            ->first();

        $requestCounts = [];
        foreach ($request->status as $status) {
            $count = \App\Models\Request::where('student_id', $student->id)
                ->where('request_status', $status)
                ->count();
            $requestCounts[$status] = $count;
        }

        return response()->json($requestCounts);
    }

    public function getRequestStatus(Request $request)
    {
        $user = $request->user();

        $student = Student::where('user_id', $user->id)
            ->first();

        $reqStatus = \App\Models\Request::where('student_id', $student->id)
            ->whereIn('request_status', $request->status === "history" ? ["complete", "cancel"] : [$request->status])
            ->with('request_credential.credential', 'payment')
            ->latest('updated_at')
            ->get();

        return response()->json($reqStatus);
    }

    public function getRequestDetail(Request $request)
    {
        $user = $request->user();

        $student = Student::where('user_id', $user->id)
            ->first();

        $reqDetail = \App\Models\Request::where('student_id', $student->id)
            ->where('request_number', $request->req_number)
            ->with('request_credential.credential', 'request_credential.credential_purpose.purpose', 'payment')
            ->first();

        return response()->json($reqDetail);
    }

    public function cancelRequest(Request $request)
    {
        $user = $request->user();

        $student = Student::where('user_id', $user->id)
            ->first();

        \App\Models\Request::where('request_number', $request->req_number)
            ->update([
                'request_status' => 'cancel',
                'message' => $request->others === null ? $request->message : $request->others
            ]);

        if ($request->has('credential_id')) {
            StudentLink::where('student_id', $student->id)
                ->where('credential_id', $request->credential_id)
                ->delete();
        }
    }

    public function requestClaim(Request $request)
    {
        RequestCredential::where('request_id', $request->id)
            ->update([
                'request_credential_status' => 'claim'
            ]);

        $admin = User::where('role', 'admin')
            ->first();

        CredentialNotification::create([
            'user_id' => $admin->id,
            'request_id' => $request->id,
            'request_status' => 'claim',
            'notification_status' => 'unread'
        ]);
    }

    public function getPaymentStatus(Request $request)
    {
        $user = $request->user();

        $student = Student::where('user_id', $user->id)
            ->first();

        $notExist = \App\Models\Request::whereDoesntHave('payment')
            ->where('request_status', 'pay')
            ->where('student_id', $student->id)
            ->exists();

        $status = $notExist ? 'no' : 'yes';

        return response()->json($status);
    }

    public function getDocumentNotif(Request $request)
    {
        $user = $request->user();

        $notif = DocumentNotification::where('user_id', $user->id)
            ->with('submit.record.document')
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
            ->with('request.request_credential.credential')
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

    public function notifCount(Request $request)
    {
        $user = $request->user();

        $docNotif = DocumentNotification::where('user_id', $user->id)
            ->where('notification_status', 'unread')
            ->count();

        $credNotif = CredentialNotification::where('user_id', $user->id)
            ->where('notification_status', 'unread')
            ->count();

        return response()->json($docNotif + $credNotif);
    }
}
