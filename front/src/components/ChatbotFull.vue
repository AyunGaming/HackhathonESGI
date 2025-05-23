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
const userInitialized = ref(false)


initializeUser({
  full_name: 'Jean Dupont',
  phone_number: '0601020304',
  address: '12 rue de la Paix, 75002 Paris'
})
// Appelle l’API Symfony qui relaie vers FastAPI
async function initializeUser(userInfo) {
  // userInfo = { full_name, phone_number, address }
  try {
    const params = new URLSearchParams(userInfo).toString()
    const res = await fetch(`http://localhost:8001/initialize_chat?${params}`, {
      method: 'GET',
      credentials: 'include'
    })
    const data = await res.json()
    if (res.ok) {
      userInitialized.value = true
      // Optionnel: afficher un message de bienvenue du bot
      if (data.response) messages.value.push({ text: data.response, sender: 'bot' })
    } else {
      messages.value.push({ text: data.error || "Erreur d'initialisation", sender: 'bot' })
    }
  } catch (e) {
    messages.value.push({ text: "Erreur de connexion à l'API.", sender: 'bot' })
  }
}

async function sendMessage() {
  if (!userInitialized.value) {
    // Appelle initializeUser ici AVANT d'envoyer le message
    await initializeUser({
      full_name: 'Jean Dupont',
      phone_number: '0601020304',
      address: '12 rue de la Paix, 75002 Paris'
    })
    // Tu peux demander ces infos à l'utilisateur via un formulaire si besoin
  }
  if (!userInput.value.trim()) return
  const input = userInput.value
  messages.value.push({ text: input, sender: 'user' })
  userInput.value = ''
  typing.value = true
  try {
    const res = await fetch('http://localhost:8001/chat', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ message: input })
    })
    const data = await res.json()
    if (res.ok && data.response) {
      messages.value.push({ text: data.response, sender: 'bot' })
    } else {
      messages.value.push({ text: data.error || "Erreur du bot", sender: 'bot' })
    }
  } catch (e) {
    messages.value.push({ text: "Erreur de connexion au serveur.", sender: 'bot' })
  }
  typing.value = false
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