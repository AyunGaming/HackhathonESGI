<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-100 to-blue-300">
    <div class="bg-white p-10 rounded-2xl shadow-xl w-full max-w-2xl">
      <h2 class="text-3xl font-bold text-blue-700 mb-8">Inscription client</h2>
      <form class="space-y-6" @submit="register">
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
          <input v-model="zip_code" type="zip_code" placeholder="Code Postal" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
        </div>
        <div class="flex items-center mt-2">
          <input id="conducteurDiff" v-model="conducteurDiff" type="checkbox" class="mr-2" />
          <label for="conducteurDiff" class="text-gray-700">Conducteur différent</label>
        </div>
        <div>
          <h3 class="font-semibold mb-2">Ajouter un véhicule</h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <input v-model="brand" type="text" placeholder="Marque" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
            <input v-model="model" type="text" placeholder="Modèle" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
            <input v-model="registration" type="text" placeholder="Immatriculation" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
            <input v-model="vin" type="text" placeholder="VIN" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
            <input v-model="circulation_date" type="date" placeholder="Date mise en circulation" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
            <input v-model="mileage" type="text" placeholder="Kilométrage" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
          </div>
          <button type="button" class="bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-2 rounded-lg shadow transition">Ajouter véhicule</button>
        </div>
        <div v-if="conducteurDiff" class="mt-6 space-y-4">
          <h3 class="font-semibold">Informations du conducteur</h3>
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
const router = useRouter()
const telephone = ref('')
const error = ref('')

const brand = ref('')
const model = ref('')
const registration = ref('')
const vin = ref('')
const circulation_date = ref('')
const mileage = ref('')
const zip_code = ref('')

const driver_last_name = ref('')
const driver_first_name = ref('')
const driver_phone = ref('')

const register = async (e) => {
  e.preventDefault()
  error.value = ''
  try {
    const vehicule = {
      brand: brand.value,
      model: model.value,
      registration: registration.value,
      vin: vin.value,
      circulation_date: circulation_date.value,
      mileage: mileage.value,
      driver: conducteurDiff.value,
      zip_code: zip_code.value
    }
    if (conducteurDiff.value) {
      vehicule.driver_last_name = driver_last_name.value
      vehicule.driver_first_name = driver_first_name.value
      vehicule.driver_phone = driver_phone.value
    } else {
      vehicule.driver_last_name = nom.value
      vehicule.driver_first_name = prenom.value
      vehicule.driver_phone = telephone.value
    }
    const response = await fetch('http://localhost:8000/api/register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        email: email.value,
        civil_title: titre.value,
        password: password.value,
        vehicule,
        titre: titre.value,
        last_name: nom.value,
        first_name: prenom.value,
        address: adresse.value,
        telephone: telephone.value,
        zip_code: zip_code.value
      })
    })
    let data = null
    try {
      data = await response.json()
    } catch (jsonErr) {
      // La réponse n'est pas du JSON
      error.value = 'Erreur serveur : réponse invalide'
      return
    }
    if (response.ok) {
      router.push('/login')
    } else {
      error.value = data.error || 'Erreur lors de l\'inscription'
    }
  } catch (err) {

    error.value = err;
  }
}
</script>
