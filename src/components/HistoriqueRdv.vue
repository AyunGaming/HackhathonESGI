<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-700 mb-6">Historique des RDV</h1>
    <input
      v-model="search"
      type="text"
      placeholder="Rechercher par garage, type ou statut..."
      class="mb-4 px-4 py-2 border rounded-lg w-full max-w-xs focus:ring-2 focus:ring-blue-400"
    />
    <div class="overflow-x-auto">
      <table class="min-w-full bg-white rounded-xl shadow">
        <thead>
          <tr class="bg-gray-100 text-gray-700">
            <th class="py-2 px-4 text-left">Date</th>
            <th class="py-2 px-4 text-left">Garage</th>
            <th class="py-2 px-4 text-left">Type</th>
            <th class="py-2 px-4 text-left">Statut</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="h in filteredHistorique"
            :key="h.id"
            class="hover:bg-gray-50 transition"
          >
            <td class="py-2 px-4 flex items-center gap-2">
              <span>📅</span> {{ h.date }}
            </td>
            <td class="py-2 px-4">{{ h.garage }}</td>
            <td class="py-2 px-4 flex items-center gap-2">
              <span>🔧</span> {{ h.type }}
            </td>
            <td class="py-2 px-4">
              <span
                :class="[
                  'px-2 py-1 rounded text-xs font-bold',
                  h.status === 'Confirmé' ? 'bg-green-100 text-green-700' :
                  h.status === 'Annulé' ? 'bg-red-100 text-red-700' :
                  'bg-yellow-100 text-yellow-700'
                ]"
              >{{ h.status }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
const search = ref('')
const historique = [
  { id: 1, date: '10/05/2024', garage: 'Garage Peugeot', type: 'Révision', status: 'Annulé' },
  { id: 2, date: '12/04/2024', garage: 'Garage Renault', type: 'Contrôle technique', status: 'Confirmé' },
  { id: 3, date: '20/03/2024', garage: 'Garage Citroën', type: 'Pneus', status: 'En attente' }
]
const filteredHistorique = computed(() =>
  historique.filter(h =>
    h.garage.toLowerCase().includes(search.value.toLowerCase()) ||
    h.type.toLowerCase().includes(search.value.toLowerCase()) ||
    h.status.toLowerCase().includes(search.value.toLowerCase())
  )
)
</script> 