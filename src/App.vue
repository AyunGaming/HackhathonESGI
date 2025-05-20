<template>
  <div class="flex h-screen bg-gray-100 items-center justify-center">
    <main v-if="!user && !showRegister" class="w-full max-w-2xl flex flex-col justify-center items-center">
      <div class="w-full">
        <!-- Connexion -->
        <h2 class="text-2xl font-bold mb-6 text-center text-blue-700">Connexion</h2>
        <form @submit.prevent="login" class="space-y-6 max-w-sm mx-auto bg-white p-8 rounded-xl shadow-lg">
          <div>
            <label class="block text-gray-700 font-medium mb-2" for="email">Email</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-blue-500">
                <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M16 12H8m8 0a4 4 0 11-8 0 4 4 0 018 0zm0 0v1a4 4 0 01-8 0v-1' /></svg>
              </span>
              <input v-model="loginForm.email" type="email" id="email" placeholder="exemple@email.com" class="w-full border border-gray-300 rounded-lg pl-10 pr-4 py-2 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition outline-none" required>
            </div>
          </div>
          <div>
            <label class="block text-gray-700 font-medium mb-2" for="password">Mot de passe</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-blue-500">
                <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 11c0-1.104.896-2 2-2s2 .896 2 2v1a2 2 0 01-2 2h-2a2 2 0 01-2-2v-1z' /></svg>
              </span>
              <input v-model="loginForm.password" type="password" id="password" placeholder="Mot de passe" class="w-full border border-gray-300 rounded-lg pl-10 pr-4 py-2 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition outline-none" required>
            </div>
          </div>
          <div class="flex justify-between gap-4 mt-6">
            <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold shadow transition">Se connecter</button>
            <button type="button" @click="showRegister = true" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-2 rounded-lg font-semibold transition">Inscription</button>
          </div>
        </form>
      </div>
    </main>
    <main v-else-if="showRegister" class="w-full max-w-2xl flex flex-col justify-center items-center">
      <!-- Inscription -->
      <div class="w-full space-y-4 max-w-lg mx-auto bg-white p-8 rounded-xl shadow-lg">
        <h2 class="text-2xl font-bold text-blue-700 mb-6">Inscription client</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <select v-model="client.titre" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition outline-none">
            <option disabled value="">Titre</option>
            <option>M.</option>
            <option>Mme</option>
            <option>Société</option>
          </select>
          <input v-model="client.nom" placeholder="Nom" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition outline-none">
          <input v-model="client.prenom" placeholder="Prénom" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition outline-none">
          <input v-model="client.adresse" placeholder="Adresse" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition outline-none">
          <input v-model="client.tel" placeholder="Téléphone" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition outline-none">
          <input v-model="client.email" type="email" placeholder="Email" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition outline-none" required>
        </div>
        <label class="flex items-center space-x-2 text-sm mt-2">
          <input type="checkbox" v-model="client.conducteurDiff" />
          <span>Conducteur différent</span>
        </label>
        <div v-if="client.conducteurDiff" class="grid grid-cols-1 md:grid-cols-3 gap-2">
          <input v-model="client.nomConducteur" placeholder="Nom conducteur" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition outline-none">
          <input v-model="client.prenomConducteur" placeholder="Prénom conducteur" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition outline-none">
          <input v-model="client.telConducteur" placeholder="Téléphone conducteur" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition outline-none">
        </div>
        <h3 class="text-md font-semibold mt-4">Ajouter un véhicule</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
          <input v-model="vehicule.marque" placeholder="Marque" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition outline-none">
          <input v-model="vehicule.modele" placeholder="Modèle" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition outline-none">
          <input v-model="vehicule.immatriculation" placeholder="Immatriculation" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition outline-none">
          <input v-model="vehicule.vin" placeholder="VIN" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition outline-none">
          <input v-model="vehicule.dateCirculation" type="date" placeholder="Date mise en circulation" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition outline-none">
          <input v-model="vehicule.kilometrage" placeholder="Kilométrage" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition outline-none">
        </div>
        <button type="button" @click="addVehicule" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold shadow transition mt-2">Ajouter véhicule</button>
        <ul class="list-disc ml-5 mt-2">
          <li v-for="(v, i) in vehicules" :key="i">{{ v.marque }} {{ v.modele }} ({{ v.immatriculation }})</li>
        </ul>
        <button @click="completeRegister" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold shadow transition mt-4">Terminer inscription</button>
      </div>
    </main>
    <div v-else class="flex h-screen w-full">
      <!-- Sidebar -->
      <aside class="w-64 bg-white shadow-lg h-full flex flex-col py-8 px-4">
        <div class="mb-10">
          <div class="text-2xl font-bold text-blue-700 mb-2">Menu</div>
        </div>
        <nav class="flex flex-col gap-4">
          <button class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-blue-50 transition font-medium text-gray-700">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 17l4 4 4-4m0-5V3a1 1 0 00-1-1H7a1 1 0 00-1 1v9m12 4h-4a1 1 0 01-1-1v-4a1 1 0 00-1-1H7a1 1 0 00-1 1v4a1 1 0 01-1 1H3"/></svg>
            Mes rendez-vous
          </button>
          <button class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-blue-50 transition font-medium text-gray-700">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            Mes véhicules
          </button>
          <button class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-blue-50 transition font-medium text-gray-700">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v4a1 1 0 001 1h3m10-5v4a1 1 0 001 1h3m-7 4v4m0 0l-4-4m4 4l4-4"/></svg>
            Historique des RDV
          </button>
        </nav>
      </aside>
      <!-- Contenu principal (placeholder) -->
      <div class="flex-1 flex flex-col items-center justify-center relative">
        <div class="text-3xl font-bold text-gray-700 mb-4">Bienvenue, {{ user.name }}</div>
        <div class="text-gray-500">Sélectionnez une section dans le menu à gauche.</div>
        <!-- Bouton Chatbot flottant -->
        <button @click="showChat = !showChat" class="fixed bottom-8 right-8 z-50 bg-blue-600 hover:bg-blue-700 text-white rounded-full shadow-lg p-4 flex items-center justify-center transition">
          <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8h2a2 2 0 012 2v8a2 2 0 01-2 2H7a2 2 0 01-2-2v-8a2 2 0 012-2h2m2-4h4a2 2 0 012 2v2a2 2 0 01-2 2h-4a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>
        </button>
        <!-- Fenêtre de chat -->
        <transition name="fade">
          <div v-if="showChat" class="fixed bottom-24 right-8 z-50 w-96 max-w-full bg-white rounded-xl shadow-2xl border border-blue-200 flex flex-col overflow-hidden" style="max-height: 800px;">
            <div class="bg-blue-600 text-white px-4 py-3 flex justify-between items-center">
              <span class="font-semibold">Chatbot</span>
              <button @click="showChat = false" class="text-white hover:text-blue-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>
            <div ref="chatMessages" class="flex-1 p-4 overflow-y-auto space-y-2 bg-blue-50" style="max-height: 500px;">
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
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onUpdated, nextTick } from 'vue'

