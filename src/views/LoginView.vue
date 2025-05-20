<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-100 to-blue-300">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md">
      <h2 class="text-3xl font-bold text-blue-700 mb-6 text-center">Connexion</h2>
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
const login = () => {
  if (email.value && password.value) {
    router.push('/dashboard')
  }
}
</script>
