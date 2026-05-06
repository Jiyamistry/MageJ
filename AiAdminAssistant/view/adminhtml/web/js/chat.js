define(['jquery'], function ($) {
    'use strict';

    return function (config) {

        function loadChatHistory() {
            $.ajax({
                url: config.historyUrl,
                type: 'GET'
            }).done(function (res) {

                if (res.success) {
                    $('#ai-chat-messages').html('');    

                    res.data.forEach(function (item) {
                        $('#ai-chat-messages').append(
                            '<div class="ai-msg user-msg">' + item.message + '</div>'
                        );

                        $('#ai-chat-messages').append(
                            '<div class="ai-msg ai-response ai-message">' + item.response + '</div>'
                        );
                    });

                    scrollToBottom();
                }
            });
        }

        function scrollToBottom() {
            let box = $('#ai-chat-messages')[0];
            if (box) {
                box.scrollTop = box.scrollHeight;
            }
        }

        $(document).on('click', '#ai-chat-toggle', function () {
            $('#ai-chat-box').toggleClass('active');

            if ($('#ai-chat-box').hasClass('active')) {
                loadChatHistory();
            }
        });

        $(document).on('click', '#ai-chat-send', function () {
            sendMessage();
        });

        $(document).on('keypress', '#ai-chat-input', function (e) {
            if (e.which === 13) {
                sendMessage();
            }
        });

        function sendMessage() {
            let input = $('#ai-chat-input');
            let message = input.val();

            if (!message) return;

            $('#ai-chat-messages').append(
                '<div class="ai-msg user-msg">' + message + '</div>'
            );

            input.val('');
            scrollToBottom();

            $.ajax({
                url: config.processUrl,
                type: 'POST',
                data: {message: message}
            }).done(function (res) {

                $('#ai-chat-messages').append(
                    '<div class="ai-msg ai-response ai-message">' + res.reply + '</div>'
                );

                scrollToBottom();
            });
        }

    };
});
