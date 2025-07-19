<template>
  <AppLayout title="Dashboard">
    <div>
      <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        <div class="border-b border-gray-200 pb-5">
          <div class="sm:flex sm:items-baseline sm:justify-between">
            <div class="sm:w-0 sm:flex-1">
              <h1 id="message-heading" class="text-base font-semibold leading-6 text-gray-900">{{ borehole.name }}</h1>
              <p class="mt-1 truncate text-sm text-gray-500">{{ borehole.depth }} m</p>
            </div>
            <!-- <Link
                :href="
                  route('sample.data.edit', {
                    sample: sample.id,
                  })
                "
                as="button"
                type="button"
                class="text-sm font-semibold leading-6 text-gray-900"
              >
                Editeaza date</Link
              > -->
            <div class="mt-4 flex items-center justify-between sm:ml-6 sm:mt-0 sm:flex-shrink-0 sm:justify-start">
              <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">eq</span>
            </div>
          </div>
        </div>
        <div v-if="isEntireBoreholeUnclassified" class="bg-amber-100 border-l-4 border-amber-500 text-amber-700 p-4 mb-4">
          <p class="font-bold">Atenție</p>
          <p>Nu s-a putut genera o stratificație relevantă deoarece nicio probă nu este identificata. Vă rugăm să identificați cel puțin o probă înainte de a genera stratificația.</p>
        </div>
        <div v-else class="max-w-lg">
          <form @submit.prevent="saveStratification">
            <div class="mt-4 grid grid-cols-1 gap-y-2 sm:grid-cols-3 sm:gap-x-4">
              <template v-for="(layer, index) in activeLayers" :key="layer">
                <input
                  type="text"
                  :value="layer.depth_from"
                  disabled
                  :class="[layer.depth_to - layer.depth_from <= 0 ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500']"
                  class="block w-full rounded-md shadow-sm sm:text-sm disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500 disabled:ring-gray-200"
                />
                <input
                  v-model.lazy="layer.depth_to"
                  type="text"
                  :class="[layer.depth_to - layer.depth_from <= 0 ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500']"
                  class="block w-full rounded-md shadow-sm sm:text-sm"
                />
                <input
                  v-model.number="layer.soil_type"
                  type="text"
                  :class="[layer.depth_to - layer.depth_from <= 0 ? 'border-red-500 text-red-500' : 'border-gray-300']"
                  class="block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                />
              </template>
            </div>
            <button
              type="submit"
              class="mt-4 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
            >
              Save
            </button>
          </form>
        </div>
        <!-- <div v-for="(layer, index) in layers" :key="index" class="layer-row">
          <input :value="layer.depth_from" disabled />
          <input type="text" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" />
          <input v-model.number="layer.depth_to" @input="onDepthToChange(index)" />
          <input v-model="layer.soil_type" />
        </div> -->

        <!-- Card Section -->
        <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
          <!-- Grid -->
          <div class="grid sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-6">
            <!-- Card -->
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
            <!-- End Card -->

            <!-- Card -->
            <a class="group flex flex-col bg-white border shadow-sm rounded-xl hover:shadow-md focus:outline-none focus:shadow-md transition dark:bg-neutral-900 dark:border-neutral-800" href="#">
              <div class="p-4 md:p-5">
                <div class="flex justify-between items-center gap-x-3">
                  <div class="grow">
                    <h3 class="group-hover:text-blue-600 font-semibold text-gray-800 dark:group-hover:text-neutral-400 dark:text-neutral-200">Calculeaza indicii de structura</h3>
                    <p class="text-sm text-gray-500 dark:text-neutral-500">Lorem, ipsum dolor.</p>
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
            <!-- End Card -->
          </div>
          <!-- End Grid -->
        </div>
        <!-- End Card Section -->
      </div>
    </div>
  </AppLayout>
