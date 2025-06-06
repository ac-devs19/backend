<?php

namespace App\Http\Controllers\Auth;

use App\Models\Staff;
use App\Models\User;
use App\Mail\OtpMail;
use App\Models\Student;
use App\Models\Information;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\EmailVerification;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        if ($request->has('staff_email_address')) {
            $request->validate([
                'staff_email_address' => ['required', 'email'],
                'password' => ['required'],
            ]);

            $user = User::whereHas('staff.information', function ($query) use ($request) {
                $query->where('email_address', $request->staff_email_address);
            })
                ->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'message' => 'The provided credentials are incorrect.',
                ]);
            }

            $staff = $user->staff->where('user_id', $user->id)
                ->where('staff_status', 'inactive')
                ->first();

            if ($staff) {
                throw ValidationException::withMessages([
                    'message' => 'Your account is inactive. Please contact the admin.',
                ]);
            }

            $token = $user->createToken('token')->plainTextToken;

            return response()->json([
                'token' => $token
            ]);
        } elseif ($request->has('student_email_address')) {
            $request->validate([
                'student_email_address' => ['required', 'email'],
                'password' => ['required'],
            ]);

            $user = User::whereHas('student.information', function ($query) use ($request) {
                $query->where('email_address', $request->student_email_address);
            })
                ->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'message' => 'The provided credentials are incorrect.',
                ]);
            }

            $token = $user->createToken('token')->plainTextToken;

            return response()->json([
                'token' => $token
            ]);
        } elseif ($request->has('student_number')) {
            $request->validate([
                'student_number' => ['required'],
                'password' => ['required'],
            ]);

            $user = User::whereHas('student', function ($query) use ($request) {
                $query->where('student_number', $request->student_number);
            })
                ->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'message' => 'The provided credentials are incorrect.',
                ]);
            }

            $token = $user->createToken('token')->plainTextToken;

            return response()->json([
                'token' => $token
            ]);
        }
    }

    public function changePassword(Request $request)
    {
        $user_id = $request->user()->id;

        $user = User::find($user_id);

        if ($user->is_password_changed === 'no') {
            $request->validate([
                'password' => ['required', 'confirmed', Password::defaults()],
            ]);

            $user->update([
                'password' => Hash::make($request->password),
                'is_password_changed' => 'yes'
            ]);
        } else {
            $request->validate([
                'current_password' => ['required'],
            ]);

            if (!Hash::check($request->current_password, $user->password)) {
                throw ValidationException::withMessages([
                    'message' => 'The current password is incorrect.',
                ]);
            }

            $request->validate([
                'password' => ['required', 'confirmed', Password::defaults()],
            ]);

            $user->update([
                'password' => Hash::make($request->password),
            ]);
        }
    }

    public function forgotPassword(Request $request)
    {
        if ($request->has('staff_email_address')) {
            $request->validate([
                'staff_email_address' => ['required', 'email'],
            ]);

            $staff = Staff::whereHas('information', function ($query) use ($request) {
                $query->where('email_address', $request->staff_email_address);
            })
                ->first();

            if (!$staff) {
                throw ValidationException::withMessages([
                    'message' => 'The email address is invalid.',
                ]);
            }

            $user = User::where('id', $staff->user_id)
                ->where('is_password_changed', 'no')
                ->first();

            if ($user) {
                throw ValidationException::withMessages([
                    'message' => 'Your email address is not allowed to forgot password at this time.',
                ]);
            }

            $otp = rand(100000, 999999);

            $this->emailVerification($staff->information, $otp);

            Mail::to($request->staff_email_address)->send(new OtpMail($otp));
        } elseif ($request->has('student_email_address')) {
            $request->validate([
                'student_email_address' => ['required', 'email'],
            ]);

            $student = Student::whereHas('information', function ($query) use ($request) {
                $query->where('email_address', $request->student_email_address);
            })
                ->first();

            if (!$student) {
                throw ValidationException::withMessages([
                    'message' => 'The email address is invalid.',
                ]);
            }

            $user = User::where('id', $student->user_id)
                ->where('is_password_changed', 'no')
                ->first();

            if ($user) {
                throw ValidationException::withMessages([
                    'message' => 'Your email address is not allowed to forgot password at this time.',
                ]);
            }

            $otp = rand(100000, 999999);

            $this->emailVerification($student->information, $otp);

            Mail::to($request->student_email_address)->send(new OtpMail($otp));
        }
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => ['required'],
        ]);

        $verification = EmailVerification::whereHas('information', function ($query) use ($request) {
            $query->where('email_address', $request->email_address);
        })
            ->orderBy('created_at', 'desc')
            ->first();

        if ($verification->otp !== $request->otp) {
            throw ValidationException::withMessages([
                'message' => 'OTP is invalid.',
            ]);
        }

        if (Carbon::now()->isAfter($verification->expired_at)) {
            throw ValidationException::withMessages([
                'message' => 'OTP expired.',
            ]);
        }

        EmailVerification::where('information_id', $verification->information->id)
            ->delete();
    }

    public function resendOtp(Request $request)
    {
        $info = Information::where('email_address', $request->email_address)
            ->first();

        $otp = rand(100000, 999999);

        $this->emailVerification($info, $otp);

        Mail::to($request->email_address)->send(new OtpMail($otp));
    }

    public function createNewPassword(Request $request)
    {
        if ($request->has('staff_email_address')) {
            $request->validate([
                'password' => ['required', 'confirmed', Password::defaults()],
            ]);

            $staff = Staff::whereHas('information', callback: function ($query) use ($request) {
                $query->where('email_address', $request->staff_email_address);
            })
                ->first();

            User::where('id', $staff->user_id)
                ->update([
                    'password' => bcrypt($request->password),
                ]);
        } elseif ($request->has('student_email_address')) {
            $request->validate([
                'password' => ['required', 'confirmed', Password::defaults()],
            ]);

            $student = Student::whereHas('information', function ($query) use ($request) {
                $query->where('email_address', $request->student_email_address);
            })
                ->first();

            User::where('id', $student->user_id)
                ->update([
                    'password' => bcrypt($request->password),
                ]);
        }
    }

    public function emailVerification($info, $otp)
    {
        EmailVerification::create([
            'information_id' => $info->id,
            'otp' => $otp,
            'expired_at' => now()->addMinutes(3),
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        $user->currentAccessToken()->delete();

        return response()->noContent();
    }
}
