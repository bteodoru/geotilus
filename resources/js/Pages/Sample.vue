<template>
  <AppLayout title="Dashboard">
    <div>
      <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        <div class="border-b border-gray-200 pb-5">
          <div class="sm:flex sm:items-baseline sm:justify-between">
            <div class="sm:w-0 sm:flex-1">
              <h1 id="message-heading" class="text-base font-semibold leading-6 text-gray-900">{{ sample.name }}</h1>
              <p class="mt-1 truncate text-sm text-gray-500">{{ sample.depth }} m</p>
            </div>
            <Link
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
            >
            <div class="mt-4 flex items-center justify-between sm:ml-6 sm:mt-0 sm:flex-shrink-0 sm:justify-start">
              <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">{{ sample.type }}</span>
            </div>
          </div>
        </div>
        <!-- Selecția sistemului de identificare -->
        <div class="mb-6">
          <label for="identification-system" class="block text-sm font-medium text-gray-700 mb-2"> Sistem de Identificare </label>
          <select id="identification-system" v-model="selectedSystem" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" @change="onSystemChange">
            <option value="">Selectează sistemul de identificare...</option>
            <option v-for="(systemInfo, systemCode) in sample.availableIdentificationSystems" :key="systemCode" :value="systemCode">{{ systemInfo.name }} {{ systemInfo.version }} ({{ systemInfo.country }})</option>
          </select>
          <!-- <p v-if="selectedSystemInfo" class="mt-1 text-sm text-gray-600">
            {{ selectedSystemInfo.description }}
          </p> -->
        </div>
        <p v-if="sample.soil_type" class="font-semibold">{{ sample.soil_type.name }}</p>
        <p v-else class="italic text-gray-600 text-sm">fara denumire</p>
        <p>{{ sample.availableIdentificationSystems[sample.soil_type.method].name }}</p>
        <!-- <p>{{ sample.availableIdentificationSystems[sample.soil_type.method].name }}</p> -->
        <!-- Card Section -->
        <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
          <!-- Grid -->
          <div class="grid sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-6">
            <!-- Card -->
            <a @click.prevent="identifySoilSample" class="group flex flex-col bg-white border shadow-sm rounded-xl hover:shadow-md focus:outline-none focus:shadow-md transition dark:bg-neutral-900 dark:border-neutral-800" href="#">
              <div class="p-4 md:p-5">
                <div class="flex justify-between items-center gap-x-3">
                  <div class="grow">
                    <h3 class="group-hover:text-blue-600 font-semibold text-gray-800 dark:group-hover:text-neutral-400 dark:text-neutral-200">Identifica proba</h3>
                    <p class="text-sm text-gray-500 dark:text-neutral-500">NP 074</p>
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
            <a @click.prevent="derivePhaseIndices" class="group flex flex-col bg-white border shadow-sm rounded-xl hover:shadow-md focus:outline-none focus:shadow-md transition dark:bg-neutral-900 dark:border-neutral-800" href="#">
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
import { Link, Head, router } from "@inertiajs/vue3"
import AppLayout from "@/Layouts/AppLayout.vue"
export default {
  components: {
    Head,
    AppLayout,
    Link,
  },
  props: {
    sample: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
      selectedSystem: "",
    }
  },
  methods: {
    identifySoilSample() {
      let form = this.$inertia.form({
        clay: this.sample.granulometry.clay,
        silt: this.sample.granulometry.silt,
        sand: this.sample.granulometry.sand,
        system: this.selectedSystem,
      })
      form.post(
        route("sample.identify", {
          sample: this.sample.id,
        }),
        {
          preserveScroll: true,
          onSuccess: () => {
            form.reset()
          },
        }
      )
    },
    derivePhaseIndices() {
      router.post(
        route("sample.phase.indices", { sample: this.sample.id }),
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
  },
}
</script>
