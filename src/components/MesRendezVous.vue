<template>
  <div>
    <h1 class="text-2xl font-bold text-blue-700 mb-6">Mes rendez-vous</h1>
    <div v-if="error" class="text-red-600 font-semibold mb-4">{{ error }}</div>
    <button
      @click="showModal = true"
      class="mb-6 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg shadow-md transition"
    >
      Prendre RDV
    </button>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="rdv in rdvs"
        :key="rdv.id"
        class="w-full max-w-full bg-white rounded-xl shadow p-4 flex flex-col gap-4 border-l-4 border-blue-500 box-border"
      >
        <div class="flex justify-between items-center">
          <span class="font-semibold text-lg flex items-center gap-2">
        <span>🚗⏳</span>
        {{ rdv.dealership }}
          </span>
          <span
        :class="[
          'px-2 py-1 rounded text-xs font-bold',
          'bg-yellow-100 text-yellow-700'
        ]"
          >{{ rdv.vehicule }}</span>
        </div>
        <div class="text-gray-500 flex items-center gap-2">
          <span>📅</span> {{ rdv.date }}
        </div>
        <div class="flex flex-wrap gap-2 mt-4 w-full">
          <button
            class="flex-1 min-w-[120px] sm:flex-none bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg shadow transition text-sm"
            @click="viewDetails(rdv)"
          >Voir détails</button>
          <button
            class="flex-1 min-w-[120px] sm:flex-none bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg shadow transition text-sm"
            @click="deleteRdv(rdv)"
          >Supprimer</button>
          <button
            class="flex-1 min-w-[120px] sm:flex-none bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow transition text-sm"
            @click="editRdv(rdv)"
          >Modifier</button>
        </div>
      </div>
    </div>

    <!-- Modal Prise de RDV (version formulaire) -->
    <div v-if="showModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
      <form
        @submit.prevent="completeBooking"
        class="relative p-8 bg-white rounded-lg shadow-xl max-w-md mx-auto"
        style="width: 95%; max-width: 600px;"
      >
        <div class="flex justify-between items-center pb-3 border-b mb-4">
          <h3 class="text-xl font-semibold text-blue-700">
  {{ isEditing ? 'Modifier le rendez-vous' : isReadOnly ? 'Lire le rendez-vous' : 'Prendre un nouveau rendez-vous' }}
</h3>
          <button type="button" @click="closeModal" class="text-gray-500 hover:text-gray-700">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
          </button>
        </div>

        <!-- Sélection véhicule -->
        <div class="mb-4">
          <label class="block font-semibold mb-1">Véhicule</label>
          <select v-model="selectedVehiculeId" :disabled="isEditing || isReadOnly" required class="w-full px-4 py-2 border rounded-lg focus:ring-blue-400 outline-none">
            <option v-for="v in vehicules" :key="v.id" :value="v.id">
              {{ v.brand }} {{ v.model }} ({{ v.registration }})
            </option>
          </select>
        </div>

        <!-- Sélection garage -->
        <div class="mb-4">
          <label class="block font-semibold mb-1">Garage</label>
          <select v-model="selectedDealershipId" :disabled="isEditing || isReadOnly" required class="w-full px-4 py-2 border rounded-lg focus:ring-blue-400 outline-none">
            <option v-for="g in garages" :key="g.id" :value="g.id">
              {{ g.name }}
            </option>
          </select>
        </div>

        <!-- Sélection services -->
        <div class="mb-4">
          <label class="block font-semibold mb-1">Services</label>
          <select v-model="selectedServiceIds" multiple :disabled="isEditing || isReadOnly" required class="w-full px-4 py-2 border rounded-lg focus:ring-blue-400 outline-none">
            <option v-for="s in services" :key="s.id" :value="s.id">
              {{ s.name }} <span v-if="s.price">({{ s.price.toFixed(2) }} €)</span>
            </option>
          </select>
          <small class="text-gray-500">Maintenez Ctrl (Windows) ou Cmd (Mac) pour sélectionner plusieurs services.</small>
        </div>

        <!-- Sélection date -->
        <div class="mb-4">
          <label class="block font-semibold mb-1">Date</label>
          <input type="date" v-model="selectedDateString" :min="minDate" :disabled="isReadOnly" required class="w-full px-4 py-2 border rounded-lg focus:ring-blue-400 outline-none" />
        </div>

        <!-- Sélection créneau -->
        <div class="mb-4">
          <label class="block font-semibold mb-1">Créneau horaire</label>
          <select v-model="selectedSlotTime" :disabled="isReadOnly" required class="w-full px-4 py-2 border rounded-lg focus:ring-blue-400 outline-none">
            <option value="">-- Choisir un créneau --</option>
            <option v-for="slot in allSlots" :key="slot.time" :value="slot.time" :disabled="!slot.available">
              {{ slot.time }} <span v-if="!slot.available">(Indisponible)</span>
            </option>
          </select>
        </div>

        <div v-if="bookingError" class="text-red-600 mb-2">{{ bookingError }}</div>

        <div class="flex justify-end gap-2 mt-6">
          <button type="button" @click="closeModal" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg transition">
            Fermer
          </button>
          <button
            v-if="!isReadOnly"
            type="submit"
            class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg shadow transition"
            :disabled="bookingLoading"
          >
            <span v-if="bookingLoading">Envoi...</span>
            <span v-else>Valider</span>
          </button>
        </div>
      </form>
    </div>
    <!-- Fin Modal -->

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'

