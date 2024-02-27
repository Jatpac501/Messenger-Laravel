<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class ChatController extends Controller
{
    public function index() {
        $users = array(
            'sender' => Auth::user()->name,
            'sender_photo' => Auth::user()->profile_photo_path,
            'recipient' => Auth::user()->name,
            'recipient_photo' => Auth::user()->profile_photo_path,
        );
        $sender_id = Auth::user()->id;
        $recipient_id = Auth::user()->id;

        $messages = Message::where(function ($query) use ($sender_id, $recipient_id) {
            $query->where('from_user_id', $sender_id)
                  ->where('to_user_id', $recipient_id);
        })->orWhere(function ($query) use ($sender_id, $recipient_id) {
            $query->where('from_user_id', $recipient_id)
                  ->where('to_user_id', $sender_id);
        })
        ->orderBy('created_at', 'asc')->get()->map(function ($message) {
                $message->text = Crypt::decryptString($message->text);
                return $message;
            });
        return Inertia::render('Chat/Chat', [
            'users' => $users,
            'users_id' => array('sender_id' => $sender_id,
                                'recipient_id' => $recipient_id,
                            ),
            'messages' => $messages,
        ]);

    }
    public function store(Request $request) {

        $message = Message::create([
            'from_user_id' => $request->from_user_id,
            'to_user_id' => $request->to_user_id,
            'text' => Crypt::encryptString($request->text),
            'read_at' => null,
        ]);

        return response()->json($message, 201);
    }

    public function markAsRead(Request $request) {
        $messageId = $request->id;
        $message = Message::find($messageId);
        if ($message && $message->to_user_id === $request->to_user_id && is_null($message->read_at)) {
            $message->read_at = now();
            $message->save();
        }

        return ;
    }
}

