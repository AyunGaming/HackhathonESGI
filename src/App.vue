<template>
  <div class="flex h-screen bg-gray-100">
    <!-- Sidebar gauche -->
    <aside class="w-1/5 bg-white p-4 border-r">
      <h2 class="text-lg font-bold mb-4">Conversations</h2>
      <p class="text-gray-500 text-sm">(à implémenter)</p>
    </aside>

    <!-- Chat principal -->
    <main class="flex-1 flex flex-col">
      <!-- Header avec photo de profil -->
      <header class="bg-white shadow p-4 text-xl font-semibold flex justify-between items-center">
        <span>Chatbot RDV Auto</span>
        <div v-if="user" class="relative">
          <img
            :src="`https://ui-avatars.com/api/?name=${user.name}`"
            class="w-10 h-10 rounded-full cursor-pointer border"
            @click="toggleMenu"
          />
          <div v-if="menuOpen" class="absolute right-0 mt-2 w-48 bg-white border rounded shadow z-10">
            <a href="#" class="block px-4 py-2 hover:bg-gray-100 text-sm" @click="logout">Déconnexion</a>
            <a href="#" class="block px-4 py-2 hover:bg-gray-100 text-sm">Mes rendez-vous</a>
            <a href="#" class="block px-4 py-2 hover:bg-gray-100 text-sm">Profil</a>
          </div>
        </div>
      </header>

      <div class="flex-1 overflow-y-auto p-6 space-y-4">
        <!-- Connexion -->
        <div v-if="!user && !showRegister">
          <h2 class="text-lg font-semibold mb-4">Connexion</h2>
          <form @submit.prevent="login" class="space-y-4 max-w-sm">
            <input v-model="loginForm.plate" type="text" placeholder="Immatriculation" class="w-full border rounded px-4 py-2" required>
            <input v-model="loginForm.password" type="password" placeholder="Mot de passe" class="w-full border rounded px-4 py-2" required>

            <div class="flex justify-between gap-4">
              <button type="submit" class="flex-1 bg-blue-600 text-white px-6 py-2 rounded">Se connecter</button>
              <button type="button" @click="showRegister = true" class="flex-1 bg-gray-200 text-gray-800 px-6 py-2 rounded hover:bg-gray-300">Inscription</button>
            </div>
          </form>


        </div>

        <!-- Inscription -->
        <div v-else-if="showRegister" class="space-y-4">
          <h2 class="text-lg font-semibold">Inscription client</h2>
          <select v-model="client.titre" class="w-full border rounded px-4 py-2">
            <option disabled value="">Titre</option>
            <option>M.</option>
            <option>Mme</option>
            <option>Société</option>
          </select>
          <input v-model="client.nom" placeholder="Nom" class="w-full border rounded px-4 py-2">
          <input v-model="client.prenom" placeholder="Prénom" class="w-full border rounded px-4 py-2">
          <input v-model="client.adresse" placeholder="Adresse" class="w-full border rounded px-4 py-2">
          <input v-model="client.tel" placeholder="Téléphone" class="w-full border rounded px-4 py-2">

          <label class="flex items-center space-x-2 text-sm">
            <input type="checkbox" v-model="client.conducteurDiff" />
            <span>Conducteur différent</span>
          </label>

          <div v-if="client.conducteurDiff" class="space-y-2">
            <input v-model="client.nomConducteur" placeholder="Nom conducteur" class="w-full border rounded px-4 py-2">
            <input v-model="client.prenomConducteur" placeholder="Prénom conducteur" class="w-full border rounded px-4 py-2">
            <input v-model="client.telConducteur" placeholder="Téléphone conducteur" class="w-full border rounded px-4 py-2">
          </div>

          <h3 class="text-md font-semibold mt-4">Ajouter un véhicule</h3>
          <input v-model="vehicule.marque" placeholder="Marque" class="w-full border rounded px-4 py-2">
          <input v-model="vehicule.modele" placeholder="Modèle" class="w-full border rounded px-4 py-2">
          <input v-model="vehicule.immatriculation" placeholder="Immatriculation" class="w-full border rounded px-4 py-2">
          <input v-model="vehicule.vin" placeholder="VIN" class="w-full border rounded px-4 py-2">
          <input v-model="vehicule.dateCirculation" type="date" placeholder="Date mise en circulation" class="w-full border rounded px-4 py-2">
          <input v-model="vehicule.kilometrage" placeholder="Kilométrage" class="w-full border rounded px-4 py-2">

          <button type="button" @click="addVehicule" class="bg-green-600 text-white px-4 py-2 rounded">Ajouter véhicule</button>

          <ul class="list-disc ml-5 mt-2">
            <li v-for="(v, i) in vehicules" :key="i">{{ v.marque }} {{ v.modele }} ({{ v.immatriculation }})</li>
          </ul>

          <button @click="completeRegister" class="bg-blue-600 text-white px-6 py-2 rounded">Terminer inscription</button>
        </div>

        <!-- Chatbot -->
        <div v-else>
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
      </div>

      <!-- Champ de message -->
      <form v-if="user" @submit.prevent="sendMessage" class="flex p-4 border-t bg-white">
        <input
          v-model="userInput"
          placeholder="Tapez votre message ici..."
          class="flex-1 border border-gray-300 rounded-l-lg px-4 py-2 focus:outline-none"
        />
        <button type="submit" class="bg-blue-600 text-white px-6 rounded-r-lg">Envoyer</button>
      </form>
    </main>
  </div>
</template>

<script setup>
import { ref } from 'vue'

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
const loginForm = ref({ plate: '', password: '' })
const menuOpen = ref(false)

const toggleMenu = () => {
  menuOpen.value = !menuOpen.value
}

const login = () => {
  user.value = { name: loginForm.value.plate.toUpperCase() }
  messages.value.push({ text: `Bonjour ${user.value.name}, comment puis-je vous aider aujourd’hui ?`, sender: 'bot' })
}

const logout = () => {
  user.value = null
  messages.value = []
  userInput.value = ''
  loginForm.value = { plate: '', password: '' }
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
</style>
