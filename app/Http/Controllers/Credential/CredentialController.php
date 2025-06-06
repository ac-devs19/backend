<?php

namespace App\Http\Controllers\Credential;

use App\Models\Link;
use App\Models\Purpose;
use App\Models\Credential;
use App\Models\StudentLink;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;

class CredentialController extends Controller
{
    public function getCredential()
    {
        $credentials = Credential::get();

        return response()->json($credentials);
    }

    public function createCredential(Request $request)
    {
        $request->validate([
            'credential_name' => ['required'],
            'amount' => ['required'],
            'working_day' => ['required'],
        ]);

        $existingCredential = Credential::where('credential_name', $request->credential_name)
            ->first();

        if ($existingCredential) {
            throw ValidationException::withMessages([
                'message' => 'This credential name is already exists.'
            ]);
        } else {
            $credential = Credential::create([
                'credential_name' => $request->credential_name,
                'amount' => $request->amount,
                'on_page' => $request->on_page,
                'working_day' => $request->working_day
            ]);
            if ($request->has('purpose_id')) {
                Link::create([
                    'purpose_id' => $request->purpose_id,
                    'credential_id' => $credential->id
                ]);
            }
        }
    }

    public function updateCredential(Request $request)
    {
        $request->validate([
            'credential_name' => ['required'],
            'amount' => ['required'],
            'working_day' => ['required'],
        ]);

        $existingCredential = Credential::where('credential_name', $request->credential_name)
            ->where('id', '!=', $request->id)
            ->first();

        if ($existingCredential) {
            throw ValidationException::withMessages([
                'message' => 'This credential name already exists.'
            ]);
        } else {
            $credential = Credential::where('id', $request->id)
                ->first();
            $credential->update([
                'credential_name' => $request->credential_name,
                'amount' => $request->amount,
                'on_page' => $request->on_page,
                'working_day' => $request->working_day
            ]);

            $link = Link::where('credential_id', $credential->id)
                ->first();

            if ($request->has('purpose_id')) {
                if (!$link) {
                    Link::create([
                        'purpose_id' => $request->purpose_id,
                        'credential_id' => $credential->id
                    ]);
                } else {
                    $link->update([
                        'purpose_id' => $request->purpose_id
                    ]);
                }
            } elseif (!$link) {

            } else {
                $link->delete();
            }
        }
    }


    public function getPurpose()
    {
        $purposes = Purpose::get();

        return response()->json($purposes);
    }

    public function createPurpose(Request $request)
    {
        $request->validate([
            'purpose_name' => ['required'],
        ]);

        $existingPurpose = Purpose::where('purpose_name', $request->purpose_name)
            ->first();

        if ($existingPurpose) {
            throw ValidationException::withMessages([
                'message' => 'This purpose name is already exists.'
            ]);
        } else {
            Purpose::create([
                'purpose_name' => $request->purpose_name,
            ]);
        }
    }

    public function updatePurpose(Request $request)
    {
        $request->validate([
            'purpose_name' => ['required'],
        ]);

        $existingPurpose = Purpose::where('purpose_name', $request->purpose_name)
            ->where('id', '!=', $request->id)
            ->first();

        if ($existingPurpose) {
            throw ValidationException::withMessages([
                'message' => 'This purpose name is already exists.'
            ]);
        } else {
            Purpose::where('id', $request->id)
                ->update([
                    'purpose_name' => $request->purpose_name,
                ]);
        }
    }

    public function getLink()
    {
        $link = Link::get();

        return response()->json($link);
    }

    public function getStudentLink()
    {
        $studentLink = StudentLink::get();

        return response()->json($studentLink);
    }
}
