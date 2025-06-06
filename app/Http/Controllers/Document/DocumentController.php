<?php

namespace App\Http\Controllers\Document;

use App\Models\Student;
use App\Models\Document;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;

class DocumentController extends Controller
{
    public function getStudentType()
    {
        $studentTypes = Student::select('student_type')
            ->distinct()
            ->get();

        return response()->json($studentTypes);
    }

    public function getDocument()
    {
        $documents = Document::get();

        return response()->json($documents);
    }

    public function createDocument(Request $request)
    {
        $request->validate([
            'document_name' => ['required'],
            'document_type' => ['required'],
        ]);

        foreach ($request->document_type as $document_type) {
            $existingDocument = Document::where('document_name', $request->document_name)
                ->where('document_type', $document_type)
                ->first();

            if ($existingDocument) {
                throw ValidationException::withMessages([
                    'message' => "This document name is already exists for this student type."
                ]);
            } else {
                Document::create([
                    'document_name' => $request->document_name,
                    'document_type' => $document_type,
                ]);
            }
        }
    }

    public function updateDocument(Request $request)
    {
        $request->validate([
            'document_name' => ['required'],
        ]);

        $existingDocument = Document::where('document_name', $request->document_name)
            ->where('document_type', $request->document_type)
            ->where('id', '!=', $request->id)
            ->first();

        if ($existingDocument) {
            throw ValidationException::withMessages([
                'message' => 'This document name is already exists for this student type.'
            ]);
        } else {
            Document::where('id', $request->id)
                ->update([
                    'document_name' => $request->document_name,
                ]);
        }
    }

    public function removeDocument(Request $request)
    {
        $document = Document::where('id', $request->id)
            ->whereHas('record')
            ->first();

        if ($document) {
            throw ValidationException::withMessages([
                'message' => "This document cannot be removed."
            ]);
        } else {
            Document::where('id', $request->id)
                ->delete();
        }
    }
}
