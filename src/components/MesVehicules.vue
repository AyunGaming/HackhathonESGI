<template>
  <div>
    <h1 class="text-2xl font-bold text-green-700 mb-6">Mes véhicules</h1>
    <button
      class="mb-6 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg shadow transition"
      @click="showModal = true"
    >
      + Nouveau véhicule
    </button>
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
        <button
          class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg shadow transition text-sm mt-4 md:mt-0"
          @click="viewDetails(vehicule)"
        >
          Voir Détails
        </button>
        <button
          class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg shadow transition text-sm mt-4 md:mt-0"
          @click="deleteVehicule(vehicule)"
        >
          Supprimer
        </button>
        <button
          class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow transition text-sm mt-4 md:mt-0"
          @click="editVehicule(vehicule)"
        >
          Modifier
        </button>
      </div>
    </div>
    <!-- Modal création véhicule -->
    <div v-if="showModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
      <form
        @submit.prevent="addVehicule"
        class="relative p-8 bg-white rounded-lg shadow-xl max-w-md mx-auto"
        style="width: 95%; max-width: 600px;"
      >
        <div class="flex justify-between items-center pb-3 border-b mb-4">
          <h3 class="text-xl font-semibold text-green-700">Ajouter un véhicule</h3>
          <button type="button" @click="closeModal" class="text-gray-500 hover:text-gray-700">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
          </button>
        </div>
        <div class="flex flex-col gap-2">
          <input v-model="form.brand" placeholder="Marque" class="border p-2 rounded" required />
          <input v-model="form.model" placeholder="Modèle" class="border p-2 rounded" required />
          <input v-model="form.registration" placeholder="Immatriculation" class="border p-2 rounded" required />
          <input v-model="form.vin" placeholder="VIN" class="border p-2 rounded" />
          <input v-model="form.circulation_date" type="date" placeholder="Date de circulation" class="border p-2 rounded" />
          <input v-model="form.mileage" type="number" placeholder="Kilométrage" class="border p-2 rounded" />
          <div class="flex items-center gap-2">
            <label>Conducteur différent ?</label>
            <input v-model="form.driver" type="checkbox" />
          </div>
          <!-- Si conducteur différent, afficher les champs, sinon les masquer -->
          <div v-if="form.driver" class="flex flex-col gap-2">
            <input v-model="form.driver_last_name" placeholder="Nom du conducteur" class="border p-2 rounded" />
            <input v-model="form.driver_first_name" placeholder="Prénom du conducteur" class="border p-2 rounded" />
            <input v-model="form.driver_phone" placeholder="Téléphone conducteur" class="border p-2 rounded" maxlength="10" />
          </div>
          <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded mt-2">Ajouter</button>
          <button type="button" @click="closeModal" class="bg-gray-300 py-2 rounded">Annuler</button>
        </div>
        <div v-if="error" class="text-red-600 mt-2">{{ error }}</div>
      </form>
    </div>
    <!-- Fin modal -->

    <!-- Modal détails véhicule -->
    <div v-if="showDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-semibold text-green-700">Détails du véhicule</h3>
          <button type="button" @click="closeDetailsModal" class="text-gray-500 hover:text-gray-700">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
          </button>
        </div>
        <div class="flex flex-col gap-4">
          <div><strong>Marque :</strong> {{ selectedVehicule.brand }}</div>
          <div><strong>Modèle :</strong> {{ selectedVehicule.model }}</div>
          <div><strong>Immatriculation :</strong> {{ selectedVehicule.registration }}</div>
          <div><strong>VIN :</strong> {{ selectedVehicule.vin }}</div>
          <div><strong>Date de circulation :</strong> {{ selectedVehicule.circulation_date }}</div>
          <div><strong>Kilométrage :</strong> {{ selectedVehicule.mileage }} km</div>
          <div v-if="selectedVehicule.driver_last_name" class="mt-4">
            <strong>Conducteur :</strong>
            <div>Nom : {{ selectedVehicule.driver_last_name }}</div>
            <div>Prénom : {{ selectedVehicule.driver_first_name }}</div>
            <div>Téléphone : {{ selectedVehicule.driver_phone }}</div>
          </div>
        </div>
      </div>
    </div>
    <!-- Fin modal détails -->

    <!-- Modal modification véhicule -->
    <div v-if="showEditModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
      <form
        @submit.prevent="updateVehicule"
        class="relative p-8 bg-white rounded-lg shadow-xl max-w-md mx-auto"
        style="width: 95%; max-width: 600px;"
      >
        <div class="flex justify-between items-center pb-3 border-b mb-4">
          <h3 class="text-xl font-semibold text-green-700">Modifier le véhicule</h3>
          <button type="button" @click="closeEditModal" class="text-gray-500 hover:text-gray-700">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
          </button>
        </div>
        <div class="flex flex-col gap-2">
          <input v-model="form.brand" placeholder="Marque" class="border p-2 rounded" required />
          <input v-model="form.model" placeholder="Modèle" class="border p-2 rounded" required />
          <input v-model="form.registration" placeholder="Immatriculation" class="border p-2 rounded" required />
          <input v-model="form.vin" placeholder="VIN" class="border p-2 rounded" />
          <input v-model="form.circulation_date" type="date" placeholder="Date de circulation" class="border p-2 rounded" />
          <input v-model="form.mileage" type="number" placeholder="Kilométrage" class="border p-2 rounded" />
          <div class="flex items-center gap-2">
            <label>Conducteur différent ?</label>
            <input v-model="form.driver" type="checkbox" />
          </div>
          <!-- Si conducteur différent, afficher les champs, sinon les masquer -->
          <div v-if="form.driver" class="flex flex-col gap-2">
            <input v-model="form.driver_last_name" placeholder="Nom du conducteur" class="border p-2 rounded" />
            <input v-model="form.driver_first_name" placeholder="Prénom du conducteur" class="border p-2 rounded" />
            <input v-model="form.driver_phone" placeholder="Téléphone conducteur" class="border p-2 rounded" maxlength="10" />
          </div>
          <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded mt-2">Modifier</button>
          <button type="button" @click="closeEditModal" class="bg-gray-300 py-2 rounded">Annuler</button>
        </div>
        <div v-if="error" class="text-red-600 mt-2">{{ error }}</div>
      </form>
    </div>
    <!-- Fin modal modification -->
  </div>
