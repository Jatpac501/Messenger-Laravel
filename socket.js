import { createServer } from 'http';
import { Server } from 'socket.io';
import axios from 'axios';

const server = createServer();
const io = new Server(server, {
    cors: {
        origin: "*",
    }
});

io.on('connection', (socket) => {
    socket.on('send-message', (message) => {
        axios.post('http://127.0.0.1:8000/api/chats', message)
            .then(response => {
                const messageWithId = { ...message, id: response.data.messageId };
                socket.emit('message-saved', messageWithId);
                socket.broadcast.emit('receive-message', messageWithId);
            })
            .catch(error => {
            });
    });
    socket.on('receive-message', (message) => {
        console.log('message get:', message);
    });
    socket.on('markAsRead-message', (messageId) => {
        socket.emit('read-message', messageId);
        socket.broadcast.emit('read-message', messageId);
        console.log(messageId);
        axios.post('http://127.0.0.1:8000/api/markAsRead', messageId)
        .then(response => {
        })
        .catch(error => {
        });
    });
});

server.listen(3000, () => {
    console.log('Server::3000');
});
