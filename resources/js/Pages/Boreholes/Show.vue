<template>
  <AppLayout title="Dashboard">
    <!-- Restul codului existent -->

    <div v-if="isEntireBoreholeUnclassified" class="bg-amber-100 border-l-4 border-amber-500 text-amber-700 p-4 mb-4">
      <p class="font-bold">Atenție</p>
      <p>Nu s-a putut genera o stratificație relevantă deoarece nicio probă nu este identificata. Vă rugăm să identificați cel puțin o probă înainte de a genera stratificația.</p>
    </div>
    <div v-else class="max-w-lg">
      <div class="">
        <!-- <div class="w-1/3"> -->
        <h3 class="text-lg font-medium mb-4">Coloana litologică</h3>
        <div v-if="granulometricStrata && granulometricStrata.length > 0" class="granulometric-controls">
          <div class="granulo-info">
            <small> ✅ {{ granulometricStrata.length }} straturi granulometrice disponibile </small>
          </div>
        </div>
        <StratigraphicColumn
          :strata="activeLayers"
          :height="600"
          :width="600"
          :granulometric-strata="granulometricStrata"
          :show-granulometric-overlay="showGranulometricOverlay"
          @boundary-moved="handleBoundaryMoved"
          @toggle-granulometric-overlay="toggleGranulometricOverlay"
        />
        <!-- <StratigraphicColumn :strata="activeLayers" :height="600" :width="300" @boundary-moved="handleBoundaryMoved" ref="stratColumn" /> -->
      </div>
      <!-- Afișează banner de preview când este activat -->
      <div v-if="showPreview" class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-4">
        <p class="font-bold">Preview modificări</p>
        <p>Aceasta este o previzualizare a stratificației procesate. Straturile adiacente de același tip au fost combinate și suprapunerile au fost rezolvate.</p>
        <div class="mt-2 flex space-x-3">
          <button @click="cancelPreview" class="text-sm text-blue-700 underline">Anulează</button>
          <button @click="applyProcessingAndSave" class="text-sm font-bold">Aplică și salvează</button>
        </div>
      </div>

      <div class="mt-4 grid grid-cols-1 gap-y-2 sm:grid-cols-3 sm:gap-x-4">
        <!-- Folosim displayLayers care afișează fie straturile originale, fie cele procesate -->
        <template v-for="(layer, index) in displayLayers" :key="index">
          <input
            type="text"
            :value="layer.depth_from"
            disabled
            :class="[layer.depth_to - layer.depth_from <= 0 ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500']"
            class="block w-full rounded-md shadow-sm sm:text-sm disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500 disabled:ring-gray-200"
          />
          <input
            v-model.number="layer.depth_to"
            @change="onDepthToChange(index)"
            @blur="$forceUpdate()"
            type="text"
            :disabled="showPreview"
            :class="[layer.depth_to - layer.depth_from <= 0 ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500']"
            class="block w-full rounded-md shadow-sm sm:text-sm"
          />
          <input
            v-model="layer.soil_type"
            @change="onDepthToChange(index)"
            @blur="$forceUpdate()"
            type="text"
            :disabled="showPreview"
            :class="[layer.depth_to - layer.depth_from <= 0 ? 'border-red-500 text-red-500' : 'border-gray-300']"
            class="block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          />
        </template>
      </div>

      <button
        type="button"
        @click.prevent="saveStratification"
        :disabled="isProcessing || showPreview"
        class="mt-4 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:bg-indigo-300"
      >
        {{ isProcessing ? "Se salvează..." : "Save" }}
      </button>
      <a @click.prevent="stratigraphy" class="group flex flex-col bg-white border shadow-sm rounded-xl hover:shadow-md focus:outline-none focus:shadow-md transition dark:bg-neutral-900 dark:border-neutral-800" href="#">
        <div class="p-4 md:p-5">
          <div class="flex justify-between items-center gap-x-3">
            <div class="grow">
              <h3 class="group-hover:text-blue-600 font-semibold text-gray-800 dark:group-hover:text-neutral-400 dark:text-neutral-200">Genereaza stratificatie</h3>
              <p class="text-sm text-gray-500 dark:text-neutral-500">Lorem</p>
            </div>
            <div>
              <svg
                class="shrink-0 size-5 text-gray-800 dark:text-neutral-200"
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <path d="m9 18 6-6-6-6" />
              </svg>
            </div>
          </div>
        </div>
      </a>
    </div>

    <!-- Restul codului existent -->
  </AppLayout>
