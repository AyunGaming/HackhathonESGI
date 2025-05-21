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
        class="bg-white rounded-xl shadow p-6 flex flex-col gap-2 border-l-4"
        :class="{
          'border-blue-500': rdv.status === 'Confirmé',
          'border-yellow-500': rdv.status === 'En attente',
          'border-red-500': rdv.status === 'Annulé'
        }"
      >
        <div class="flex justify-between items-center">
          <span class="font-semibold text-lg flex items-center gap-2">
            <span v-if="rdv.status === 'Confirmé'">✅</span>
            <span v-else-if="rdv.status === 'En attente'">⏳</span>
            <span v-else>❌</span>
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
        <button
          class="mt-2 text-blue-600 hover:underline text-sm self-end"
        >Voir détails</button>
      </div>
    </div>

    <!-- Modal Prise de RDV -->
    <div v-if="showModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
      <div class="relative p-8 bg-white rounded-lg shadow-xl max-w-md mx-auto" style="width: 95%; max-width: 600px;">
        <div class="flex justify-between items-center pb-3 border-b mb-4">
          <h3 class="text-xl font-semibold text-blue-700">Prendre un nouveau rendez-vous</h3>
          <button @click="closeModal" class="text-gray-500 hover:text-gray-700">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
          </button>
        </div>

        <!-- Contenu de la modale par étape -->
        <div v-if="currentStep === 1">
          <h4 class="text-lg font-semibold mb-4">Étape 1 : Identifier votre véhicule</h4>
          <p class="text-gray-600 mb-4">Identifiez votre véhicule parmi les deux critères ci-dessous.</p>
          <input type="text" placeholder="Immatriculation" class="w-full px-4 py-2 border rounded-lg mb-4 focus:ring-blue-400 outline-none">
          <input type="text" placeholder="VIN" class="w-full px-4 py-2 border rounded-lg focus:ring-blue-400 outline-none">
        </div>

        <div v-else-if="currentStep === 2">
          <h4 class="text-lg font-semibold mb-4">Étape 2 : Choisir un réparateur</h4>
          <p class="text-gray-600 mb-4">Sélectionnez votre concessionnaire ou réparateur agréé.</p>
          <select class="w-full px-4 py-2 border rounded-lg focus:ring-blue-400 outline-none">
            <option value="">-- Choisir un garage --</option>
            <option>Garage A</option>
            <option>Garage B</option>
          </select>
        </div>

        <div v-else-if="currentStep === 3">
          <h4 class="text-lg font-semibold mb-4">Étape 3 : Sélectionner les opérations</h4>
          <p class="text-gray-600 mb-4">Choisissez les services de maintenance souhaités.</p>
          <div class="space-y-3">
            <div class="flex items-center justify-between border p-3 rounded-lg">
              <label class="flex items-center gap-2">
                <input type="checkbox" class="form-checkbox">
                <span>Service Huile Moteur</span>
              </label>
              <span class="font-semibold">225,00 €</span>
            </div>
             <div class="flex items-center justify-between border p-3 rounded-lg">
              <label class="flex items-center gap-2">
                <input type="checkbox" class="form-checkbox">
                <span>Service Plaquettes de frein Avant</span>
              </label>
              <span class="font-semibold">345,00 €</span>
            </div>
             <div class="flex items-center justify-between border p-3 rounded-lg">
              <label class="flex items-center gap-2">
                <input type="checkbox" class="form-checkbox">
                <span>Service Liquide de Frein</span>
              </label>
              <span class="font-semibold">66,00 €</span>
            </div>
            <!-- ... autres services ... -->
          </div>
        </div>

        <div v-else-if="currentStep === 4">
          <h4 class="text-lg font-semibold mb-4">Étape 4 : Choisir un créneau</h4>
          <p class="text-gray-600 mb-4">Sélectionnez une date et une heure pour votre rendez-vous.</p>

          <!-- Sélection des jours -->
          <div class="flex space-x-2 overflow-x-auto mb-4 pb-2 border-b">
            <button
              v-for="day in next7Days"
              :key="day.dateString"
              @click="selectDate(day)"
              :class="[
                'flex flex-col items-center px-4 py-2 rounded-lg border transition',
                selectedDate && selectedDate.dateString === day.dateString ? 'bg-blue-500 text-white border-blue-600' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              ]"
            >
              <span class="font-semibold">{{ day.dayOfWeek }}</span>
              <span class="text-sm">{{ day.dayOfMonth }}</span>
            </button>
          </div>

          <!-- Créneaux horaires -->
          <div v-if="selectedDate" class="space-y-3 max-h-60 overflow-y-auto">
            <div v-if="availableSlots.length === 0" class="text-center text-gray-500">Aucun créneau disponible pour ce jour.</div>
            <button
              v-for="slot in availableSlots"
              :key="slot.time"
              @click="selectSlot(slot)"
              :disabled="!slot.available"
              :class="[
                'w-full text-left px-4 py-3 rounded-lg transition flex justify-between items-center',
                slot.available ? (selectedSlot && selectedSlot.time === slot.time ? 'bg-blue-500 text-white border-blue-600' : 'bg-blue-100 text-blue-700 hover:bg-blue-200') : 'bg-gray-200 text-gray-500 cursor-not-allowed opacity-75'
              ]"
            >
              <span>{{ slot.time }}</span>
              <span v-if="!slot.available" class="text-red-600 text-xs font-semibold">Indisponible</span>
            </button>
          </div>
           <div v-else class="text-center text-gray-500">Sélectionnez une date pour voir les créneaux.</div>

        </div>

        <!-- Navigation Modale -->
        <div class="flex justify-between mt-6">
          <button
            v-if="currentStep > 1"
            @click="prevStep"
            class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg transition"
          >
            Précédent
          </button>
          <div v-else></div> <!-- Placeholder pour alignement -->
          <button
            v-if="currentStep < 4"
            @click="nextStep"
            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg shadow transition"
          >
            Suivant
          </button>
          <button
            v-else
            @click="completeBooking"
            class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg shadow transition"
          >
            Terminer
          </button>
        </div>

      </div>
    </div>
    <!-- Fin Modal -->

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const rdvs = ref([])
const loading = ref(true)
const error = ref(null)
const user_id = ref(null)

