<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import moment from 'moment';
import io from 'socket.io-client';
const socket = io('http://localhost:3000');

const props = defineProps({
    messages: Array,
    users: Object,
    users_id: Object,
});
const senderId = ref(props.users_id.sender_id);
const recipientId = ref(props.users_id.recipient_id);

const messages = ref(props.messages || []);
const messageText = ref('');

const sendMessage = () => {
    if (messageText.value.trim() !== '') {
        const msg = {
            from_user_id: props.users_id.sender_id,
            to_user_id: props.users_id.recipient_id,
            text: messageText.value,
            created_at: moment().utcOffset(240).format('LLL'),
        };
        socket.emit('send-message', msg);
        messages.value.push(msg);
        messageText.value = '';
    }
};

socket.on('receive-message', (message) => {
    messages.value.push(message);
});

const observer = new IntersectionObserver(
    (entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const message = entry.target.getAttribute('data-id');
                markMessageAsRead(message);
                observer.unobserve(entry.target);
            }
        });
    },
    {
        root: null,
        threshold: 0.1
    }
);

const markMessageAsRead = (messageId) => {
    const msg = {
            id: messageId,
            to_user_id: props.users_id.recipient_id,
    };
    socket.emit('read-message', msg);
};
onMounted(() => {
    messages.value.forEach((message) => {
        const element = document.querySelector(`#message-${message.id}`);
        if (element) observer.observe(element);
    });
});

onUnmounted(() => {
    observer.disconnect();
});

</script>

<template>
    <AppLayout title="Chats">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ props.users.recipient }}
            </h2>
        </template>
        <div class="py-6 ">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 ">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-2 h-[70vh] overflow-y-scroll">
                    <div
                        class="p-4 overflow-hidden flex flex-row"
                        v-for="message in messages"
                        :key="message.id"
                        :id="`message-${message.id}`"
                        :data-id="message.id"
                    >
                        <img
                            v-if="props.users.sender_photo"
                            :src="'/storage/' + props.users.sender_photo"
                            alt="Avatar"
                            class="h-10 w-10 rounded-full mr-2"
                        />
                        <div class="flex flex-col message whitespace-pre-wrap text-pretty">
                            <div class="text-blue-500 font-semibold message_name">{{ message.from_user_id === senderId ? props.users.sender : props.users.recipient }}</div>
                            <div class="text-slate-400 text-sm message_date">{{ moment(message.created_at).utcOffset(240).format('MMMM Do, h:mm a') }}</div>
                            <div class="text-white message-text col-span-2" :class=" {'my-message': message.from_user_id === props.sender_id}">{{ message.text }}</div>
                            <div v-if="message.read_at" class="text-slate-400 text-xs text-smread-receipt">viewed</div>
                        </div>
                    </div>
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

<style>
.message {
    max-width: 60%;
    white-space: pre-wrap;
    word-wrap: break-word;
}
::-webkit-scrollbar {
  width: 12px;
}

::-webkit-scrollbar-track {
  background: transparent;
}

::-webkit-scrollbar-thumb {
  background: rgba(136, 136, 136, .5);
  border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
  background: #555;
  border-radius: 10px;
}

</style>