</template>
<script>
import { ref, computed } from "vue"
import { Link, Head, router } from "@inertiajs/vue3"
import AppLayout from "@/Layouts/AppLayout.vue"
import StratificationService from "@/services/StratificationService.js"
import StratigraphicColumn from "@/Components/StratigraphicColumn.vue"
export default {
  components: {
    Head,
    AppLayout,
    Link,
    StratigraphicColumn,
  },
  props: ["borehole", "strataData", "granulometricStrata"],

  data() {
    return {
      layers: [],
      isProcessing: false,
      showPreview: false, // Determină dacă afișăm stratificația procesată ca preview
      previewLayers: [], // Straturile procesate pentru preview
      showGranulometricOverlay: true,
      // granulometricStrata: [], // Stratigrafia din clasificarea granulometrică
      // showGranulometricOverlay: false, // Toggle pentru afișarea overlay-ului
      // loadingGranulometric: false, // Loading state pentru cererea granulometrică
    }
  },

  mounted() {
    this.updateLayers(this.strataData)
  },

  computed: {
    hasGranulometricData() {
      return this.granulometricStrata && this.granulometricStrata.length > 0
    },
    canShowOverlay() {
      return this.hasGranulometricData && this.showGranulometricOverlay
    },
    activeLayers() {
      if (this.showPreview) {
        return this.strataData
      }
      return this.layers.filter((layer) => layer.isValid)
    },

    isEntireBoreholeUnclassified() {
      return this.strataData.length === 1 && this.strataData[0].soil_type === null
    },

    // Straturile care vor fi afișate în interfață
    displayLayers() {
      return this.showPreview ? this.previewLayers : this.activeLayers
    },
  },

  watch: {
    strataData: {
      handler(newVal) {
        this.updateLayers(newVal)
      },
      immediate: true,
      deep: true,
    },
    // activeLayers: {
    //   handler(newLayers) {
    //     // Folosim un debounce pentru a limita frecvența actualizărilor
    //     clearTimeout(this.debounceTimeout)
    //     this.debounceTimeout = setTimeout(() => {
    //       // Procesăm straturile și actualizăm preview-ul
    //       this.previewLayers = StratificationService.processStrata(newLayers)
    //       this.showPreview = true
    //     }, 300)
    //   },
    //   deep: true, // Important: observă schimbările în proprietățile adânci ale obiectelor
    // },
  },

  methods: {
    toggleGranulometricOverlay() {
      if (!this.hasGranulometricData) {
        console.warn("Nu există date granulometrice pentru overlay")
        return
      }

      this.showGranulometricOverlay = !this.showGranulometricOverlay
      console.log("🔄 Toggle overlay granulometric:", this.showGranulometricOverlay)
    },
    stratigraphy() {
      router.post(
        route("borehole.stratigraphy", { borehole: this.borehole.id }),
        {},
        {
          preserveScroll: true,
          onSuccess: () => {
            // Poți adăuga acțiuni suplimentare aici, de exemplu, afișarea unui mesaj de succes
          },
          onError: (errors) => {
            // Gestionarea erorilor
          },
        }
      )
    },
    // Înlocuiește metoda handleBoundaryMoved din Show.vue

    // handleBoundaryMoved(data) {
    //   // Verificăm dacă primim datele în noul format
    //   if (data.primaryChange && data.affectedLayers) {
    //     // Noul format cu modificări multiple
    //     this.handleComplexBoundaryMove(data)
    //   } else {
    //     // Formatul vechi - pentru compatibilitate
    //     this.handleSimpleBoundaryMove(data)
    //   }
    // },

    // handleComplexBoundaryMove(data) {
    //   const { primaryChange, affectedLayers, newDepth } = data

    //   // Creăm o copie a straturilor pentru a asigura reactivitatea
    //   const updatedLayers = JSON.parse(JSON.stringify(this.layers))

    //   // Aplicăm modificarea principală
    //   updatedLayers[primaryChange.index] = {
    //     ...updatedLayers[primaryChange.index],
    //     depth_to: primaryChange.layer.depth_to,
    //     isValid: primaryChange.layer.isValid !== undefined ? primaryChange.layer.isValid : true,
    //   }

    //   // Aplicăm toate modificările afectate
    //   affectedLayers.forEach((affected) => {
    //     updatedLayers[affected.index] = {
    //       ...updatedLayers[affected.index],
    //       depth_from: affected.layer.depth_from,
    //       depth_to: affected.layer.depth_to,
    //       isValid: affected.layer.isValid !== undefined ? affected.layer.isValid : true,
    //     }
    //   })

    //   // Actualizăm modelul de date
    //   this.layers = updatedLayers

    //   // Forțăm actualizarea coloanei stratigrafice
    //   this.$nextTick(() => {
    //     if (this.$refs.stratColumn) {
    //       this.$refs.stratColumn.updateVisualization()
    //     }
    //   })

    //   // Generăm preview-ul
    //   this.generatePreview()
    // },

    handleBoundaryMoved(data) {
      // console.log("handleBoundaryMoved apelat cu:", data) // DEBUG

      // Verificăm tipul de modificare
      if (data.type === "complex") {
        this.handleComplexBoundaryMove(data)
      } else {
        // Pentru compatibilitate cu versiunea simplă
        this.handleSimpleBoundaryMove(data)
      }
    },

    handleComplexBoundaryMove(data) {
      // console.log("Gestionând modificare complexă:", data) // DEBUG

      const { primaryChange, affectedLayers, newDepth, direction } = data

      // console.log(
      //   "Layers înainte de modificare:",
      //   JSON.stringify(
      //     this.layers.map((l) => ({
      //       id: l.id,
      //       soil_type: l.soil_type,
      //       depth_from: l.depth_from,
      //       depth_to: l.depth_to,
      //       isValid: l.isValid,
      //     }))
      //   )
      // ) // DEBUG

      // Creăm o copie completă a straturilor pentru a asigura reactivitatea
      const updatedLayers = JSON.parse(JSON.stringify(this.layers))

      // IMPORTANT: Mapez indicii din sortedStrata către this.layers
      const createLayerIndexMap = () => {
        const map = new Map()
        let sortedIndex = 0

        // Construiesc mapping-ul ignorând straturile invalide
        this.layers.forEach((layer, layerIndex) => {
          const isValidFlag = layer.isValid !== false
          const thickness = parseFloat(layer.depth_to) - parseFloat(layer.depth_from)
          const hasValidThickness = thickness > 0.01

          if (isValidFlag && hasValidThickness) {
            map.set(sortedIndex, layerIndex)
            // console.log(`📍 Mapping: sortedIndex ${sortedIndex} → layerIndex ${layerIndex} (${layer.soil_type})`) // DEBUG
            sortedIndex++
          } else {
            // console.log(`🚫 Ignorat mapping pentru stratul invalid ${layerIndex} (${layer.soil_type})`) // DEBUG
          }
        })

        return map
      }

      const indexMap = createLayerIndexMap()
      // console.log("📊 Index mapping creat:", indexMap) // DEBUG

      // Aplicăm modificările cu maparea corectă a indicilor
      affectedLayers.forEach((affected) => {
        const sortedIndex = affected.index
        const layerData = affected.layer

        // Găsesc indexul real în this.layers
        const realLayerIndex = indexMap.get(sortedIndex)

        if (realLayerIndex === undefined) {
          // console.log(`⚠️ AVERTISMENT: Nu pot găsi indexul real pentru sortedIndex ${sortedIndex}`) // DEBUG
          return
        }

        // console.log(`🔄 Actualizând stratul real ${realLayerIndex} (sorted: ${sortedIndex}):`, layerData.soil_type) // DEBUG

        // Verific dacă stratul era deja invalid - NU îl "reînviu"
        const existingLayer = updatedLayers[realLayerIndex]
        if (existingLayer.isValid === false && layerData.isValid !== false) {
          // console.log(`🛑 Opresc reînvierea stratului invalid ${realLayerIndex} (${existingLayer.soil_type})`) // DEBUG
          return
        }

        // Actualizez stratul complet
        updatedLayers[realLayerIndex] = {
          ...updatedLayers[realLayerIndex],
          depth_from: layerData.depth_from,
          depth_to: layerData.depth_to,
          isValid: layerData.isValid !== undefined ? layerData.isValid : updatedLayers[realLayerIndex].isValid,
        }

        // console.log(`✅ Stratul ${realLayerIndex} actualizat:`, {
        //   soil_type: updatedLayers[realLayerIndex].soil_type,
        //   depth_from: updatedLayers[realLayerIndex].depth_from,
        //   depth_to: updatedLayers[realLayerIndex].depth_to,
        //   isValid: updatedLayers[realLayerIndex].isValid,
        // }) // DEBUG
      })

      // Validare finală - verificăm din nou grosimile
      updatedLayers.forEach((layer, index) => {
        const thickness = parseFloat(layer.depth_to) - parseFloat(layer.depth_from)

        if (thickness <= 0) {
          // console.log(`🚨 VALIDARE: Stratul ${index} are grosime negativă/zero: ${thickness}m - invalidat`) // DEBUG
          layer.isValid = false
        } else if (layer.isValid !== false) {
          layer.isValid = true
        }
      })

      // console.log(
      //   "Layers după modificare:",
      //   JSON.stringify(
      //     updatedLayers.map((l) => ({
      //       id: l.id,
      //       soil_type: l.soil_type,
      //       depth_from: l.depth_from,
      //       depth_to: l.depth_to,
      //       isValid: l.isValid,
      //     }))
      //   )
      // ) // DEBUG

      // Actualizăm modelul de date
      this.layers = updatedLayers

      // Forțăm actualizarea coloanei stratigrafice
      this.$nextTick(() => {
        // console.log("NextTick - actualizând vizualizarea complexă") // DEBUG
        if (this.$refs.stratColumn) {
          this.$refs.stratColumn.forceRedraw() // Folosim forceRedraw în loc de updateVisualization
          // console.log("forceRedraw apelat") // DEBUG
        } else {
          // console.log("EROARE: stratColumn ref nu există!") // DEBUG
        }
      })

      // Generăm preview-ul
      // this.generatePreview() // Comentez temporar pentru debug
    },

    handleSimpleBoundaryMove(data) {
      console.log("Gestionând modificare simplă (legacy):", data) // DEBUG

      // Păstrăm logica veche pentru compatibilitate
      const { currentLayer, nextLayer, prevLayer, indices } = data

      console.log("Layers înainte de modificare simplă:", JSON.stringify(this.layers)) // DEBUG

      // Creăm o copie a straturilor pentru a asigura reactivitatea
      const updatedLayers = JSON.parse(JSON.stringify(this.layers))

      // Actualizăm stratul curent
      if (indices && indices.currentLayerIndex !== undefined) {
        updatedLayers[indices.currentLayerIndex] = {
          ...updatedLayers[indices.currentLayerIndex],
          depth_to: currentLayer.depth_to,
        }
        console.log("Stratul curent actualizat:", updatedLayers[indices.currentLayerIndex]) // DEBUG
      }

      // Actualizăm stratul următor, dacă există
      if (nextLayer && indices.nextLayerIndex !== undefined) {
        updatedLayers[indices.nextLayerIndex] = {
          ...updatedLayers[indices.nextLayerIndex],
          depth_from: nextLayer.depth_from,
        }
        console.log("Stratul următor actualizat:", updatedLayers[indices.nextLayerIndex]) // DEBUG
      }

      // Actualizăm stratul precedent, dacă există
      if (prevLayer && indices.prevLayerIndex !== undefined) {
        updatedLayers[indices.prevLayerIndex] = {
          ...updatedLayers[indices.prevLayerIndex],
          depth_to: prevLayer.depth_to,
        }
        console.log("Stratul precedent actualizat:", updatedLayers[indices.prevLayerIndex]) // DEBUG
      }

      // Actualizăm modelul de date
      this.layers = updatedLayers

      console.log("Layers după modificare simplă:", JSON.stringify(this.layers)) // DEBUG

      // Forțăm actualizarea coloanei stratigrafice
      this.$nextTick(() => {
        // console.log("NextTick - actualizând vizualizarea simplă") // DEBUG
        if (this.$refs.stratColumn) {
          this.$refs.stratColumn.forceRedraw()
          // console.log("forceRedraw apelat pentru modificare simplă") // DEBUG
        } else {
          console.log("EROARE: stratColumn ref nu există!") // DEBUG
        }
      })

      // Generăm preview-ul
      // this.generatePreview() // Comentez temporar pentru debug
    },
    // In your parent component
    _handleBoundaryMoved(data) {
      const { currentLayer, nextLayer, indices } = data

      // Create a deep copy of the layers array to ensure Vue detects the change
      const updatedLayers = JSON.parse(JSON.stringify(this.layers))

      // Update the current layer
      updatedLayers[indices.currentLayerIndex].depth_to = currentLayer.depth_to

      // Update the next layer if it exists
      if (nextLayer && indices.nextLayerIndex !== undefined) {
        updatedLayers[indices.nextLayerIndex].depth_from = nextLayer.depth_from
      }

      // Update the model data
      this.layers = updatedLayers

      // Force the stratification column to update
      this.$nextTick(() => {
        if (this.$refs.stratColumn) {
          this.$refs.stratColumn.updateVisualization()
        }
      })

      // Generate a preview if needed
      // this.debouncedGeneratePreview()
    },
    handleBoundaryMoved_(data) {
      const { currentLayer, nextLayer, indices } = data

      // Actualizăm stratul curent
      const updatedLayers = [...this.layers]
      updatedLayers[indices.currentLayerIndex] = {
        ...updatedLayers[indices.currentLayerIndex],
        depth_to: currentLayer.depth_to,
      }

      // Actualizăm și stratul următor dacă există
      if (nextLayer && indices.nextLayerIndex !== undefined) {
        updatedLayers[indices.nextLayerIndex] = {
          ...updatedLayers[indices.nextLayerIndex],
          depth_from: nextLayer.depth_from,
        }
      }

      // Actualizăm modelul de date
      this.layers = updatedLayers

      // Generăm un preview actualizat
      // this.debouncedGeneratePreview()
    },

    // Metodă pentru a regenera coloana stratgrafică
    refreshStratigraphy() {
      if (this.$refs.stratColumn) {
        this.$refs.stratColumn.updateVisualization()
      }
    },
    updateLayers(data) {
      this.layers = data.map((layer) => ({
        ...layer,
        isValid: true,
      }))
    },

    // Procesează modificarea pe un input
    onDepthToChange(index) {
      // Mai întâi, aplicăm logica existentă pentru a actualiza straturile următoare
      this.updateAdjacentLayers(index)

      // Apoi, generăm un preview al stratificației procesate
      this.generatePreview()
    },

    // Actualizează straturile adiacente (logica ta existentă)
    updateAdjacentLayers(index) {
      const currentLayer = this.layers[index]
      const nextIndex = index + 1

      // Actualizăm depth_from al stratului următor
      if (this.layers[nextIndex]) {
        const nextLayer = this.layers[nextIndex]
        const previousMaxDepth = Math.max(currentLayer.depth_from, currentLayer.depth_to)

        nextLayer.depth_from = previousMaxDepth
        currentLayer.depth_to = currentLayer.depth_to - currentLayer.depth_from < 0 ? currentLayer.depth_from : currentLayer.depth_to

        // Verificăm și actualizăm straturile următoare
        this.checkAndUpdateNextLayers(nextIndex)
      }
    },

    checkAndUpdateNextLayers(index) {
      // Implementarea ta existentă...
      if (index >= this.layers.length) return

      const previousLayer = this.layers[index - 1]
      const currentLayer = this.layers[index]

      const previousMaxDepth = Math.max(previousLayer.depth_from, previousLayer.depth_to)
      currentLayer.depth_from = previousMaxDepth

      const thickness = currentLayer.depth_to - currentLayer.depth_from

      if (thickness <= 0) {
        currentLayer.isValid = false
      } else {
        currentLayer.isValid = true
      }

      const nextIndex = index + 1
      if (this.layers[nextIndex]) {
        this.checkAndUpdateNextLayers(nextIndex)
      }
    },

    // Generează un preview al stratificației procesate
    generatePreview() {
      // Folosim nextTick pentru a ne asigura că toate modificările au fost aplicate
      this.$nextTick(() => {
        // Procesăm straturile folosind serviciul
        this.previewLayers = StratificationService.processStrata(this.activeLayers)

        // Activăm afișarea preview-ului
        this.showPreview = true
      })
    },

    // Revine la straturile originale (neprocessate)
    cancelPreview() {
      this.showPreview = false
    },

    // Aplică procesarea și salvează
    applyProcessingAndSave() {
      // Forțează pierderea focus-ului de pe orice input activ
      document.activeElement.blur()

      // Folosim un timeout mic pentru a permite evenimentului blur să fie procesat
      setTimeout(() => {
        // Validăm datele
        const validation = StratificationService.validateStrata(this.activeLayers)
        if (!validation.valid) {
          alert(validation.message)
          return
        }

        // Setăm flag-ul de procesare pentru a preveni click-uri multiple
        this.isProcessing = true

        // Procesăm straturile
        const processedStrata = StratificationService.processStrata(this.activeLayers)

        // Trimitem la server
        router.post(
          route("borehole.update.stratigraphy", { borehole: this.borehole.id }),
          { strata: processedStrata },
          {
            onSuccess: () => {
              alert("Stratificația a fost salvată cu succes.")
              // Actualizăm modelul local
              this.updateLayers(processedStrata)
              this.showPreview = false
              this.isProcessing = false
            },
            onError: (errors) => {
              console.error("Eroare la salvare:", errors)
              this.isProcessing = false
            },
          }
        )
      }, 50)
    },

    // Combină cele două metode
    saveStratification() {
      console.log("Funcția saveStratification a fost apelată")
      this.applyProcessingAndSave()
    },
  },
}
</script>
