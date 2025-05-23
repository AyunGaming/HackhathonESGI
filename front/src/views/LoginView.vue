<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-100 to-blue-300">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md">
      <h2 class="text-3xl font-bold text-blue-700 mb-6 text-center">Connexion</h2>
      <p v-if="error" class="text-red-500 text-sm text-center mt-2">{{ error }}</p>
      <form @submit.prevent="login" class="space-y-5">
        <div>
          <label class="block text-gray-700 mb-1" for="email">Email</label>
          <input v-model="email" type="email" id="email" required
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
        </div>
        <div>
          <label class="block text-gray-700 mb-1" for="password">Mot de passe</label>
          <input v-model="password" type="password" id="password" required
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
        </div>
        <button type="submit"
          class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg shadow transition">Se connecter</button>
        <p class="text-center text-sm mt-2">
          Pas de compte ?
          <router-link to="/register" class="text-blue-600 hover:underline">Inscription</router-link>
        </p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
const email = ref('')
const password = ref('')
const router = useRouter()
const error = ref('')

const login = async () => {
  error.value = ''
  try {
    const response = await fetch('http://localhost:8000/api/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include', // important pour la session
      body: JSON.stringify({
        email: email.value,
        password: password.value
      })
    })
    const data = await response.json()
    if (response.ok && data.success) {
      // Tu peux stocker les infos user ici si besoin
      router.push('/dashboard')
    } else {
      error.value = data.error || 'Erreur de connexion'
    }
  } catch (e) {
    error.value = 'Erreur réseau'
  }
}
</script>