const props = defineProps({
  rdvs: { type: Array, default: () => [] },
  rdvsLoading: Boolean,
  rdvsError: String
})

const loading = ref(true)
const error = ref(null)

const showModal = ref(false)
const selectedDate = ref(null)
const selectedSlot = ref(null)
const selectedVehiculeId = ref('')
const selectedDealershipId = ref('')
const selectedServiceIds = ref([])
const vehicules = ref([])
const garages = ref([])
const services = ref([])
const bookingLoading = ref(false)
const bookingError = ref(null)
const client_id = ref(null)
const user_id = ref(null) // Ajoute ceci en haut avec les autres refs
const selectedDateString = ref('');
const selectedSlotTime = ref('');
const minDate = new Date().toISOString().split('T')[0];
const emit = defineEmits(['refreshRdv'])

const isEditing = ref(false)
const editingRdvId = ref(null)
const isReadOnly = ref(false) // Nouvelle ref pour le mode lecture seule

// Chargement des listes dynamiques à l'ouverture de la modale
const fetchVehicules = async () => {
  try {
    const resUser = await fetch('http://localhost:8000/api/session/user', { credentials: 'include' });
    if (!resUser.ok) throw new Error('Erreur utilisateur');
    const data = await resUser.json();
    user_id.value = data.id;
    client_id.value = data.client;
    const res = await fetch('http://localhost:8000/api/vehicules/user/' + client_id.value, { credentials: 'include' });
    if (!res.ok) throw new Error('Erreur véhicules');
    vehicules.value = await res.json();
  } catch (err) {
    vehicules.value = [];
    bookingError.value = 'Erreur lors du chargement des véhicules';
    throw err;
  }
};

const fetchGarages = async () => {
  try {
    const res = await fetch('http://localhost:8000/api/dealerships', { credentials: 'include' });
    if (!res.ok) throw new Error('Erreur garages');
    garages.value = await res.json();
  } catch (err) {
    garages.value = [];
    bookingError.value = 'Erreur lors du chargement des garages';
    throw err;
  }
};

const fetchServices = async () => {
  try {
    const res = await fetch('http://localhost:8000/api/services', { credentials: 'include' });
    if (!res.ok) throw new Error('Erreur services');
    services.value = await res.json();
  } catch (err) {
    services.value = [];
    bookingError.value = 'Erreur lors du chargement des services';
    throw err;
  }
}

watch(showModal, async (val) => {
  if (val) {
    bookingError.value = null; // Reset l’erreur à chaque ouverture
    try {
      await Promise.all([fetchVehicules(), fetchGarages(), fetchServices()]);
    } catch (error) {
      console.error('Error loading data:', error);
      bookingError.value = 'Erreur lors du chargement des données. Veuillez réessayer.';
    }
  }
})

