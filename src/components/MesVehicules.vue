<template>
  <div>
    <h1 class="text-2xl font-bold text-green-700 mb-6">Mes véhicules</h1>
    <div v-if="error" class="text-red-600 font-semibold mb-4">{{ error }}</div>
    <div class="flex flex-col gap-6">
      <div
        v-for="vehicule in vehicules"
        :key="vehicule.id"
        class="bg-white rounded-xl shadow flex flex-col md:flex-row items-center gap-6 p-6 border-l-4 border-green-500 hover:scale-[1.02] transition"
      >
        <div class="flex-shrink-0 flex items-center justify-center w-20 h-20 bg-green-100 rounded-full">
          <span class="text-4xl">🚗</span>
        </div>
        <div class="flex-1 w-full">
          <div class="flex flex-col md:flex-row md:items-center md:gap-6">
            <div class="font-semibold text-lg text-green-700">{{ vehicule.brand }} {{ vehicule.model }}</div>
            <div class="text-gray-500 text-sm">Immatriculation : <span class="font-mono">{{ vehicule.registration }}</span></div>
          </div>
          <div class="flex flex-wrap gap-4 mt-2 text-gray-400 text-sm">
            <div>Date de circulation : <span class="text-gray-700">{{ vehicule.circulation_date }}</span></div>
            <div>VIN : <span class="text-gray-700">{{ vehicule.vin }}</span></div>
            <div>Kilométrage : <span class="text-gray-700">{{ vehicule.mileage }} km</span></div>
          </div>
        </div>
        <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg shadow transition text-sm mt-4 md:mt-0">
          Voir fiche
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const vehicules = ref([])
const loading = ref(true)
const error = ref(null)
const client_id = ref(null)

onMounted(async () => {
  try {
    const res = await fetch('http://localhost:8000/api/session/user', { credentials: 'include' });
    const data = await res.json();
    client_id.value = data.client;

    const vehiculesRes = await fetch('http://localhost:8000/api/vehicules/user/' + client_id.value, { credentials: 'include' })
    const vehiculesData = await vehiculesRes.json()
    console.log('Réponse API véhicules:', vehiculesData);
    if (!vehiculesRes.ok) {
      // Affiche le message d'erreur retourné par l'API et l'ID utilisé
      error.value = `Erreur API: ${vehiculesData.error} (ID envoyé: ${client_id.value})`
      return
    }
    vehicules.value = vehiculesData
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
})
</script>