<template>
  <div>
    <button @click="showChat = !showChat" class="fixed bottom-8 right-8 z-50 bg-blue-600 text-white rounded-full p-4">
      Chat
    </button>
    <transition name="fade">
      <div v-if="showChat" class="fixed bottom-24 right-8 z-50 w-96 bg-white rounded-xl shadow-xl p-4">
        <div class="flex justify-between items-center mb-2">
          <strong>Chatbot</strong>
          <button @click="showChat = false">X</button>
        </div>
        <div class="overflow-y-auto max-h-60 space-y-2 mb-2">
          <div v-for="(msg, index) in messages" :key="index" :class="msg.sender === 'bot' ? 'text-left' : 'text-right'">
            <div class="px-3 py-1 rounded" :class="msg.sender === 'bot' ? 'bg-gray-200' : 'bg-blue-500 text-white'">
              {{ msg.text }}
            </div>
          </div>
        </div>
        <form @submit.prevent="sendMessage" class="flex">
          <input v-model="userInput" placeholder="Votre message..." class="flex-1 border px-2 py-1 rounded-l" />
          <button type="submit" class="bg-blue-600 text-white px-4 rounded-r">Envoyer</button>
        </form>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, nextTick, onUpdated } from 'vue'

const showChat = ref(false)
const userInput = ref('')
const messages = ref([])
const typing = ref(false)
const chatMessages = ref(null)

function sendMessage() {
  if (!userInput.value.trim()) return
  const input = userInput.value
  messages.value.push({ text: input, sender: 'user' })
  userInput.value = ''
  typing.value = true
  setTimeout(() => {
    messages.value.push({ text: `Vous avez dit : "${input}"`, sender: 'bot' })
    typing.value = false
  }, 800)
}

onUpdated(() => {
  if (showChat.value && chatMessages.value) {
    nextTick(() => {
      chatMessages.value.scrollTop = chatMessages.value.scrollHeight
    })
  }
})
</script>

<style>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
