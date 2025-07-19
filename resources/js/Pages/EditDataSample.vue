<template>
  <!-- <AppLayout title="Dashboard"> -->
  <div class="max-w-2xl mx-auto py-8">
    <div class="space-y-10">
      <div>
        <h3 class="text-lg font-medium text-gray-900">Granulometrie</h3>
        <div class="mt-6 grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-6">
          <div class="sm:col-span-2">
            <label for="clay" class="block text-sm font-medium text-gray-900">Argila</label>
            <div class="mt-1">
              <input
                name="clay"
                id="clay"
                v-model="form.granulometry.clay"
                class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
              />
            </div>
          </div>
          <div class="sm:col-span-2">
            <label for="silt" class="block text-sm font-medium text-gray-900">Peaf</label>
            <div class="mt-1">
              <input
                type="text"
                name="silt"
                id="silt"
                v-model="form.granulometry.silt"
                class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
              />
            </div>
          </div>

          <div class="sm:col-span-2">
            <label for="sand" class="block text-sm font-medium text-gray-900">Nisip</label>
            <div class="mt-1">
              <input
                type="text"
                name="sand"
                id="sand"
                v-model="form.granulometry.sand"
                class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
              />
            </div>
          </div>
          <div class="sm:col-span-4">
            <label for="gravel" class="block text-sm font-medium text-gray-900">Pietris</label>
            <div class="mt-1">
              <input
                id="gravel"
                name="gravel"
                v-model="form.granulometry.gravel"
                class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
              />
            </div>
          </div>
        </div>
      </div>

      <div>
        <h3 class="text-lg font-medium text-gray-900">Indici de structura</h3>
        <div class="mt-6 grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-6">
          <div class="sm:col-span-4">
            <label for="water-content" class="block text-sm font-medium text-gray-900">Umiditate</label>
            <div class="mt-2">
              <input
                id="water-content"
                name="water-content"
                v-model="form.water_content.water_content"
                class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
              />
            </div>
          </div>
          <div class="sm:col-span-3">
            <label for="bulk-density" class="block text-sm font-medium text-gray-900">Densitate in stare naturala</label>
            <div class="mt-2">
              <input
                type="text"
                name="bulk-density"
                id="bulk-density"
                v-model="form.bulk_density.bulk_density"
                class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
              />
            </div>
          </div>

          <div class="sm:col-span-3">
            <label for="particle-density" class="block text-sm font-medium text-gray-900">Densitatea scheletului</label>
            <div class="mt-2">
              <input
                type="text"
                name="particle-density"
                id="particle-density"
                v-model="form.particle_density.particle_density"
                class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
              />
            </div>
          </div>
        </div>
      </div>
      <div>
        <h3 class="text-lg font-medium text-gray-900">Limite Atterberg</h3>

        <div class="mt-6 grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-6">
          <div class="sm:col-span-3">
            <label for="plastic-limit" class="block text-sm font-medium text-gray-900">Limita inferioara</label>
            <div class="mt-2">
              <input
                type="text"
                name="plastic-limit"
                id="plastic-limit"
                v-model="form.plasticity.plastic_limit"
                class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
              />
            </div>
          </div>

          <div class="sm:col-span-3">
            <label for="liquid-limit" class="block text-sm font-medium text-gray-900">Limita superioara</label>
            <div class="mt-2">
              <input
                type="text"
                name="liquid-limit"
                id="liquid-limit"
                v-model="form.plasticity.liquid_limit"
                class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="mt-6 flex items-center justify-end gap-x-6">
      <Link
        :href="
          route('sample.show', {
            sample: sample.id,
          })
        "
        as="button"
        type="button"
        class="text-sm font-semibold leading-6 text-gray-900"
      >
        Renunta</Link
      >
      <button
        @click="update"
        type="submit"
        class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
      >
        Actualizeaza
      </button>
    </div>
  </div>
  <!-- </AppLayout> -->
</template>
<script>
import { Link, Head, useForm } from "@inertiajs/vue3"

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
      form: useForm({
        // clay: "",
        // silt: "",
        // sand: "",
        // gravel: "",
        // water_content: "",
        // density: "",
        // particle_density: "",
        // plastic_limit: "",
        // liquid_limit: "",
        granulometry: {
          clay: "",
          silt: "",
          sand: "",
          gravel: "",
        },
        water_content: {
          water_content: "",
        },
        bulk_density: {
          bulk_density: "",
        },
        particle_density: {
          particle_density: "",
        },
        plasticity: {
          plastic_limit: "",
          liquid_limit: "",
        },
      }),
    }
  },
  mounted() {
    this.form.granulometry.clay = this.sample.granulometry?.clay || ""
    this.form.granulometry.silt = this.sample.granulometry?.silt || ""
    this.form.granulometry.sand = this.sample.granulometry?.sand || ""
    this.form.granulometry.gravel = this.sample.granulometry?.gravel || ""
    this.form.water_content.water_content = this.sample.water_content?.water_content || ""
    this.form.plasticity.plastic_limit = this.sample.plasticity?.plastic_limit || ""
    this.form.plasticity.liquid_limit = this.sample.plasticity?.liquid_limit || ""
    this.form.bulk_density.bulk_density = this.sample.bulk_density?.bulk_density || ""
    this.form.particle_density.particle_density = this.sample.particle_density?.particle_density || ""
  },
  methods: {
    update() {
      this.form.post(
        route("sample.data.update", {
          sample: this.sample.id,
        }),
        {
          preserveScroll: true,
          onSuccess: () => {
            // this.form.reset()
          },
        }
      )
    },
  },
}
</script>
