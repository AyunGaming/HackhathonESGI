<template>
  <div>
    <h1 class="text-2xl font-bold text-green-700 mb-6">Mes véhicules</h1>
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
            <div class="font-semibold text-lg text-green-700">{{ vehicule.marque }} {{ vehicule.modele }}</div>
            <div class="text-gray-500 text-sm">Immatriculation : <span class="font-mono">{{ vehicule.immatriculation }}</span></div>
          </div>
          <div class="flex flex-wrap gap-4 mt-2 text-gray-400 text-sm">
            <div>Année : <span class="text-gray-700">{{ vehicule.annee }}</span></div>
            <div>Énergie : <span class="text-gray-700">{{ vehicule.energie }}</span></div>
            <div>Kilométrage : <span class="text-gray-700">{{ vehicule.kilometrage }} km</span></div>
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
const vehicules = [
  {
    id: 1,
    marque: 'Peugeot',
    modele: '208',
    immatriculation: 'AA-123-BB',
    annee: 2020,
    energie: 'Essence',
    kilometrage: 45000
  },
  {
    id: 2,
    marque: 'Renault',
    modele: 'Clio',
    immatriculation: 'BB-456-CC',
    annee: 2019,
    energie: 'Diesel',
    kilometrage: 67000
  },
  {
    id: 3,
    marque: 'Citroën',
    modele: 'C3',
    immatriculation: 'CC-789-DD',
    annee: 2021,
    energie: 'Hybride',
    kilometrage: 22000
  }
]
</script> 