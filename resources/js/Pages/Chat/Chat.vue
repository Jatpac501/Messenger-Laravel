<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import moment from 'moment';
import { io } from 'socket.io-client';
const socket = io('http://localhost:3000');

// Заменить moment на day.js или date-fns

const props = defineProps({
    messages: Array,
    users: Object,
    users_id: Object,
});

const messages = ref(props.messages || []);
const messageText = ref('');

const sendMessage = () => {
    if (messageText.value.trim() !== '') {
        const tempId = Date.now();
        const msg = {
            from_user_id: props.users_id.sender_id,
            to_user_id: props.users_id.recipient_id,
            text: messageText.value,
            tempId,
            created_at: moment().utcOffset(240).format('LLL'),
        };
        socket.emit('send-message', msg);
        messages.value.push({ ...msg, id: tempId });
        messageText.value = '';
    }
    nextTick(() => scrollToLastMessage());
};

const scrollToLastMessage = () => {
    const messagesContainer = document.querySelector('#messages').querySelectorAll('.message');
    //Заделка под скролл до ласт непросмотренного (<div class="message"/>)
    const lastMessages = messagesContainer[messagesContainer.length-1];
    lastMessages?.scrollIntoView({behavior: 'smooth', block: "end"});
};


socket.on('message-saved', (messageWithId) => {
    const index = messages.value.findIndex(m => m.tempId === messageWithId.tempId);
    if (index !== -1) {
        messages.value[index].id = messageWithId.id;
    }
});

socket.on('receive-message', (message) => {
    messages.value.push(message);
    nextTick(() => {
        const element = document.querySelector(`#message-${message.id}`);
        if (!element.classList.contains('viewed')) observer.observe(element);
        scrollToLastMessage();
    });
});
socket.on('read-message', (readMessageInfo) => {
    const messageIndex = messages.value.findIndex(m => m.id == readMessageInfo.id);
    if (messageIndex != -1) {
        messages.value[messageIndex].read_at = readMessageInfo.read_at;
    }
});

const markMessageAsRead = (messageId) => {
    const msg = {
            id: messageId,
            to_user_id: props.users_id.recipient_id,
            read_at: moment().utcOffset(240).format('LLL'),
    };
    if (msg.to_user_id === props.users_id.sender_id) socket.emit('markAsRead-message', msg);
};

const observer = new IntersectionObserver(
    (entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const message = entry.target.getAttribute('data-id');
                markMessageAsRead(message);
                observer.unobserve(entry.target);
            }
        });
    },
    {root: null,threshold: 0.8}
);

onMounted(() => {
    messages.value.forEach((message) => {
        const element = document.querySelector(`#message-${message.id}`);
        if (!element.classList.contains('viewed') || !element.classList.contains('my-message')) observer.observe(element);
    });
    nextTick(() => scrollToLastMessage());
});

onUnmounted(() => {
    observer?.disconnect();
});
</script>

<template>
    <AppLayout title="Chats">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ props.users.recipient }}
            </h2>
        </template>
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 ">
                <div id="messages" class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg h-[70vh] overflow-y-scroll px-2">
                    <div
                        v-for="message in messages"
                        class="p-4 overflow-hidden flex flex-row"
                        :class="{
                            'viewed': message.read_at,
                            'my-message': message.from_user_id === props.users_id.sender_id
                        }"
                        :key="message.id"
                        :id="`message-${message.id}`"
                        :data-id="message.id"
                    >
                        <img
                            :src="message.from_user_id === props.users_id.sender_id ? '/storage/' + props.users.sender_photo : '/storage/' + props.users.recipient_photo"
                            alt="Avatar"
                            class="h-10 w-10 rounded-full mr-2"
                        />
                        <div class="flex flex-col message whitespace-pre-wrap text-pretty">
                            <div class="text-blue-500 font-semibold message_name">{{ message.from_user_id === props.users_id.sender_id ? props.users.sender : props.users.recipient }}</div>
                            <div class="text-slate-400 text-sm message_date">{{ moment(message.created_at).utcOffset(240).format('MMMM Do, h:mm a') }}</div>
                            <div class="text-white message-text col-span-2 message" :class=" {'my-message': message.from_user_id === props.sender_id}">{{ message.text }}</div>
                            <div v-if="message.read_at" class="text-slate-400 text-xs text-smread-receipt">viewed</div>
                        </div>
                    </div>
                    <KOCTblLb class="message"/>
                </div>
            </div>
        </div>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-2 flex flex-nowrap gap-4">
                <textarea class="w-full whitespace-pre-wrap text-wrap border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" v-model="messageText"></textarea>

                <PrimaryButton @click="sendMessage">Send</PrimaryButton>
            </div>
        </div>
    </AppLayout>
</template>

