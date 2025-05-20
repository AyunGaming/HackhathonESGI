<template>
  <div class="flex min-h-screen bg-gray-100">
    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-lg flex flex-col py-8 px-4 rounded-r-2xl">
      <nav class="flex flex-col gap-4">
        <button
          v-for="item in menu"
          :key="item.key"
          :class="['flex items-center gap-3 px-4 py-2 rounded-lg transition font-medium text-lg', section === item.key ? item.activeClass : item.baseClass]"
          @click="section = item.key"
        >
          <span class="text-2xl">{{ item.emoji }}</span>
          {{ item.label }}
        </button>
      </nav>
      <div class="flex-1"></div>
      <button
        @click="logout"
        class="flex items-center gap-2 mt-8 px-4 py-2 rounded-lg bg-red-100 text-red-700 font-semibold hover:bg-red-200 transition shadow self-start"
        title="Déconnexion"
      >
        <span class="text-xl">🚪</span>
        Déconnexion
      </button>
    </aside>
    <!-- Contenu principal -->
    <main class="flex-1 p-8">
      <component :is="currentComponent" />
      <!-- Bouton Chatbot flottant -->
      <button
        @click="showChat = !showChat"
        class="fixed bottom-8 right-8 z-50 bg-blue-600 hover:bg-blue-700 text-white rounded-full shadow-lg p-4 flex items-center justify-center transition text-3xl"
        title="Ouvrir le chatbot"
      >💬</button>
      <!-- Fenêtre de chat -->
      <transition name="fade">
        <div
          v-if="showChat"
          class="fixed bottom-24 right-8 z-50 w-96 max-w-full bg-white rounded-xl shadow-2xl border border-blue-200 flex flex-col overflow-hidden"
          style="max-height: 600px;"
        >
          <div class="bg-blue-600 text-white px-4 py-3 flex justify-between items-center">
            <span class="font-semibold">Chatbot</span>
            <button @click="showChat = false" class="text-white hover:text-blue-200 text-xl">✖️</button>
          </div>
          <div class="flex-1 p-4 overflow-y-auto space-y-2 bg-blue-50" style="max-height: 350px;">
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
          <form @submit.prevent="sendMessage" class="flex p-2 border-t bg-white">
            <input
              v-model="userInput"
              placeholder="Tapez votre message ici..."
              class="flex-1 border border-gray-300 rounded-l-lg px-4 py-2 focus:outline-none"
            />
            <button type="submit" class="bg-blue-600 text-white px-6 rounded-r-lg">Envoyer</button>
          </form>
        </div>
      </transition>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, onUpdated } from 'vue'
import { useRouter } from 'vue-router'
import MesRendezVous from '../components/MesRendezVous.vue'
import MesVehicules from '../components/MesVehicules.vue'
import HistoriqueRdv from '../components/HistoriqueRdv.vue'
// Icônes SVG en composants Vue
const IconRdv = { template: `<svg ...></svg>` }
const IconVehicule = { template: `<svg ...></svg>` }
const IconHistory = { template: `<svg ...></svg>` }

const section = ref('rdv')
const showChat = ref(false)
const menu = [
  { key: 'rdv', label: 'Mes rendez-vous', emoji: '📅', activeClass: 'bg-blue-100 text-blue-700 shadow', baseClass: 'text-gray-700 hover:bg-blue-50' },
  { key: 'vehicules', label: 'Mes véhicules', emoji: '🚗', activeClass: 'bg-green-100 text-green-700 shadow', baseClass: 'text-gray-700 hover:bg-green-50' },
  { key: 'historique', label: 'Historique des RDV', emoji: '📜', activeClass: 'bg-gray-200 text-gray-700 shadow', baseClass: 'text-gray-700 hover:bg-gray-100' }
]

// Composants de contenu (exemples)
// const RdvSection = { template: `<div class='text-2xl font-bold text-blue-700'>Mes rendez-vous (exemple)</div>` }
// const VehiculesSection = { template: `<div class='text-2xl font-bold text-green-700'>Mes véhicules (exemple)</div>` }
// const HistoriqueSection = { template: `<div class='text-2xl font-bold text-gray-700'>Historique des RDV (exemple)</div>` }

const currentComponent = computed(() => {
  if (section.value === 'rdv') return MesRendezVous
  if (section.value === 'vehicules') return MesVehicules
  return HistoriqueRdv
})

// Chatbot (exemple statique)
const messages = ref([{ text: 'Bonjour, comment puis-je vous aider ?', sender: 'bot' }])
const userInput = ref('')
const typing = ref(false)
const sendMessage = () => {
  if (!userInput.value.trim()) return
  messages.value.push({ text: userInput.value, sender: 'user' })
  const input = userInput.value
  userInput.value = ''
  typing.value = true
  setTimeout(() => {
    messages.value.push({ text: `Vous avez dit : \"${input}\". (réponse simulée du bot)`, sender: 'bot' })
    typing.value = false
  }, 800)
}
const chatMessages = ref(null)
onUpdated(() => {
  if (showChat.value && chatMessages.value) {
    nextTick(() => {
      chatMessages.value.scrollTop = chatMessages.value.scrollHeight
    })
  }
})

const router = useRouter()
const logout = () => {
  // Ici tu peux aussi faire un emit ou clear le store utilisateur
  router.push('/login')
}
</script>
