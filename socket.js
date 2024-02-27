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
        socket.broadcast.emit('receive-message', message);
        console.log('message received:', message);
        axios.post('http://127.0.0.1:8000/api/chats', message)
            .then(response => {
                console.log('Message saved');
            })
            .catch(error => {
                console.log('Error saving message', error);
            });
    });
    socket.on('receive-message', (message) => {
        console.log('message get:', message);
    });
    socket.on('disconnect', () => {
    });
    socket.on('read-message', (messageId) => {
        console.log(messageId);
        axios.post('http://127.0.0.1:8000/api/markAsRead', messageId)
        .then(response => {
            console.log('Message read');
        })
        .catch(error => {
            console.log('Error read message', error);
        });
    });
});

server.listen(3000, () => {
    console.log('Server::3000');
});
