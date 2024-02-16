<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\Mail;
use App\Models\ResetPassword;
use Illuminate\Support\Facades\Hash;
use App\Actions\Fortify\PasswordValidationRules;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ResetPasswordController extends Controller
{
    use PasswordValidationRules;
    public function sendMail(Request $request) {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);
        ResetPassword::updateOrCreate(
            ['email' => $request->email],
            ['token'=> Hash::make($this->generateCode())]
        );
        return Inertia::render('Auth/VerifyCode', [
            'email' => $request->email,
        ]);
    }
    public function generateCode() {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = substr(str_shuffle($characters), 0, 6);
        error_log($code);
        return $code;
    }

    public function checkCode(Request $request, $email) {
        $record = ResetPassword::where('email', $email)->first();
        if ($record && Hash::check($request->token, $record->token)) {
            return Inertia::render('Auth/ResetPassword', [
                'email'=> $email,
                'token'=> $record->token
            ]);
        } else {
            return 'error';
        }
    }

    public function updatePassword(Request $request)
{
    $request->validate([
        'email' => 'required|email|exists:users,email',
        'password' => $this->passwordRules(),
        'token' => 'required',
   ]);
    $record = ResetPassword::where('email', $request->email)->first();
    $user = User::where('email', $request->email)->first();

    if ($record && $request->token == $record->token && $user) {
        $user->password = Hash::make($request->password);
        return redirect('/');
    } else {
        return 'error';
    }
}

}
//Y94FV3@jcW9q
