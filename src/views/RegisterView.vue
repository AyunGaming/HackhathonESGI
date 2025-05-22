<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-100 to-blue-300">
    <div class="bg-white p-10 rounded-2xl shadow-xl w-full max-w-2xl">
      <h2 class="text-3xl font-bold text-blue-700 mb-8">Inscription client</h2>
      <form class="space-y-6" @submit.prevent="register">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <select v-model="titre" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition">
            <option value="">Titre</option>
            <option value="M.">M.</option>
            <option value="Mme">Mme</option>
            <option value="Société">Société</option>
          </select>
          <input v-model="nom" type="text" placeholder="Nom" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
          <input v-model="prenom" type="text" placeholder="Prénom" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
          <input v-model="adresse" type="text" placeholder="Adresse" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
          <input v-model="telephone" type="text" placeholder="Téléphone" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
          <input v-model="email" type="email" placeholder="Email" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
          <input v-model="password" type="password" placeholder="Mot de passe" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
          <input v-model="zip_code" type="text" placeholder="Code Postal" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
        </div>
        <div class="flex items-center mt-2">
          <input id="conducteurDiff" v-model="conducteurDiff" type="checkbox" class="mr-2" />
          <label for="conducteurDiff" class="text-gray-700">Conducteur différent</label>
        </div>
        <div>
  <h3 class="font-semibold mb-4 text-lg text-blue-700">Ajouter des véhicules</h3>
  <div v-for="(vehicule, index) in vehicules" :key="index" class="mb-6 p-4 border rounded-lg shadow-sm">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <input v-model="vehicule.brand" placeholder="Marque" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
      <input v-model="vehicule.model" placeholder="Modèle" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
      <input v-model="vehicule.registration" placeholder="Immatriculation" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
      <input v-model="vehicule.vin" placeholder="VIN" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
      <input v-model="vehicule.circulation_date" type="date" placeholder="Date mise en circulation" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
      <input v-model="vehicule.mileage" placeholder="Kilométrage" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
    </div>
    <button type="button" @click="removeVehicule(index)" class="mt-3 text-red-600 hover:text-red-800 font-semibold transition">Supprimer ce véhicule</button>
  </div>

  <button type="button" @click="addVehicule" class="mb-6 bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg shadow transition font-semibold">
    ➕ Ajouter un véhicule
  </button>
</div>

<div v-if="conducteurDiff" class="mt-6 space-y-4">
  <h3 class="font-semibold text-lg text-blue-700 mb-4">Informations du conducteur</h3>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <input v-model="driver_last_name" type="text" placeholder="Nom conducteur" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
    <input v-model="driver_first_name" type="text" placeholder="Prénom conducteur" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
    <input v-model="driver_phone" type="text" placeholder="Téléphone conducteur" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
  </div>
</div>

        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg shadow transition mt-4">Terminer inscription</button>
        <p v-if="error" class="text-red-500 text-sm mt-2">{{ error }}</p>
      </form>
      <p class="text-center text-sm mt-2">
        Vous avez déja un compte ?
        <router-link to="/login" class="text-blue-600 hover:underline">Connexion</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const nom = ref('')
const adresse = ref('')
const prenom = ref('')
const titre = ref('')
const conducteurDiff = ref(false)
const email = ref('')
const password = ref('')
const zip_code = ref('') // ajouté car manquant
const router = useRouter()
const telephone = ref('')
const error = ref('')

const vehicules = ref([
  {
    brand: '',
    model: '',
    registration: '',
    vin: '',
    circulation_date: '',
    mileage: '',
    driver_last_name: '',
    driver_first_name: '',
    driver_phone: '',
  }
])

const driver_last_name = ref('')
const driver_first_name = ref('')
const driver_phone = ref('')

const register = async (e) => {
  e.preventDefault()
  error.value = ''

  if (vehicules.value.length === 0) {
    error.value = 'Veuillez ajouter au moins un véhicule.'
    return
  }

  try {
    // Prépare le tableau véhicules à envoyer
    const vehiculesToSend = vehicules.value.map(v => ({
      ...v,
      driver: conducteurDiff.value,
      driver_last_name: conducteurDiff.value ? driver_last_name.value : nom.value,
      driver_first_name: conducteurDiff.value ? driver_first_name.value : prenom.value,
      driver_phone: conducteurDiff.value ? driver_phone.value : telephone.value,
    }))

    const response = await fetch('http://localhost:8000/api/register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        email: email.value,
        civil_title: titre.value,
        password: password.value,
        last_name: nom.value,
        first_name: prenom.value,
        address: adresse.value,
        telephone: telephone.value,
        zip_code: zip_code.value,
        vehicules: vehiculesToSend,
      })
    })

    let data = null
    try {
      data = await response.json()
    } catch (jsonErr) {
      error.value = 'Erreur serveur : réponse invalide'
      return
    }

    if (response.ok) {
      router.push('/login')
    } else {
      error.value = data.error || 'Erreur lors de l\'inscription'
    }
  } catch (err) {
    error.value = err.message || err
  }
}

const addVehicule = () => {
  vehicules.value.push({
    brand: '',
    model: '',
    registration: '',
    vin: '',
    circulation_date: '',
    mileage: '',
    driver_last_name: conducteurDiff.value ? driver_last_name.value : nom.value,
    driver_first_name: conducteurDiff.value ? driver_first_name.value : prenom.value,
    driver_phone: conducteurDiff.value ? driver_phone.value : telephone.value,
  })
}

const removeVehicule = (index) => {
  vehicules.value.splice(index, 1)
}
</script>
