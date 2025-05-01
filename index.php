<!DOCTYPE html>
<html>
<head>
    <title>聊天室</title>
    <style>
        #messages { border: 1px solid #ccc; height: 300px; overflow-y: scroll; }
    </style>
</head>
<body>
    <div id="messages"></div>
    <input id="message" type="text" placeholder="輸入消息..." />
    <button onclick="sendMessage()">發送</button>

    <script>
        const conn = new WebSocket('ws://localhost:8080');

        // 在前端解壓縮消息
        conn.onmessage = function(e) {
            const messages = document.getElementById('messages');
            messages.innerHTML += '<div>' + e.data + '</div>';
            messages.scrollTop = messages.scrollHeight;
        };

        function sendMessage() {
            const messageInput = document.getElementById('message');
            if (conn.readyState === WebSocket.OPEN) {
                conn.send(messageInput.value);
                messageInput.value = '';
            } else {
                alert('WebSocket 尚未連線成功！');
            }
        }
    </script>
</body>
</html>