onMounted(async () => {
  try {
    const res = await fetch('http://localhost:8000/api/session/user', { credentials: 'include' });
    const data = await res.json();
    user_id.value = data.id;

    const rdvsRes = await fetch('http://localhost:8000/api/appointements/user/' + user_id.value, { credentials: 'include' })
    const rdvsData = await rdvsRes.json()
    console.log('Réponse API rdvsData:', rdvsData);
    if (!rdvsRes.ok) {
      // Affiche le message d'erreur retourné par l'API et l'ID utilisé
      error.value = `Erreur API: ${rdvsData.error} (ID envoyé: ${client_id.value})`
      return
    }
    rdvs.value = rdvsData
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
})

onMounted(async () => {
  try {
    loading.value = true
    
    }catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
})

const showModal = ref(false)
const currentStep = ref(1)
const selectedDate = ref(null)
const selectedSlot = ref(null)

// Génère les 7 prochains jours pour la sélection
const next7Days = computed(() => {
  const days = [];
  const today = new Date();
  const optionsDayOfWeek = { weekday: 'short' };
  const optionsDayOfMonth = { day: 'numeric' };

  for (let i = 0; i < 7; i++) {
    const date = new Date(today);
    date.setDate(today.getDate() + i);
    days.push({
      dateObject: date,
      dateString: date.toISOString().split('T')[0], // YYYY-MM-DD
      dayOfWeek: date.toLocaleDateString('fr-FR', optionsDayOfWeek),
      dayOfMonth: date.toLocaleDateString('fr-FR', optionsDayOfMonth),
    });
  }
  return days;
});

// Génère les créneaux horaires pour une journée (9h-18h, par heure)
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

// Simulate la disponibilité des créneaux (un créneau est indisponible pour test)
const availableSlots = computed(() => {
  // Pour l'exemple, on rend le créneau de 14h le premier jour (aujourd'hui) indisponible
   if (selectedDate.value && selectedDate.value.dateString === next7Days.value[0].dateString) {
      return allSlots.value.map(slot => ({
          ...slot,
          available: slot.time !== '14:00'
      }));
   }
   // Pour les autres jours, tous les créneaux sont disponibles dans cet exemple
   return allSlots.value.map(slot => ({ ...slot, available: true }));
});

const selectDate = (day) => {
  selectedDate.value = day;
  selectedSlot.value = null; // Réinitialise le créneau quand la date change
};

const selectSlot = (slot) => {
    if (slot.available) {
        selectedSlot.value = slot;
        console.log(`Créneau sélectionné: ${selectedDate.value.dateString} à ${selectedSlot.value.time}`);
        // Tu peux avancer automatiquement à l'étape suivante ici si tu veux
        // nextStep();
    }
};

const nextStep = () => {
  if (currentStep.value === 4) {
     if (!selectedDate.value || !selectedSlot.value) {
        alert('Veuillez sélectionner une date et un créneau.');
        return; // Empêche de passer à l'étape suivante si rien n'est sélectionné
     }
  }
  if (currentStep.value < 4) {
    currentStep.value++;
  }
}

const prevStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

const closeModal = () => {
  showModal.value = false
  currentStep.value = 1 // Réinitialise l'étape
  selectedDate.value = null // Réinitialise la date
  selectedSlot.value = null // Réinitialise le créneau
}

const completeBooking = () => {
  if (selectedDate.value && selectedSlot.value) {
    alert(`Rendez-vous demandé le ${selectedDate.value.dateString} à ${selectedSlot.value.time} (Logique à implémenter)`);
    closeModal();
  } else {
    alert('Veuillez sélectionner une date et un créneau.');
  }
}
</script>