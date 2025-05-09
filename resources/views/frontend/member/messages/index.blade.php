@extends('frontend.layouts.member_panel')
@section('panel_content')
    <div class="aiz-chat">
        <div class="row no-gutters">
            <div class="col-lg-4">
                <div class="chat-user-list-wrap z-1035">
                    <div class="overlay dark c-pointer" data-toggle="class-toggle" data-target=".chat-user-list-wrap"
                        data-same=".aiz-all-chat-toggler"></div>
                    <div
                        class="chat-user-list-header d-flex justify-content-between align-items-center bg-white border-bottom border-right h6 mb-0">
                        <span class="p-2 m-1">{{ translate('Members') }}</span>
                        <button class="btn btn-icon d-lg-none" data-toggle="class-toggle"
                            data-target=".chat-user-list-wrap"><i class="fa-solid fa-times"></i></button>
                    </div>
                    <div class="chat-user-list border-right py-3 c-scrollbar-light">
                        @forelse ($chat_threads as $key => $single_chat_thread)
                            @php
                                $num_of_message = $single_chat_thread->chats->where('seen', 0)->count();
                                $current_user = Auth::user()->id;
                            @endphp
                            @if ($single_chat_thread->receiver != null && $single_chat_thread->sender != null)
                                <a href="javascript:void(0)" class="chat-user-item p-3 d-block text-inherit"
                                    data-url="{{ route('chat_view', $single_chat_thread->id) }}"
                                    data-refresh="{{ route('chat_refresh', $single_chat_thread->id) }}"
                                    onclick="loadChats(this)">
                                    @if ($current_user == $single_chat_thread->sender->id)
                                        @php $user_to_show = 'receiver';  @endphp
                                    @else
                                        @php $user_to_show = 'sender';  @endphp
                                    @endif
                                    <div class="media">
                                        <span class="avatar avatar-sm mr-3 flex-shrink-0">
                                            @if ($single_chat_thread->$user_to_show->photo != null)
                                                <img src="{{ uploaded_asset($single_chat_thread->$user_to_show->photo) }}">
                                            @else
                                                <img
                                                    src="{{ static_asset('assets/frontend/default/img/avatar-place.png') }}">
                                            @endif

                                            @if (Cache::has('user-is-online-' . $single_chat_thread->$user_to_show->id))
                                                <span
                                                    class="badge badge-dot badge-circle badge-success badge-status badge-md"></span>
                                            @else
                                                <span
                                                    class="badge badge-dot badge-circle badge-secondary badge-status badge-md"></span>
                                            @endif
                                        </span>
                                        <div class="media-body minw-0">
                                            <h6 class="mt-0 mb-1 fs-14 text-truncate">
                                                {{ $single_chat_thread->$user_to_show->first_name . ' ' . $single_chat_thread->$user_to_show->last_name }}
                                            </h6>
                                            @if ($single_chat_thread->chats->last() != null)
                                                @if ($single_chat_thread->chats->last()->message != null)
                                                    <div class="fs-12 text-truncate opacity-60">
                                                        {{ $single_chat_thread->chats->last()->message }}</div>
                                                @else
                                                    <div class="fs-12 text-truncate opacity-60">
                                                        {{ translate('Attachments') }}</div>
                                                @endif
                                            @endif
                                        </div>
                                        <div class="ml-2 text-right">
                                            @if ($single_chat_thread->chats->last() != null)
                                                <div class="opacity-60 fs-10 mb-1">
                                                    {{ Carbon\Carbon::parse($single_chat_thread->chats->last()->created_at)->diffForHumans() }}
                                                </div>
                                            @endif
                                            <span
                                                class="badge badge-primary badge-circle flex-shrink-0 ml-4">{{ count($single_chat_thread->chats->where('sender_user_id', '!=', Auth::user()->id)->where('seen', 0)) }}</span>
                                        </div>
                                    </div>
                                </a>
                            @endif
                        @empty
                            <div class=" text-center">
                                <i class="fa-solid fa-frown la-4x mb-4 opacity-40"></i>
                                <h4>{{ translate('Nothing Found') }}</h4>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-lg-8" id="single_chat">
                <div class="chat-box-wrap h-100">
                    <div
                        class="attached-top bg-white border-bottom chat-header d-flex justify-content-between align-items-center p-3 shadow-sm">
                        <div class="media">
                            <h6 class="mb-0">{{ translate('Chats') }}</h6>
                        </div>
                        <button class="aiz-mobile-toggler d-lg-none aiz-all-chat-toggler mr-2" data-toggle="class-toggle"
                            data-target=".chat-user-list-wrap">
                            <span></span>
                        </button>
                    </div>
                    <div class="px-3 py-5 text-center">
                        <i class="fa-solid fa-user la-6x text-primary mb-4"></i>
                        <h5>{{ translate('Select a Member to view chats') }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('modal')
    @include('modals.package_update_alert_modal')
@endsection

@section('script')
    <script type="text/javascript">
        function loadChats(el) {
            $('.selected-chat').each(function() {
                $(this).removeClass('bg-soft-primary');
                $(this).removeClass('selected-chat');
            });
            $(el).addClass('selected-chat');
            $(el).addClass('bg-soft-primary');
            $.get($(el).data('url'), {}, function(data) {
                $('#single_chat').html(data);
                AIZ.extra.scrollToBottom();
                initializeLoadMore();
                $('#send-mesaage').on('submit', function(e) {
                    e.preventDefault();
                    send_reply();
                });
            });
        }

        function send_reply() {
            var chat_thread_id = $('#chat_thread_id').val();
            var message = $('#message').val();
            var attachment = $('#attachment').val();
            if (message.length > 0 || attachment.length > 0) {
                $.post('{{ route('chat.reply') }}', {
                    _token: '{{ csrf_token() }}',
                    chat_thread_id: chat_thread_id,
                    message: message,
                    attachment: attachment
                }, function(data) {
                    $('#message').val('');
                    $('#attachment').val('');
                    $('#chat-messages').append(data);
                    AIZ.extra.scrollToBottom();
                });
            }
        }
        $(document).on('click', '.chat-attachment', function() {
            AIZ.uploader.trigger(
                this,
                'direct',
                'all',
                '',
                true,
                function(files) {
                    $('#attachment').val(files);
                    send_reply();
                }
            );
        });
        $(document).ready(function() {
            setInterval(function() {
                refreshChats();
            }, 5000);
        });

        function refreshChats() {
            var el = $('.selected-chat');
            $.get($(el).data('refresh'), {}, function(data) {
                if (data.count > 0) {
                    $('#chat-messages').append(data.messages);
                    AIZ.extra.scrollToBottom();
                }
            });
        }

        function initializeLoadMore() {
            $('.load-more-btn').on('click', function() {
                $.post('{{ route('get-old-message') }}', {
                    _token: '{{ csrf_token() }}',
                    first_message_id: $(this).data('first')
                }, function(data) {
                    if (data.first_message_id > 0) {
                        $('#chat-messages').prepend(data.messages);
                        $('.load-more-btn').data('first', data.first_message_id);
                    }
                });
            });
        }

        function package_update_alert() {
            $('.package_update_alert_modal').modal('show');
        }
    </script>
@endsection