const showRegister = ref(false)
const client = ref({
  titre: '',
  nom: '',
  prenom: '',
  adresse: '',
  tel: '',
  conducteurDiff: false,
  nomConducteur: '',
  prenomConducteur: '',
  telConducteur: ''
})

const vehicule = ref({
  marque: '',
  modele: '',
  immatriculation: '',
  vin: '',
  dateCirculation: '',
  kilometrage: ''
})
const vehicules = ref([])

const addVehicule = () => {
  vehicules.value.push({ ...vehicule.value })
  vehicule.value = { marque: '', modele: '', immatriculation: '', vin: '', dateCirculation: '', kilometrage: '' }
}

const completeRegister = () => {
  alert("Inscription complétée avec " + vehicules.value.length + " véhicule(s)")
  showRegister.value = false
}

const userInput = ref('')
const typing = ref(false)
const messages = ref([])
const user = ref(null)
const loginForm = ref({ email: '', password: '' })
const menuOpen = ref(false)
const showChat = ref(false)
const chatMessages = ref(null)

const toggleMenu = () => {
  menuOpen.value = !menuOpen.value
}

const login = () => {
  // Validation email simple côté front
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(loginForm.value.email)) {
    alert('Veuillez entrer un email valide.');
    return;
  }
  user.value = { name: loginForm.value.email.split('@')[0] }
  messages.value.push({ text: `Bonjour ${user.value.name}, comment puis-je vous aider aujourd'hui ?`, sender: 'bot' })
}

const logout = () => {
  user.value = null
  messages.value = []
  userInput.value = ''
  loginForm.value = { email: '', password: '' }
  menuOpen.value = false
}

const sendMessage = () => {
  if (!userInput.value.trim()) return
  messages.value.push({ text: userInput.value, sender: 'user' })
  const input = userInput.value
  userInput.value = ''
  typing.value = true

  setTimeout(() => {
    messages.value.push({ text: `Vous avez dit : "${input}". (réponse simulée du bot)`, sender: 'bot' })
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
:root {
  --salmon: #FA8072;
  --salmon-light: #FFA07A;
}
.bg-salmon {
  background-color: var(--salmon);
}
.bg-salmon-light {
  background-color: var(--salmon-light);
}
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
