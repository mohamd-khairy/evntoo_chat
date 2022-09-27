@extends('layouts.app')

@section('content')


<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">chat</div>

                <div class="card-body" style="height: 750px">
                    <div class="card chat-app">


                        <div id="plist" class="people-list">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fa fa-search"></i></span>
                                </div>
                                <input type="text" class="form-control" placeholder="Search...">
                            </div>
                            <ul class="list-unstyled chat-list mt-2 mb-0">
                                @foreach($users as $user)
                                <a href="{{route('home' , ['user_id' => $user->id])}}">
                                    <li class="clearfix">
                                        <img src="https://bootdey.com/img/Content/avatar/avatar{{$user->id}}.png" alt="avatar">
                                        <div class="about">
                                            <div class="name">{{$user->name}}</div>
                                            <div class="status status_user{{$user->id}}" id="status{{$user->id}}"> <i class="fa fa-circle offline"></i>
                                                {{$user->created_at->diffForHumans()}}
                                            </div>
                                        </div>
                                    </li>
                                </a>
                                @endforeach
                            </ul>
                        </div>
                        <div class="chat">
                            <div class="chat-header clearfix">
                                <div class="row">
                                    @if($selected_chat && $selected_user)
                                    <div class="col-lg-6">
                                        <a href="javascript:void(0);" data-toggle="modal" data-target="#view_info">
                                            <img src="https://bootdey.com/img/Content/avatar/avatar{{$selected_user->id}}.png" alt="avatar">
                                        </a>
                                        <div class="chat-about">
                                            <h6 class="m-b-0">{{$selected_user->name}}</h6>
                                            <small>Last seen: {{$selected_chat->updated_at->diffForHumans()}}</small>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 hidden-sm text-right">
                                        <a href="javascript:void(0);" class="btn btn-outline-secondary"><i class="fa fa-camera"></i></a>
                                        <a href="javascript:void(0);" class="btn btn-outline-primary"><i class="fa fa-image"></i></a>
                                        <a href="javascript:void(0);" class="btn btn-outline-info"><i class="fa fa-cogs"></i></a>
                                        <a href="javascript:void(0);" class="btn btn-outline-warning"><i class="fa fa-question"></i></a>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            <div class="chat-history">
                                <ul class="m-b-0" id="messageWrapper">
                                    @if(!empty($selected_chat->messages))
                                    @foreach($selected_chat->messages->reverse() as $message)
                                    @if($message->sender_id != auth()->user()->id)
                                    <li class="clearfix">
                                        <div class="message-data text-right">
                                            <span class="message-data-time"> {{ date('h:i A, l' , strtotime($message->created_at)) }}</span>
                                            <img src="https://bootdey.com/img/Content/avatar/avatar7.png" alt="avatar">
                                        </div>
                                        <div class="message other-message float-right"> {{$message->message}} </div>
                                    </li>
                                    @else
                                    <li class="clearfix">
                                        <div class="message-data">
                                            <span class="message-data-time">{{ date('h:i A, l' , strtotime($message->created_at)) }}</span>
                                        </div>
                                        <div class="message my-message">{{$message->message}}</div>
                                    </li>
                                    @endif
                                    @endforeach
                                    @endif

                                </ul>
                            </div>
                            <div class="chat-message clearfix">
                                <div class="input-group mb-0">
                                    @if($selected_chat)
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fa fa-send"></i></span>
                                    </div>
                                    <input type="text" id="chat-input" class="form-control" placeholder="Enter text here...">
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


@endsection

@push('scripts')
<script>
    $(function() {

        let user_id = "{{ auth()->user()->id }}";
        let selected_chat_id = "{{ $selected_chat ? $selected_chat->id : null }}";
        let selected_user_id = "{{ $selected_user ? $selected_user->id : null }}";
        let $chatInput = $("#chat-input");
        let $messageWrapper = $("#messageWrapper");
        let $hi = $("#chat-history");


        var socket = io.connect('http://localhost:9090');

        socket.on('connect', function() {
            socket.emit('user_connected', user_id);
            socket.emit('joinGroup', selected_chat_id);
        });

        socket.on('updateUserStatus', (data) => {
            $.each(data, function(key, val) {
                if (val !== null && val !== 0) {
                    let $userStatusIcon = $('.status_user' + key);
                    $userStatusIcon.html('<i class="fa fa-circle online" ></i> online');
                } else {
                    let $userStatusIcon = $('.status_user' + key);
                    $userStatusIcon.html('<i class="fa fa-circle offline" ></i> Last Join at ' + moment().format("hh:mm"));
                }
            });
        });

        socket.on("message", function(message) {
            appendMessageToReceiver(message);
        });

        $chatInput.keypress(function(e) {
            let message = $(this).val();
            if (e.which === 13 && !e.shiftKey) {
                $chatInput.val("");
                sendMessage(message);
                return false;
            }
        });
        

        function sendMessage(message) {
            let url = "{{ route('message.send-message') }}";
            let form = $(this);
            let formData = new FormData();
            let token = "{{ csrf_token() }}";
            formData.append('message', message);
            formData.append('chat_id', selected_chat_id);
            formData.append('sender_id', user_id);
            formData.append('receiver_id', selected_user_id);
            formData.append('_token', token);
            appendMessageToSender(message);
            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'JSON',
                success: function(response) {
                    if (response.success) {
                        // console.log(response.data);
                        socket.emit('message', message, selected_user_id , selected_chat_id);
                    }
                }
            });
        }

        function appendMessageToSender(message) {
            let content = `
                <li class="clearfix">
                    <div class="message-data">
                        <span class="message-data-time">10:12 AM, Today</span>
                    </div>
                    <div class="message my-message">${message}</div>
                </li>
            `;
            $messageWrapper.append(content);
        }

        function appendMessageToReceiver(message) {
            let content = `
                <li class="clearfix">
                    <div class="message-data text-right">
                        <span class="message-data-time">10:10 AM, Today</span>
                        <img src="https://bootdey.com/img/Content/avatar/avatar7.png" alt="avatar">
                    </div>
                    <div class="message other-message float-right">${message}</div>
                </li>
            `;
            $messageWrapper.append(content);
        }

    });
</script>
@endpush