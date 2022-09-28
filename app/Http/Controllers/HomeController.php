<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use FCM;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('auth');
    }

    public function chat(Request $request)
    {
        $users = User::where('id', '!=', auth()->user()->id)->get();
        $chats = Chat::with('sender_user', 'receiver_user', 'messages')
            ->whereIn('receiver_id', $users->pluck('id'))
            ->orWhereIn('sender_id', $users->pluck('id'))
            ->orderBy('updated_at', 'desc')->get();

        if (request('user_id')) {
            $selected_chat = Chat::with('sender_user', 'receiver_user', 'messages')
                ->where(function ($q) {
                    $q->where('receiver_id', request('user_id'))
                        ->where('sender_id', auth()->user()->id);
                })->orWhere(function ($q) {
                    $q->where('receiver_id', auth()->user()->id)
                        ->where('sender_id', request('user_id'));
                })
                ->orderBy('updated_at', 'desc')->first();


            $selected_chat = $selected_chat ? $selected_chat : Chat::create([
                'sender_id' => auth()->user()->id,
                'receiver_id' => request('user_id')
            ]);
        } else {
            $selected_chat = $chats ? $chats->first() : null;
        }
        $selected_user = $selected_chat ? ($selected_chat->sender_user->id == auth()->user()->id ? $selected_chat->receiver_user : $selected_chat->sender_user) : null;

        return view('chat', compact('users', 'chats', 'selected_chat', 'selected_user'));
    }

    public function save_token(Request $request)
    {
        $user = User::find($request->id);;
        $user->fcm_token = $request->token ?? null;
        $user->save();
        return response()->json($request->all());
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $users = User::where('id', '!=', auth()->user()->id)->get();
        $chats = Chat::with('sender_user', 'receiver_user', 'messages')
            ->whereIn('receiver_id', $users->pluck('id'))
            ->orWhereIn('sender_id', $users->pluck('id'))
            ->orderBy('updated_at', 'desc')->get();

        if (request('user_id')) {
            $selected_chat = Chat::with('sender_user', 'receiver_user', 'messages')
                ->where(function ($q) {
                    $q->where('receiver_id', request('user_id'))
                        ->where('sender_id', auth()->user()->id);
                })->orWhere(function ($q) {
                    $q->where('receiver_id', auth()->user()->id)
                        ->where('sender_id', request('user_id'));
                })
                ->orderBy('updated_at', 'desc')->first();


            $selected_chat = $selected_chat ? $selected_chat : Chat::create([
                'sender_id' => auth()->user()->id,
                'receiver_id' => request('user_id')
            ]);
        } else {
            $selected_chat = $chats ? $chats->first() : null;
        }
        $selected_user = $selected_chat ? ($selected_chat->sender_user->id == auth()->user()->id ? $selected_chat->receiver_user : $selected_chat->sender_user) : null;

        return view('home', compact('users', 'chats', 'selected_chat', 'selected_user'));
    }

    public function sendMessage(Request $request)
    {
        // dd($request->all());
        $this->fcm(request('receiver_id'), auth()->user()->name, request('message', 'test'));
        $data = ['message' => request('message'), 'sender_id' => request('sender_id'), 'receiver_id' => request('receiver_id'), 'chat_id' => request('chat_id')];
        $data = Message::create($data);
        return response()->json(['success' => true, 'data' => $data]);
    }


    public function fcm($user_id, $senderName = 'mohamed', $msg = 'hello world')
    {
        $optionBuilder = new OptionsBuilder();
        $optionBuilder->setTimeToLive(60 * 20);

        $notificationBuilder = new PayloadNotificationBuilder('new message from: ' . $senderName);
        $notificationBuilder->setBody($msg)
            ->setSound('default')
            ->setClickAction('https://chat.evntoo.website');

        $dataBuilder = new PayloadDataBuilder();
        $dataBuilder->addData(['user_id' => $user_id, 'senderName' => $senderName, 'message' => $msg]);

        $option = $optionBuilder->build();
        $notification = $notificationBuilder->build();
        $data = $dataBuilder->build();

        $tokens = User::all()->pluck('fcm_token')->toArray();

        $downstreamResponse = FCM::sendTo($tokens, $option, $notification, $data);
        return $downstreamResponse->numberSuccess();
    }
}