// Méthode pour supprimer un RDV
async function deleteRdv(rdv) {
  console.log('Suppression du rendez-vous :', rdv.id)
  if (confirm('Voulez-vous vraiment supprimer ce rendez-vous ?')) {
    try {
      const res = await fetch('http://localhost:8000/api/appointements/'+ rdv.id, {
        method: 'DELETE',
        credentials: 'include'
      });
      if (!res.ok) throw new Error('Erreur lors de la suppression');
      window.location.reload()
    } catch (e) {
      alert(e.message);
    }
  }
}

function editRdv(rdv) {
  isEditing.value = true
  editingRdvId.value = rdv.id
  // Utilise Number() pour matcher le type des options
  selectedVehiculeId.value = rdv.vehicule_id != null ? Number(rdv.vehicule_id) : ''
  selectedDealershipId.value = rdv.dealership_id != null ? Number(rdv.dealership_id) : ''
  selectedServiceIds.value = Array.isArray(rdv.service_ids)
    ? rdv.service_ids.map(id => Number(id))
    : []
  selectedDateString.value = rdv.date || ''
  selectedSlotTime.value = rdv.time || ''
  showModal.value = true
}

const completeBooking = async () => {
  if (isEditing.value) {
    // En édition, on ne vérifie que date et créneau
    if (!selectedDateString.value || !selectedSlotTime.value) {
      bookingError.value = 'Veuillez remplir la date et le créneau horaire.'
      return
    }
  } else {
    // En création, on vérifie tout
    if (
      !selectedVehiculeId.value ||
      !selectedDealershipId.value ||
      !selectedServiceIds.value.length ||
      !selectedDateString.value ||
      !selectedSlotTime.value
    ) {
      bookingError.value = 'Veuillez remplir tous les champs.'
      return
    }
  }
  bookingLoading.value = true
  bookingError.value = null
  try {
    let url = 'http://localhost:8000/api/appointements'
    let method = 'POST'
    let body = {
      vehicule_id: selectedVehiculeId.value,
      dealership_id: selectedDealershipId.value,
      service_ids: selectedServiceIds.value,
      date: selectedDateString.value,
      time: selectedSlotTime.value
    }
    if (isEditing.value && editingRdvId.value) {
      url += `/${editingRdvId.value}`
      method = 'PUT'
      // N'envoyer que date et time pour l'update
      body = {
        date: selectedDateString.value,
        time: selectedSlotTime.value
      }
    }
    const res = await fetch(url, {
      method,
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    })
    const data = await res.json()
    if (!res.ok) throw new Error(data.error || 'Erreur lors de la prise de rendez-vous')
    closeModal()
    window.location.reload()
  } catch (e) {
    bookingError.value = e.message
  } finally {
    bookingLoading.value = false
  }
}

const closeModal = () => {
  showModal.value = false
  isEditing.value = false
  isReadOnly.value = false
  editingRdvId.value = null
  selectedVehiculeId.value = ''
  selectedDealershipId.value = ''
  selectedServiceIds.value = []
  selectedDateString.value = ''
  selectedSlotTime.value = ''
  bookingError.value = null
}

const viewDetails = (rdv) => {
  isReadOnly.value = true
  isEditing.value = false
  editingRdvId.value = rdv.id
  selectedVehiculeId.value = rdv.vehicule_id != null ? Number(rdv.vehicule_id) : ''
  selectedDealershipId.value = rdv.dealership_id != null ? Number(rdv.dealership_id) : ''
  selectedServiceIds.value = Array.isArray(rdv.service_ids)
    ? rdv.service_ids.map(id => Number(id))
    : []
  selectedDateString.value = rdv.date || ''
  console.log(rdv.date)
  selectedSlotTime.value = rdv.time || ''
  showModal.value = true
}



const allSlots = computed(() => {
  const slots = [];
  for (let hour = 9; hour <= 18; hour++) {
    slots.push({
      time: `${hour}:00`,
      available: true, // Par défaut disponible
    });
  }
  return slots;
});
</script>