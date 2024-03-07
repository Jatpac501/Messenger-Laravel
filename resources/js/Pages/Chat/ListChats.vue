<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import moment from 'moment';
import { io } from 'socket.io-client';
const socket = io(import.meta.env.VITE_SOCKET_SERVER_URL || 'http://localhost:3000');

const props = defineProps({
    user: Object,
    listChats: Object,
    users: Object,
});

const chats = ref(props.listChats || []);
const messages = ref(chats.value);
const userId = ref('');
const goToChat = (id) => {
    if (id !== '') {window.location.href = `/chat?id=${id}`};
};

const unreadCounts = computed(() => {
  const counts = {};
  for (const [chatId, chatMessages] of Object.entries(messages.value)) {
    const unreadMessages = chatMessages.filter(message => message.read_at === null);
    counts[chatId] = unreadMessages.length;
  }
  return counts;
});
</script>
<template>
    <AppLayout title="Chats">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Chats
            </h2>
        </template>
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col gap-2">
                <div>
                    <InputLabel for="user_id" value="User ID" />
                    <TextInput v-model="userId" id="user_id" />
                    <PrimaryButton @click="goToChat(userId)">Enter</PrimaryButton>
                </div>
                <button
                    v-for="chat in users"
                    @click="goToChat(chat.id)"
                    :id="`chat-${chat.id}`"
                    class="text-white bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg w-full py-4 px-6 flex flex-row "
                >
                    <div>
                        <img
                            :src="'/storage/' + chat.profile_photo_path"
                            alt="Avatar"
                            class="h-10 w-10 rounded-full mr-2"
                        />
                    </div>
                    <div class="text-start">
                        <div class="font-semibold flex flex-row gap-2">
                            {{ chat.name }}
                            <div v-if="unreadCounts[chat.id] > 0" class="text-red-600 text-sm ">
                                {{ unreadCounts[chat.id] }}
                            </div>
                        </div>
                        <div class="text-slate-400 text-sm">{{ moment(chats[chat.id][chats[chat.id].length-1].created_at).utcOffset(240).format('MMMM Do, h:mm a') }}</div>
                        <div class="text-slate-400 truncate max-w-[50dvw] text-sm">{{ chats[chat.id][chats[chat.id].length-1].text }}</div>

                    </div>
                </button>
            </div>
        </div>
    </AppLayout>
</template>


