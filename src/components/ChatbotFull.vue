<template>
  <div class="flex flex-col h-full bg-white rounded-xl shadow-lg">
    <div class="bg-blue-600 text-white px-4 py-3 flex justify-between items-center rounded-t-xl">
      <span class="font-semibold">Chatbot RDV Auto</span>
    </div>
    <div ref="chatMessages" class="flex-1 p-4 overflow-y-auto space-y-2 bg-blue-50">
      <div v-for="(msg, index) in messages" :key="index" class="flex">
        <div
          :class="msg.sender === 'bot' ? 'bg-gray-200 text-left' : 'bg-blue-500 text-white ml-auto text-right'"
          class="rounded-lg px-4 py-2 max-w-xl mb-2"
        >
          {{ msg.text }}
        </div>
      </div>
      <div v-if="typing" class="text-sm text-gray-500 italic">Le bot est en train de répondre...</div>
    </div>
    <form @submit.prevent="sendMessage" class="flex p-4 border-t bg-white rounded-b-xl">
      <input
        v-model="userInput"
        placeholder="Tapez votre message ici..."
        class="flex-1 border border-gray-300 rounded-l-lg px-4 py-2 focus:outline-none"
      />
      <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 rounded-r-lg transition">Envoyer</button>
    </form>
  </div>
</template>

<script setup>
import { ref, onUpdated, nextTick } from 'vue'

const messages = ref([
  { text: 'Bonjour, comment puis-je vous aider ?', sender: 'bot' }
])
const userInput = ref('')
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

// Auto-scroll to bottom
onUpdated(() => {
  if (chatMessages.value) {
    nextTick(() => {
      chatMessages.value.scrollTop = chatMessages.value.scrollHeight
    })
  }
})
</script>

<style scoped>
.h-full {
  height: 100%;
}
.flex-1 {
  flex: 1;
}
</style>