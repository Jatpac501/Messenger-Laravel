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
        $user = Auth::user();
        $messages = Message::where('from_user_id', $user->id)->orWhere('to_user_id', $user->id)->orderBy('created_at', 'asc')->get()->map(function ($message) {
            $message->text = Crypt::decryptString($message->text);
            return $message;
        });
        $chats = $messages->groupBy(function ($message) use ($user) {
            return $message->from_user_id === $user->id ? $message->to_user_id : $message->from_user_id;
        });
        $users = User::whereIn('id', $chats->keys()->all())->get();
        return Inertia::render('Chat/ListChats', [
            'user'=> $user,
            'listChats'=> $chats,
            'users'=> $users,
        ]);
    }
    public function show(Request $request) {
        $recipient = User::where('id', $request->id)->first();
        $users = array(
            'sender' => Auth::user()->name,
            'sender_photo' => Auth::user()->profile_photo_path,
            'recipient' => $recipient->name,
            'recipient_photo' => $recipient->profile_photo_path,
        );
        $sender_id = Auth::user()->id;
        $recipient_id = $recipient->id;

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
            'users_id' => array('sender_id' => $sender_id, 'recipient_id' => $recipient_id),
            'messages' => $messages,
        ]);

    }
    public function store(Request $request) {
        $validatedData = $request->validate([
            'from_user_id' => 'required',
            'to_user_id' => 'required',
            'text' => 'required|string',
        ]);
        $encryptedText = Crypt::encryptString($request->text);
        $message = new Message([
            'from_user_id' => $validatedData['from_user_id'],
            'to_user_id' => $validatedData['to_user_id'],
            'text' => $encryptedText,
        ]);
        $message->save();

        return response()->json(['messageId' => $message->id], 201);
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

