<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const isRecording = ref(false);
const audioUrl = ref(null);
let mediaRecorder = null;
let audioChunks = [];

const startRecording = async () => {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    mediaRecorder = new MediaRecorder(stream);
    mediaRecorder.ondataavailable = (event) => {
      audioChunks.push(event.data);
    };
    mediaRecorder.onstop = () => {
      const audioBlob = new Blob(audioChunks, { type: 'audio/mpeg' });
      audioUrl.value = URL.createObjectURL(audioBlob);
      audioChunks = [];
      sendAudioMessage(audioBlob);
    };
    mediaRecorder.start();
    isRecording.value = true;
  } catch (error) {
    console.error('Ошибка при записи аудио:', error);
  }
};

const stopRecording = () => {
  if (mediaRecorder) {
    mediaRecorder.stop();
    isRecording.value = false;
  }
};

const toogleRecorder = async () => {
    if (mediaRecorder && isRecording.value) {
        // При уже идущей записи - остановить ее
        mediaRecorder.stop();
        isRecording.value = false;
    } else {
        try {
            // Запрос разрешения на использование микрофона и начало записи
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            mediaRecorder = new MediaRecorder(stream);
            // Инициализация массива для накопления чанков данных
            audioChunks = [];
            mediaRecorder.ondataavailable = (event) => {
                // Сбор аудиоданных в массив
                audioChunks.push(event.data);
            };
            mediaRecorder.onstop = () => {
                // Обработка завершения записи
                // Создание Blob из накопленных данных и формирование URL для аудио
                const audioBlob = new Blob(audioChunks, { type: 'audio/mpeg' });
                audioUrl.value = URL.createObjectURL(audioBlob);
                // Очистка массива аудиоданных и отправка сообщения на сервер
                audioChunks = [];
                sendAudioMessage(audioBlob);
            };

            // Начало записи аудио
            mediaRecorder.start();
            isRecording.value = true;
        } catch (error) {
            console.error('Ошибка при запросе доступа к микрофону:', error);
        }
    }
};

const sendAudioMessage = (audioBlob) => {
  // Функция для отправки аудио blob на сервер через API
};
</script>

<template>
    <AppLayout title="тесты">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                тест
            </h2>
        </template>
        <DialogModal>
            <template #header>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    тест
                </h2>
            </template>
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 text-white font-bold flex flex-col gap-2 text-center">
                <div v-if="isRecording === true">🎙️ Идет Запись...</div>
                <div v-else>♿ Не Идет Запись...</div>
                <PrimaryButton @click="startRecording">Начать запись</PrimaryButton>
                <PrimaryButton @click="stopRecording" :disabled="!isRecording">Остановить запись</PrimaryButton>
                <PrimaryButton @click="toogleRecorder">Свитчер: {{ isRecording ? '🎙️🔴' : '🎙️⚫' }}</PrimaryButton>
                <audio v-if="audioUrl" :src="audioUrl" controls class="w-full bg-transparent"></audio>
            </div>
        </DialogModal>
    </AppLayout>
</template>