</template>

<script setup>
import { ref } from 'vue'
const props = defineProps({
  vehicules: { type: Array, default: () => [] },
  vehiculesLoading: Boolean,
  vehiculesError: String
})

const showModal = ref(false)
const showDetailsModal = ref(false)
const showEditModal = ref(false)
const selectedVehicule = ref(null)
const form = ref({
  brand: '',
  model: '',
  registration: '',
  vin: '',
  circulation_date: '',
  mileage: 0,
  driver: false,
  driver_last_name: '',
  driver_first_name: '',
  driver_phone: ''
})

const error = ref(null)
const client_id = window.localStorage.getItem('client_id')
const user = JSON.parse(window.localStorage.getItem('user') || '{}')

// Ajouter un véhicule
const addVehicule = async () => {
  error.value = null
  // Si conducteur n'est PAS différent, remplir avec infos user
  if (!form.value.driver) {
    form.value.driver_last_name = user.last_name || ''
    form.value.driver_first_name = user.first_name || ''
    form.value.driver_phone = user.phone || ''
  }
  try {
    const res = await fetch('http://localhost:8000/api/vehicules', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ ...form.value, client_id })
    })
    const data = await res.json()
    if (!res.ok) {
      error.value = data.error || 'Erreur lors de l\'ajout'
      return
    }
    window.location.reload()
  } catch (e) {
    error.value = e.message
  }
}

// Voir détails
const viewDetails = (vehicule) => {
  selectedVehicule.value = vehicule
  showDetailsModal.value = true
}

// Supprimer
const deleteVehicule = async (vehicule) => {
  if (!confirm('Voulez-vous vraiment supprimer ce véhicule ?')) return
  try {
    const res = await fetch(`http://localhost:8000/api/vehicules/${vehicule.id}`, {
      method: 'DELETE',
      credentials: 'include'
    })
    const data = await res.json()
    if (!res.ok) {
      error.value = data.error || 'Erreur lors de la suppression'
      return
    }
    window.location.reload()
  } catch (e) {
    error.value = e.message
  }
}

// Modifier
const editVehicule = (vehicule) => {
  selectedVehicule.value = vehicule
  Object.assign(form.value, { ...vehicule })
  showEditModal.value = true
}

const updateVehicule = async () => {
  error.value = null
  try {
    const res = await fetch(`http://localhost:8000/api/vehicules/${selectedVehicule.value.id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify(form.value)
    })
    const data = await res.json()
    if (!res.ok) {
      error.value = data.error || 'Erreur lors de la modification'
      return
    }
    window.location.reload()
  } catch (e) {
    error.value = e.message
  }
}

const closeModal = () => {
  showModal.value = false
  error.value = null
  Object.assign(form.value, {
    brand: '',
    model: '',
    registration: '',
    vin: '',
    circulation_date: '',
    mileage: 0,
    driver: false,
    driver_last_name: '',
    driver_first_name: '',
    driver_phone: ''
  })
}
const closeDetailsModal = () => {
  showDetailsModal.value = false
  selectedVehicule.value = null
}
const closeEditModal = () => {
  showEditModal.value = false
  error.value = null
}
</script>