</template>
<script>
import StratificationService from "@/services/StratificationService"
import { Link, Head, router } from "@inertiajs/vue3"
import AppLayout from "@/Layouts/AppLayout.vue"
export default {
  components: {
    Head,
    AppLayout,
    Link,
  },
  props: ["borehole", "strataData"],
  data() {
    return {
      layers: [],
      //   layers: [
      //     { depth_from: 0, depth_to: 2.5, soil_type: "Argilă", isValid: true },
      //     { depth_from: 2.5, depth_to: 4, soil_type: "Nisip", isValid: true },
      //     { depth_from: 4, depth_to: 6, soil_type: "Praf", isValid: true },
      //     { depth_from: 6, depth_to: 8.7, soil_type: "Argilă", isValid: true },
      //   ],
    }
  },
  mounted() {
    // this.layers = this.strataData
    // this.layers = this.strataData.map((layer) => ({
    //   ...layer,
    //   isValid: true,
    // }))
    this.updateLayers(this.strataData)
  },
  computed: {
    activeLayers() {
      return this.layers.filter((layer) => layer.isValid)
    },
    isEntireBoreholeUnclassified() {
      // Verifică dacă avem doar un singur strat și acesta are soil_type null
      return this.strataData.length === 1 && this.strataData[0].soil_type === null
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
  },
  methods: {
    updateLayers(data) {
      this.layers = data.map((layer) => ({
        ...layer,
        isValid: true,
        // error: '',
      }))
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

    // methods: {
    // onDepthToChange: _.debounce(function (index) {
    onDepthToChange(index) {
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
    // }, 250),

    checkAndUpdateNextLayers(index) {
      // Dacă nu mai sunt straturi, ieșim din funcție
      if (index >= this.layers.length) return

      const previousLayer = this.layers[index - 1]
      const currentLayer = this.layers[index]

      // Actualizăm depth_from al stratului curent
      const previousMaxDepth = Math.max(previousLayer.depth_from, previousLayer.depth_to)
      currentLayer.depth_from = previousMaxDepth

      // Calculăm grosimea stratului curent
      const thickness = currentLayer.depth_to - currentLayer.depth_from

      if (thickness <= 0) {
        // Marcăm stratul ca inactiv
        currentLayer.isValid = false
      } else {
        // Marcăm stratul ca activ
        currentLayer.isValid = true
      }

      // Continuăm verificarea cu stratul următor
      const nextIndex = index + 1
      if (this.layers[nextIndex]) {
        this.checkAndUpdateNextLayers(nextIndex)
      }
    },

    addLayer() {
      const lastLayer = this.layers[this.layers.length - 1]
      const newDepthFrom = Math.max(lastLayer.depth_from, lastLayer.depth_to)
      const newDepthTo = newDepthFrom + 1 // Valoare implicită

      this.layers.push({
        depth_from: newDepthFrom,
        depth_to: newDepthTo,
        soil_type: "",
        isValid: true,
      })
    },

    removeLayer_(index) {
      if (this.layers.length > 1) {
        this.layers.splice(index, 1)

        // Actualizăm depth_from pentru straturile următoare
        for (let i = index; i < this.layers.length; i++) {
          if (i === 0) {
            this.layers[i].depth_from = 0
          } else {
            const previousLayer = this.layers[i - 1]
            this.layers[i].depth_from = Math.max(previousLayer.depth_from, previousLayer.depth_to)
          }
        }
      }
    },
    saveStratification_() {
      console.log("Funcția saveStratification a fost apelată")

      // Asigură-te că toate modificările din inputs sunt aplicate
      this.$nextTick(() => {
        // Toate datele sunt actualizate acum
        const activeLayers = this.layers.filter((layer) => layer.isValid)
        console.log("Straturi active:", activeLayers)

        router.post(
          route("borehole.update.stratigraphy", { borehole: this.borehole.id }),
          { strata: this.layers },
          {
            onSuccess: () => {
              alert("Stratificația a fost salvată cu succes.")
            },
            onError: (errors) => {
              console.error("Eroare la salvare:", errors)
            },
          }
        )
      })
    },
    saveStratification__() {
      console.log("this.layers")
      console.log("Funcția saveStratification a fost apelată")
      // Validare finală înainte de trimitere
      // const activeLayers = this.layers.filter((layer) => layer.isValid)

      // if (activeLayers.length === 0) {
      //   alert("Trebuie să existe cel puțin un strat activ.")
      //   return
      // }

      // let valid = true

      // activeLayers.forEach((layer) => {
      //   if (!layer.soil_type) {
      //     alert("Toate straturile active trebuie să aibă un tip de sol selectat.")
      //     valid = false
      //   }
      // })

      // if (!valid) return

      // Trimiterea datelor către server
      router.post(
        route("borehole.update.stratigraphy", { borehole: this.borehole.id }),
        { strata: activeLayers },
        {
          onSuccess: () => {
            alert("Stratificația a fost salvată cu succes.")
          },
        }
      )
    },
    saveStratification() {
      // Asigură-te că toate câmpurile sunt actualizate înainte de procesare
      this.$nextTick(() => {
        // Validează straturile
        const validation = StratificationService.validateStrata(this.layers)

        if (!validation.isValid) {
          alert(validation.error)
          return
        }

        // Procesează straturile pentru a elimina suprapunerile și a combina straturile adiacente
        const processedStrata = StratificationService.processStrata(this.layers)

        // Trimiterea datelor către server
        router.post(
          route("borehole.update.stratigraphy", { borehole: this.borehole.id }),
          { strata: processedStrata },
          {
            onSuccess: () => {
              alert("Stratificația a fost salvată cu succes.")
              // Actualizează datele locale cu straturile procesate
              this.updateLayers(processedStrata)
            },
            onError: (errors) => {
              console.error("Eroare la salvare:", errors)
            },
          }
        )
      })
    },

    // Pentru viitoarele funcționalități de drag-and-drop
    onLayerBoundaryDrag(index, newDepth, isTop) {
      // Actualizează adâncimea stratului
      if (isTop) {
        this.layers[index].depth_from = newDepth
      } else {
        this.layers[index].depth_to = newDepth
      }

      // Procesează straturile pentru preview în timp real
      const processedStrata = StratificationService.processStrata(this.layers)

      // Actualizează vizualizarea
      this.previewLayers = processedStrata
    },

    // Funcție pentru a aplica modificările din drag-and-drop
    applyDragChanges() {
      // Procesează straturile finale
      const processedStrata = StratificationService.processStrata(this.layers)

      // Actualizează datele locale
      this.updateLayers(processedStrata)

      // Opțional: salvează automat pe server
      this.saveStratification()
    },
  },
}
</script>
