<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-100 to-blue-300">
    <div class="bg-white p-10 rounded-2xl shadow-xl w-full max-w-2xl">
      <h2 class="text-3xl font-bold text-blue-700 mb-8">Inscription client</h2>
      <form class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <select class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition">
            <option>Titre</option>
            <option>M.</option>
            <option>Mme</option>
            <option>Société</option>
          </select>
          <input type="text" placeholder="Nom" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
          <input type="text" placeholder="Prénom" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
          <input type="text" placeholder="Adresse" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
          <input type="text" placeholder="Téléphone" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
          <input type="email" placeholder="Email" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
        </div>
        <div class="flex items-center mt-2">
          <input id="conducteurDiff" v-model="conducteurDiff" type="checkbox" class="mr-2" />
          <label for="conducteurDiff" class="text-gray-700">Conducteur différent</label>
        </div>
        <div>
          <h3 class="font-semibold mb-2">Ajouter un véhicule</h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <input type="text" placeholder="Marque" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
            <input type="text" placeholder="Modèle" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
            <input type="text" placeholder="Immatriculation" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
            <input type="text" placeholder="VIN" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
            <input type="date" placeholder="Date mise en circulation" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
            <input type="text" placeholder="Kilométrage" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
          </div>
          <button type="button" class="bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-2 rounded-lg shadow transition">Ajouter véhicule</button>
        </div>
        <div v-if="conducteurDiff" class="mt-6 space-y-4">
          <h3 class="font-semibold">Informations du conducteur</h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="text" placeholder="Nom conducteur" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
            <input type="text" placeholder="Prénom conducteur" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
            <input type="email" placeholder="Email conducteur" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition" />
          </div>
        </div>
        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg shadow transition mt-4">Terminer inscription</button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
const conducteurDiff = ref(false)
</script>
